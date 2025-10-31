<?php

namespace App\Http\Controllers;

use App\Models\SolicitacaoCDF;
use App\Models\RespostaCDF;
use App\Models\RespostaCDFItem;
use App\Models\RespostaCDFAnexo;
use App\Models\Fornecedor;
use App\Models\FornecedorItem;
use App\Models\Notificacao;
use App\Models\OrcamentoItem;
use App\Services\CnpjService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CdfRespostaController extends Controller
{
    /**
     * Exibir formulário de resposta para o fornecedor
     * GET /responder-cdf/{token}
     */
    public function exibirFormulario($token)
    {
        try {
            // Buscar solicitação pelo token
            $solicitacao = SolicitacaoCDF::where('token_resposta', $token)
                ->with(['orcamento', 'itens.item'])
                ->first();

            // Validações
            if (!$solicitacao) {
                return view('cdf.resposta-invalida', [
                    'motivo' => 'token_invalido',
                    'mensagem' => 'Link de resposta não encontrado ou inválido.'
                ]);
            }

            // Verificar se já foi respondido
            if ($solicitacao->respondido) {
                return view('cdf.resposta-invalida', [
                    'motivo' => 'ja_respondido',
                    'mensagem' => 'Esta solicitação já foi respondida.',
                    'data_resposta' => $solicitacao->data_resposta_fornecedor
                ]);
            }

            // Verificar se expirou
            if (!$solicitacao->linkValido()) {
                return view('cdf.resposta-invalida', [
                    'motivo' => 'link_expirado',
                    'mensagem' => 'O prazo para responder esta solicitação expirou.',
                    'valido_ate' => $solicitacao->valido_ate
                ]);
            }

            // Retornar view com formulário
            return view('cdf.resposta-fornecedor', [
                'solicitacao' => $solicitacao,
                'orcamento' => $solicitacao->orcamento,
                'itens' => $solicitacao->itens
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao exibir formulário CDF: ' . $e->getMessage(), [
                'token' => $token,
                'trace' => $e->getTraceAsString()
            ]);

            return view('cdf.resposta-invalida', [
                'motivo' => 'erro_sistema',
                'mensagem' => 'Erro ao carregar formulário. Por favor, tente novamente.'
            ]);
        }
    }

    /**
     * Salvar resposta do fornecedor
     * POST /api/cdf/responder
     */
    public function salvarResposta(Request $request)
    {
        try {
            // LOG DETALHADO: Dados recebidos antes da validação
            Log::info('CDF: Iniciando salvamento de resposta', [
                'all_data' => $request->all(),
                'has_files' => $request->hasFile('anexos'),
                'headers' => $request->headers->all()
            ]);

            // FIX: Reconstruir array 'itens' a partir de campos flat (itens[0][campo] => valor)
            // Isso é necessário quando a requisição vem via ModuleProxy com multipart/form-data
            $allData = $request->all();
            $itens = [];
            foreach ($allData as $key => $value) {
                if (preg_match('/^itens\[(\d+)\]\[(.+)\]$/', $key, $matches)) {
                    $index = (int)$matches[1];
                    $field = $matches[2];
                    $itens[$index][$field] = $value;
                    unset($allData[$key]); // Remover campo flat
                }
            }
            if (!empty($itens)) {
                $allData['itens'] = array_values($itens); // Reindexar
                $request->merge($allData); // Atualizar request com array reconstruído
            }

            Log::info('CDF: Dados após reconstrução do array', [
                'has_itens_array' => isset($allData['itens']),
                'itens_count' => isset($allData['itens']) ? count($allData['itens']) : 0
            ]);

            // Validar dados recebidos
            // ✅ FIX: Usar 'numeric' ao invés de 'integer' para aceitar strings numéricas de multipart/form-data
            $validated = $request->validate([
                'token' => 'required|string',
                'cnpj' => 'required|string|min:14|max:18', // Aceita formatado ou não
                'razao_social' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'telefone' => 'required|string|max:50',
                'validade_proposta' => 'required|numeric|min:1|max:365', // numeric aceita string "30"
                'forma_pagamento' => 'required|string|max:255',
                'observacoes_gerais' => 'nullable|string',
                'assinatura_digital' => 'required|string',
                'itens' => 'required|array|min:1',
                'itens.*.item_orcamento_id' => 'required|numeric|exists:cp_itens_orcamento,id', // numeric aceita string "123"
                'itens.*.preco_unitario' => 'required|numeric|min:0',
                'itens.*.preco_total' => 'required|numeric|min:0',
                'itens.*.marca' => 'required|string|max:255',
                'itens.*.prazo_entrega' => 'required|numeric|min:0|max:365', // numeric aceita string "15"
                'itens.*.observacoes' => 'nullable|string',
                'anexos.*' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'
            ]);

            // Buscar solicitação
            $solicitacao = SolicitacaoCDF::where('token_resposta', $validated['token'])->first();

            if (!$solicitacao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitação não encontrada.'
                ], 404);
            }

            // Validar novamente se pode responder
            if ($solicitacao->respondido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta solicitação já foi respondida.'
                ], 400);
            }

            if (!$solicitacao->linkValido()) {
                return response()->json([
                    'success' => false,
                    'message' => 'O prazo para responder esta solicitação expirou.'
                ], 400);
            }

            DB::beginTransaction();

            // 1. Criar ou atualizar fornecedor com enriquecimento de dados
            $cnpjLimpo = preg_replace('/\D/', '', $validated['cnpj']);

            // Consultar dados do CNPJ na Receita Federal
            $cnpjService = app(CnpjService::class);
            $dadosCnpj = $cnpjService->consultar($cnpjLimpo);

            // Dados do fornecedor (usar dados da CDF se consulta falhar)
            $dadosFornecedor = [
                'tipo_documento' => 'CNPJ',
                'numero_documento' => $cnpjLimpo,
                'razao_social' => $dadosCnpj['success'] ? $dadosCnpj['razao_social'] : $validated['razao_social'],
                'nome_fantasia' => $dadosCnpj['nome_fantasia'] ?? null,
                'email' => $validated['email'],
                'telefone' => $validated['telefone'],
                'status' => 'cdf_respondida',
                'origem' => 'CDF'
            ];

            // Adicionar dados de endereço se consulta teve sucesso
            if ($dadosCnpj['success']) {
                $dadosFornecedor['uf'] = $dadosCnpj['uf'] ?? 'XX';
                $dadosFornecedor['cidade'] = $dadosCnpj['municipio'] ?? 'Não informado';
                $dadosFornecedor['logradouro'] = $dadosCnpj['logradouro'] ?? 'Não informado';
                $dadosFornecedor['bairro'] = $dadosCnpj['bairro'] ?? 'Não informado';
                $dadosFornecedor['numero'] = $dadosCnpj['numero'] ?? '';
                $dadosFornecedor['complemento'] = $dadosCnpj['complemento'] ?? '';
                $dadosFornecedor['cep'] = $dadosCnpj['cep'] ?? '';
            } else {
                // Dados genéricos se não conseguiu consultar
                $dadosFornecedor['uf'] = 'XX';
                $dadosFornecedor['cidade'] = 'Não informado';
                $dadosFornecedor['logradouro'] = 'Não informado';
                $dadosFornecedor['bairro'] = 'Não informado';
            }

            $fornecedor = Fornecedor::updateOrCreate(
                ['numero_documento' => $cnpjLimpo],
                $dadosFornecedor
            );

            // 2. Criar resposta CDF
            $resposta = RespostaCDF::create([
                'solicitacao_cdf_id' => $solicitacao->id,
                'fornecedor_id' => $fornecedor->id,
                'validade_proposta' => (int)$validated['validade_proposta'], // ✅ Cast para int
                'forma_pagamento' => $validated['forma_pagamento'],
                'observacoes_gerais' => $validated['observacoes_gerais'] ?? null,
                'assinatura_digital' => $validated['assinatura_digital'],
                'data_resposta' => now()
            ]);

            // 3. Criar itens da resposta e vincular fornecedor aos produtos
            foreach ($validated['itens'] as $itemData) {
                // Criar item da resposta CDF
                $respostaCDFItem = RespostaCDFItem::create([
                    'resposta_cdf_id' => $resposta->id,
                    'item_orcamento_id' => (int)$itemData['item_orcamento_id'], // ✅ Cast para int
                    'preco_unitario' => (float)$itemData['preco_unitario'], // ✅ Cast para float
                    'preco_total' => (float)$itemData['preco_total'], // ✅ Cast para float
                    'marca' => $itemData['marca'],
                    'prazo_entrega' => (int)$itemData['prazo_entrega'], // ✅ Cast para int
                    'observacoes' => $itemData['observacoes'] ?? null
                ]);

                // Buscar dados do item do orçamento
                $itemOrcamento = OrcamentoItem::find($itemData['item_orcamento_id']);

                if ($itemOrcamento) {
                    // Vincular fornecedor ao produto (para aparecer no Mapa de Fornecedores)
                    FornecedorItem::updateOrCreate(
                        [
                            'fornecedor_id' => $fornecedor->id,
                            'descricao' => $itemOrcamento->descricao
                        ],
                        [
                            'fornecedor_id' => $fornecedor->id,
                            'descricao' => $itemOrcamento->descricao,
                            'codigo_catmat' => $itemOrcamento->codigo_catmat ?? null,
                            'preco_referencia' => (float)$itemData['preco_unitario'], // ✅ Cast para float
                            'unidade' => $itemOrcamento->medida_fornecimento ?? 'Unidade'
                        ]
                    );
                }
            }

            // 4. Processar anexos (se houver)
            if ($request->hasFile('anexos')) {
                foreach ($request->file('anexos') as $anexo) {
                    $nomeArquivo = time() . '_' . $anexo->getClientOriginalName();
                    $caminho = $anexo->storeAs('cdf-respostas/' . $solicitacao->id, $nomeArquivo, 'public');

                    RespostaCDFAnexo::create([
                        'resposta_cdf_id' => $resposta->id,
                        'nome_arquivo' => $anexo->getClientOriginalName(),
                        'caminho' => $caminho,
                        'tamanho' => $anexo->getSize()
                    ]);
                }
            }

            // 5. Atualizar solicitação como respondida
            $solicitacao->update([
                'respondido' => true,
                'data_resposta_fornecedor' => now(),
                'status' => 'Respondido'
            ]);

            // 6. Criar notificação para o usuário que criou a solicitação
            if ($solicitacao->orcamento && $solicitacao->orcamento->user_id) {
                Notificacao::create([
                    'user_id' => $solicitacao->orcamento->user_id,
                    'tipo' => 'cdf_respondida',
                    'titulo' => 'CDF Respondida',
                    'mensagem' => "O fornecedor {$fornecedor->razao_social} respondeu sua solicitação de CDF.",
                    'dados' => json_encode([
                        'solicitacao_cdf_id' => $solicitacao->id,
                        'resposta_cdf_id' => $resposta->id,
                        'fornecedor_id' => $fornecedor->id,
                        'fornecedor_nome' => $fornecedor->razao_social,
                        'orcamento_id' => $solicitacao->orcamento_id
                    ]),
                    'lida' => false
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Resposta enviada com sucesso! Obrigado por sua cotação.',
                'data' => [
                    'resposta_id' => $resposta->id,
                    'valor_total' => $resposta->valor_total
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            // LOG DETALHADO: Erros de validação específicos
            Log::error('CDF: Falha na validação', [
                'validation_errors' => $e->errors(),
                'received_data' => $request->all(),
                'failed_rules' => $e->validator->failed()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar resposta CDF: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar resposta. Por favor, tente novamente.'
            ], 500);
        }
    }

    /**
     * Consultar CNPJ (API auxiliar para preenchimento automático)
     * GET /api/cdf/consultar-cnpj/{cnpj}
     */
    public function consultarCnpj($cnpj)
    {
        try {
            // Remover formatação do CNPJ
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

            // 1. Tentar buscar fornecedor no banco de dados local primeiro
            $fornecedor = Fornecedor::where('numero_documento', $cnpjLimpo)->first();

            if ($fornecedor) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'razao_social' => $fornecedor->razao_social,
                        'email' => $fornecedor->email,
                        'telefone' => $fornecedor->telefone,
                        'fonte' => 'banco_dados'
                    ]
                ]);
            }

            // 2. Se não encontrou no banco, consultar Receita Federal via CnpjService
            $cnpjService = app(\App\Services\CnpjService::class);
            $resultado = $cnpjService->consultar($cnpjLimpo);

            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'razao_social' => $resultado['razao_social'],
                        'email' => $resultado['email'],
                        'telefone' => $resultado['telefone'],
                        'fonte' => 'receita_federal',
                        'situacao' => $resultado['situacao'] ?? null,
                        'warning' => $resultado['warning'] ?? null
                    ]
                ]);
            }

            // 3. Se não encontrou em nenhum lugar
            return response()->json([
                'success' => false,
                'message' => $resultado['message'] ?? 'CNPJ não encontrado.'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Erro ao consultar CNPJ: ' . $e->getMessage(), [
                'cnpj' => $cnpj,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar CNPJ. Tente novamente.'
            ], 500);
        }
    }

    /**
     * Visualizar resposta CDF (para usuário interno)
     * GET /api/cdf/resposta/{id}
     */
    public function visualizarResposta(Request $request, $id)
    {
        try {
            Log::info('visualizarResposta: Iniciando', [
                'resposta_id' => $id,
                'is_ajax' => $request->ajax(),
                'wants_json' => $request->wantsJson()
            ]);

            $resposta = RespostaCDF::with([
                'solicitacao.orcamento',
                'fornecedor',
                'itens.itemOrcamento',
                'anexos'
            ])->findOrFail($id);

            Log::info('visualizarResposta: Resposta encontrada', [
                'resposta_id' => $resposta->id,
                'fornecedor_id' => $resposta->fornecedor_id,
                'itens_count' => $resposta->itens->count()
            ]);

            // Se for requisição AJAX, retornar JSON
            if ($request->wantsJson() || $request->ajax()) {
                // Montar array de itens com informações completas
                $itens = $resposta->itens->map(function($itemResposta) {
                    return [
                        'id' => $itemResposta->id,
                        'descricao' => $itemResposta->itemOrcamento->descricao ?? 'N/A',
                        'quantidade' => $itemResposta->itemOrcamento->quantidade ?? 0,
                        'unidade' => $itemResposta->itemOrcamento->unidade ?? 'UN',
                        'preco_unitario' => $itemResposta->preco_unitario,
                        'preco_total' => $itemResposta->preco_total,
                        'marca' => $itemResposta->marca,
                        'prazo_entrega' => $itemResposta->prazo_entrega,
                        'observacoes' => $itemResposta->observacoes
                    ];
                });

                return response()->json([
                    'success' => true,
                    'resposta' => [
                        'id' => $resposta->id,
                        'data_resposta' => $resposta->data_resposta,
                        'validade_proposta' => $resposta->validade_proposta,
                        'forma_pagamento' => $resposta->forma_pagamento,
                        'observacoes_gerais' => $resposta->observacoes_gerais,
                        'assinatura_digital' => $resposta->assinatura_digital,
                        'valor_total' => $resposta->valor_total
                    ],
                    'fornecedor' => [
                        'id' => $resposta->fornecedor->id,
                        'numero_documento' => $resposta->fornecedor->numero_documento,
                        'razao_social' => $resposta->fornecedor->razao_social,
                        'email' => $resposta->fornecedor->email,
                        'telefone' => $resposta->fornecedor->telefone
                    ],
                    'itens' => $itens,
                    'anexos' => $resposta->anexos->map(function($anexo) {
                        return [
                            'id' => $anexo->id,
                            'nome_arquivo' => $anexo->nome_arquivo,
                            'caminho' => $anexo->caminho,
                            'tamanho' => $anexo->tamanho
                        ];
                    })
                ]);
            }

            // Se for navegação normal, retornar view
            return view('cdf.visualizar-resposta', [
                'resposta' => $resposta,
                'solicitacao' => $resposta->solicitacao,
                'fornecedor' => $resposta->fornecedor
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao visualizar resposta CDF: ' . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao carregar resposta.'
                ], 500);
            }

            return redirect()->back()->with('error', 'Erro ao carregar resposta.');
        }
    }

    /**
     * Listar CDFs enviadas (para usuário interno)
     * GET /cdfs-enviadas
     */
    public function listarCdfs(Request $request)
    {
        $filtro = $request->get('filtro', 'todos'); // todos, pendentes, respondidos

        $query = SolicitacaoCDF::with(['orcamento', 'resposta'])
            ->orderBy('created_at', 'desc');

        if ($filtro === 'pendentes') {
            $query->where('respondido', false);
        } elseif ($filtro === 'respondidos') {
            $query->where('respondido', true);
        }

        $cdfs = $query->paginate(20);

        return view('cdf.listar', [
            'cdfs' => $cdfs,
            'filtro' => $filtro
        ]);
    }

    /**
     * Apagar CDF e seus dados relacionados (para usuário interno)
     * DELETE /api/cdf/{id}
     */
    public function apagarCDF(Request $request, $id)
    {
        try {
            Log::info('apagarCDF: Iniciando', ['cdf_id' => $id]);

            // Buscar solicitação CDF com relacionamentos
            $solicitacao = SolicitacaoCDF::with(['resposta.itens', 'resposta.anexos', 'itens'])
                ->findOrFail($id);

            DB::beginTransaction();

            // 1. Se houver resposta, apagar anexos físicos e registros
            if ($solicitacao->resposta) {
                $resposta = $solicitacao->resposta;

                // Apagar arquivos físicos dos anexos
                foreach ($resposta->anexos as $anexo) {
                    if (Storage::disk('public')->exists($anexo->caminho)) {
                        Storage::disk('public')->delete($anexo->caminho);
                    }
                    $anexo->delete();
                }

                // Apagar itens da resposta
                $resposta->itens()->delete();

                // Apagar resposta
                $resposta->delete();

                Log::info('apagarCDF: Resposta e dados relacionados apagados', [
                    'resposta_id' => $resposta->id
                ]);
            }

            // 2. Apagar itens da solicitação
            $solicitacao->itens()->delete();

            // 3. Apagar a solicitação CDF
            $solicitacao->delete();

            DB::commit();

            Log::info('apagarCDF: CDF apagada com sucesso', ['cdf_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'CDF apagada com sucesso!'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('apagarCDF: CDF não encontrada', ['cdf_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'CDF não encontrada.'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('apagarCDF: Erro ao apagar CDF', [
                'cdf_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao apagar CDF. Por favor, tente novamente.'
            ], 500);
        }
    }
}
