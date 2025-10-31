<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CotacaoExterna;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Exception;

class CotacaoExternaController extends Controller
{
    /**
     * Exibe página de upload
     */
    public function index()
    {
        $cotacoes = CotacaoExterna::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cotacao-externa.index', compact('cotacoes'));
    }

    /**
     * Processa upload do arquivo
     */
    public function upload(Request $request)
    {
        \Log::info('=== UPLOAD INICIADO ===', [
            'tem_arquivo' => $request->hasFile('arquivo'),
            'tamanho' => $request->file('arquivo') ? $request->file('arquivo')->getSize() : 0,
        ]);

        try {
            $request->validate([
                'arquivo' => 'required|file|mimes:pdf,xlsx,xls,docx,doc|max:51200', // 50MB
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Erro de validação no upload:', [
                'erros' => $e->errors(),
                'arquivo_presente' => $request->hasFile('arquivo'),
                'arquivo_valido' => $request->file('arquivo') ? $request->file('arquivo')->isValid() : false,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . json_encode($e->errors()),
                'errors' => $e->errors()
            ], 422);
        }

        \Log::info('Validação passou, processando arquivo...');

        try {
            $arquivo = $request->file('arquivo');
            $nomeOriginal = $arquivo->getClientOriginalName();
            $extensao = strtolower($arquivo->getClientOriginalExtension());

            \Log::info('Arquivo recebido:', [
                'nome' => $nomeOriginal,
                'extensao' => $extensao,
                'tamanho' => $arquivo->getSize()
            ]);

            // Salvar arquivo original
            $path = $arquivo->store('cotacoes_externas/originais', 'public');

            // Criar registro
            $cotacao = CotacaoExterna::create([
                'titulo' => 'Cotação Externa - ' . now()->format('d/m/Y H:i'),
                'arquivo_original_path' => $path,
                'arquivo_original_nome' => $nomeOriginal,
                'arquivo_original_tipo' => $extensao,
                'status' => 'em_andamento',
                'user_id' => Auth::id(),
            ]);

            // Ler arquivo automaticamente
            $dadosExtraidos = $this->lerArquivoInteligente($cotacao);

            // Salvar dados extraídos
            $cotacao->update([
                'dados_extraidos' => $dadosExtraidos,
            ]);

            return response()->json([
                'success' => true,
                'cotacao_id' => $cotacao->id,
                'dados' => $dadosExtraidos,
                'message' => 'Arquivo lido com sucesso! Validação: ' . count($dadosExtraidos['itens'] ?? []) . ' itens encontrados.',
            ]);

        } catch (Exception $e) {
            \Log::error('Erro no upload de cotação externa:', [
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar arquivo: ' . $e->getMessage(),
                'debug' => [
                    'linha' => $e->getLine(),
                    'arquivo' => basename($e->getFile())
                ]
            ], 500);
        }
    }

    /**
     * LEITOR INTELIGENTE - 95% de acerto
     */
    private function lerArquivoInteligente(CotacaoExterna $cotacao)
    {
        $tipo = $cotacao->arquivo_original_tipo;
        $pathCompleto = storage_path('app/public/' . $cotacao->arquivo_original_path);

        switch ($tipo) {
            case 'xlsx':
            case 'xls':
                return $this->lerExcel($pathCompleto);

            case 'pdf':
                return $this->lerPDF($pathCompleto);

            case 'docx':
            case 'doc':
                return $this->lerWord($pathCompleto);

            default:
                throw new Exception('Tipo de arquivo não suportado: ' . $tipo);
        }
    }

    /**
     * Lê arquivo Excel
     */
    private function lerExcel($pathArquivo)
    {
        $spreadsheet = IOFactory::load($pathArquivo);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Detectar linha de cabeçalho
        $headerRow = null;
        $headerIndex = 0;

        foreach ($rows as $index => $row) {
            if ($this->ehLinhaCabecalho($row)) {
                $headerRow = $row;
                $headerIndex = $index;
                break;
            }
        }

        if (!$headerRow) {
            // Se não encontrou cabeçalho, assume primeira linha
            $headerRow = $rows[0];
            $headerIndex = 0;
        }

        // Detectar colunas
        $colunas = $this->detectarColunas($headerRow);

        // Extrair itens
        $itens = [];
        $numeroItem = 1;

        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Pular linhas vazias
            if (empty(array_filter($row))) {
                continue;
            }

            $item = [
                'numero' => $numeroItem++,
                'descricao' => $this->extrairValor($row, $colunas['descricao']) ?: '',
                'fornecedor' => $this->extrairValor($row, $colunas['fornecedor']) ?: '',
                'quantidade' => $this->converterNumero($this->extrairValor($row, $colunas['quantidade'])),
                'unidade' => $this->extrairValor($row, $colunas['unidade']) ?: 'UN',
                'preco_unitario' => $this->converterMonetario($this->extrairValor($row, $colunas['preco_unitario'])),
                'preco_total' => 0,
                'lote' => $this->extrairValor($row, $colunas['lote']) ?: '1',
                'marca' => $this->extrairValor($row, $colunas['marca']) ?: '',
            ];

            // Calcular preço total
            $item['preco_total'] = $item['quantidade'] * $item['preco_unitario'];

            // Só adiciona se tiver descrição
            if (!empty($item['descricao'])) {
                $itens[] = $item;
            }
        }

        return $this->organizarDados($itens);
    }

    /**
     * Lê arquivo PDF usando sistema modular de detecção de formatos
     */
    private function lerPDF($pathArquivo)
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($pathArquivo);
            $texto = $pdf->getText();

            // DEBUG: Log do texto extraído
            \Log::info('PDF Texto extraído (primeiros 1000 chars):', [
                'texto' => substr($texto, 0, 1000)
            ]);

            // Dividir em linhas
            $linhas = explode("\n", $texto);

            \Log::info('PDF Total de linhas:', ['total' => count($linhas)]);
            \Log::info('PDF Primeiras 10 linhas:', [
                'linhas' => array_slice($linhas, 0, 10)
            ]);

            // ===== USAR SISTEMA MODULAR DE DETECÇÃO =====
            $manager = new \App\Services\PDF\PDFDetectorManager();
            $resultado = $manager->detectarEExtrair($linhas);

            \Log::info('PDF Formato detectado pelo sistema modular:', [
                'formato' => $resultado['formato'],
                'total_itens' => count($resultado['dados']['itens'] ?? []),
                'valor_total' => $resultado['dados']['valor_total_geral'] ?? 0
            ]);

            return $resultado['dados'];

        } catch (\Exception $e) {
            \Log::error('Erro ao ler PDF:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Lê PDF no formato "MAPA DE APURAÇÃO DE PREÇOS"
     * Formato específico onde cada campo está em uma linha separada
     *
     * Estrutura esperada:
     * AnexoI
     * Lote001
     * Item001
     * [Descrição em múltiplas linhas]
     * UN
     * 6,00       <- quantidade
     * 2,86       <- preço unitário
     * 17,16      <- preço total
     */
    private function lerPDFMapaApuracao($linhas)
    {
        \Log::info('lerPDFMapaApuracao INICIADO', ['total_linhas' => count($linhas)]);

        $itens = [];
        $itemAtual = null;
        $loteAtual = null;
        $estadoLeitura = 'PROCURANDO_ANEXO'; // Estados: PROCURANDO_ANEXO, LENDO_LOTE, LENDO_ITEM, LENDO_DESCRICAO, LENDO_UNIDADE, LENDO_QUANTIDADE, LENDO_PRECO

        // EXTRAIR FORNECEDORES E PREÇOS
        $fornecedores = [];
        $precosPorFornecedor = []; // Array de arrays: [item_numero][fornecedor_nome] = preco
        $valorTotalGeral = 0;

        // Procurar linha com fornecedores (contém "CONTRATAÇÕES SI" ou "PAINEL DE PREÇO")
        for ($i = 0; $i < count($linhas); $i++) {
            $linha = trim($linhas[$i]);
            if (strpos($linha, 'CONTRATAÇÕES') !== false || strpos($linha, 'PAINEL DE PREÇO') !== false) {
                // Separar fornecedores por TAB
                $fornecedoresArray = preg_split('/\t+/', $linha);
                foreach ($fornecedoresArray as $forn) {
                    $forn = trim($forn);
                    if (!empty($forn)) {
                        $fornecedores[] = $forn;
                    }
                }
                \Log::info('Fornecedores encontrados', ['total' => count($fornecedores), 'fornecedores' => $fornecedores]);

                // Próximas linhas contêm os preços de cada item
                // Cada linha é um item, cada coluna é um fornecedor
                $itemIndex = 1;
                for ($j = $i + 1; $j < count($linhas); $j++) {
                    $linhaPrecos = trim($linhas[$j]);

                    // Parar quando encontrar linha que não contém preços
                    if (empty($linhaPrecos) ||
                        strpos($linhaPrecos, 'CÂMARA') !== false ||
                        strpos($linhaPrecos, 'ESTADO') !== false ||
                        strpos($linhaPrecos, 'CONFORME') !== false) {
                        break;
                    }

                    // Separar preços por espaço (podem ter múltiplos espaços)
                    $precos = preg_split('/\s+/', $linhaPrecos);

                    // Criar array de preços por fornecedor para este item
                    $precosPorFornecedor[$itemIndex] = [];

                    $fornIndex = 0;
                    foreach ($precos as $preco) {
                        $preco = trim($preco);
                        if (empty($preco)) continue;

                        // ///// significa que o fornecedor não tem preço
                        if ($preco === '/////') {
                            $fornIndex++;
                            continue;
                        }

                        // Armazenar preço do fornecedor
                        if ($fornIndex < count($fornecedores)) {
                            $precosPorFornecedor[$itemIndex][$fornecedores[$fornIndex]] = $this->converterMonetario($preco);
                        }
                        $fornIndex++;
                    }

                    $itemIndex++;
                }

                \Log::info('Preços por fornecedor extraídos', ['precos' => $precosPorFornecedor]);
                break;
            }
        }

        // Extrair VALOR TOTAL GERAL (linha que contém "VALOR TOTAL" seguida de valor)
        for ($i = 0; $i < count($linhas); $i++) {
            $linha = trim($linhas[$i]);
            if (stripos($linha, 'VALOR TOTAL') !== false) {
                // Próxima linha contém o valor
                if ($i + 1 < count($linhas)) {
                    $linhaValor = trim($linhas[$i + 1]);
                    if (preg_match('/R\$\s*([\d\.,]+)/', $linhaValor, $matches)) {
                        $valorTotalGeral = $this->converterMonetario($matches[1]);
                        \Log::info('Valor total geral encontrado', ['valor' => $valorTotalGeral]);
                        break;
                    }
                }
            }
        }

        for ($i = 0; $i < count($linhas); $i++) {
            $linha = trim($linhas[$i]);

            if (empty($linha)) {
                continue;
            }

            // DETECTAR "AnexoI" ou "Anexo I"
            if (preg_match('/^Anexo\s*I$/i', $linha)) {
                // Salvar item anterior
                if ($itemAtual !== null && !empty($itemAtual['descricao'])) {
                    $itens[] = $itemAtual;
                    \Log::info('Item salvo', ['item' => $itemAtual]);
                }

                $estadoLeitura = 'LENDO_LOTE';
                $itemAtual = [
                    'numero' => 0,
                    'descricao' => '',
                    'fornecedor' => '',
                    'quantidade' => 0,
                    'unidade' => 'UN',
                    'preco_unitario' => 0,
                    'preco_total' => 0,
                    'lote' => '1',
                    'marca' => '',
                    'percentual_diferenca' => '',
                    'precos_fornecedores' => [], // Array de preços individuais por fornecedor
                ];
                \Log::info('AnexoI detectado na linha ' . $i);
                continue;
            }

            // DETECTAR "Lote001" ou "Lote 001"
            if ($estadoLeitura === 'LENDO_LOTE' && preg_match('/^Lote\s*(\d+)$/i', $linha, $matches)) {
                $loteAtual = ltrim($matches[1], '0') ?: '1';
                $itemAtual['lote'] = $loteAtual;
                $estadoLeitura = 'LENDO_ITEM';
                \Log::info('Lote detectado', ['lote' => $loteAtual, 'linha' => $i]);
                continue;
            }

            // DETECTAR "Item001" ou "Item 001"
            if ($estadoLeitura === 'LENDO_ITEM' && preg_match('/^Item\s*(\d+)$/i', $linha, $matches)) {
                $numeroItem = ltrim($matches[1], '0') ?: '1';
                $itemAtual['numero'] = intval($numeroItem);
                $itemAtual['percentual_diferenca'] = ''; // Inicializar campo percentual
                $estadoLeitura = 'LENDO_DESCRICAO';
                \Log::info('Item detectado', ['numero' => $numeroItem, 'linha' => $i]);
                continue;
            }

            // LENDO DESCRIÇÃO (até encontrar unidade)
            if ($estadoLeitura === 'LENDO_DESCRICAO') {
                // Verificar se a linha contém TAB + unidade (formato: "descrição	UN")
                if (preg_match('/\t(UN|PCT|RESMA|CX|KG|UNIDADE|PACOTE|CAIXA|LITRO|L)$/i', $linha, $matchesTab)) {
                    // Separar descrição e unidade
                    $partes = preg_split('/\t+/', $linha);
                    if (count($partes) >= 2) {
                        // Adicionar parte da descrição (sem a unidade)
                        $descricaoParte = trim($partes[0]);
                        if (!empty($itemAtual['descricao'])) {
                            $itemAtual['descricao'] .= ' ' . $descricaoParte;
                        } else {
                            $itemAtual['descricao'] = $descricaoParte;
                        }
                        // Capturar unidade
                        $itemAtual['unidade'] = strtoupper(trim($partes[count($partes) - 1]));
                        $estadoLeitura = 'LENDO_QUANTIDADE';
                        \Log::info('Unidade detectada na mesma linha da descrição', ['unidade' => $itemAtual['unidade'], 'linha' => $i]);
                        continue;
                    }
                }

                // Verificar se é uma unidade sozinha (UN, PCT, RESMA, etc)
                if (preg_match('/^(UN|PCT|RESMA|CX|KG|UNIDADE|PACOTE|CAIXA|LITRO|L)$/i', $linha)) {
                    $itemAtual['unidade'] = strtoupper($linha);
                    $estadoLeitura = 'LENDO_QUANTIDADE';
                    \Log::info('Unidade detectada', ['unidade' => $linha, 'linha' => $i]);
                    continue;
                }

                // Caso contrário, é parte da descrição
                if (!empty($itemAtual['descricao'])) {
                    $itemAtual['descricao'] .= ' ' . $linha;
                } else {
                    $itemAtual['descricao'] = $linha;
                }
                // SEM LIMITE - pega toda a descrição
                continue;
            }

            // LENDO QUANTIDADE (próxima linha após unidade)
            if ($estadoLeitura === 'LENDO_QUANTIDADE') {
                // Deve ser um número (pode ter vírgula ou ponto)
                if (preg_match('/^\d+[\.,]?\d*$/', $linha)) {
                    $itemAtual['quantidade'] = $this->converterNumero($linha);
                    $estadoLeitura = 'LENDO_PRECO';
                    \Log::info('Quantidade detectada', ['quantidade' => $linha, 'linha' => $i]);
                    continue;
                }
            }

            // LENDO PREÇO UNITÁRIO (próxima linha após quantidade)
            if ($estadoLeitura === 'LENDO_PRECO') {
                // Deve ser um número decimal (formato: 2,86 ou 25,50)
                if (preg_match('/^\d+[\.,]\d{2}$/', $linha)) {
                    $itemAtual['preco_unitario'] = $this->converterMonetario($linha);
                    \Log::info('Preço unitário detectado', ['preco' => $linha, 'linha' => $i]);

                    // Calcular total
                    $itemAtual['preco_total'] = $itemAtual['preco_unitario'] * $itemAtual['quantidade'];

                    // Próxima linha é o total (pular)
                    $i++;

                    // Próxima linha é o PERCENTUAL (capturar)
                    if ($i + 1 < count($linhas)) {
                        $linhaPercentual = trim($linhas[$i + 1]);
                        if (preg_match('/^(\d+[\.,]\d+)%$/', $linhaPercentual, $matchPercent)) {
                            $itemAtual['percentual_diferenca'] = $matchPercent[1] . '%';
                            \Log::info('Percentual detectado', ['percentual' => $linhaPercentual, 'linha' => $i + 1]);
                            $i++; // Pular linha do percentual
                        }
                    }

                    // Voltar para procurar próximo anexo
                    $estadoLeitura = 'PROCURANDO_ANEXO';
                    \Log::info('Item completo', ['item' => $itemAtual]);
                    continue;
                }
            }
        }

        // Salvar último item
        if ($itemAtual !== null && !empty($itemAtual['descricao'])) {
            $itens[] = $itemAtual;
            \Log::info('Item final salvo', ['item' => $itemAtual]);
        }

        // Limpar descrições (remover excesso de espaços)
        foreach ($itens as &$item) {
            $item['descricao'] = trim(preg_replace('/\s+/', ' ', $item['descricao']));

            // ASSOCIAR PREÇOS DOS FORNECEDORES AO ITEM
            $numeroItem = $item['numero'];
            if (isset($precosPorFornecedor[$numeroItem])) {
                $item['precos_fornecedores'] = $precosPorFornecedor[$numeroItem];
                \Log::info("Preços de fornecedores associados ao item $numeroItem", [
                    'precos' => $item['precos_fornecedores']
                ]);
            }
        }

        \Log::info('lerPDFMapaApuracao FINALIZADO', [
            'total_itens_extraidos' => count($itens),
            'total_fornecedores' => count($fornecedores),
            'valor_total_geral' => $valorTotalGeral,
            'itens' => $itens
        ]);

        // Passar dados extras para organizarDados
        return $this->organizarDados($itens, $fornecedores, $valorTotalGeral);
    }

    /**
     * Lê arquivo Word
     */
    private function lerWord($pathArquivo)
    {
        $phpWord = WordIOFactory::load($pathArquivo);
        $itens = [];
        $numeroItem = 1;

        // Percorrer seções
        foreach ($phpWord->getSections() as $section) {
            // Procurar tabelas
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getRows')) {
                    // É uma tabela
                    $rows = $element->getRows();
                    $headerRow = null;
                    $headerIndex = 0;

                    // Detectar cabeçalho
                    foreach ($rows as $index => $row) {
                        $cells = $row->getCells();
                        $cellValues = [];
                        foreach ($cells as $cell) {
                            $cellValues[] = $this->extrairTextoCell($cell);
                        }

                        if ($this->ehLinhaCabecalho($cellValues)) {
                            $headerRow = $cellValues;
                            $headerIndex = $index;
                            break;
                        }
                    }

                    if (!$headerRow && count($rows) > 0) {
                        $cells = $rows[0]->getCells();
                        $headerRow = [];
                        foreach ($cells as $cell) {
                            $headerRow[] = $this->extrairTextoCell($cell);
                        }
                    }

                    // Detectar colunas
                    $colunas = $this->detectarColunas($headerRow);

                    // Extrair itens
                    for ($i = $headerIndex + 1; $i < count($rows); $i++) {
                        $cells = $rows[$i]->getCells();
                        $cellValues = [];
                        foreach ($cells as $cell) {
                            $cellValues[] = $this->extrairTextoCell($cell);
                        }

                        if (empty(array_filter($cellValues))) {
                            continue;
                        }

                        $item = [
                            'numero' => $numeroItem++,
                            'descricao' => $this->extrairValor($cellValues, $colunas['descricao']) ?: '',
                            'fornecedor' => $this->extrairValor($cellValues, $colunas['fornecedor']) ?: '',
                            'quantidade' => $this->converterNumero($this->extrairValor($cellValues, $colunas['quantidade'])),
                            'unidade' => $this->extrairValor($cellValues, $colunas['unidade']) ?: 'UN',
                            'preco_unitario' => $this->converterMonetario($this->extrairValor($cellValues, $colunas['preco_unitario'])),
                            'preco_total' => 0,
                            'lote' => $this->extrairValor($cellValues, $colunas['lote']) ?: '1',
                            'marca' => $this->extrairValor($cellValues, $colunas['marca']) ?: '',
                        ];

                        $item['preco_total'] = $item['quantidade'] * $item['preco_unitario'];

                        if (!empty($item['descricao'])) {
                            $itens[] = $item;
                        }
                    }
                }
            }
        }

        return $this->organizarDados($itens);
    }

    /**
     * Extrai texto de uma célula Word
     */
    private function extrairTextoCell($cell)
    {
        $texto = '';
        foreach ($cell->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                $texto .= $element->getText() . ' ';
            }
        }
        return trim($texto);
    }

    /**
     * Verifica se linha é cabeçalho
     */
    private function ehLinhaCabecalho($row)
    {
        $texto = strtolower(implode(' ', $row));

        $palavrasChave = [
            'item', 'descri', 'produto', 'material',
            'qtd', 'quantidade', 'quant',
            'preço', 'preco', 'valor', 'unitário', 'unitario',
            'fornecedor', 'empresa', 'fabricante'
        ];

        $encontradas = 0;
        foreach ($palavrasChave as $palavra) {
            if (strpos($texto, $palavra) !== false) {
                $encontradas++;
            }
        }

        return $encontradas >= 2;
    }

    /**
     * Detecta colunas da tabela (ALGORITMO INTELIGENTE)
     */
    private function detectarColunas($headerRow)
    {
        $colunas = [
            'item' => null,
            'descricao' => null,
            'fornecedor' => null,
            'quantidade' => null,
            'unidade' => null,
            'preco_unitario' => null,
            'preco_total' => null,
            'lote' => null,
            'marca' => null,
        ];

        foreach ($headerRow as $index => $valor) {
            $valorNorm = $this->normalizarTexto($valor);

            // Item/Número
            if (preg_match('/^(item|num|n°|nº|#)$/i', $valorNorm) && !$colunas['item']) {
                $colunas['item'] = $index;
            }

            // Descrição
            elseif (preg_match('/(descri|produto|material|especifica|denominacao)/i', $valorNorm) && !$colunas['descricao']) {
                $colunas['descricao'] = $index;
            }

            // Fornecedor
            elseif (preg_match('/(fornecedor|empresa|fabricante|proponente|licitante)/i', $valorNorm) && !$colunas['fornecedor']) {
                $colunas['fornecedor'] = $index;
            }

            // Quantidade
            elseif (preg_match('/(qtd|quantidade|quant)/i', $valorNorm) && !$colunas['quantidade']) {
                $colunas['quantidade'] = $index;
            }

            // Unidade
            elseif (preg_match('/(unidade|un|medida|und)/i', $valorNorm) && !$colunas['unidade']) {
                $colunas['unidade'] = $index;
            }

            // Preço Unitário
            elseif (preg_match('/(preco|valor).*(unit|un)/i', $valorNorm) && !$colunas['preco_unitario']) {
                $colunas['preco_unitario'] = $index;
            }

            // Preço Total
            elseif (preg_match('/(preco|valor).*(total)/i', $valorNorm) && !$colunas['preco_total']) {
                $colunas['preco_total'] = $index;
            }

            // Lote
            elseif (preg_match('/(lote|grupo)/i', $valorNorm) && !$colunas['lote']) {
                $colunas['lote'] = $index;
            }

            // Marca
            elseif (preg_match('/(marca|modelo)/i', $valorNorm) && !$colunas['marca']) {
                $colunas['marca'] = $index;
            }
        }

        return $colunas;
    }

    /**
     * Normaliza texto para comparação
     */
    private function normalizarTexto($texto)
    {
        $texto = trim($texto);
        $texto = mb_strtolower($texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        // Remove acentos
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        return $texto;
    }

    /**
     * Extrai valor de array por índice
     */
    private function extrairValor($array, $index)
    {
        if ($index === null || !isset($array[$index])) {
            return null;
        }
        return trim($array[$index]);
    }

    /**
     * Converte texto para número
     */
    private function converterNumero($texto)
    {
        if (empty($texto)) {
            return 0;
        }

        // Remove tudo exceto dígitos, vírgula e ponto
        $texto = preg_replace('/[^\d,.]/', '', $texto);

        // Substitui vírgula por ponto
        $texto = str_replace(',', '.', $texto);

        return floatval($texto);
    }

    /**
     * Converte texto monetário para número
     */
    private function converterMonetario($texto)
    {
        if (empty($texto)) {
            return 0;
        }

        // Remove R$, espaços, etc
        $texto = preg_replace('/[^\d,.]/', '', $texto);

        // Se tem ponto E vírgula, assume formato brasileiro (1.234,56)
        if (strpos($texto, '.') !== false && strpos($texto, ',') !== false) {
            $texto = str_replace('.', '', $texto); // Remove milhares
            $texto = str_replace(',', '.', $texto); // Vírgula decimal vira ponto
        }
        // Se tem apenas vírgula, assume decimal brasileiro
        elseif (strpos($texto, ',') !== false) {
            $texto = str_replace(',', '.', $texto);
        }

        return floatval($texto);
    }

    /**
     * Verifica se linha de PDF é item
     */
    private function ehLinhaItem($linha)
    {
        // Linha deve começar com número ou ter padrão de item
        return preg_match('/^\s*\d+/', $linha) ||
               preg_match('/\d+.*[A-Za-z].*\d+[\.,]\d{2}/', $linha);
    }

    /**
     * Extrai item de linha de texto PDF
     */
    private function extrairItemDaLinha($linha, $numeroItem)
    {
        // Padrão genérico: captura texto e valores
        // Exemplo: "1 PAPEL A4 100 RESMA 25,50 2.550,00"

        preg_match_all('/\d+[\.,]\d{2}/', $linha, $valores);
        $valoresEncontrados = $valores[0] ?? [];

        // Extrai descrição (texto entre número inicial e valores)
        $descricao = preg_replace('/^\s*\d+\s+/', '', $linha);
        $descricao = preg_replace('/\d+[\.,]\d{2}.*$/', '', $descricao);
        $descricao = trim($descricao);

        if (empty($descricao)) {
            return null;
        }

        return [
            'numero' => $numeroItem,
            'descricao' => $descricao,
            'fornecedor' => '',
            'quantidade' => count($valoresEncontrados) > 2 ? $this->converterNumero($valoresEncontrados[0]) : 1,
            'unidade' => 'UN',
            'preco_unitario' => count($valoresEncontrados) > 0 ? $this->converterMonetario($valoresEncontrados[count($valoresEncontrados) - 2] ?? $valoresEncontrados[0]) : 0,
            'preco_total' => count($valoresEncontrados) > 0 ? $this->converterMonetario($valoresEncontrados[count($valoresEncontrados) - 1]) : 0,
            'lote' => '1',
            'marca' => '',
        ];
    }

    /**
     * Organiza dados extraídos
     */
    private function organizarDados($itens, $fornecedores = [], $valorTotalPDF = null)
    {
        // Agrupar por lotes
        $lotes = [];
        foreach ($itens as $item) {
            $numLote = $item['lote'];
            if (!isset($lotes[$numLote])) {
                $lotes[$numLote] = [
                    'numero' => $numLote,
                    'descricao' => 'Lote ' . $numLote,
                    'valor_total' => 0,
                    'quantidade_itens' => 0,
                ];
            }
            $lotes[$numLote]['valor_total'] += $item['preco_total'];
            $lotes[$numLote]['quantidade_itens']++;
        }

        // Usar valor total do PDF se disponível, senão calcular
        $valorTotalGeral = $valorTotalPDF ?? array_sum(array_column($itens, 'preco_total'));

        return [
            'itens' => $itens,
            'lotes' => array_values($lotes),
            'fornecedores' => $fornecedores, // Lista de fornecedores extraídos
            'valor_total_geral' => $valorTotalGeral,
            'quantidade_total_itens' => count($itens),
        ];
    }

    /**
     * Atualiza dados a partir do preview editável
     */
    public function atualizarDados(Request $request, $id)
    {
        $cotacao = CotacaoExterna::findOrFail($id);

        $cotacao->update([
            'dados_extraidos' => $request->dados,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dados atualizados com sucesso!',
        ]);
    }

    /**
     * Salva dados do orçamentista
     */
    public function salvarOrcamentista(Request $request, $id)
    {
        try {
            \Log::info('salvarOrcamentista INICIADO', [
                'id' => $id,
                'dados_recebidos' => $request->except(['brasao', '_token'])
            ]);

            $cotacao = CotacaoExterna::findOrFail($id);

            // PERMITIR SALVAR SEM NOME (opcional)
            // O usuário pode querer ver o preview antes de preencher tudo
            $dados = [];

            // Pegar todos os campos orcamentista_ enviados (mesmo vazios)
            foreach ($request->all() as $key => $value) {
                if (str_starts_with($key, 'orcamentista_')) {
                    // Salvar mesmo se vazio, para permitir preview
                    $dados[$key] = $value;
                }
            }

            \Log::info('Dados para salvar (permitindo vazios):', ['dados' => $dados]);

            // Upload de brasão se enviado
            if ($request->hasFile('brasao')) {
                $brasao = $request->file('brasao');
                $brasaoPath = $brasao->store('cotacoes_externas/brasoes', 'public');
                // Salvar apenas o caminho relativo (sem 'storage/')
                // O preview.blade.php já adiciona 'storage/' na frente
                $dados['brasao_path'] = $brasaoPath;
                \Log::info('Brasão enviado e salvo', ['path' => $brasaoPath, 'full_path' => storage_path('app/public/' . $brasaoPath)]);
            }

            $cotacao->update($dados);

            \Log::info('Orçamentista salvo com sucesso', ['cotacao_id' => $cotacao->id]);

            return response()->json([
                'success' => true,
                'message' => 'Dados salvos com sucesso!',
            ]);

        } catch (\Exception $e) {
            \Log::error('Erro ao salvar orçamentista:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostra preview do PDF
     */
    public function preview(Request $request, $id)
    {
        try {
            \Log::info('=== PREVIEW INICIADO ===', [
                'cotacao_id' => $id,
                'template' => $request->input('template', 'padrao')
            ]);

            $cotacao = CotacaoExterna::findOrFail($id);

            // Permitir seleção de template (padrão ou mapa-apuracao)
            $template = $request->input('template', 'padrao');

            \Log::info('Cotação encontrada', [
                'titulo' => $cotacao->titulo,
                'total_itens' => count($cotacao->dados_extraidos['itens'] ?? [])
            ]);

        // Preparar dados para o template preview.blade.php
        $dados = $cotacao->dados_extraidos;

        // Criar objeto fake $orcamento para compatibilidade com preview.blade.php
        $orcamentoFake = new \stdClass();

        // Campos básicos
        $orcamentoFake->id = $cotacao->id;
        $orcamentoFake->numero = 'COTAÇÃO-' . str_pad($cotacao->id, 4, '0', STR_PAD_LEFT);
        $orcamentoFake->nome = $cotacao->titulo;
        $orcamentoFake->titulo = $cotacao->titulo;
        $orcamentoFake->objeto = $cotacao->titulo;
        $orcamentoFake->created_at = $cotacao->created_at;
        $orcamentoFake->data_conclusao = $cotacao->data_conclusao;

        // Dados do orçamentista
        $orcamentoFake->orcamentista_nome = $cotacao->orcamentista_nome;
        $orcamentoFake->orcamentista_cpf = $cotacao->orcamentista_cpf;
        $orcamentoFake->orcamentista_setor = $cotacao->orcamentista_setor;
        $orcamentoFake->orcamentista_razao_social = $cotacao->orcamentista_razao_social;
        $orcamentoFake->orcamentista_cnpj = $cotacao->orcamentista_cnpj;
        $orcamentoFake->orcamentista_endereco = $cotacao->orcamentista_endereco;
        $orcamentoFake->orcamentista_cidade = $cotacao->orcamentista_cidade;
        $orcamentoFake->orcamentista_uf = $cotacao->orcamentista_uf;
        $orcamentoFake->orcamentista_cep = $cotacao->orcamentista_cep;
        $orcamentoFake->brasao_path = $cotacao->brasao_path;

        // Campos adicionais esperados pelo template
        $orcamentoFake->orgao_interessado = $cotacao->orcamentista_razao_social ?? 'NÃO INFORMADO';
        $orcamentoFake->referencia_externa = 'COTAÇÃO EXTERNA';
        $orcamentoFake->metodo_obtencao_preco = 'DOCUMENTO EXTERNO';
        $orcamentoFake->observacoes = 'Cotação externa importada de documento PDF.';
        $orcamentoFake->observacao_justificativa = null; // Não usado em cotação externa
        $orcamentoFake->orcamentista_cargo = 'Orçamentista';
        $orcamentoFake->orcamentista_cpf_cnpj = $cotacao->orcamentista_cnpj ?? $cotacao->orcamentista_cpf;
        $orcamentoFake->orcamentista_matricula = null;
        $orcamentoFake->orcamentista_portaria = null;
        $orcamentoFake->validade_dias = 90;
        $orcamentoFake->user = null; // Não tem user associado em cotação externa

        // Collections vazias para evitar erros
        $orcamentoFake->coletasEcommerce = collect();
        $orcamentoFake->contratacoesSimilares = collect();
        $orcamentoFake->solicitacoesCDF = collect();

        // CRÍTICO: Converter itens de array para collection de objetos
        // A view espera $orcamento->itens como collection de objetos
        // E os campos devem ser: lote_numero, item_numero (não lote e numero)
        $itensCollection = collect($dados['itens'] ?? [])->map(function($item, $index) {
            $obj = new \stdClass();
            $obj->lote_numero = $item['lote'] ?? '01';
            $obj->item_numero = $item['numero'] ?? ($index + 1);
            $obj->descricao = $item['descricao'] ?? '';
            $obj->quantidade = $item['quantidade'] ?? 0;
            $obj->unidade = $item['unidade'] ?? 'UN';
            $obj->preco_unitario = $item['preco_unitario'] ?? 0;
            $obj->preco_total = $item['preco_total'] ?? 0;
            $obj->percentual_diferenca = $item['percentual_diferenca'] ?? '';
            $obj->marca = $item['marca'] ?? '';
            $obj->fornecedor = $item['fornecedor'] ?? '';

            // PREÇOS DOS FORNECEDORES (para exibir no preview)
            $obj->precos_fornecedores = $item['precos_fornecedores'] ?? [];

            // Campos adicionais esperados pelo template
            $obj->amostras_selecionadas = json_encode([]);

            return $obj;
        });

        $orcamentoFake->itens = $itensCollection;

        // Calcular valores para o template
        $lotes = $dados['lotes'] ?? [];
        $valorGlobal = collect($dados['itens'] ?? [])->sum('preco_total');

        // Preparar fornecedores com valor total
        $fornecedoresMap = [];
        foreach ($dados['itens'] ?? [] as $item) {
            $fornecedor = $item['fornecedor'] ?? 'NÃO INFORMADO';
            if (!isset($fornecedoresMap[$fornecedor])) {
                $fornecedoresMap[$fornecedor] = [
                    'nome' => $fornecedor,
                    'cnpj' => '',
                    'valor_total' => 0,
                ];
            }
            $fornecedoresMap[$fornecedor]['valor_total'] += $item['preco_total'] ?? 0;
        }

        $fornecedoresOrdenados = collect($fornecedoresMap)->sortByDesc('valor_total')->values()->all();

        // Renderizar HTML usando template selecionado
        $viewName = $template === 'mapa-apuracao'
            ? 'orcamentos.templates.mapa-apuracao'
            : 'orcamentos.templates.padrao';

        $html = view($viewName, [
            'isPreview' => true,
            'cotacaoExterna' => true,
            'orcamento' => $orcamentoFake,
            'titulo' => $cotacao->titulo,
            'dados' => $dados, // Para template mapa-apuracao
            'orcamentista' => [
                'nome' => $cotacao->orcamentista_nome,
                'cpf' => $cotacao->orcamentista_cpf,
                'setor' => $cotacao->orcamentista_setor,
                'razao_social' => $cotacao->orcamentista_razao_social,
                'cnpj' => $cotacao->orcamentista_cnpj,
                'endereco' => $cotacao->orcamentista_endereco,
                'cidade' => $cotacao->orcamentista_cidade,
                'uf' => $cotacao->orcamentista_uf,
                'cep' => $cotacao->orcamentista_cep,
            ],
            'brasao_path' => $cotacao->brasao_path,
            'itens' => $dados['itens'] ?? [],
            'lotes' => $lotes,
            'fornecedores' => $dados['fornecedores'] ?? [],
            'fornecedoresOrdenados' => $fornecedoresOrdenados,
            'valorGlobal' => $valorGlobal,
            'valor_total_geral' => $valorGlobal,
        ])->render();

        // Gerar PDF com mPDF
        // Se template é mapa-apuracao, usar paisagem, senão retrato
        $orientation = ($template === 'mapa-apuracao') ? 'L' : 'P';

        \Log::info('Iniciando criação do mPDF...', ['template' => $template, 'orientation' => $orientation]);

        try {
            $mpdfConfig = [
                'mode' => 'utf-8',
                'format' => 'A4-' . $orientation,
                'orientation' => $orientation,
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'tempDir' => storage_path('app/mpdf_temp'),
            ];

            \Log::info('Configuração do mPDF preparada', $mpdfConfig);

            $mpdf = new \Mpdf\Mpdf($mpdfConfig);

            \Log::info('mPDF instanciado com sucesso');

            \Log::info('Escrevendo HTML no PDF...', ['tamanho_html' => strlen($html)]);

            $mpdf->WriteHTML($html);

            \Log::info('HTML escrito com sucesso, gerando output...');

            $pdfOutput = $mpdf->Output('', 'S');

            \Log::info('PDF gerado com sucesso!', ['tamanho_pdf' => strlen($pdfOutput)]);

            // Retornar PDF inline (abre no navegador)
            return response($pdfOutput)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="preview_cotacao_' . $cotacao->id . '.pdf"');

        } catch (\Mpdf\MpdfException $mpdfError) {
            \Log::error('ERRO DO MPDF:', [
                'erro' => $mpdfError->getMessage(),
                'linha' => $mpdfError->getLine(),
                'arquivo' => $mpdfError->getFile(),
            ]);

            return response('<h1>Erro ao gerar PDF (mPDF)</h1><pre>' .
                'Erro: ' . $mpdfError->getMessage() . "\n\n" .
                'Arquivo: ' . $mpdfError->getFile() . ':' . $mpdfError->getLine() .
                '</pre>', 500);
        }

        } catch (\Exception $e) {
            \Log::error('ERRO NO PREVIEW:', [
                'cotacao_id' => $id,
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);

            // Retornar erro em HTML para debug
            return response('<h1>Erro ao gerar preview</h1><pre>' .
                'Erro: ' . $e->getMessage() . "\n\n" .
                'Arquivo: ' . $e->getFile() . ':' . $e->getLine() . "\n\n" .
                'Trace: ' . $e->getTraceAsString() .
                '</pre>', 500);
        }
    }

    /**
     * Finaliza e salva em Realizados
     */
    public function concluir($id)
    {
        $cotacao = CotacaoExterna::findOrFail($id);

        // Gerar PDF final
        $pdfPath = $this->gerarPDFFinal($cotacao);

        // Atualizar status
        $cotacao->update([
            'arquivo_pdf_path' => $pdfPath,
            'status' => 'concluido',
            'data_conclusao' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cotação concluída e salva em Realizados!',
            'pdf_url' => Storage::url($pdfPath),
        ]);
    }

    /**
     * Gera PDF final
     */
    private function gerarPDFFinal(CotacaoExterna $cotacao)
    {
        $dados = $cotacao->dados_extraidos;

        // Criar objeto fake $orcamento (MESMA LÓGICA DO preview())
        $orcamentoFake = new \stdClass();
        $orcamentoFake->id = $cotacao->id;
        $orcamentoFake->numero = 'COTAÇÃO-' . str_pad($cotacao->id, 4, '0', STR_PAD_LEFT);
        $orcamentoFake->nome = $cotacao->titulo;
        $orcamentoFake->titulo = $cotacao->titulo;
        $orcamentoFake->objeto = $cotacao->titulo;
        $orcamentoFake->created_at = $cotacao->created_at;
        $orcamentoFake->data_conclusao = now(); // AGORA é oficial, não é mais preview

        // Dados do orçamentista
        $orcamentoFake->orcamentista_nome = $cotacao->orcamentista_nome;
        $orcamentoFake->orcamentista_cpf = $cotacao->orcamentista_cpf;
        $orcamentoFake->orcamentista_setor = $cotacao->orcamentista_setor;
        $orcamentoFake->orcamentista_razao_social = $cotacao->orcamentista_razao_social;
        $orcamentoFake->orcamentista_cnpj = $cotacao->orcamentista_cnpj;
        $orcamentoFake->orcamentista_endereco = $cotacao->orcamentista_endereco;
        $orcamentoFake->orcamentista_cidade = $cotacao->orcamentista_cidade;
        $orcamentoFake->orcamentista_uf = $cotacao->orcamentista_uf;
        $orcamentoFake->orcamentista_cep = $cotacao->orcamentista_cep;
        $orcamentoFake->brasao_path = $cotacao->brasao_path;

        // Campos adicionais
        $orcamentoFake->orgao_interessado = $cotacao->orcamentista_razao_social ?? 'NÃO INFORMADO';
        $orcamentoFake->referencia_externa = 'COTAÇÃO EXTERNA';
        $orcamentoFake->metodo_obtencao_preco = 'DOCUMENTO EXTERNO';
        $orcamentoFake->observacoes = 'Cotação externa importada de documento PDF.';
        $orcamentoFake->observacao_justificativa = null;
        $orcamentoFake->orcamentista_cargo = 'Orçamentista';
        $orcamentoFake->orcamentista_cpf_cnpj = $cotacao->orcamentista_cnpj ?? $cotacao->orcamentista_cpf;
        $orcamentoFake->orcamentista_matricula = null;
        $orcamentoFake->orcamentista_portaria = null;
        $orcamentoFake->validade_dias = 90;
        $orcamentoFake->user = null;

        // Collections vazias
        $orcamentoFake->coletasEcommerce = collect();
        $orcamentoFake->contratacoesSimilares = collect();
        $orcamentoFake->solicitacoesCDF = collect();

        // Converter itens
        $itensCollection = collect($dados['itens'] ?? [])->map(function($item, $index) {
            $obj = new \stdClass();
            $obj->lote_numero = $item['lote'] ?? '01';
            $obj->item_numero = $item['numero'] ?? ($index + 1);
            $obj->descricao = $item['descricao'] ?? '';
            $obj->quantidade = $item['quantidade'] ?? 0;
            $obj->unidade = $item['unidade'] ?? 'UN';
            $obj->preco_unitario = $item['preco_unitario'] ?? 0;
            $obj->preco_total = $item['preco_total'] ?? 0;
            $obj->percentual_diferenca = $item['percentual_diferenca'] ?? '';
            $obj->marca = $item['marca'] ?? '';
            $obj->fornecedor = $item['fornecedor'] ?? '';
            $obj->precos_fornecedores = $item['precos_fornecedores'] ?? [];
            $obj->amostras_selecionadas = json_encode([]);
            return $obj;
        });

        $orcamentoFake->itens = $itensCollection;

        // Calcular valores (MESMA LÓGICA DO preview())
        $lotes = $dados['lotes'] ?? [];
        $valorGlobal = collect($dados['itens'] ?? [])->sum('preco_total');

        // Preparar fornecedores com valor total
        $fornecedoresMap = [];
        foreach ($dados['itens'] ?? [] as $item) {
            $fornecedor = $item['fornecedor'] ?? 'NÃO INFORMADO';
            if (!isset($fornecedoresMap[$fornecedor])) {
                $fornecedoresMap[$fornecedor] = [
                    'nome' => $fornecedor,
                    'cnpj' => '', // Pode não ter CNPJ individual por item
                    'valor_total' => 0,
                ];
            }
            $fornecedoresMap[$fornecedor]['valor_total'] += $item['preco_total'] ?? 0;
        }

        $fornecedoresOrdenados = collect($fornecedoresMap)->sortByDesc('valor_total')->values()->all();

        // Renderizar view como HTML (isPreview = FALSE para remover aviso)
        $html = view('orcamentos.preview', [
            'isPreview' => false, // ✅ SEM AVISO DE PREVIEW
            'cotacaoExterna' => true,
            'orcamento' => $orcamentoFake,
            'titulo' => $cotacao->titulo,
            'orcamentista' => [
                'nome' => $cotacao->orcamentista_nome,
                'cpf' => $cotacao->orcamentista_cpf,
                'setor' => $cotacao->orcamentista_setor,
                'razao_social' => $cotacao->orcamentista_razao_social,
                'cnpj' => $cotacao->orcamentista_cnpj,
                'endereco' => $cotacao->orcamentista_endereco,
                'cidade' => $cotacao->orcamentista_cidade,
                'uf' => $cotacao->orcamentista_uf,
                'cep' => $cotacao->orcamentista_cep,
            ],
            'brasao_path' => $cotacao->brasao_path,
            'itens' => $dados['itens'] ?? [],
            'lotes' => $lotes,
            'fornecedores' => $dados['fornecedores'] ?? [],
            'fornecedoresOrdenados' => $fornecedoresOrdenados,
            'valorGlobal' => $valorGlobal,
            'valor_total_geral' => $valorGlobal,
        ])->render();

        // Gerar PDF (usar mesma biblioteca do sistema)
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);

        $mpdf->WriteHTML($html);

        $filename = 'cotacao_' . $cotacao->id . '_' . time() . '.pdf';
        $path = 'cotacoes_externas/pdfs/' . $filename;

        Storage::disk('public')->put($path, $mpdf->Output('', 'S'));

        return $path;
    }
}
