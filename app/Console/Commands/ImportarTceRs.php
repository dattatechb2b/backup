<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContratoExterno;
use App\Models\ItemContratoExterno;
use App\Models\CheckpointImportacao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportarTceRs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cesta:importar-tce-rs
                            {tipo : Tipo de importação: contratos ou licitacoes}
                            {--url= : URL do CSV/ZIP (opcional)}
                            {--arquivo= : Path local do arquivo (opcional)}
                            {--limpar : Limpar dados antigos antes de importar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa contratos ou licitações do TCE-RS via CSV';

    /**
     * URLs padrão dos datasets TCE-RS
     */
    private const URLS_PADRAO = [
        'contratos' => 'https://dados.tce.rs.gov.br/dataset/contratos-consolidado-2025',
        'licitacoes' => 'https://dados.tce.rs.gov.br/dataset/licitacoes-consolidado-2025',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tipo = $this->argument('tipo');

        if (!in_array($tipo, ['contratos', 'licitacoes'])) {
            $this->error("❌ Tipo inválido! Use: contratos ou licitacoes");
            return 1;
        }

        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📥 IMPORTAÇÃO TCE-RS - " . strtoupper($tipo));
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();

        try {
            // 1. Determinar arquivo (pode ser ZIP ou CSV direto)
            $arquivo = $this->determinarArquivo($tipo);

            if (!$arquivo || !file_exists($arquivo)) {
                $this->error("❌ Arquivo não encontrado!");
                return 1;
            }

            // 2. Verificar se é ZIP e extrair
            $arquivosParaProcessar = [];
            if (strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)) === 'zip') {
                $this->info("📦 Arquivo ZIP detectado. Extraindo...");
                $arquivosParaProcessar = $this->extrairZip($arquivo);
            } else {
                $arquivosParaProcessar = [$arquivo];
            }

            // 3. Processar arquivos
            foreach ($arquivosParaProcessar as $arq) {
                $this->processarArquivoIndividual($arq, $tipo);
            }

            $this->newLine();
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("✅ IMPORTAÇÃO CONCLUÍDA!");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ ERRO: " . $e->getMessage());
            Log::error("Erro ao importar TCE-RS", [
                'tipo' => $tipo,
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    /**
     * Processa um arquivo individual (CONTRATO ou ITEM_CON)
     */
    private function processarArquivoIndividual(string $arquivo, string $tipo): void
    {
        $this->newLine();
        $this->info("📄 Processando: " . basename($arquivo));

        // Calcular checksum
        $this->info("🔐 Calculando checksum...");
        $checksum = hash_file('sha256', $arquivo);
        $this->line("   Checksum: " . substr($checksum, 0, 16) . "...");

        // Determinar tipo de arquivo (CONTRATO ou ITEM_CON)
        $nomeArquivo = strtoupper(basename($arquivo, '.csv'));
        $isItemCon = str_contains($nomeArquivo, 'ITEM');

        $fonte = 'TCE-RS-' . strtoupper($tipo) . ($isItemCon ? '-ITENS' : '-CONTRATOS');

        // Verificar se já foi processado
        if ($this->jaProcessado($fonte, basename($arquivo), $checksum)) {
            $this->info("✅ Arquivo já processado (checksum igual). Pulando.");
            return;
        }

        // Limpar dados antigos se solicitado
        if ($this->option('limpar')) {
            $this->limparDadosAntigos($fonte);
        }

        // Criar checkpoint
        $checkpoint = CheckpointImportacao::create([
            'fonte' => $fonte,
            'arquivo' => basename($arquivo),
            'checksum' => $checksum,
            'status' => 'em_processamento',
            'iniciado_em' => now(),
        ]);

        try {
            // Processar CSV
            $this->newLine();
            $this->info("📊 Processando registros...");

            if ($isItemCon) {
                $this->processarCsvItens($arquivo, $checkpoint, $tipo);
            } else {
                $this->processarCsvContratos($arquivo, $checkpoint, $tipo);
            }

            // Finalizar
            $checkpoint->update([
                'status' => 'concluido',
                'finalizado_em' => now(),
            ]);

            $this->newLine();
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Registros processados', $checkpoint->registros_processados],
                    ['Novos', $checkpoint->registros_novos],
                    ['Atualizados', $checkpoint->registros_atualizados],
                    ['Erros', $checkpoint->registros_erro],
                ]
            );

        } catch (\Exception $e) {
            $checkpoint->update([
                'status' => 'erro',
                'erro_mensagem' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extrai arquivo ZIP e retorna lista de CSVs
     */
    private function extrairZip(string $zipPath): array
    {
        $zip = new \ZipArchive();
        $extractPath = storage_path('app/temp/tce_rs_extract_' . time());

        if ($zip->open($zipPath) !== true) {
            throw new \Exception("Não foi possível abrir o arquivo ZIP");
        }

        // Criar diretório de extração
        if (!file_exists($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        $zip->extractTo($extractPath);
        $zip->close();

        $this->info("   ✓ Arquivos extraídos em: {$extractPath}");

        // Buscar CSVs relevantes (CONTRATO e ITEM_CON)
        $csvs = [];
        $arquivosRelevantes = ['CONTRATO', 'ITEM_CON'];

        foreach (scandir($extractPath) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'csv') {
                continue;
            }

            $nomeArquivo = strtoupper(basename($file, '.csv'));
            foreach ($arquivosRelevantes as $relevante) {
                if (str_contains($nomeArquivo, $relevante)) {
                    $csvs[] = $extractPath . '/' . $file;
                    $this->line("   📄 Encontrado: {$file}");
                }
            }
        }

        if (empty($csvs)) {
            throw new \Exception("Nenhum arquivo CSV relevante encontrado no ZIP");
        }

        // Ordenar: CONTRATO primeiro, ITEM_CON depois
        usort($csvs, function($a, $b) {
            $aIsItem = str_contains(strtoupper($a), 'ITEM');
            $bIsItem = str_contains(strtoupper($b), 'ITEM');
            return $aIsItem <=> $bIsItem;
        });

        return $csvs;
    }

    /**
     * Determina qual arquivo usar (download ou local)
     */
    private function determinarArquivo(string $tipo): ?string
    {
        // Se informou arquivo local
        if ($arquivoLocal = $this->option('arquivo')) {
            return $arquivoLocal;
        }

        // Se informou URL, fazer download
        if ($url = $this->option('url')) {
            return $this->baixarArquivo($url, $tipo);
        }

        // Usar URL padrão
        $this->warn("⚠️  Nenhum arquivo ou URL informado.");
        $this->warn("   Para importar, você precisa:");
        $this->line("   1. Acessar: " . self::URLS_PADRAO[$tipo]);
        $this->line("   2. Baixar o CSV manualmente");
        $this->line("   3. Executar: php artisan cesta:importar-tce-rs {$tipo} --arquivo=/path/to/file.csv");

        return null;
    }

    /**
     * Baixa arquivo de uma URL
     */
    private function baixarArquivo(string $url, string $tipo): string
    {
        $this->info("⬇️  Baixando arquivo...");
        $this->line("   URL: {$url}");

        $nomeArquivo = 'tce_rs_' . $tipo . '_' . date('Y_m_d_His') . '.csv';
        $destino = storage_path('app/temp/' . $nomeArquivo);

        // Criar diretório se não existir
        if (!file_exists(dirname($destino))) {
            mkdir(dirname($destino), 0755, true);
        }

        // Fazer download
        $response = Http::timeout(300)->get($url);

        if (!$response->successful()) {
            throw new \Exception("Erro ao baixar arquivo: " . $response->status());
        }

        file_put_contents($destino, $response->body());

        $tamanho = filesize($destino);
        $this->info("   ✓ Download concluído: " . number_format($tamanho / 1024 / 1024, 2) . " MB");

        return $destino;
    }

    /**
     * Verifica se arquivo já foi processado
     */
    private function jaProcessado(string $fonte, string $arquivo, string $checksum): bool
    {
        return CheckpointImportacao::where('fonte', $fonte)
            ->where('arquivo', $arquivo)
            ->where('checksum', $checksum)
            ->where('status', 'concluido')
            ->exists();
    }

    /**
     * Limpa dados antigos da fonte
     */
    private function limparDadosAntigos(string $fonte): void
    {
        $this->warn("🗑️  Limpando dados antigos...");

        $total = ContratoExterno::where('fonte', $fonte)->count();

        if ($total > 0) {
            if ($this->confirm("   Excluir {$total} registros existentes?", false)) {
                ContratoExterno::where('fonte', $fonte)->delete();
                $this->info("   ✓ {$total} registros excluídos");
            }
        }
    }

    /**
     * Processa CSV de CONTRATOS linha a linha (streaming)
     */
    private function processarCsvContratos(string $arquivo, CheckpointImportacao $checkpoint, string $tipo): void
    {
        $handle = fopen($arquivo, 'r');

        if (!$handle) {
            throw new \Exception("Não foi possível abrir o arquivo");
        }

        // Detectar encoding
        $primeiraLinha = fgets($handle);
        rewind($handle);

        $encoding = mb_detect_encoding($primeiraLinha, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        $this->line("   Encoding detectado: {$encoding}");

        // Ler header
        $header = fgetcsv($handle, 0, ';');

        if (!$header) {
            throw new \Exception("Arquivo CSV inválido (sem header)");
        }

        // Converter encoding do header se necessário
        if ($encoding !== 'UTF-8') {
            $header = array_map(fn($h) => mb_convert_encoding($h, 'UTF-8', $encoding), $header);
        }

        $this->line("   Colunas encontradas: " . count($header));

        // Mostrar primeiras colunas para debug
        if (count($header) > 0) {
            $this->newLine();
            $this->info("   📋 Primeiras colunas detectadas:");
            $primeiras = array_slice($header, 0, min(10, count($header)));
            foreach ($primeiras as $idx => $col) {
                $this->line("      " . ($idx + 1) . ". " . $col);
            }
            if (count($header) > 10) {
                $this->line("      ... (+" . (count($header) - 10) . " colunas)");
            }
            $this->newLine();
        }

        $linha = 0;
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat(' %current% linhas | %elapsed% | %memory:6s%');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $linha++;

            // Pular se já processou esta linha
            if ($linha <= $checkpoint->ultima_linha_processada) {
                continue;
            }

            try {
                // Converter encoding se necessário
                if ($encoding !== 'UTF-8') {
                    $row = array_map(fn($r) => mb_convert_encoding($r ?? '', 'UTF-8', $encoding), $row);
                }

                // Mapear colunas
                $dados = array_combine($header, $row);

                // Processar registro de contrato
                $resultado = $this->processarRegistroContrato($dados, $tipo);

                if ($resultado === 'novo') {
                    $checkpoint->increment('registros_novos');
                } elseif ($resultado === 'atualizado') {
                    $checkpoint->increment('registros_atualizados');
                }

                $checkpoint->update([
                    'registros_processados' => $linha,
                    'ultima_linha_processada' => $linha,
                ]);

                // Atualizar progress bar a cada 100 linhas
                if ($linha % 100 === 0) {
                    $progressBar->advance(100);
                }

            } catch (\Exception $e) {
                $this->error("Erro na linha {$linha}: " . $e->getMessage());
                Log::warning("Erro ao processar linha {$linha}", [
                    'erro' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'dados' => $dados ?? [],
                ]);
                $checkpoint->increment('registros_erro');
            }
        }

        $progressBar->finish();
        fclose($handle);

        $this->newLine(2);
    }

    /**
     * Processa CSV de ITENS linha a linha (streaming)
     */
    private function processarCsvItens(string $arquivo, CheckpointImportacao $checkpoint, string $tipo): void
    {
        $handle = fopen($arquivo, 'r');

        if (!$handle) {
            throw new \Exception("Não foi possível abrir o arquivo");
        }

        // Detectar encoding
        $primeiraLinha = fgets($handle);
        rewind($handle);

        $encoding = mb_detect_encoding($primeiraLinha, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        $this->line("   Encoding detectado: {$encoding}");

        // Ler header
        $header = fgetcsv($handle, 0, ';');

        if (!$header) {
            throw new \Exception("Arquivo CSV inválido (sem header)");
        }

        // Converter encoding do header se necessário
        if ($encoding !== 'UTF-8') {
            $header = array_map(fn($h) => mb_convert_encoding($h, 'UTF-8', $encoding), $header);
        }

        $this->line("   Colunas encontradas: " . count($header));

        // Mostrar primeiras colunas para debug
        if (count($header) > 0) {
            $this->newLine();
            $this->info("   📋 Primeiras colunas detectadas:");
            $primeiras = array_slice($header, 0, min(10, count($header)));
            foreach ($primeiras as $idx => $col) {
                $this->line("      " . ($idx + 1) . ". " . $col);
            }
            if (count($header) > 10) {
                $this->line("      ... (+" . (count($header) - 10) . " colunas)");
            }
            $this->newLine();
        }

        $linha = 0;
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat(' %current% linhas | %elapsed% | %memory:6s%');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $linha++;

            // Pular se já processou esta linha
            if ($linha <= $checkpoint->ultima_linha_processada) {
                continue;
            }

            try {
                // Converter encoding se necessário
                if ($encoding !== 'UTF-8') {
                    $row = array_map(fn($r) => mb_convert_encoding($r ?? '', 'UTF-8', $encoding), $row);
                }

                // Mapear colunas
                $dados = array_combine($header, $row);

                // Processar registro de item
                $resultado = $this->processarRegistroItem($dados, $tipo);

                if ($resultado === 'novo') {
                    $checkpoint->increment('registros_novos');
                } elseif ($resultado === 'atualizado') {
                    $checkpoint->increment('registros_atualizados');
                }

                $checkpoint->update([
                    'registros_processados' => $linha,
                    'ultima_linha_processada' => $linha,
                ]);

                // Atualizar progress bar a cada 100 linhas
                if ($linha % 100 === 0) {
                    $progressBar->advance(100);
                }

            } catch (\Exception $e) {
                $this->error("❌ Erro na linha {$linha}: " . $e->getMessage());
                Log::warning("Erro ao processar linha {$linha} (item)", [
                    'erro' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'dados' => $dados ?? [],
                ]);
                $checkpoint->increment('registros_erro');
            }
        }

        $progressBar->finish();
        fclose($handle);

        $this->newLine(2);
    }

    /**
     * Processa um registro individual de CONTRATO
     */
    private function processarRegistroContrato(array $dados, string $tipo): string
    {
        // Extrair e normalizar dados
        $contratoData = $this->extrairDadosContrato($dados, $tipo);

        // Calcular hash para deduplicação
        $hash = $this->calcularHash($contratoData);

        // UPSERT (insert or update)
        // WORKAROUND: Não salvar dados_originais se banco estiver em SQL_ASCII
        $dadosParaSalvar = array_merge($contratoData, [
            'hash_normalizado' => $hash,
        ]);

        // Tentar salvar com dados_originais, se falhar, salvar sem
        try {
            $contrato = ContratoExterno::updateOrCreate(
                ['hash_normalizado' => $hash],
                array_merge($dadosParaSalvar, ['dados_originais' => $dados])
            );
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Untranslatable character') ||
                str_contains($e->getMessage(), 'SQL_ASCII')) {
                // Salvar sem dados_originais por causa de encoding
                $contrato = ContratoExterno::updateOrCreate(
                    ['hash_normalizado' => $hash],
                    array_merge($dadosParaSalvar, ['dados_originais' => null])
                );
            } else {
                throw $e;
            }
        }

        return $contrato->wasRecentlyCreated ? 'novo' : 'atualizado';
    }

    /**
     * Processa um registro individual de ITEM
     */
    private function processarRegistroItem(array $dados, string $tipo): string
    {
        // Extrair dados do item
        try {
            $itemData = $this->extrairDadosItem($dados, $tipo);
        } catch (\Exception $e) {
            throw new \Exception("Erro em extrairDadosItem: " . $e->getMessage());
        }

        // Buscar contrato pai pelo id_externo
        $contrato = ContratoExterno::where('id_externo', $itemData['contrato_id_externo'])
            ->where('fonte', 'LIKE', 'TCE-RS-%')
            ->first();

        if (!$contrato) {
            // Se contrato não existe, pular este item
            throw new \Exception("Contrato pai não encontrado: " . $itemData['contrato_id_externo']);
        }

        // Calcular hash para deduplicação
        $hash = $this->calcularHashItem($itemData, $contrato->id);

        // UPSERT (insert or update)
        // WORKAROUND: Não salvar dados_originais se banco estiver em SQL_ASCII
        $dadosParaSalvar = [
            'contrato_id' => $contrato->id,
            'hash_normalizado' => $hash,
            'numero_item' => $itemData['numero_item'] ?? null,
            'descricao' => $itemData['descricao'] ?? null,
            'descricao_normalizada' => $itemData['descricao_normalizada'] ?? null,
            'quantidade' => $itemData['quantidade'] ?? null,
            'unidade' => $itemData['unidade'] ?? 'UN',
            'valor_unitario' => $itemData['valor_unitario'] ?? null,
            'valor_total' => $itemData['valor_total'] ?? null,
            'catmat' => $itemData['catmat'] ?? null,
            'catser' => $itemData['catser'] ?? null,
            'qualidade_score' => $itemData['qualidade_score'] ?? 0,
            'flags_qualidade' => $itemData['flags_qualidade'] ?? [],
        ];

        // Tentar salvar com dados_originais, se falhar, salvar sem
        try {
            $item = ItemContratoExterno::updateOrCreate(
                ['hash_normalizado' => $hash],
                array_merge($dadosParaSalvar, ['dados_originais' => $itemData['dados_originais']])
            );
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Untranslatable character') ||
                str_contains($e->getMessage(), 'SQL_ASCII')) {
                // Salvar sem dados_originais por causa de encoding
                $item = ItemContratoExterno::updateOrCreate(
                    ['hash_normalizado' => $hash],
                    array_merge($dadosParaSalvar, ['dados_originais' => null])
                );
            } else {
                throw $e;
            }
        }

        return $item->wasRecentlyCreated ? 'novo' : 'atualizado';
    }

    /**
     * Extrai dados do contrato do array CSV
     * Mapeamento flexível para múltiplas variações de nomes de colunas
     */
    private function extrairDadosContrato(array $dados, string $tipo): array
    {
        $fonte = 'TCE-RS-' . strtoupper($tipo);

        // Helper para buscar valor com múltiplos nomes possíveis
        $buscar = function(array $possiveisNomes) use ($dados) {
            foreach ($possiveisNomes as $nome) {
                if (isset($dados[$nome]) && $dados[$nome] !== '') {
                    return $dados[$nome];
                }
            }
            return null;
        };

        return [
            'fonte' => $fonte,

            // ID Externo - variações comuns em sistemas TCE
            'id_externo' => $buscar([
                'CD_CONTRATO', 'id_contrato', 'codigo_contrato', 'NR_CONTRATO',
                'ID', 'id', 'codigo', 'CD_DOCUMENTO'
            ]) ?? uniqid('tce_'),

            // Número do Contrato
            'numero_contrato' => $buscar([
                'NR_CONTRATO', 'numero_contrato', 'numero', 'contrato',
                'DS_NUMERO_CONTRATO', 'NR_DOCUMENTO'
            ]),

            // Objeto do Contrato
            'objeto' => $buscar([
                'DS_OBJETO', 'objeto', 'descricao', 'DS_DESCRICAO',
                'TX_OBJETO', 'objeto_contrato'
            ]),

            // Valor Total
            'valor_total' => $this->normalizarValor($buscar([
                'VL_CONTRATO', 'valor_contrato', 'valor_total', 'valor',
                'VL_TOTAL', 'VL_INICIAL'
            ])),

            // Data de Assinatura
            'data_assinatura' => $this->normalizarData($buscar([
                'DT_ASSINATURA', 'data_assinatura', 'data', 'DT_CONTRATO',
                'DT_INICIO_VIGENCIA', 'data_inicio'
            ])),

            // Data Vigência Início
            'data_vigencia_inicio' => $this->normalizarData($buscar([
                'DT_INICIO_VIGENCIA', 'vigencia_inicio', 'DT_INICIO',
                'data_inicio_vigencia'
            ])),

            // Data Vigência Fim
            'data_vigencia_fim' => $this->normalizarData($buscar([
                'DT_FIM_VIGENCIA', 'vigencia_fim', 'DT_FIM',
                'data_fim_vigencia', 'DT_TERMINO'
            ])),

            // Órgão - Nome
            'orgao_nome' => $buscar([
                'DS_ORGAO', 'orgao', 'orgao_nome', 'NM_ORGAO',
                'nome_orgao', 'DS_ENTIDADE'
            ]),

            // Órgão - CNPJ
            'orgao_cnpj' => $this->normalizarCNPJ($buscar([
                'NR_CNPJ_ORGAO', 'cnpj_orgao', 'orgao_cnpj', 'CNPJ_ORGAO',
                'NR_CNPJ_ENTIDADE', 'cnpj'
            ])),

            // UF e Município
            'orgao_uf' => $buscar(['UF', 'uf', 'SG_UF']) ?? 'RS',
            'orgao_municipio' => $buscar([
                'DS_MUNICIPIO', 'municipio', 'NM_MUNICIPIO',
                'cidade', 'DS_CIDADE'
            ]),

            // Fornecedor/Contratado - Nome
            'fornecedor_nome' => $buscar([
                'NM_CONTRATADO', 'contratado', 'fornecedor', 'fornecedor_nome',
                'DS_FORNECEDOR', 'NM_FORNECEDOR', 'NM_PESSOA'
            ]),

            // Fornecedor/Contratado - CNPJ
            'fornecedor_cnpj' => $this->normalizarCNPJ($buscar([
                'NR_CNPJ_CONTRATADO', 'cnpj_contratado', 'fornecedor_cnpj',
                'CNPJ_FORNECEDOR', 'NR_CNPJ_FORNECEDOR', 'NR_CNPJ_PESSOA'
            ])),

            // Metadata
            'url_fonte' => 'https://dados.tce.rs.gov.br/',
            'qualidade_score' => $this->calcularQualidadeScore($dados),
            'flags_qualidade' => $this->calcularFlagsQualidade($dados),
        ];
    }

    /**
     * Calcula hash para deduplicação de contrato
     */
    private function calcularHash(array $dados): string
    {
        $chave = implode('|', [
            $dados['fonte'],
            $dados['id_externo'],
            $dados['numero_contrato'] ?? '',
            $dados['orgao_cnpj'] ?? '',
            $dados['data_assinatura'] ?? '',
        ]);

        return hash('sha256', mb_strtolower($chave));
    }

    /**
     * Extrai dados do item do array CSV
     */
    private function extrairDadosItem(array $dados, string $tipo): array
    {
        // DEBUG: Verificar se todas as chaves existem
        if (!array_key_exists('catser', $dados)) {
            // catser não existe, isso é esperado
        }

        // Helper para buscar valor com múltiplos nomes possíveis
        $buscar = function(array $possiveisNomes) use ($dados) {
            foreach ($possiveisNomes as $nome) {
                if (isset($dados[$nome]) && $dados[$nome] !== '') {
                    return $dados[$nome];
                }
            }
            return null;
        };

        return [
            // ID do contrato pai (para buscar depois)
            'contrato_id_externo' => $buscar([
                'CD_CONTRATO', 'id_contrato', 'codigo_contrato',
                'NR_CONTRATO', 'CD_DOCUMENTO'
            ]),

            // Número do item
            'numero_item' => (int) ($buscar([
                'NR_ITEM', 'numero_item', 'item', 'NR_SEQUENCIAL',
                'SEQ_ITEM'
            ]) ?? 0),

            // Descrição do item
            'descricao' => $buscar([
                'DS_ITEM', 'descricao', 'descricao_item', 'DS_DESCRICAO',
                'TX_DESCRICAO', 'DS_OBJETO_ITEM'
            ]),

            // Descrição normalizada (mesmo valor por enquanto)
            'descricao_normalizada' => $buscar([
                'DS_ITEM', 'descricao', 'descricao_item'
            ]),

            // Quantidade
            'quantidade' => $this->normalizarValor($buscar([
                'QT_ITEM', 'quantidade', 'QT_CONTRATADA', 'VL_QUANTIDADE'
            ])),

            // Unidade
            'unidade' => $buscar([
                'DS_UNIDADE', 'unidade', 'UN', 'unidade_medida',
                'SG_UNIDADE'
            ]) ?? 'UN',

            // Valor unitário (PREÇO!)
            'valor_unitario' => $this->normalizarValor($buscar([
                'VL_UNITARIO', 'valor_unitario', 'preco_unitario',
                'VL_PRECO_UNITARIO', 'VL_UNIT'
            ])),

            // Valor total
            'valor_total' => $this->normalizarValor($buscar([
                'VL_TOTAL_ITEM', 'valor_total', 'VL_ITEM',
                'VL_TOTAL', 'valor_total_item'
            ])),

            // CATMAT (se houver)
            'catmat' => $buscar(['CD_CATMAT', 'catmat', 'CATMAT']),

            // CATSER (se houver)
            'catser' => $buscar(['CD_CATSER', 'catser', 'CATSER']),

            // Dados originais (sem incluir para evitar erro de encoding)
            'dados_originais' => null,

            // Qualidade (calculada dinamicamente)
            'qualidade_score' => $this->calcularQualidadeScoreItem($dados),
            'flags_qualidade' => $this->calcularFlagsQualidadeItem($dados),
        ];
    }

    /**
     * Calcula hash para deduplicação de item
     */
    private function calcularHashItem(array $itemData, int $contratoId): string
    {
        $chave = implode('|', [
            $contratoId,
            $itemData['numero_item'] ?? '',
            $itemData['descricao'] ?? '',
            $itemData['valor_unitario'] ?? '',
        ]);

        return hash('sha256', mb_strtolower($chave));
    }

    /**
     * Normaliza valor monetário
     */
    private function normalizarValor($valor): float
    {
        if (is_numeric($valor)) {
            return (float) $valor;
        }

        if (is_string($valor)) {
            // Remove tudo exceto números, vírgula e ponto
            $valor = preg_replace('/[^0-9,.]/', '', $valor);
            // Troca vírgula por ponto
            $valor = str_replace(',', '.', $valor);
            return (float) $valor;
        }

        return 0.0;
    }

    /**
     * Normaliza data
     */
    private function normalizarData($data): ?string
    {
        if (empty($data)) {
            return null;
        }

        try {
            $dt = \Carbon\Carbon::parse($data);
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Normaliza CNPJ para 14 dígitos
     */
    private function normalizarCNPJ($cnpj): ?string
    {
        if (empty($cnpj)) {
            return null;
        }

        // Remove tudo exceto números
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Completa com zeros à esquerda até 14 dígitos
        if (strlen($cnpj) < 14) {
            $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);
        }

        return substr($cnpj, 0, 14);
    }

    /**
     * Calcula score de qualidade (0-100)
     */
    private function calcularQualidadeScore(array $dados): int
    {
        $score = 100;

        // Penalizar por campos faltantes
        if (empty($dados['numero'])) $score -= 10;
        if (empty($dados['orgao_cnpj'])) $score -= 10;
        if (empty($dados['fornecedor_cnpj'])) $score -= 10;
        if (empty($dados['data_assinatura'])) $score -= 15;
        if (empty($dados['valor']) || $this->normalizarValor($dados['valor']) <= 0) $score -= 15;

        return max(0, $score);
    }

    /**
     * Calcula flags de qualidade para contratos
     */
    private function calcularFlagsQualidade(array $dados): array
    {
        $flags = [];

        if (empty($dados['numero'])) $flags[] = 'sem_numero';
        if (empty($dados['orgao_cnpj'])) $flags[] = 'sem_cnpj_orgao';
        if (empty($dados['fornecedor_cnpj'])) $flags[] = 'sem_cnpj_fornecedor';
        if (empty($dados['data_assinatura'])) $flags[] = 'sem_data';
        if ($this->normalizarValor($dados['valor'] ?? 0) <= 0) $flags[] = 'valor_zerado';

        return $flags;
    }

    /**
     * Calcula score de qualidade para itens (0-100)
     */
    private function calcularQualidadeScoreItem(array $dados): int
    {
        $score = 100;

        // Penalizar por campos faltantes em itens
        if (empty($dados['DS_ITEM'] ?? $dados['descricao'])) $score -= 20;

        $valorUnit = $this->normalizarValor($dados['VL_UNITARIO'] ?? $dados['valor_unitario'] ?? 0);
        if (empty($dados['VL_UNITARIO'] ?? $dados['valor_unitario']) || $valorUnit <= 0) {
            $score -= 30; // Valor unitário é CRÍTICO
        }

        // Detecção de outliers de preço
        if ($valorUnit > 0) {
            if ($valorUnit < 0.10) $score -= 20; // Preço muito baixo (< R$ 0,10)
            if ($valorUnit > 1000000) $score -= 25; // Preço muito alto (> R$ 1mi)
        }

        if (empty($dados['QT_ITEM'] ?? $dados['quantidade'])) $score -= 15;
        if (empty($dados['DS_UNIDADE'] ?? $dados['unidade'])) $score -= 10;

        // Verificar coerência: valor_total = valor_unitario * quantidade
        $valorTotal = $this->normalizarValor($dados['VL_TOTAL_ITEM'] ?? $dados['valor_total'] ?? 0);
        $quantidade = $this->normalizarValor($dados['QT_ITEM'] ?? $dados['quantidade'] ?? 0);
        if ($valorUnit > 0 && $quantidade > 0 && $valorTotal > 0) {
            $esperado = $valorUnit * $quantidade;
            $diferenca = abs($esperado - $valorTotal) / max($esperado, $valorTotal);
            if ($diferenca > 0.05) { // > 5% de diferença
                $score -= 15; // Inconsistência matemática
            }
        }

        return max(0, $score);
    }

    /**
     * Calcula flags de qualidade para itens
     */
    private function calcularFlagsQualidadeItem(array $dados): array
    {
        $flags = [];

        if (empty($dados['DS_ITEM'] ?? $dados['descricao'])) {
            $flags[] = 'sem_descricao';
        }

        $valorUnit = $this->normalizarValor($dados['VL_UNITARIO'] ?? $dados['valor_unitario'] ?? 0);
        if ($valorUnit <= 0) {
            $flags[] = 'sem_preco'; // FLAG CRÍTICA!
        }

        // Detecção de outliers
        if ($valorUnit > 0) {
            if ($valorUnit < 0.10) $flags[] = 'preco_muito_baixo';
            if ($valorUnit > 1000000) $flags[] = 'preco_muito_alto';
        }

        if (empty($dados['QT_ITEM'] ?? $dados['quantidade'])) {
            $flags[] = 'sem_quantidade';
        }

        if (empty($dados['DS_UNIDADE'] ?? $dados['unidade'])) {
            $flags[] = 'sem_unidade';
        }

        // Verificar coerência matemática
        $valorTotal = $this->normalizarValor($dados['VL_TOTAL_ITEM'] ?? $dados['valor_total'] ?? 0);
        $quantidade = $this->normalizarValor($dados['QT_ITEM'] ?? $dados['quantidade'] ?? 0);
        if ($valorUnit > 0 && $quantidade > 0 && $valorTotal > 0) {
            $esperado = $valorUnit * $quantidade;
            $diferenca = abs($esperado - $valorTotal) / max($esperado, $valorTotal);
            if ($diferenca > 0.05) {
                $flags[] = 'calculo_inconsistente';
            }
        }

        // Flags positivas
        $catmat = (isset($dados['CD_CATMAT']) && !empty($dados['CD_CATMAT'])) ||
                  (isset($dados['catmat']) && !empty($dados['catmat']));
        if ($catmat) {
            $flags[] = 'com_catmat';
        }

        $catser = (isset($dados['CD_CATSER']) && !empty($dados['CD_CATSER'])) ||
                  (isset($dados['catser']) && !empty($dados['catser']));
        if ($catser) {
            $flags[] = 'com_catser';
        }

        return $flags;
    }
}
