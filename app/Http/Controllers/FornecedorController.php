<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use App\Models\FornecedorItem;
use App\Models\ContratoPNCP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FornecedorController extends Controller
{
    /**
     * Lista todos os fornecedores (com busca opcional via AJAX)
     */
    public function index(Request $request)
    {
        try {
            // Se for requisição AJAX com parâmetro de busca
            if ($request->ajax() || $request->wantsJson()) {
                $termo = $request->get('busca');

                if ($termo) {
                    // Limpar termo (remover caracteres especiais para buscar CNPJ)
                    $termoLimpo = preg_replace('/\D/', '', $termo);

                    $query = Fornecedor::query();

                    // Se for número, buscar por CNPJ/CPF
                    if ($termoLimpo) {
                        $query->where('numero_documento', 'LIKE', "%{$termoLimpo}%");
                    } else {
                        // Buscar por razão social ou nome fantasia
                        $query->where(function($q) use ($termo) {
                            $q->where('razao_social', 'ILIKE', "%{$termo}%")
                              ->orWhere('nome_fantasia', 'ILIKE', "%{$termo}%");
                        });
                    }

                    $fornecedores = $query->orderBy('razao_social')->limit(100)->get();

                    // Adicionar número formatado
                    $fornecedores->each(function($fornecedor) {
                        $fornecedor->numero_documento_formatado = $fornecedor->numero_documento_formatado;
                    });

                    return response()->json([
                        'success' => true,
                        'fornecedores' => $fornecedores
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Termo de busca não informado'
                ], 400);
            }

            // Requisição normal (view)
            $fornecedores = Fornecedor::with('itens')
                ->orderBy('razao_social')
                ->get();

            Log::info('FornecedorController@index: Carregando view', [
                'total_fornecedores' => $fornecedores->count(),
                'fornecedores_ids' => $fornecedores->pluck('id')->toArray()
            ]);

            return view('fornecedores', compact('fornecedores'));

        } catch (\Exception $e) {
            Log::error('Erro ao listar fornecedores: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao buscar fornecedores: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Erro ao carregar fornecedores');
        }
    }

    /**
     * Salva novo fornecedor
     */
    public function store(Request $request)
    {
        try {
            // Log dos dados recebidos ANTES da validação
            Log::info('[FORNECEDOR STORE] Dados recebidos:', [
                'request_all' => $request->all(),
                'has_tipo_documento' => $request->has('tipo_documento'),
                'has_numero_documento' => $request->has('numero_documento'),
                'has_razao_social' => $request->has('razao_social'),
                'has_cep' => $request->has('cep'),
                'email_value' => $request->input('email'),
                'site_value' => $request->input('site'),
            ]);

            $validated = $request->validate([
                'tipo_documento' => 'required|in:CNPJ,CPF',
                'numero_documento' => 'required|string',
                'razao_social' => 'required|string|max:255',
                'nome_fantasia' => 'nullable|string|max:255',
                'inscricao_estadual' => 'nullable|string|max:50',
                'inscricao_municipal' => 'nullable|string|max:50',
                'telefone' => 'nullable|string|max:50',
                'celular' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'site' => 'nullable|url|max:255',
                'cep' => 'required|string|max:10',
                'logradouro' => 'required|string|max:255',
                'numero' => 'required|string|max:20',
                'complemento' => 'nullable|string|max:100',
                'bairro' => 'required|string|max:100',
                'cidade' => 'required|string|max:100',
                'uf' => 'required|string|size:2',
                'observacoes' => 'nullable|string',
            ], [
                'tipo_documento.required' => 'Tipo de documento é obrigatório',
                'numero_documento.required' => 'CNPJ/CPF é obrigatório',
                'razao_social.required' => 'Razão Social é obrigatória',
                'cep.required' => 'CEP é obrigatório',
                'logradouro.required' => 'Logradouro é obrigatório',
                'numero.required' => 'Número é obrigatório',
                'bairro.required' => 'Bairro é obrigatório',
                'cidade.required' => 'Cidade é obrigatória',
                'uf.required' => 'UF é obrigatória',
                'email.email' => 'E-mail inválido',
                'site.url' => 'Site deve ser uma URL válida',
            ]);

            Log::info('[FORNECEDOR STORE] Validação passou com sucesso');

            // Limpar CNPJ/CPF
            $validated['numero_documento'] = preg_replace('/\D/', '', $validated['numero_documento']);

            Log::info('[FORNECEDOR STORE] CNPJ/CPF limpo:', [
                'numero_documento' => $validated['numero_documento']
            ]);

            // Verificar se CNPJ/CPF já existe
            $existe = Fornecedor::where('numero_documento', $validated['numero_documento'])->first();

            if ($existe) {
                Log::warning('[FORNECEDOR STORE] CNPJ/CPF JÁ EXISTE!', [
                    'numero_documento_tentado' => $validated['numero_documento'],
                    'fornecedor_existente_id' => $existe->id,
                    'fornecedor_existente_razao_social' => $existe->razao_social
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'CNPJ/CPF já cadastrado!'
                ], 422);
            }

            Log::info('[FORNECEDOR STORE] CNPJ/CPF OK, não existe duplicata');

            // Adicionar user_id
            $validated['user_id'] = Auth::id();

            // Criar fornecedor
            $fornecedor = Fornecedor::create($validated);

            // Salvar itens do fornecedor (se houver)
            if ($request->has('itens') && is_array($request->itens)) {
                foreach ($request->itens as $itemData) {
                    $fornecedor->itens()->create([
                        'descricao' => $itemData['descricao'],
                        'codigo_catmat' => $itemData['codigo_catmat'] ?? null,
                        'unidade' => $itemData['unidade'],
                        'preco_referencia' => $itemData['preco_referencia'] ?? null,
                    ]);
                }
            }

            Log::info('Fornecedor cadastrado com sucesso', [
                'id' => $fornecedor->id,
                'itens_count' => count($request->itens ?? [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fornecedor cadastrado com sucesso!',
                'fornecedor' => $fornecedor
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log detalhado do erro de validação
            Log::error('[FORNECEDOR] ERRO DE VALIDAÇÃO:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            // Tratar erro de constraint unique (CNPJ/CPF duplicado)
            if ($e->getCode() === '23505' || strpos($e->getMessage(), 'cp_fornecedores_numero_documento_unique') !== false) {
                Log::warning('Tentativa de cadastrar CNPJ/CPF duplicado', [
                    'numero_documento' => $validated['numero_documento'] ?? 'N/A'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Este CNPJ/CPF já está cadastrado no sistema!'
                ], 422);
            }

            // Outros erros de banco
            Log::error('Erro de banco ao cadastrar fornecedor: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar no banco de dados. Verifique os dados e tente novamente.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Erro ao cadastrar fornecedor: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao cadastrar fornecedor. Por favor, tente novamente.',
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ]
            ], 500);
        }
    }

    /**
     * Atualiza fornecedor existente
     */
    public function update(Request $request, $id)
    {
        try {
            $fornecedor = Fornecedor::findOrFail($id);

            $validated = $request->validate([
                'tipo_documento' => 'required|in:CNPJ,CPF',
                'numero_documento' => 'required|string',
                'razao_social' => 'required|string|max:255',
                'nome_fantasia' => 'nullable|string|max:255',
                'inscricao_estadual' => 'nullable|string|max:50',
                'inscricao_municipal' => 'nullable|string|max:50',
                'telefone' => 'nullable|string|max:50',
                'celular' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:255',
                'site' => 'nullable|url|max:255',
                'cep' => 'required|string|max:10',
                'logradouro' => 'required|string|max:255',
                'numero' => 'required|string|max:20',
                'complemento' => 'nullable|string|max:100',
                'bairro' => 'required|string|max:100',
                'cidade' => 'required|string|max:100',
                'uf' => 'required|string|size:2',
                'observacoes' => 'nullable|string',
            ]);

            // Limpar CNPJ/CPF
            $validated['numero_documento'] = preg_replace('/\D/', '', $validated['numero_documento']);

            // Verificar se CNPJ/CPF já existe em outro fornecedor
            $existe = Fornecedor::where('numero_documento', $validated['numero_documento'])
                                ->where('id', '!=', $id)
                                ->first();
            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'CNPJ/CPF já cadastrado em outro fornecedor!'
                ], 422);
            }

            // Atualizar fornecedor
            $fornecedor->update($validated);

            // Atualizar itens do fornecedor
            // Remover todos os itens existentes e recriar (mais simples que tentar fazer merge)
            if ($request->has('itens')) {
                $fornecedor->itens()->delete();

                if (is_array($request->itens)) {
                    foreach ($request->itens as $itemData) {
                        $fornecedor->itens()->create([
                            'descricao' => $itemData['descricao'],
                            'codigo_catmat' => $itemData['codigo_catmat'] ?? null,
                            'unidade' => $itemData['unidade'],
                            'preco_referencia' => $itemData['preco_referencia'] ?? null,
                        ]);
                    }
                }
            }

            Log::info('Fornecedor atualizado com sucesso', [
                'id' => $fornecedor->id,
                'itens_count' => count($request->itens ?? [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fornecedor atualizado com sucesso!',
                'fornecedor' => $fornecedor
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log detalhado do erro de validação
            Log::error('[FORNECEDOR] ERRO DE VALIDAÇÃO:', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            // Tratar erro de constraint unique (CNPJ/CPF duplicado)
            if ($e->getCode() === '23505' || strpos($e->getMessage(), 'cp_fornecedores_numero_documento_unique') !== false) {
                Log::warning('Tentativa de atualizar para CNPJ/CPF duplicado', [
                    'id' => $id,
                    'numero_documento' => $validated['numero_documento'] ?? 'N/A'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Este CNPJ/CPF já está cadastrado em outro fornecedor!'
                ], 422);
            }

            // Outros erros de banco
            Log::error('Erro de banco ao atualizar fornecedor: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao salvar no banco de dados. Verifique os dados e tente novamente.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar fornecedor: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro inesperado ao atualizar fornecedor. Por favor, tente novamente.',
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ]
            ], 500);
        }
    }
    /**
     * Importa fornecedores de planilha Excel/CSV
     */
    public function importarPlanilha(Request $request)
    {
        try {
            Log::info('importarPlanilha: INÍCIO', [
                'has_file' => $request->hasFile('planilha'),
                'file_name' => $request->hasFile('planilha') ? $request->file('planilha')->getClientOriginalName() : 'N/A'
            ]);

            $request->validate([
                'planilha' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ]);

            $arquivo = $request->file('planilha');
            $spreadsheet = IOFactory::load($arquivo->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $linhas = $sheet->toArray();

            Log::info('importarPlanilha: Planilha carregada', [
                'total_linhas' => count($linhas)
            ]);

            if (count($linhas) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Planilha vazia ou sem dados!'
                ], 422);
            }

            // Detectar colunas automaticamente
            $colunas = $this->detectarColunasFornecedores($linhas);

            $importados = 0;
            $erros = [];

            DB::beginTransaction();

            foreach ($linhas as $index => $linha) {
                // Pular cabeçalho (primeira linha)
                if ($index === 0) continue;

                // Pular linhas vazias
                if (empty(array_filter($linha))) continue;

                try {
                    $dados = [
                        'tipo_documento' => 'CNPJ', // Default
                        'numero_documento' => $linha[$colunas['cnpj']] ?? '',
                        'razao_social' => $linha[$colunas['razao_social']] ?? '',
                        'nome_fantasia' => $linha[$colunas['nome_fantasia']] ?? null,
                        'telefone' => $linha[$colunas['telefone']] ?? null,
                        'email' => $linha[$colunas['email']] ?? null,
                        'cep' => $linha[$colunas['cep']] ?? '',
                        'logradouro' => $linha[$colunas['logradouro']] ?? '',
                        'numero' => ($colunas['numero'] !== null && isset($linha[$colunas['numero']]))
                            ? substr($linha[$colunas['numero']], 0, 20)
                            : 'S/N',
                        'bairro' => $linha[$colunas['bairro']] ?? '',
                        'cidade' => $linha[$colunas['cidade']] ?? '',
                        'uf' => $linha[$colunas['uf']] ?? '',
                        'user_id' => Auth::id(),
                    ];

                    // Limpar CNPJ
                    $dados['numero_documento'] = preg_replace('/\D/', '', $dados['numero_documento']);

                    Log::info("importarPlanilha: Processando linha " . ($index + 1), [
                        'dados_extraidos' => $dados,
                        'linha_raw' => $linha,
                        'colunas_mapeadas' => $colunas
                    ]);

                    // Validar campos obrigatórios (apenas CNPJ e Razão Social)
                    if (empty($dados['numero_documento']) || empty($dados['razao_social'])) {
                        $camposFaltando = [];
                        if (empty($dados['numero_documento'])) $camposFaltando[] = 'CNPJ/CPF';
                        if (empty($dados['razao_social'])) $camposFaltando[] = 'Razão Social';

                        $erros[] = "Linha " . ($index + 1) . ": Campos obrigatórios faltando: " . implode(', ', $camposFaltando);
                        continue;
                    }

                    // Verificar se já existe
                    $existe = Fornecedor::where('numero_documento', $dados['numero_documento'])->first();
                    if ($existe) {
                        $erros[] = "Linha " . ($index + 1) . ": CNPJ {$dados['numero_documento']} já cadastrado";
                        continue;
                    }

                    Fornecedor::create($dados);
                    $importados++;

                } catch (\Exception $e) {
                    $erros[] = "Linha " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            $mensagem = "{$importados} fornecedores importados com sucesso!";
            if (count($erros) > 0) {
                $mensagem .= " (" . count($erros) . " erros encontrados)";
            }

            Log::info('importarPlanilha: FINALIZADO COM SUCESSO', [
                'importados' => $importados,
                'erros_count' => count($erros),
                'erros' => $erros
            ]);

            return response()->json([
                'success' => true,
                'message' => $mensagem,
                'importados' => $importados,
                'erros' => $erros
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao importar planilha de fornecedores: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao importar planilha: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detecta automaticamente as colunas da planilha de fornecedores
     */
    private function detectarColunasFornecedores($linhas)
    {
        $cabecalho = array_map('strtolower', $linhas[0]);

        $colunas = [
            'cnpj' => 0,
            'razao_social' => 1,
            'nome_fantasia' => 2,
            'telefone' => 3,
            'email' => 4,
            'cep' => 5,
            'logradouro' => 6,
            'numero' => null,  // Não assumir default - será 'S/N' se não encontrar
            'bairro' => 8,
            'cidade' => 9,
            'uf' => 10,
        ];

        // Tentar detectar por cabeçalho
        foreach ($cabecalho as $index => $valor) {
            // CORREÇÃO 1: Remover asteriscos e espaços extras para melhorar detecção
            $valor = trim(str_replace('*', '', $valor));

            if (preg_match('/cnpj|documento/i', $valor)) $colunas['cnpj'] = $index;
            if (preg_match('/raz(a|ã)o\s*social|empresa/i', $valor)) $colunas['razao_social'] = $index;
            if (preg_match('/fantasia/i', $valor)) $colunas['nome_fantasia'] = $index;
            // CORREÇÃO 4: Adicionar detecção de "celular" além de "telefone" e "fone"
            if (preg_match('/telefone|fone|celular/i', $valor)) $colunas['telefone'] = $index;
            if (preg_match('/email|e-mail/i', $valor)) $colunas['email'] = $index;
            if (preg_match('/cep/i', $valor)) $colunas['cep'] = $index;
            if (preg_match('/logradouro|endere(c|ç)o|rua|avenida/i', $valor)) $colunas['logradouro'] = $index;
            // CORREÇÃO 3: Remover ^ do início para aceitar "número" em qualquer posição
            if (preg_match('/n(u|ú)mero|num\b|nº/i', $valor)) $colunas['numero'] = $index;
            if (preg_match('/bairro/i', $valor)) $colunas['bairro'] = $index;
            if (preg_match('/cidade|munic(i|í)pio/i', $valor)) $colunas['cidade'] = $index;
            // CORREÇÃO 2: Usar word boundary (\b) em vez de ^$ para aceitar "uf" com espaços
            if (preg_match('/\buf\b|estado/i', $valor)) $colunas['uf'] = $index;
        }

        return $colunas;
    }

    /**
     * Consulta dados de CNPJ em APIs da Receita Federal
     *
     * Usa múltiplas APIs com fallback para garantir disponibilidade:
     * 1. BrasilAPI (principal)
     * 2. ReceitaWS (fallback)
     *
     * @param string $cnpj
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultarCNPJ($cnpj)
    {
        try {
            // Limpar CNPJ (remover pontos, barras e traços)
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

            // Validar se tem 14 dígitos
            if (strlen($cnpjLimpo) !== 14) {
                return response()->json([
                    'success' => false,
                    'message' => 'CNPJ inválido. Deve conter 14 dígitos.'
                ], 400);
            }

            // Validar CNPJ (algoritmo oficial)
            if (!$this->validarCNPJ($cnpjLimpo)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CNPJ inválido. Verificação de dígitos falhou.'
                ], 400);
            }

            // Tentar BrasilAPI primeiro
            $dados = $this->consultarBrasilAPI($cnpjLimpo);

            // Se falhar, tentar ReceitaWS
            if (!$dados) {
                $dados = $this->consultarReceitaWS($cnpjLimpo);
            }

            // Se ainda falhar, retornar erro
            if (!$dados) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível consultar o CNPJ. Tente novamente em alguns instantes.'
                ], 503);
            }

            return response()->json([
                'success' => true,
                'data' => $dados
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao consultar CNPJ: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar CNPJ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta CNPJ na BrasilAPI
     */
    private function consultarBrasilAPI($cnpj)
    {
        try {
            $response = Http::timeout(10)->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'razao_social' => $data['razao_social'] ?? '',
                    'nome_fantasia' => $data['nome_fantasia'] ?? '',
                    'cnpj' => $data['cnpj'] ?? $cnpj,
                    'cnae_fiscal' => $data['cnae_fiscal'] ?? '',
                    'cnae_fiscal_descricao' => $data['cnae_fiscal_descricao'] ?? '',
                    'natureza_juridica' => $data['natureza_juridica'] ?? '',
                    'porte' => $this->mapearPorte($data['porte'] ?? ''),
                    'capital_social' => $data['capital_social'] ?? '',
                    'situacao_cadastral' => $data['descricao_situacao_cadastral'] ?? '',
                    'data_situacao_cadastral' => $data['data_situacao_cadastral'] ?? '',
                    'email' => $data['email'] ?? '',
                    'telefone' => $this->formatarTelefone($data['ddd_telefone_1'] ?? ''),
                    'cep' => $data['cep'] ?? '',
                    'logradouro' => $data['logradouro'] ?? '',
                    'numero' => $data['numero'] ?? '',
                    'complemento' => $data['complemento'] ?? '',
                    'bairro' => $data['bairro'] ?? '',
                    'municipio' => $data['municipio'] ?? '',
                    'uf' => $data['uf'] ?? '',
                    'fonte' => 'BrasilAPI'
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Erro ao consultar BrasilAPI: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Consulta CNPJ na ReceitaWS (fallback)
     */
    private function consultarReceitaWS($cnpj)
    {
        try {
            $response = Http::timeout(10)->get("https://www.receitaws.com.br/v1/cnpj/{$cnpj}");

            if ($response->successful()) {
                $data = $response->json();

                // ReceitaWS retorna status "ERROR" em caso de erro
                if (isset($data['status']) && $data['status'] === 'ERROR') {
                    return null;
                }

                return [
                    'razao_social' => $data['nome'] ?? '',
                    'nome_fantasia' => $data['fantasia'] ?? '',
                    'cnpj' => $data['cnpj'] ?? $cnpj,
                    'cnae_fiscal' => $data['atividade_principal'][0]['code'] ?? '',
                    'cnae_fiscal_descricao' => $data['atividade_principal'][0]['text'] ?? '',
                    'natureza_juridica' => $data['natureza_juridica'] ?? '',
                    'porte' => $this->mapearPorte($data['porte'] ?? ''),
                    'capital_social' => $data['capital_social'] ?? '',
                    'situacao_cadastral' => $data['situacao'] ?? '',
                    'data_situacao_cadastral' => $data['data_situacao'] ?? '',
                    'email' => $data['email'] ?? '',
                    'telefone' => $data['telefone'] ?? '',
                    'cep' => $data['cep'] ?? '',
                    'logradouro' => $data['logradouro'] ?? '',
                    'numero' => $data['numero'] ?? '',
                    'complemento' => $data['complemento'] ?? '',
                    'bairro' => $data['bairro'] ?? '',
                    'municipio' => $data['municipio'] ?? '',
                    'uf' => $data['uf'] ?? '',
                    'fonte' => 'ReceitaWS'
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Erro ao consultar ReceitaWS: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validação de CNPJ (algoritmo oficial da Receita Federal)
     */
    private function validarCNPJ($cnpj)
    {
        // Verifica se tem 14 dígitos
        if (strlen($cnpj) != 14) {
            return false;
        }

        // Verifica se todos os dígitos são iguais (ex: 11111111111111)
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        // Validação do primeiro dígito verificador
        $soma = 0;
        $multiplicadores = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $multiplicadores[$i];
        }

        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;

        if ($cnpj[12] != $digito1) {
            return false;
        }

        // Validação do segundo dígito verificador
        $soma = 0;
        $multiplicadores = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $multiplicadores[$i];
        }

        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        if ($cnpj[13] != $digito2) {
            return false;
        }

        return true;
    }

    /**
     * Mapeia código de porte para texto legível
     */
    private function mapearPorte($codigo)
    {
        $portes = [
            '01' => 'MEI',
            '03' => 'EPP',
            '05' => 'ME',
            '00' => 'Não informado',
            'MICRO EMPRESA' => 'ME',
            'EMPRESA DE PEQUENO PORTE' => 'EPP',
            'DEMAIS' => 'Grande',
        ];

        return $portes[$codigo] ?? $codigo;
    }

    /**
     * Formata telefone
     */
    private function formatarTelefone($telefone)
    {
        $telefone = preg_replace('/[^0-9]/', '', $telefone);

        if (strlen($telefone) === 10) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
        }

        if (strlen($telefone) === 11) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 1) . ' ' . substr($telefone, 3, 4) . '-' . substr($telefone, 7);
        }

        return $telefone;
    }

    /**
     * Busca fornecedor por ID (para edição)
     */
    public function show($id)
    {
        try {
            $fornecedor = Fornecedor::with('itens')->findOrFail($id);

            return response()->json([
                'success' => true,
                'fornecedor' => $fornecedor
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fornecedor não encontrado'
            ], 404);
        }
    }

    /**
     * Excluir fornecedor (hard delete - remove permanentemente)
     */
    public function destroy($id)
    {
        try {
            $fornecedor = Fornecedor::findOrFail($id);

            // Salvar informações para log antes de deletar
            $cnpj = $fornecedor->numero_documento;
            $razao = $fornecedor->razao_social;

            // Hard delete - remove permanentemente do banco
            $fornecedor->delete();

            Log::info("Fornecedor #{$id} excluído PERMANENTEMENTE", [
                'cnpj' => $cnpj,
                'razao_social' => $razao
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fornecedor excluído com sucesso!'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir fornecedor: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir fornecedor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gera e faz download de planilha modelo para importação de fornecedores
     */
    public function downloadModelo()
    {
        try {
            // Criar novo spreadsheet
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Definir cabeçalhos
            $colunas = [
                'A' => 'RAZÃO SOCIAL *',
                'B' => 'NOME FANTASIA',
                'C' => 'CNPJ/CPF *',
                'D' => 'INSCRIÇÃO ESTADUAL',
                'E' => 'INSCRIÇÃO MUNICIPAL',
                'F' => 'TELEFONE',
                'G' => 'CELULAR',
                'H' => 'EMAIL',
                'I' => 'SITE',
                'J' => 'CEP *',
                'K' => 'LOGRADOURO *',
                'L' => 'NÚMERO *',
                'M' => 'COMPLEMENTO',
                'N' => 'BAIRRO *',
                'O' => 'CIDADE *',
                'P' => 'UF *',
                'Q' => 'OBSERVAÇÕES'
            ];

            // Aplicar cabeçalhos com estilo
            foreach ($colunas as $coluna => $nome) {
                $sheet->setCellValue($coluna . '1', $nome);

                // Estilo do cabeçalho
                $sheet->getStyle($coluna . '1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '3B82F6']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Ajustar largura das colunas
                $sheet->getColumnDimension($coluna)->setAutoSize(true);
            }

            // Adicionar 3 linhas de exemplo
            $exemplos = [
                ['EMPRESA EXEMPLO LTDA', 'Exemplo Comércio', '12.345.678/0001-90', '123456789', '987654321', '(84) 3456-7890', '(84) 9 8765-4321', 'contato@exemplo.com', 'https://exemplo.com', '59000-000', 'Rua Exemplo', '123', 'Sala 01', 'Centro', 'Natal', 'RN', 'Fornecedor homologado'],
                ['JOÃO SILVA ME', 'Silva Distribuidora', '98.765.432/0001-10', '987654321', '123456789', '(84) 3222-3333', '(84) 9 1111-2222', 'joao@silva.com', '', '59010-000', 'Av. Principal', '456', '', 'Alecrim', 'Natal', 'RN', ''],
                ['MARIA SANTOS', '', '123.456.789-00', '', '', '(84) 3333-4444', '(84) 9 9999-8888', 'maria@email.com', '', '59020-000', 'Rua das Flores', '789', 'Apt 202', 'Petrópolis', 'Natal', 'RN', 'Atende delivery']
            ];

            $linha = 2;
            foreach ($exemplos as $exemplo) {
                $col = 'A';
                foreach ($exemplo as $valor) {
                    $sheet->setCellValue($col . $linha, $valor);
                    $col++;
                }
                $linha++;
            }

            // Criar Writer para XLSX
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            // Nome do arquivo
            $nomeArquivo = 'modelo_fornecedores_' . date('Y-m-d') . '.xlsx';

            // Headers para download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $nomeArquivo . '"');
            header('Cache-Control: max-age=0');

            // Salvar no output
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            Log::error('Erro ao gerar planilha modelo: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar planilha modelo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar por código CATMAT (busca local + API externa)
     * Usado na Pesquisa Rápida (aba CATMAT/CATSER)
     */
    public function buscarPorCodigo(Request $request)
    {
        try {
            $codigo = $request->get('codigo');
            $descricao = $request->get('descricao');

            // Validar entrada
            if (empty($codigo) && empty($descricao)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Digite um código CATMAT ou descrição para buscar.'
                ], 400);
            }

            $resultados = [
                'fornecedores_locais' => [],
                'api_externa' => null
            ];

            // ========================================
            // PARTE 1: BUSCA LOCAL (Fornecedores cadastrados)
            // ========================================
            if ($codigo) {
                $fornecedoresLocais = Fornecedor::whereHas('itens', function ($query) use ($codigo, $descricao) {
                    $query->where('codigo_catmat', 'ILIKE', "%{$codigo}%");
                    if ($descricao) {
                        $query->orWhere('descricao', 'ILIKE', "%{$descricao}%");
                    }
                })
                ->with(['itens' => function ($query) use ($codigo, $descricao) {
                    $query->where('codigo_catmat', 'ILIKE', "%{$codigo}%");
                    if ($descricao) {
                        $query->orWhere('descricao', 'ILIKE', "%{$descricao}%");
                    }
                }])
                ->orderBy('razao_social')
                ->get();

                $resultados['fornecedores_locais'] = $fornecedoresLocais;
            }

            // ========================================
            // PARTE 2: BUSCA NA API EXTERNA (ComprasNet/Gov.br)
            // ========================================
            // TODO: Será implementado quando o usuário passar a URL da API
            $resultados['api_externa'] = [
                'disponivel' => false,
                'mensagem' => 'Integração com API externa em desenvolvimento'
            ];

            Log::info('Busca por código CATMAT', [
                'codigo' => $codigo,
                'descricao' => $descricao,
                'fornecedores_encontrados' => $resultados['fornecedores_locais']->count()
            ]);

            return response()->json([
                'success' => true,
                'resultados' => $resultados,
                'total_fornecedores' => $resultados['fornecedores_locais']->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar por código CATMAT: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar fornecedores que fornecem um determinado item/serviço
     * Usado no Mapa de Fornecedores
     *
     * SE descricao vazio/null: Retorna TODOS os fornecedores (para modal CDF)
     * SE descricao fornecido: Filtra por itens que matcham a descrição
     */
    public function buscarPorItem(Request $request)
    {
        try {
            $descricao = $request->get('descricao');

            // ✅ NOVO: Se não houver descrição, retornar TODOS os fornecedores
            if (!$descricao || strlen(trim($descricao)) == 0) {
                $fornecedores = Fornecedor::with('itens')
                    ->orderBy('razao_social')
                    ->limit(100) // Limitar a 100 para não sobrecarregar
                    ->get();

                Log::info('Listagem de TODOS os fornecedores (sem filtro)', [
                    'fornecedores_encontrados' => $fornecedores->count()
                ]);

                // Formatar dados para compatibilidade com o frontend
                $fornecedoresFormatados = $fornecedores->map(function($fornecedor) {
                    return [
                        'cnpj' => $fornecedor->numero_documento_formatado,
                        'razao_social' => $fornecedor->razao_social,
                        'nome_fantasia' => $fornecedor->nome_fantasia,
                        'telefone' => $fornecedor->telefone,
                        'email' => $fornecedor->email,
                        'origem' => 'LOCAL'
                    ];
                });

                return response()->json([
                    'success' => true,
                    'fornecedores' => $fornecedoresFormatados,
                    'total' => $fornecedoresFormatados->count()
                ]);
            }

            // Validar comprimento mínimo APENAS se houver descrição
            if (strlen(trim($descricao)) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Digite pelo menos 3 caracteres para buscar.'
                ], 400);
            }

            // PASSO 1: Buscar fornecedores locais
            $fornecedores = Fornecedor::whereHas('itens', function ($query) use ($descricao) {
                $query->where('descricao', 'ILIKE', "%{$descricao}%")
                      ->orWhere('codigo_catmat', 'ILIKE', "%{$descricao}%");
            })
            ->with(['itens' => function ($query) use ($descricao) {
                // Carregar apenas os itens que matcham a busca
                $query->where('descricao', 'ILIKE', "%{$descricao}%")
                      ->orWhere('codigo_catmat', 'ILIKE', "%{$descricao}%");
            }])
            ->orderBy('razao_social')
            ->get();

            Log::info('Busca por item no Mapa de Fornecedores (LOCAL)', [
                'termo' => $descricao,
                'fornecedores_locais' => $fornecedores->count()
            ]);

            // Formatar dados locais
            $fornecedoresFormatados = $fornecedores->map(function($fornecedor) {
                return [
                    'cnpj' => $fornecedor->numero_documento_formatado,
                    'razao_social' => $fornecedor->razao_social,
                    'nome_fantasia' => $fornecedor->nome_fantasia,
                    'telefone' => $fornecedor->telefone,
                    'email' => $fornecedor->email,
                    'origem' => 'LOCAL'
                ];
            })->toArray();

            // PASSO 2: Buscar no CATMAT + API de Preços Compras.gov
            try {
                Log::info('Busca por item no Mapa de Fornecedores (CATMAT+API)', ['termo' => $descricao]);

                $fornecedoresExternos = $this->buscarFornecedoresCATMAT($descricao);

                if (!empty($fornecedoresExternos)) {
                    Log::info('Fornecedores encontrados no CATMAT+API', ['total' => count($fornecedoresExternos)]);
                    $fornecedoresFormatados = array_merge($fornecedoresFormatados, $fornecedoresExternos);
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar fornecedores no CATMAT+API (não impacta busca local)', [
                    'erro' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'fornecedores' => $fornecedoresFormatados,
                'total' => count($fornecedoresFormatados)
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar fornecedores por item: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar fornecedores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar fornecedores via CATMAT Local + API de Preços Compras.gov
     */
    private function buscarFornecedoresCATMAT($termo)
    {
        $fornecedores = [];

        try {
            Log::info('[COMPRAS.GOV LOCAL Mapa] Buscando fornecedores localmente', ['termo' => $termo]);

            // Buscar DIRETAMENTE na tabela local de preços (cp_precos_comprasgov)
            $precos = DB::connection('pgsql_main')
                ->table('cp_precos_comprasgov')
                ->select(
                    'catmat_codigo',
                    'descricao_item',
                    'preco_unitario',
                    'unidade_fornecimento',
                    'fornecedor_nome',
                    'fornecedor_cnpj',
                    'municipio',
                    'uf',
                    'orgao_nome',
                    'data_compra'
                )
                ->whereRaw(
                    "to_tsvector('portuguese', descricao_item) @@ plainto_tsquery('portuguese', ?)",
                    [$termo]
                )
                ->where('preco_unitario', '>', 0)
                ->whereNotNull('fornecedor_cnpj')
                ->orderBy('data_compra', 'desc')
                ->limit(1000) // ✅ 31/10/2025: Aumentado de 200→1000
                ->get();

            if ($precos->isEmpty()) {
                Log::info('[COMPRAS.GOV LOCAL Mapa] Nenhum fornecedor encontrado');
                return [];
            }

            Log::info('[COMPRAS.GOV LOCAL Mapa] Preços encontrados', ['total' => $precos->count()]);

            // Processar cada preço praticado
            foreach ($precos as $preco) {
                $cnpj = preg_replace('/\D/', '', $preco->fornecedor_cnpj ?? '');

                if (!$cnpj || strlen($cnpj) != 14) continue;

                if (!isset($fornecedores[$cnpj])) {
                    $fornecedores[$cnpj] = [
                        'cnpj' => $this->formatarCNPJ($cnpj),
                        'razao_social' => $preco->fornecedor_nome ?? 'Não informado',
                        'nome_fantasia' => null,
                        'telefone' => null,
                        'email' => null,
                        'logradouro' => null,
                        'numero' => null,
                        'bairro' => null,
                        'cidade' => $preco->municipio,
                        'uf' => $preco->uf,
                        'cep' => null,
                        'origem' => 'COMPRAS.GOV',
                        'produtos' => []
                    ];
                }

                // Adicionar produto fornecido
                $fornecedores[$cnpj]['produtos'][] = [
                    'descricao' => $preco->descricao_item,
                    'valor' => floatval($preco->preco_unitario),
                    'unidade' => $preco->unidade_fornecimento ?? 'UN',
                    'data' => $preco->data_compra,
                    'orgao' => $preco->orgao_nome ?? 'N/A',
                    'catmat' => $preco->catmat_codigo
                ];

                // ✅ 31/10/2025: Aumentado de 50→200
                if (count($fornecedores) >= 200) {
                    break;
                }
            }

            Log::info('[COMPRAS.GOV LOCAL Mapa] Busca finalizada', ['fornecedores' => count($fornecedores)]);

        } catch (\Exception $e) {
            Log::error('[COMPRAS.GOV LOCAL Mapa] Erro geral', ['erro' => $e->getMessage()]);
        }

        // Retornar array ASSOCIATIVO indexado por CNPJ (sem formatação)
        return $fornecedores;
    }

    /**
     * Sugerir fornecedores da base pública (PNCP)
     * Ordenação: CATMAT > termo > ocorrências
     *
     * GET /api/fornecedores/sugerir?catmat=123456&termo=papel&limite=50
     */
    public function sugerir(Request $request)
    {
        try {
            $catmat = $request->input('catmat');
            $termo = $request->input('termo');
            $limite = $request->input('limite', 50);

            if (!$catmat && !$termo) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Informe ao menos um parâmetro: catmat ou termo'
                ], 400);
            }

            $query = Fornecedor::where('origem', 'pncp')
                ->where('status', '!=', 'oculto');

            // Prioridade 1: Match por CATMAT
            if ($catmat) {
                $query->where(function($q) use ($catmat) {
                    $q->whereJsonContains('tags_segmento', $catmat)
                      ->orWhereRaw("tags_segmento::text LIKE ?", ["%{$catmat}%"]);
                });
            }

            // Prioridade 2: Match por termo (razão social ou tags)
            if ($termo) {
                $query->where(function($q) use ($termo) {
                    $q->where('razao_social', 'ILIKE', "%{$termo}%")
                      ->orWhereRaw("tags_segmento::text ILIKE ?", ["%{$termo}%"]);
                });
            }

            // Ordenação: CATMAT > termo > ocorrências
            $fornecedores = $query->orderBy('ocorrencias', 'desc')
                ->orderBy('razao_social', 'asc')
                ->limit($limite)
                ->get();

            // Formatar response
            $resultado = $fornecedores->map(function($fornecedor) {
                return [
                    'id' => $fornecedor->id,
                    'cnpj' => $fornecedor->numero_documento,
                    'cnpj_formatado' => $fornecedor->numero_documento_formatado,
                    'razao_social' => $fornecedor->razao_social,
                    'tags' => $fornecedor->tags_segmento ?? [],
                    'ocorrencias' => $fornecedor->ocorrencias,
                    'status' => $fornecedor->status,
                    'fonte' => $fornecedor->origem,
                    'fonte_url' => $fornecedor->fonte_url,
                    'ultima_atualizacao' => $fornecedor->ultima_atualizacao?->format('d/m/Y H:i'),
                ];
            });

            return response()->json([
                'sucesso' => true,
                'total' => $resultado->count(),
                'fornecedores' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao sugerir fornecedores: ' . $e->getMessage());

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar sugestões: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar base de fornecedores PNCP manualmente (admin)
     *
     * POST /api/fornecedores/atualizar-pncp
     */
    public function atualizarPNCP(Request $request)
    {
        try {
            // Executar comando em background
            Artisan::call('fornecedores:popular-pncp', [
                '--meses' => $request->input('meses', 6)
            ]);

            $output = Artisan::output();

            return response()->json([
                'sucesso' => true,
                'mensagem' => 'Atualização iniciada com sucesso',
                'detalhes' => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao executar atualização: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar fornecedores no banco PNCP local
     *
     * GET /api/fornecedores/buscar-pncp?termo=caneta
     */
    public function buscarPNCP(Request $request)
    {
        try {
            $termo = $request->input('termo');

            if (!$termo || strlen($termo) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Termo de busca deve ter pelo menos 3 caracteres'
                ], 400);
            }

            Log::info('[FornecedorController] Buscando fornecedores PNCP', [
                'termo' => $termo
            ]);

            // ✅ CORRIGIDO: Buscar direto na API do PNCP (banco local está vazio)
            // Buscar 3 páginas (cada página tem ~20 contratos) = ~60 contratos
            $contratos = $this->buscarPNCPTempoReal($termo, 3);

            // ✅ CORRIGIDO: Agrupar por CNPJ do FORNECEDOR (não do órgão!)
            $fornecedoresAgrupados = [];

            foreach ($contratos as $contrato) {
                // Agora $contrato é array (da API), não Eloquent Model
                $cnpj = $contrato['fornecedor_cnpj'] ?? null;

                if (!$cnpj) continue;

                if (!isset($fornecedoresAgrupados[$cnpj])) {
                    $fornecedoresAgrupados[$cnpj] = [
                        'cnpj' => $this->formatarCNPJ($cnpj),
                        'razao_social' => $contrato['fornecedor_razao_social'] ?? 'Fornecedor não identificado',
                        'nome_fantasia' => null,
                        'telefone' => null,
                        'email' => null,
                        'logradouro' => null,
                        'numero' => null,
                        'complemento' => null,
                        'bairro' => null,
                        'municipio' => $contrato['orgao_municipio'] ?? null,
                        'uf' => $contrato['orgao_uf'] ?? null,
                        'cep' => null,
                        'total_contratos' => 0,
                        'produtos' => []
                    ];
                }

                $fornecedoresAgrupados[$cnpj]['total_contratos']++;
                $fornecedoresAgrupados[$cnpj]['produtos'][] = [
                    'descricao' => $contrato['objeto_contrato'] ?? 'N/A',
                    'valor_unitario' => $contrato['valor_global'] ?? 0,
                    'unidade_medida' => 'UN',
                    'data_contrato' => $contrato['data_publicacao'] ?? null,
                    'orgao' => $contrato['orgao_razao_social'] ?? 'N/A'
                ];
            }

            // Converter para array e limitar produtos por fornecedor
            $fornecedores = array_values($fornecedoresAgrupados);

            foreach ($fornecedores as &$fornecedor) {
                // Limitar a 10 produtos por fornecedor
                $fornecedor['produtos'] = array_slice($fornecedor['produtos'], 0, 10);
            }

            return response()->json([
                'success' => true,
                'total' => count($fornecedores),
                'fornecedores' => $fornecedores,
                'termo_buscado' => $termo, // DEBUG: verificar se termo está sendo recebido
                'timestamp' => now()->toDateTimeString() // DEBUG: verificar se está usando cache
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error('[FornecedorController] Erro ao buscar fornecedores PNCP', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar fornecedores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * MAPA DE FORNECEDORES: Buscar fornecedores por produto/CATMAT/CNPJ
     * Similar à Pesquisa Rápida, mas retorna FORNECEDORES ao invés de contratos
     */
    public function buscarPorProduto(Request $request)
    {
        try {
            $termo = $request->input('termo');

            if (!$termo || strlen($termo) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Digite pelo menos 3 caracteres para buscar'
                ], 400);
            }

            Log::info('[MapaFornecedores] Buscando fornecedores por produto/CNPJ/nome', [
                'termo' => $termo
            ]);

            // Detectar tipo de busca
            $termoLimpo = preg_replace('/\D/', '', $termo);
            $isCNPJ = strlen($termoLimpo) == 14;

            $fornecedores = [];

            if ($isCNPJ) {
                // BUSCA POR CNPJ (todas as fontes)
                Log::info('[MapaFornecedores] Busca por CNPJ', ['cnpj' => $termoLimpo]);
                $fornecedores = $this->buscarPorCNPJAmplo($termoLimpo);
            } else {
                // BUSCA POR PRODUTO OU NOME (ampla)
                Log::info('[MapaFornecedores] Busca ampla', ['termo' => $termo]);
                $fornecedores = $this->buscarAmplo($termo);
            }

            Log::info('[MapaFornecedores] Total de fornecedores encontrados', [
                'total' => count($fornecedores)
            ]);

            // Sanitizar UTF-8 para evitar erro "Malformed UTF-8 characters"
            $fornecedoresSanitizados = $this->sanitizeUTF8(array_values($fornecedores));

            return response()->json([
                'success' => true,
                'fornecedores' => $fornecedoresSanitizados,
                'total' => count($fornecedores),
                'termo_buscado' => $termo
            ]);

        } catch (\Exception $e) {
            Log::error('[MapaFornecedores] Erro ao buscar fornecedores', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar fornecedores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * BUSCA PROGRESSIVA: Retorna fornecedores conforme vão sendo encontrados
     * Envia resultados em chunks via streaming response
     */
    public function buscarPorProdutoProgressivo(Request $request)
    {
        $termo = $request->input('termo');

        if (!$termo || strlen($termo) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Digite pelo menos 3 caracteres para buscar'
            ], 400);
        }

        return response()->stream(function () use ($termo) {
            $fornecedores = [];
            $totalEnviado = 0;
            $maxFornecedores = 500;

            // Helper para enviar chunk de fornecedores
            $enviarChunk = function($fornecedoresChunk, $fonte) use (&$totalEnviado, $maxFornecedores) {
                if ($totalEnviado >= $maxFornecedores) {
                    return false; // Parar se atingiu o limite
                }

                $chunk = array_slice($fornecedoresChunk, 0, $maxFornecedores - $totalEnviado);
                if (count($chunk) > 0) {
                    echo 'data: ' . json_encode([
                        'fornecedores' => array_values($chunk),
                        'fonte' => $fonte,
                        'total_parcial' => count($chunk)
                    ]) . "\n\n";
                    ob_flush();
                    flush();
                    $totalEnviado += count($chunk);
                }
                return true; // Continuar
            };

            // 1. CMED - Mais rápido, enviar primeiro
            try {
                Log::info('[Streaming] Buscando CMED...');
                $fornecedoresCMED = \App\Models\MedicamentoCmed::formatarParaMapaFornecedores($termo, 500);
                if (!$enviarChunk($fornecedoresCMED, 'CMED')) return;
                foreach ($fornecedoresCMED as $forn) {
                    $fornecedores[$forn['cnpj'] ?? uniqid()] = $forn;
                }
            } catch (\Exception $e) {
                Log::warning('[Streaming] Erro no CMED', ['erro' => $e->getMessage()]);
            }

            // 2. LOCAL - Busca local rápida
            try {
                Log::info('[Streaming] Buscando LOCAL...');
                $locais = Fornecedor::where(function($query) use ($termo) {
                    $query->where('razao_social', 'ILIKE', "%{$termo}%")
                          ->orWhere('nome_fantasia', 'ILIKE', "%{$termo}%");
                })->with('itens')->limit(100)->get();

                $fornecedoresLocal = [];
                foreach ($locais as $fornecedor) {
                    $cnpj = $fornecedor->numero_documento ?? 'LOCAL_' . $fornecedor->id;
                    if (!isset($fornecedores[$cnpj])) {
                        $fornecedoresLocal[] = [
                            'cnpj' => $this->formatarCNPJ($fornecedor->numero_documento),
                            'razao_social' => $fornecedor->razao_social,
                            'nome_fantasia' => $fornecedor->nome_fantasia,
                            'telefone' => $fornecedor->telefone ?? $fornecedor->celular,
                            'email' => $fornecedor->email,
                            'cidade' => $fornecedor->cidade,
                            'uf' => $fornecedor->uf,
                            'origem' => 'LOCAL',
                            'produtos' => []
                        ];
                    }
                }

                if (!$enviarChunk($fornecedoresLocal, 'LOCAL')) return;
                foreach ($fornecedoresLocal as $forn) {
                    $fornecedores[$forn['cnpj'] ?? uniqid()] = $forn;
                }
            } catch (\Exception $e) {
                Log::warning('[Streaming] Erro no LOCAL', ['erro' => $e->getMessage()]);
            }

            // 3. COMPRAS.GOV - CATMAT + API de Preços
            if ($totalEnviado < $maxFornecedores) {
                try {
                    Log::info('[Streaming] Buscando COMPRAS.GOV...');
                    $fornecedoresComprasGov = $this->buscarFornecedoresCATMAT($termo);

                    $novos = [];
                    foreach ($fornecedoresComprasGov as $forn) {
                        $cnpj = $forn['cnpj'] ?? uniqid();
                        if (!isset($fornecedores[$cnpj])) {
                            $novos[] = $forn;
                            $fornecedores[$cnpj] = $forn;
                        }
                    }

                    if (!$enviarChunk($novos, 'COMPRAS.GOV')) return;
                } catch (\Exception $e) {
                    Log::warning('[Streaming] Erro no COMPRAS.GOV', ['erro' => $e->getMessage()]);
                }
            }

            // 4. PNCP - Mais demorado, enviar por último
            if ($totalEnviado < $maxFornecedores) {
                try {
                    Log::info('[Streaming] Buscando PNCP...', ['ja_encontrados' => $totalEnviado]);
                    $contratosPNCP = $this->buscarPNCPTempoReal($termo, 1);

                    $fornecedoresPNCP = [];
                    foreach ($contratosPNCP as $contrato) {
                        $cnpj = $contrato['contratada']['cnpj'] ?? null;
                        if ($cnpj && !isset($fornecedores[$cnpj])) {
                            if (!isset($fornecedoresPNCP[$cnpj])) {
                                $fornecedoresPNCP[$cnpj] = [
                                    'cnpj' => $cnpj,
                                    'razao_social' => $contrato['contratada']['nome'],
                                    'origem' => 'PNCP',
                                    'produtos' => []
                                ];
                            }
                        }
                    }

                    if (!$enviarChunk(array_values($fornecedoresPNCP), 'PNCP')) return;
                } catch (\Exception $e) {
                    Log::warning('[Streaming] Erro no PNCP', ['erro' => $e->getMessage()]);
                }
            } else {
                Log::info('[Streaming] PNCP pulado - já encontrados suficientes', [
                    'total' => $totalEnviado,
                    'motivo' => $totalEnviado >= 50 ? 'Mais de 50 resultados' : 'Limite atingido'
                ]);
            }

            // Enviar sinal de conclusão
            echo 'data: ' . json_encode(['done' => true, 'total' => $totalEnviado]) . "\n\n";
            ob_flush();
            flush();

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Busca AMPLA por CNPJ (todas as fontes possíveis)
     */
    private function buscarPorCNPJAmplo($cnpj)
    {
        $fornecedores = [];

        // 1. Buscar no banco LOCAL
        $fornecedorLocal = Fornecedor::where('numero_documento', $cnpj)
            ->with('itens')
            ->first();

        if ($fornecedorLocal) {
            $fornecedores[$cnpj] = [
                'cnpj' => $this->formatarCNPJ($cnpj),
                'razao_social' => $fornecedorLocal->razao_social,
                'nome_fantasia' => $fornecedorLocal->nome_fantasia,
                'telefone' => $fornecedorLocal->telefone ?? $fornecedorLocal->celular,
                'email' => $fornecedorLocal->email,
                'logradouro' => $fornecedorLocal->logradouro,
                'numero' => $fornecedorLocal->numero,
                'bairro' => $fornecedorLocal->bairro,
                'cidade' => $fornecedorLocal->cidade,
                'uf' => $fornecedorLocal->uf,
                'cep' => $fornecedorLocal->cep,
                'origem' => 'LOCAL' . ($fornecedorLocal->origem == 'CDF' ? ' (CDF)' : ''),
                'produtos' => []
            ];

            // Adicionar produtos do fornecedor local
            foreach ($fornecedorLocal->itens as $item) {
                $fornecedores[$cnpj]['produtos'][] = [
                    'descricao' => $item->descricao,
                    'valor' => $item->preco_referencia,
                    'unidade' => $item->unidade,
                    'catmat' => $item->codigo_catmat
                ];
            }
        }

        // 2. Buscar nos contratos PNCP LOCAL
        $contratos = ContratoPNCP::where('fornecedor_cnpj', $cnpj)->limit(100)->get();

        if ($contratos->isNotEmpty()) {
            if (!isset($fornecedores[$cnpj])) {
                $primeiroContrato = $contratos->first();
                $fornecedores[$cnpj] = [
                    'cnpj' => $this->formatarCNPJ($cnpj),
                    'razao_social' => $primeiroContrato->fornecedor_razao_social ?? 'Não informado',
                    'nome_fantasia' => null,
                    'telefone' => null,
                    'email' => null,
                    'logradouro' => null,
                    'numero' => null,
                    'bairro' => null,
                    'cidade' => null,
                    'uf' => null,
                    'cep' => null,
                    'origem' => 'PNCP Local',
                    'produtos' => []
                ];
            }

            // Adicionar produtos dos contratos
            foreach ($contratos as $contrato) {
                $fornecedores[$cnpj]['produtos'][] = [
                    'descricao' => $contrato->objeto_contrato,
                    'valor' => $contrato->valor_unitario_estimado ?? $contrato->valor_global,
                    'unidade' => $contrato->unidade_medida,
                    'data' => $contrato->data_publicacao_pncp?->format('Y-m-d'),
                    'orgao' => $contrato->orgao_razao_social
                ];
            }
        }

        // 3. Consultar RECEITA FEDERAL para enriquecer dados
        if (!isset($fornecedores[$cnpj]) || !$fornecedores[$cnpj]['uf']) {
            try {
                $cnpjService = app(\App\Services\CnpjService::class);
                $dadosCnpj = $cnpjService->consultar($cnpj);

                if ($dadosCnpj['success']) {
                    if (!isset($fornecedores[$cnpj])) {
                        $fornecedores[$cnpj] = [
                            'cnpj' => $this->formatarCNPJ($cnpj),
                            'razao_social' => $dadosCnpj['razao_social'],
                            'nome_fantasia' => $dadosCnpj['nome_fantasia'] ?? null,
                            'telefone' => $dadosCnpj['telefone'] ?? null,
                            'email' => $dadosCnpj['email'] ?? null,
                            'logradouro' => $dadosCnpj['logradouro'] ?? null,
                            'numero' => $dadosCnpj['numero'] ?? null,
                            'bairro' => $dadosCnpj['bairro'] ?? null,
                            'cidade' => $dadosCnpj['municipio'] ?? null,
                            'uf' => $dadosCnpj['uf'] ?? null,
                            'cep' => $dadosCnpj['cep'] ?? null,
                            'origem' => 'Receita Federal',
                            'produtos' => []
                        ];
                    } else {
                        // Enriquecer dados existentes
                        $fornecedores[$cnpj]['uf'] = $fornecedores[$cnpj]['uf'] ?? $dadosCnpj['uf'];
                        $fornecedores[$cnpj]['cidade'] = $fornecedores[$cnpj]['cidade'] ?? $dadosCnpj['municipio'];
                        $fornecedores[$cnpj]['telefone'] = $fornecedores[$cnpj]['telefone'] ?? $dadosCnpj['telefone'];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('[MapaFornecedores] Erro ao consultar Receita Federal', [
                    'cnpj' => $cnpj,
                    'erro' => $e->getMessage()
                ]);
            }
        }

        return $fornecedores;
    }

    /**
     * Busca AMPLA por produto ou nome (múltiplas fontes)
     */
    private function buscarAmplo($termo)
    {
        Log::info('🔍 [buscarAmplo] INICIANDO BUSCA AMPLA', ['termo' => $termo]);
        $fornecedores = [];

        // 1. CMED - Base ANVISA de Medicamentos (PRIORIDADE para medicamentos)
        try {
            Log::info('[Mapa Fornecedores] Buscando no CMED...', ['termo' => $termo]);
            $fornecedoresCMED = \App\Models\MedicamentoCmed::formatarParaMapaFornecedores($termo, 500);

            foreach ($fornecedoresCMED as $fornecedor) {
                $cnpj = $fornecedor['cnpj'] ?? 'CMED_' . uniqid();

                if (!isset($fornecedores[$cnpj])) {
                    $fornecedores[$cnpj] = $fornecedor;
                } else {
                    // Mesclar produtos se o fornecedor já existe
                    $fornecedores[$cnpj]['produtos'] = array_merge(
                        $fornecedores[$cnpj]['produtos'],
                        $fornecedor['produtos']
                    );
                    // Atualizar origem para mostrar que aparece em múltiplas fontes
                    if (strpos($fornecedores[$cnpj]['origem'], 'CMED') === false) {
                        $fornecedores[$cnpj]['origem'] .= ' + CMED';
                    }
                }
            }

            Log::info('[Mapa Fornecedores] Medicamentos CMED adicionados', [
                'termo' => $termo,
                'total_cmed' => count($fornecedoresCMED)
            ]);
        } catch (\Exception $e) {
            Log::warning('[Mapa Fornecedores] Erro ao buscar no CMED', ['erro' => $e->getMessage()]);
        }

        // 2. Buscar fornecedores LOCAIS que fornecem o produto
        $fornecedoresLocais = Fornecedor::whereHas('itens', function($q) use ($termo) {
            $q->where('descricao', 'ILIKE', "%{$termo}%");
        })->with('itens')->limit(500)->get();

        foreach ($fornecedoresLocais as $forn) {
            $cnpj = $forn->numero_documento;
            $fornecedores[$cnpj] = [
                'cnpj' => $this->formatarCNPJ($cnpj),
                'razao_social' => $forn->razao_social,
                'nome_fantasia' => $forn->nome_fantasia,
                'telefone' => $forn->telefone ?? $forn->celular,
                'email' => $forn->email,
                'logradouro' => $forn->logradouro,
                'numero' => $forn->numero,
                'bairro' => $forn->bairro,
                'cidade' => $forn->cidade,
                'uf' => $forn->uf,
                'cep' => $forn->cep,
                'origem' => 'LOCAL' . ($forn->origem == 'CDF' ? ' (CDF)' : ''),
                'produtos' => []
            ];

            // Adicionar produtos que correspondem ao termo
            foreach ($forn->itens->where('descricao', 'ILIKE', "%{$termo}%") as $item) {
                $fornecedores[$cnpj]['produtos'][] = [
                    'descricao' => $item->descricao,
                    'valor' => $item->preco_referencia,
                    'unidade' => $item->unidade,
                    'catmat' => $item->codigo_catmat
                ];
            }
        }

        // 2. Buscar fornecedores por NOME (razão social ou nome fantasia)
        $fornecedoresPorNome = Fornecedor::where(function($q) use ($termo) {
            $q->where('razao_social', 'ILIKE', "%{$termo}%")
              ->orWhere('nome_fantasia', 'ILIKE', "%{$termo}%");
        })->with('itens')->limit(500)->get();

        foreach ($fornecedoresPorNome as $forn) {
            $cnpj = $forn->numero_documento;
            if (!isset($fornecedores[$cnpj])) {
                $fornecedores[$cnpj] = [
                    'cnpj' => $this->formatarCNPJ($cnpj),
                    'razao_social' => $forn->razao_social,
                    'nome_fantasia' => $forn->nome_fantasia,
                    'telefone' => $forn->telefone ?? $forn->celular,
                    'email' => $forn->email,
                    'logradouro' => $forn->logradouro,
                    'numero' => $forn->numero,
                    'bairro' => $forn->bairro,
                    'cidade' => $forn->cidade,
                    'uf' => $forn->uf,
                    'cep' => $forn->cep,
                    'origem' => 'LOCAL (Nome)',
                    'produtos' => []
                ];

                // Adicionar todos os produtos deste fornecedor
                foreach ($forn->itens as $item) {
                    $fornecedores[$cnpj]['produtos'][] = [
                        'descricao' => $item->descricao,
                        'valor' => $item->preco_referencia,
                        'unidade' => $item->unidade,
                        'catmat' => $item->codigo_catmat
                    ];
                }
            }
        }

        // 3. Buscar fornecedores no COMPRAS.GOV LOCAL (PRIORIDADE - base local)
        try {
            $fornecedoresComprasGov = $this->buscarFornecedoresCATMAT($termo);

            foreach ($fornecedoresComprasGov as $cnpj => $fornecedor) {
                if (!isset($fornecedores[$cnpj])) {
                    $fornecedores[$cnpj] = $fornecedor;
                } else {
                    // Mesclar produtos se o fornecedor já existe
                    $fornecedores[$cnpj]['produtos'] = array_merge(
                        $fornecedores[$cnpj]['produtos'],
                        $fornecedor['produtos']
                    );
                    // Atualizar origem para mostrar que aparece em múltiplas fontes
                    if (strpos($fornecedores[$cnpj]['origem'], 'COMPRAS.GOV') === false) {
                        $fornecedores[$cnpj]['origem'] .= ' + COMPRAS.GOV';
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('[Mapa Fornecedores] Erro ao buscar no CATMAT+API Compras.gov', ['erro' => $e->getMessage()]);
        }

        // 4. Buscar contratos PNCP em TEMPO REAL (API) - APÓS Compras.gov LOCAL
        $contratosPNCP = $this->buscarPNCPTempoReal($termo, 1); // APENAS 1 página = ~500 contratos

        foreach ($contratosPNCP as $contrato) {
            $cnpj = $contrato['fornecedor_cnpj'] ?? null;
            if (!$cnpj || strlen($cnpj) != 14) continue;

            if (!isset($fornecedores[$cnpj])) {
                $fornecedores[$cnpj] = [
                    'cnpj' => $this->formatarCNPJ($cnpj),
                    'razao_social' => $contrato['fornecedor_razao_social'] ?? 'Não informado',
                    'nome_fantasia' => null,
                    'telefone' => null,
                    'email' => null,
                    'logradouro' => null,
                    'numero' => null,
                    'bairro' => null,
                    'cidade' => $contrato['orgao_municipio'] ?? null,
                    'uf' => $contrato['orgao_uf'] ?? null,
                    'cep' => null,
                    'origem' => 'PNCP',
                    'produtos' => []
                ];
            } else {
                // Se já existe (veio do Compras.gov), mesclar origem
                if (strpos($fornecedores[$cnpj]['origem'], 'PNCP') === false) {
                    $fornecedores[$cnpj]['origem'] .= ' + PNCP';
                }
            }

            // Adicionar produto do contrato
            $fornecedores[$cnpj]['produtos'][] = [
                'descricao' => $contrato['objeto_contrato'] ?? '',
                'valor' => $contrato['valor_global'] ?? 0,
                'unidade' => 'CONTRATO',
                'data' => $contrato['data_publicacao'] ?? null,
                'orgao' => $contrato['orgao_razao_social'] ?? 'N/A'
            ];
        }

        // 5. Buscar fornecedores no LICITACON (TCE-RS)
        // ⚠️ Desabilitado: API não retorna fornecedores (apenas metadados de datasets)
        // try {
        //     $fornecedoresLicitaCon = $this->buscarFornecedoresLicitaCon($termo);
        //
        //     foreach ($fornecedoresLicitaCon as $cnpj => $fornecedor) {
        //         if (!isset($fornecedores[$cnpj])) {
        //             $fornecedores[$cnpj] = $fornecedor;
        //         }
        //     }
        // } catch (\Exception $e) {
        //     Log::warning('[Mapa Fornecedores] Erro ao buscar no LicitaCon', ['erro' => $e->getMessage()]);
        // }

        // 6. Buscar fornecedores no PORTAL DA TRANSPARÊNCIA (CGU)
        // ⚠️ Temporariamente desabilitado: endpoint /contratos exige codigoOrgao
        // try {
        //     $fornecedoresPortalCGU = $this->buscarFornecedoresPortalCGU($termo);
        //
        //     foreach ($fornecedoresPortalCGU as $cnpj => $fornecedor) {
        //         if (!isset($fornecedores[$cnpj])) {
        //             $fornecedores[$cnpj] = $fornecedor;
        //         }
        //     }
        // } catch (\Exception $e) {
        //     Log::warning('[Mapa Fornecedores] Erro ao buscar no Portal CGU', ['erro' => $e->getMessage()]);
        // }

        // ✅ 31/10/2025: Aumentado de 200→500 para mais resultados
        $fornecedores = array_slice($fornecedores, 0, 500, true);

        Log::info('[Mapa Fornecedores] Busca ampla finalizada', [
            'termo' => $termo,
            'total_fornecedores' => count($fornecedores)
        ]);

        return $fornecedores;
    }

    /**
     * Buscar contratos do PNCP em TEMPO REAL via API
     * NÃO armazena no banco local para economizar armazenamento
     * BUSCA SEM FILTROS - Todas as palavras retornam resultados
     */
    private function buscarPNCPTempoReal($termo, $paginas = 5)
    {
        $contratos = [];
        $dataFinal = now()->format('Ymd');
        $dataInicial = now()->subMonths(6)->format('Ymd'); // Últimos 6 meses (API limita a 365 dias)

        Log::info('[PNCP Tempo Real] 🔍 INICIANDO BUSCA', [
            'termo_recebido' => $termo,
            'tipo_termo' => gettype($termo),
            'tamanho_termo' => strlen($termo),
            'paginas' => $paginas,
            'periodo' => "{$dataInicial} até {$dataFinal}"
        ]);

        try {
            for ($pagina = 1; $pagina <= $paginas; $pagina++) {
                $params = [
                    'dataInicial' => $dataInicial,
                    'dataFinal' => $dataFinal,
                    'q' => $termo,
                    'pagina' => $pagina
                ];

                $url = "https://pncp.gov.br/api/consulta/v1/contratos?" . http_build_query($params);

                Log::info("[PNCP Tempo Real] 🌐 PÁGINA {$pagina} - URL COMPLETA", [
                    'url' => $url,
                    'parametros' => $params,
                    'query_string' => http_build_query($params)
                ]);

                $inicioReq = microtime(true);
                $response = Http::timeout(15)->get($url); // 15s de timeout
                $tempoReq = round((microtime(true) - $inicioReq) * 1000, 2);

                Log::info("[PNCP Tempo Real] Página {$pagina}/{$paginas}", [
                    'tempo_ms' => $tempoReq,
                    'status' => $response->status()
                ]);

                if (!$response->successful()) {
                    Log::warning('[PNCP Tempo Real] Erro ao buscar contratos', [
                        'termo' => $termo,
                        'pagina' => $pagina,
                        'status' => $response->status(),
                        'tempo_ms' => $tempoReq
                    ]);
                    break;
                }

                $data = $response->json();

                Log::info("[PNCP Tempo Real] 📦 RESPOSTA RECEBIDA PÁGINA {$pagina}", [
                    'total_registros' => isset($data['data']) ? count($data['data']) : 0,
                    'tem_dados' => isset($data['data']) && !empty($data['data']),
                    'primeiro_objeto' => isset($data['data'][0]['objetoContrato']) ? substr($data['data'][0]['objetoContrato'], 0, 100) : 'N/A'
                ]);

                if (!isset($data['data']) || empty($data['data'])) {
                    Log::info("[PNCP Tempo Real] Sem mais dados na página {$pagina}");
                    break; // Sem mais dados
                }

                foreach ($data['data'] as $contrato) {
                    $objetoContrato = $contrato['objetoContrato'] ?? '';
                    $fornecedorCNPJ = $contrato['niFornecedor'] ?? $contrato['cnpjContratado'] ?? null;
                    $fornecedorNome = $contrato['nomeRazaoSocialFornecedor'] ?? $contrato['razaoSocialFornecedor'] ?? null;

                    // VALIDAR APENAS SE TEM FORNECEDOR (SEM FILTRO DE PALAVRA!)
                    // A API já filtrou por 'q=termo', então aceitar TUDO que a API retornar
                    if (!$fornecedorCNPJ) {
                        continue; // Apenas pular se não tem fornecedor
                    }

                    $orgao = $contrato['orgaoEntidade'] ?? [];
                    $unidadeOrgao = $contrato['unidadeOrgao'] ?? [];

                    $contratos[] = [
                        'fornecedor_cnpj' => preg_replace('/\D/', '', $fornecedorCNPJ),
                        'fornecedor_razao_social' => $fornecedorNome,
                        'objeto_contrato' => substr($objetoContrato, 0, 500),
                        'valor_global' => $contrato['valorGlobal'] ?? $contrato['valorInicial'] ?? 0,
                        'orgao_cnpj' => $orgao['cnpj'] ?? null,
                        'orgao_razao_social' => $orgao['razaoSocial'] ?? 'N/A',
                        'orgao_uf' => $unidadeOrgao['ufSigla'] ?? null,
                        'orgao_municipio' => $unidadeOrgao['municipioNome'] ?? null,
                        'data_publicacao' => $contrato['dataPublicacaoPncp'] ?? $contrato['dataAssinatura'] ?? null
                    ];
                }

                // Pequeno delay entre páginas
                if ($pagina < $paginas) {
                    usleep(200000); // 200ms
                }
            }
        } catch (\Exception $e) {
            Log::error('[PNCP Tempo Real] Erro ao buscar na API', [
                'termo' => $termo,
                'erro' => $e->getMessage()
            ]);
        }

        Log::info('[PNCP Tempo Real] Busca finalizada', [
            'termo' => $termo,
            'contratos_encontrados' => count($contratos)
        ]);

        return $contratos;
    }

    /**
     * Buscar fornecedor específico por CNPJ
     */
    private function buscarPorCNPJ($cnpj)
    {
        $fornecedores = [];

        // 1. Buscar no banco local (cp_fornecedores)
        $fornecedorLocal = Fornecedor::where('numero_documento', $cnpj)->first();

        if ($fornecedorLocal) {
            $fornecedores[$cnpj] = [
                'cnpj' => $this->formatarCNPJ($cnpj),
                'razao_social' => $fornecedorLocal->razao_social,
                'nome_fantasia' => $fornecedorLocal->nome_fantasia,
                'telefone' => $fornecedorLocal->telefone ?? $fornecedorLocal->celular,
                'email' => $fornecedorLocal->email,
                'logradouro' => $fornecedorLocal->logradouro,
                'numero' => $fornecedorLocal->numero,
                'complemento' => $fornecedorLocal->complemento,
                'bairro' => $fornecedorLocal->bairro,
                'cidade' => $fornecedorLocal->cidade,
                'uf' => $fornecedorLocal->uf,
                'cep' => $fornecedorLocal->cep,
                'origem' => 'LOCAL',
                'produtos' => []
            ];
        }

        // 2. Buscar nos contratos PNCP
        $contratos = ContratoPNCP::where('fornecedor_cnpj', $cnpj)->limit(100)->get();

        if ($contratos->isNotEmpty()) {
            if (!isset($fornecedores[$cnpj])) {
                $primeiroContrato = $contratos->first();
                $fornecedores[$cnpj] = [
                    'cnpj' => $this->formatarCNPJ($cnpj),
                    'razao_social' => $primeiroContrato->fornecedor_razao_social ?? 'Não informado',
                    'nome_fantasia' => null,
                    'telefone' => null,
                    'email' => null,
                    'logradouro' => null,
                    'numero' => null,
                    'complemento' => null,
                    'bairro' => null,
                    'cidade' => null,
                    'uf' => null,
                    'cep' => null,
                    'origem' => 'PNCP',
                    'produtos' => []
                ];
            }

            // Adicionar produtos fornecidos
            foreach ($contratos as $contrato) {
                $fornecedores[$cnpj]['produtos'][] = [
                    'descricao' => $contrato->objeto_contrato,
                    'valor' => $contrato->valor_unitario_estimado ?? $contrato->valor_global,
                    'unidade' => $contrato->unidade_medida,
                    'data' => $contrato->data_publicacao_pncp?->format('Y-m-d'),
                    'orgao' => $contrato->orgao_razao_social
                ];
            }
        }

        return $fornecedores;
    }

    /**
     * Buscar fornecedores que forneceram determinado produto/CATMAT
     */
    private function buscarPorTermoProduto($termo)
    {
        $fornecedores = [];

        // Buscar contratos PNCP que contenham o termo no objeto
        $contratos = ContratoPNCP::buscarPorTermo($termo, 12, 100);

        // Agrupar por fornecedor (CNPJ)
        foreach ($contratos as $contrato) {
            $cnpj = $contrato->fornecedor_cnpj;

            if (!$cnpj) continue;

            if (!isset($fornecedores[$cnpj])) {
                // Verificar se fornecedor está cadastrado localmente
                $fornecedorLocal = Fornecedor::where('numero_documento', $cnpj)->first();

                if ($fornecedorLocal) {
                    $fornecedores[$cnpj] = [
                        'cnpj' => $this->formatarCNPJ($cnpj),
                        'razao_social' => $fornecedorLocal->razao_social,
                        'nome_fantasia' => $fornecedorLocal->nome_fantasia,
                        'telefone' => $fornecedorLocal->telefone ?? $fornecedorLocal->celular,
                        'email' => $fornecedorLocal->email,
                        'logradouro' => $fornecedorLocal->logradouro,
                        'numero' => $fornecedorLocal->numero,
                        'complemento' => $fornecedorLocal->complemento,
                        'bairro' => $fornecedorLocal->bairro,
                        'cidade' => $fornecedorLocal->cidade,
                        'uf' => $fornecedorLocal->uf,
                        'cep' => $fornecedorLocal->cep,
                        'origem' => 'LOCAL',
                        'produtos' => []
                    ];
                } else {
                    $fornecedores[$cnpj] = [
                        'cnpj' => $this->formatarCNPJ($cnpj),
                        'razao_social' => $contrato->fornecedor_razao_social ?? 'Não informado',
                        'nome_fantasia' => null,
                        'telefone' => null,
                        'email' => null,
                        'logradouro' => null,
                        'numero' => null,
                        'complemento' => null,
                        'bairro' => null,
                        'cidade' => null,
                        'uf' => null,
                        'cep' => null,
                        'origem' => 'PNCP',
                        'produtos' => []
                    ];
                }
            }

            // Adicionar produto que o fornecedor forneceu
            $fornecedores[$cnpj]['produtos'][] = [
                'descricao' => $contrato->objeto_contrato,
                'valor' => $contrato->valor_unitario_estimado ?? $contrato->valor_global,
                'unidade' => $contrato->unidade_medida,
                'data' => $contrato->data_publicacao_pncp?->format('Y-m-d'),
                'orgao' => $contrato->orgao_razao_social
            ];
        }

        return $fornecedores;
    }

    /**
     * Buscar fornecedores no Compras.gov (API Pública)
     * Retorna fornecedores que forneceram o produto pesquisado
     */
    /**
     * Buscar fornecedores via CATMAT Local + API de Preços Compras.gov
     * (Substituição do método antigo que usava endpoint 404)
     */
    private function buscarFornecedoresComprasGov($termo)
    {
        $fornecedores = [];

        try {
            Log::info('[COMPRAS.GOV LOCAL Mapa] Buscando fornecedores localmente', ['termo' => $termo]);

            // Buscar DIRETAMENTE na tabela local de preços (cp_precos_comprasgov)
            $precos = DB::connection('pgsql_main')
                ->table('cp_precos_comprasgov')
                ->select(
                    'catmat_codigo',
                    'descricao_item',
                    'preco_unitario',
                    'unidade_fornecimento',
                    'fornecedor_nome',
                    'fornecedor_cnpj',
                    'municipio',
                    'uf',
                    'orgao_nome',
                    'data_compra'
                )
                ->whereRaw(
                    "to_tsvector('portuguese', descricao_item) @@ plainto_tsquery('portuguese', ?)",
                    [$termo]
                )
                ->where('preco_unitario', '>', 0)
                ->whereNotNull('fornecedor_cnpj')
                ->orderBy('data_compra', 'desc')
                ->limit(1000) // ✅ 31/10/2025: Aumentado de 200→1000
                ->get();

            if ($precos->isEmpty()) {
                Log::info('[COMPRAS.GOV LOCAL Mapa] Nenhum fornecedor encontrado');
                return $fornecedores;
            }

            Log::info('[COMPRAS.GOV LOCAL Mapa] Preços encontrados', ['total' => $precos->count()]);

            // Processar cada preço praticado
            foreach ($precos as $preco) {
                $cnpj = preg_replace('/\D/', '', $preco->fornecedor_cnpj ?? '');

                if (!$cnpj || strlen($cnpj) != 14) continue;

                if (!isset($fornecedores[$cnpj])) {
                    $fornecedores[$cnpj] = [
                        'cnpj' => $this->formatarCNPJ($cnpj),
                        'razao_social' => $preco->fornecedor_nome ?? 'Não informado',
                        'nome_fantasia' => null,
                        'telefone' => null,
                        'email' => null,
                        'logradouro' => null,
                        'numero' => null,
                        'bairro' => null,
                        'cidade' => $preco->municipio,
                        'uf' => $preco->uf,
                        'cep' => null,
                        'origem' => 'COMPRAS.GOV',
                        'produtos' => []
                    ];
                }

                // Adicionar produto fornecido
                $fornecedores[$cnpj]['produtos'][] = [
                    'descricao' => $preco->descricao_item,
                    'valor' => floatval($preco->preco_unitario),
                    'unidade' => $preco->unidade_fornecimento ?? 'UN',
                    'data' => $preco->data_compra,
                    'orgao' => $preco->orgao_nome ?? 'N/A',
                    'catmat' => $preco->catmat_codigo
                ];

                // ✅ 31/10/2025: Aumentado de 50→200
                if (count($fornecedores) >= 200) {
                    break;
                }
            }

            Log::info('[COMPRAS.GOV LOCAL Mapa] Busca finalizada', ['fornecedores' => count($fornecedores)]);

        } catch (\Exception $e) {
            Log::error('[COMPRAS.GOV LOCAL Mapa] Erro geral', ['erro' => $e->getMessage()]);
        }

        return $fornecedores;
    }

    /**
     * Buscar fornecedores no LicitaCon (TCE-RS)
     * ⚠️ LIMITAÇÃO: API busca apenas títulos de datasets, não conteúdo de CSVs
     * Retorna poucos resultados para pesquisas de produtos específicos
     */
    private function buscarFornecedoresLicitaCon($termo)
    {
        $fornecedores = [];

        try {
            Log::info('[LicitaCon] Buscando fornecedores', ['termo' => $termo]);

            $url = 'https://dados.tce.rs.gov.br/api/3/action/package_search';
            $response = Http::timeout(10)->get($url, [
                'q' => $termo,
                'rows' => 100 // Limite de 100 datasets
            ]);

            if (!$response->successful()) {
                Log::info('[LicitaCon] Sem resposta da API');
                return $fornecedores;
            }

            $data = $response->json();
            $datasets = $data['result']['results'] ?? [];

            Log::info('[LicitaCon] Datasets encontrados', ['total' => count($datasets)]);

            // ⚠️ PROBLEMA: API retorna apenas metadados, não os dados dos CSVs
            // Para obter fornecedores reais, seria necessário baixar e parsear cada CSV
            // Isso não é viável para uma busca em tempo real (timeout)

            // Por enquanto, retornar apenas informações básicas dos datasets
            foreach (array_slice($datasets, 0, 10) as $dataset) {
                // Extrair informações do dataset (não do CSV)
                $orgao = $dataset['organization']['title'] ?? 'TCE-RS';

                // Datasets do LicitaCon não contêm CNPJ de fornecedores nos metadados
                // Seria necessário baixar o CSV para obter essa informação

                Log::debug('[LicitaCon] Dataset encontrado', [
                    'titulo' => $dataset['title'] ?? 'N/A',
                    'orgao' => $orgao
                ]);
            }

            Log::info('[LicitaCon] ⚠️ API não retorna fornecedores (apenas metadados de datasets)');

        } catch (\Exception $e) {
            Log::error('[LicitaCon] Erro na busca', ['erro' => $e->getMessage()]);
        }

        return $fornecedores;
    }

    /**
     * Buscar fornecedores no Portal da Transparência (CGU)
     * ⚠️ TEMPORARIAMENTE DESABILITADO: Endpoint /contratos exige codigoOrgao
     */
    private function buscarFornecedoresPortalCGU($termo)
    {
        $fornecedores = [];

        try {
            Log::info('[Portal CGU] 🟡 Temporariamente desabilitado (endpoint /contratos exige codigoOrgao)');

            // TODO: Implementar busca por licitações ao invés de contratos
            // Endpoint alternativo: /api-de-dados/licitacoes
            // Não exige código de órgão, apenas datas

            return $fornecedores;

        } catch (\Exception $e) {
            Log::error('[Portal CGU] Erro na busca', ['erro' => $e->getMessage()]);
        }

        return $fornecedores;
    }

    /**
     * Formatar CNPJ para XX.XXX.XXX/XXXX-XX
     */
    private function formatarCNPJ($cnpj)
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) === 14) {
            return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj);
        }

        return $cnpj;
    }

    /**
     * Listar todos os fornecedores locais (para modal de importação)
     */
    public function listarLocal(Request $request)
    {
        try {
            $fornecedores = Fornecedor::orderBy('razao_social')
                ->limit(200)
                ->get();

            $fornecedoresFormatados = $fornecedores->map(function($fornecedor) {
                return [
                    'cnpj' => $fornecedor->numero_documento_formatado ?? $fornecedor->numero_documento,
                    'razao_social' => $fornecedor->razao_social,
                    'nome_fantasia' => $fornecedor->nome_fantasia,
                    'telefone' => $fornecedor->telefone,
                    'email' => $fornecedor->email,
                    'endereco' => $fornecedor->endereco,
                    'municipio' => $fornecedor->municipio,
                    'uf' => $fornecedor->uf,
                    'origem' => 'LOCAL'
                ];
            });

            return response()->json([
                'success' => true,
                'fornecedores' => $fornecedoresFormatados,
                'total' => $fornecedoresFormatados->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar fornecedores locais: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar fornecedores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sanitizar strings para UTF-8 válido (evita erro "Malformed UTF-8 characters")
     * Percorre recursivamente arrays e objetos convertendo todas as strings
     */
    private function sanitizeUTF8($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeUTF8'], $data);
        }

        if (is_object($data)) {
            $data = (array) $data;
            $data = array_map([$this, 'sanitizeUTF8'], $data);
            return (object) $data;
        }

        if (is_string($data)) {
            // Converter de qualquer encoding para UTF-8
            if (!mb_check_encoding($data, 'UTF-8')) {
                // Tentar detectar e converter
                $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
            }

            // Remover caracteres inválidos
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');

            // Substituir caracteres de controle por espaço (exceto \n, \r, \t)
            $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $data);

            return $data;
        }

        return $data;
    }
}
