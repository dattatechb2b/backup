<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CatalogoProduto;
use App\Models\HistoricoPreco;
use App\Models\ArpItem;
use App\Models\ContratoPNCP;
use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatalogoController extends Controller
{
    /**
     * Listar produtos do catálogo (paginado)
     *
     * GET /api/catalogo?busca=papel&ativo=1&pagina=1&limite=20
     */
    public function index(Request $request)
    {
        $busca = $request->input('busca');
        $ativo = $request->input('ativo', true);
        $limite = $request->input('limite', 20);

        $query = CatalogoProduto::query();

        // Filtro: apenas ativos
        if ($ativo) {
            $query->ativo();
        }

        // Busca fulltext (descrição ou tags)
        if ($busca) {
            $query->buscarGeral($busca);
        }

        $produtos = $query->orderBy('descricao_padrao', 'asc')->paginate($limite);

        // Adicionar estatísticas de preço para cada produto
        $produtosComEstatisticas = $produtos->map(function ($produto) {
            return array_merge($produto->toArray(), [
                'estatisticas_preco' => $produto->estatisticasPrecos(),
                'ultimo_preco' => $produto->ultimoPreco(),
            ]);
        });

        return response()->json([
            'sucesso' => true,
            'total' => $produtos->total(),
            'pagina_atual' => $produtos->currentPage(),
            'total_paginas' => $produtos->lastPage(),
            'produtos' => $produtosComEstatisticas,
        ]);
    }

    /**
     * Exibir produto específico
     *
     * GET /api/catalogo/{id}
     */
    public function show($id)
    {
        $produto = CatalogoProduto::with(['catmatRelacionado', 'historicoPrecos'])->find($id);

        if (!$produto) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Produto não encontrado',
            ], 404);
        }

        return response()->json([
            'sucesso' => true,
            'produto' => array_merge($produto->toArray(), [
                'estatisticas_preco' => $produto->estatisticasPrecos(),
                'ultimo_preco' => $produto->ultimoPreco(),
            ]),
        ]);
    }

    /**
     * Criar novo produto no catálogo
     *
     * POST /api/catalogo
     * Body: {
     *   "descricao_padrao": "...",
     *   "catmat": "123456",
     *   "unidade": "UN",
     *   "especificacao": "...",
     *   "tags": "escritorio,papel"
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'descricao_padrao' => 'required|string',
            'catmat' => 'nullable|string|max:20|exists:catmat,codigo',
            'catser' => 'nullable|string|max:20|exists:catmat,codigo',
            'unidade' => 'required|string|max:50',
            'especificacao' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $produto = CatalogoProduto::create($validated);

        Log::info('Produto criado no catálogo', [
            'produto_id' => $produto->id,
            'descricao' => $produto->descricao_padrao,
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Produto adicionado ao catálogo com sucesso',
            'produto' => $produto,
        ], 201);
    }

    /**
     * Atualizar produto do catálogo
     *
     * PUT /api/catalogo/{id}
     */
    public function update(Request $request, $id)
    {
        $produto = CatalogoProduto::find($id);

        if (!$produto) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Produto não encontrado',
            ], 404);
        }

        $validated = $request->validate([
            'descricao_padrao' => 'sometimes|string',
            'catmat' => 'nullable|string|max:20|exists:catmat,codigo',
            'catser' => 'nullable|string|max:20|exists:catmat,codigo',
            'unidade' => 'sometimes|string|max:50',
            'especificacao' => 'nullable|string',
            'tags' => 'nullable|string',
            'ativo' => 'sometimes|boolean',
        ]);

        $produto->update($validated);

        Log::info('Produto atualizado no catálogo', [
            'produto_id' => $produto->id,
            'descricao' => $produto->descricao_padrao,
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Produto atualizado com sucesso',
            'produto' => $produto,
        ]);
    }

    /**
     * Excluir produto do catálogo (soft delete - apenas marca como inativo)
     *
     * DELETE /api/catalogo/{id}
     */
    public function destroy($id)
    {
        $produto = CatalogoProduto::find($id);

        if (!$produto) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Produto não encontrado',
            ], 404);
        }

        // Soft delete: apenas marcar como inativo
        $produto->update(['ativo' => false]);

        Log::info('Produto desativado no catálogo', [
            'produto_id' => $produto->id,
            'descricao' => $produto->descricao_padrao,
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Produto desativado com sucesso',
        ]);
    }

    /**
     * Buscar referências de preço do PNCP para um produto
     * Retorna ARPs e contratos similares
     *
     * GET /api/catalogo/{id}/referencias-preco
     */
    public function referenciasPreco($id)
    {
        try {
            $produto = CatalogoProduto::find($id);

            if (!$produto) {
                return response()->json([
                    'sucesso' => false,
                    'mensagem' => 'Produto não encontrado',
                ], 404);
            }

            $referencias = [
                'produto' => $produto,
                'estatisticas_catalogo' => $produto->estatisticasPrecos(),
                'referencias_arp' => [],
                'referencias_historico' => [],
            ];

            // 1. Buscar ARPs com mesmo CATMAT
            if ($produto->catmat) {
                $referencias['referencias_arp'] = ArpItem::with(['ata' => function ($query) {
                    $query->vigentes();
                }])
                    ->porCatmat($produto->catmat)
                    ->deAtasVigentes()
                    ->ordenarPorPreco('asc')
                    ->limit(10)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'descricao' => $item->descricao,
                            'unidade' => $item->unidade,
                            'preco_unitario' => $item->preco_unitario,
                            'preco_formatado' => $item->preco_formatado,
                            'badge' => $item->badge_emoji,
                            'ata_numero' => $item->ata->numero_ata ?? 'N/A',
                            'orgao' => $item->ata->orgao_gerenciador ?? 'N/A',
                            'fornecedor' => $item->ata->fornecedor_razao ?? 'N/A',
                            'vigencia_fim' => $item->ata->vigencia_fim ?? null,
                            'fonte_url' => $item->ata->fonte_url ?? null,
                        ];
                    });
            }

            // 2. Buscar histórico de preços registrados
            $referencias['referencias_historico'] = HistoricoPreco::where('catalogo_produto_id', $id)
                ->orWhere('catmat', $produto->catmat)
                ->ultimosDias(90)
                ->orderBy('data_coleta', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($historico) {
                    return [
                        'fonte' => $historico->fonte_label,
                        'preco_unitario' => $historico->preco_unitario,
                        'preco_formatado' => $historico->preco_formatado,
                        'badge' => $historico->badge_emoji,
                        'data_coleta' => $historico->data_coleta->format('d/m/Y H:i'),
                        'fonte_url' => $historico->fonte_url,
                    ];
                });

            // 3. Calcular estatísticas gerais de ARPs
            if ($produto->catmat) {
                $estatisticasArp = ArpItem::porCatmat($produto->catmat)
                    ->deAtasVigentes()
                    ->selectRaw('MIN(preco_unitario) as preco_min, AVG(preco_unitario) as preco_medio, MAX(preco_unitario) as preco_max, COUNT(*) as total_registros')
                    ->first();

                $referencias['estatisticas_arp'] = [
                    'preco_min' => $estatisticasArp->preco_min ?? null,
                    'preco_medio' => $estatisticasArp->preco_medio ?? null,
                    'preco_max' => $estatisticasArp->preco_max ?? null,
                    'total_registros' => $estatisticasArp->total_registros ?? 0,
                ];
            }

            return response()->json([
                'sucesso' => true,
                'referencias' => $referencias,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar referências de preço: ' . $e->getMessage(), [
                'produto_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar referências de preço: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adicionar preço manualmente ao histórico de um produto
     *
     * POST /api/catalogo/{id}/adicionar-preco
     * Body: {
     *   "preco_unitario": 10.50,
     *   "fonte": "MANUAL",
     *   "badge": "🟢",
     *   "fonte_url": "https://..."
     * }
     */
    public function adicionarPreco(Request $request, $id)
    {
        $produto = CatalogoProduto::find($id);

        if (!$produto) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Produto não encontrado',
            ], 404);
        }

        $validated = $request->validate([
            'preco_unitario' => 'required|numeric|min:0.01',
            'fonte' => 'required|in:ARP,CONTRATO,MANUAL',
            'badge' => 'nullable|string',
            'fonte_url' => 'nullable|url',
        ]);

        $historico = HistoricoPreco::create([
            'catalogo_produto_id' => $produto->id,
            'catmat' => $produto->catmat,
            'fonte' => $validated['fonte'],
            'fonte_url' => $validated['fonte_url'] ?? null,
            'preco_unitario' => $validated['preco_unitario'],
            'badge' => $validated['badge'] ?? '⚪',
            'data_coleta' => now(),
        ]);

        Log::info('Preço adicionado manualmente ao catálogo', [
            'produto_id' => $produto->id,
            'preco' => $validated['preco_unitario'],
            'fonte' => $validated['fonte'],
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Preço adicionado ao histórico com sucesso',
            'historico' => $historico,
        ], 201);
    }

    /**
     * Buscar produtos no banco PNCP local
     *
     * GET /api/catalogo/buscar-pncp?termo=caneta&cnpj=00000000000191
     */
    public function buscarPNCP(Request $request)
    {
        try {
            $termo = $request->input('termo');
            $cnpj = $request->input('cnpj'); // Opcional

            if (!$termo || strlen($termo) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Termo de busca deve ter pelo menos 3 caracteres'
                ], 400);
            }

            Log::info('[CatalogoController] Buscando produtos (CATMAT+API primeiro, depois PNCP)', [
                'termo' => $termo,
                'cnpj' => $cnpj
            ]);

            $produtosFormatados = collect();

            // ===================================================================
            // ETAPA 1: BUSCAR NO CATMAT LOCAL + API DE PREÇOS COMPRAS.GOV
            // ===================================================================
            try {
                Log::info('[CatalogoController] [1/2] Buscando no CATMAT+API...');
                $produtosComprasGov = $this->buscarNoCATMATComPrecos($termo);

                if (!empty($produtosComprasGov)) {
                    Log::info('[CatalogoController] [1/2] CATMAT+API retornou ' . count($produtosComprasGov) . ' produtos');
                    $produtosFormatados = $produtosFormatados->merge($produtosComprasGov);
                } else {
                    Log::info('[CatalogoController] [1/2] CATMAT+API não retornou produtos');
                }
            } catch (\Exception $e) {
                Log::warning('[CatalogoController] [1/2] Erro no CATMAT+API (não impacta PNCP)', [
                    'erro' => $e->getMessage()
                ]);
            }

            // ===================================================================
            // ETAPA 2: BUSCAR NO PNCP LOCAL (mantém funcionalidade existente)
            // ===================================================================
            try {
                Log::info('[CatalogoController] [2/2] Buscando no PNCP local...');
                $produtos = ContratoPNCP::buscarPorTermo($termo, 12, 100);

                // Filtrar por CNPJ se fornecido
                if ($cnpj) {
                    $cnpjLimpo = preg_replace('/\D/', '', $cnpj);
                    $produtos = $produtos->filter(function($contrato) use ($cnpjLimpo) {
                        $contratoCNPJ = preg_replace('/\D/', '', $contrato->orgao_cnpj ?? '');
                        return $contratoCNPJ === $cnpjLimpo;
                    })->values();
                }

                // Formatar resultados PNCP
                $produtosPNCP = $produtos->map(function($contrato) {
                    return [
                        'id' => $contrato->id,
                        'descricao' => $contrato->objeto_contrato,
                        'valor_unitario' => $contrato->valor_unitario ?? $contrato->valor_global,
                        'unidade_medida' => $contrato->unidade_medida,
                        'tipo' => $contrato->tipo,
                        'orgao' => $contrato->orgao,
                        'orgao_uf' => $contrato->orgao_uf,
                        'data_publicacao' => $contrato->data_publicacao_pncp ? $contrato->data_publicacao_pncp->format('d/m/Y') : null,
                        'numero_controle_pncp' => $contrato->numero_controle_pncp,
                        'fonte' => 'PNCP'
                    ];
                });

                if ($produtosPNCP->count() > 0) {
                    Log::info('[CatalogoController] [2/2] PNCP retornou ' . $produtosPNCP->count() . ' produtos');
                    $produtosFormatados = $produtosFormatados->merge($produtosPNCP);
                } else {
                    Log::info('[CatalogoController] [2/2] PNCP não retornou produtos');
                }
            } catch (\Exception $e) {
                Log::warning('[CatalogoController] [2/2] Erro no PNCP local', [
                    'erro' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'total' => $produtosFormatados->count(),
                'produtos' => $produtosFormatados
            ]);

        } catch (\Exception $e) {
            Log::error('[CatalogoController] Erro geral ao buscar produtos', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar produtos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar orçamentos realizados para o catálogo
     *
     * GET /api/catalogo/orcamentos-realizados?termo=&uf=&periodo=&pagina=1
     */
    public function orcamentosRealizados(Request $request)
    {
        try {
            $termo = $request->input('termo');
            $uf = $request->input('uf');
            $periodo = $request->input('periodo'); // em dias
            $pagina = $request->input('pagina', 1);
            $porPagina = 12;

            // FILTRO MANUAL POR TENANT_ID

            Log::info('[CatalogoController] Buscando orçamentos realizados', [
                'termo' => $termo,
                'uf' => $uf,
                'periodo' => $periodo,
                'tenant_id' => $tenantId
            ]);

            // Query base: apenas orçamentos realizados DO TENANT
            $query = Orcamento::where('status', 'realizado')
                ->whereNotNull('data_conclusao')
                ->with(['itens', 'user']);

            // Filtro por termo (busca no objeto e nome)
            if ($termo) {
                $query->where(function($q) use ($termo) {
                    $q->where('objeto', 'ILIKE', "%{$termo}%")
                      ->orWhere('nome', 'ILIKE', "%{$termo}%");
                });
            }

            // Filtro por UF
            if ($uf) {
                $query->where('orcamentista_uf', $uf);
            }

            // Filtro por período
            if ($periodo) {
                $dataLimite = now()->subDays((int)$periodo);
                $query->where('data_conclusao', '>=', $dataLimite);
            }

            // Paginar
            $orcamentos = $query->orderBy('data_conclusao', 'desc')
                ->paginate($porPagina, ['*'], 'pagina', $pagina);

            // Formatar orçamentos com valor total calculado
            $orcamentosFormatados = $orcamentos->map(function($orc) {
                // Calcular valor total dos itens
                $valorTotal = 0;
                $totalItens = 0;

                foreach($orc->itens as $item) {
                    $valorTotal += ($item->preco_unitario ?? 0) * ($item->quantidade ?? 0);
                    $totalItens++;
                }

                return [
                    'id' => $orc->id,
                    'numero' => $orc->numero,
                    'nome' => $orc->nome,
                    'objeto' => $orc->objeto,
                    'orcamentista_razao_social' => $orc->orcamentista_razao_social,
                    'orcamentista_cidade' => $orc->orcamentista_cidade,
                    'orcamentista_uf' => $orc->orcamentista_uf,
                    'data_conclusao' => $orc->data_conclusao ? $orc->data_conclusao->format('Y-m-d') : null,
                    'valor_total' => $valorTotal,
                    'total_itens' => $totalItens,
                ];
            });

            // Calcular estatísticas gerais (FILTRADO POR TENANT)
            $todosOrcamentos = Orcamento::where('status', 'realizado')
                ->whereNotNull('data_conclusao')
                ->with('itens')
                ->get();

            $estatisticas = [
                'total' => $todosOrcamentos->count(),
                'valor_total' => 0,
                'total_itens' => 0,
                'estados' => $todosOrcamentos->pluck('orcamentista_uf')->filter()->unique()->count(),
            ];

            foreach($todosOrcamentos as $orc) {
                foreach($orc->itens as $item) {
                    $estatisticas['valor_total'] += ($item->preco_unitario ?? 0) * ($item->quantidade ?? 0);
                    $estatisticas['total_itens']++;
                }
            }

            return response()->json([
                'success' => true,
                'total' => $orcamentos->total(),
                'pagina_atual' => $orcamentos->currentPage(),
                'total_paginas' => $orcamentos->lastPage(),
                'orcamentos' => $orcamentosFormatados,
                'estatisticas' => $estatisticas
            ]);

        } catch (\Exception $e) {
            Log::error('[CatalogoController] Erro ao buscar orçamentos realizados', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar orçamentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar produtos (itens agrupados) dos orçamentos realizados
     *
     * GET /api/catalogo/produtos-locais?termo=&uf=&periodo=&pagina=1
     */
    public function produtosLocais(Request $request)
    {
        try {
            $termo = $request->input('termo');
            $uf = $request->input('uf');
            $periodo = $request->input('periodo'); // em dias
            $pagina = $request->input('pagina', 1);
            $porPagina = 12;

            // FILTRO MANUAL POR TENANT_ID
            // Pegar tenant_id do request (vem do middleware ProxyAuth)
            $tenantId = $request->attributes->get('tenant')['id'] ?? $request->header('x-tenant-id');

            Log::info('[CatalogoController] Buscando produtos locais', [
                'termo' => $termo,
                'uf' => $uf,
                'periodo' => $periodo,
                'tenant_id' => $tenantId
            ]);

            // Buscar todos os itens de orçamentos concluídos/realizados DO TENANT
            $query = OrcamentoItem::whereHas('orcamento', function($q) use ($uf, $periodo, $tenantId) {
                $q->where('status', 'realizado')
                  ->whereNotNull('data_conclusao');

                // Filtro por UF
                if ($uf) {
                    $q->where('orcamentista_uf', $uf);
                }

                // Filtro por período
                if ($periodo) {
                    $dataLimite = now()->subDays((int)$periodo);
                    $q->where('data_conclusao', '>=', $dataLimite);
                }
            })
            ->with(['orcamento']);

            // Filtro por termo (busca na descrição do item)
            if ($termo) {
                $query->where('descricao', 'ILIKE', "%{$termo}%");
            }

            // Agrupar por descrição similar e coletar dados
            $todosItens = $query->get();

            // Agrupar produtos por descrição normalizada
            $produtosAgrupados = [];

            // Lista de palavras que indicam descrições inúteis
            $descricoesInvalidas = ['lote', 'nome legível', 'nome legivel', 'cargo', 'função', 'funcao'];

            foreach($todosItens as $item) {
                // Normalizar descrição (remover espaços extras, maiúsculas)
                $descricaoNormalizada = trim(strtoupper($item->descricao));
                $descricaoLower = strtolower($item->descricao);

                // Verificar se a descrição contém palavras inválidas
                $descricaoInvalida = false;
                foreach ($descricoesInvalidas as $palavraInvalida) {
                    if (stripos($descricaoLower, $palavraInvalida) !== false) {
                        $descricaoInvalida = true;
                        break;
                    }
                }

                // Pular se a descrição for inválida
                if ($descricaoInvalida) {
                    continue;
                }

                // Pular se o preço for 0 ou nulo
                if (!$item->preco_unitario || $item->preco_unitario <= 0) {
                    continue;
                }

                if (!isset($produtosAgrupados[$descricaoNormalizada])) {
                    $produtosAgrupados[$descricaoNormalizada] = [
                        'descricao' => $item->descricao,
                        'medida_fornecimento' => $item->medida_fornecimento,
                        'precos' => [],
                        'quantidade_total' => 0,
                        'orcamentos_ids' => [],
                        'orgaos' => [],
                        'ufs' => [],
                    ];
                }

                // Adicionar dados do item
                $produtosAgrupados[$descricaoNormalizada]['precos'][] = [
                    'preco_unitario' => (float)$item->preco_unitario,
                    'quantidade' => (float)$item->quantidade,
                    'orcamento_numero' => $item->orcamento->numero,
                    'data_conclusao' => $item->orcamento->data_conclusao ? $item->orcamento->data_conclusao->format('Y-m-d') : null,
                    'orgao' => $item->orcamento->orcamentista_razao_social,
                    'uf' => $item->orcamento->orcamentista_uf,
                ];

                $produtosAgrupados[$descricaoNormalizada]['quantidade_total'] += (float)$item->quantidade;

                if (!in_array($item->orcamento_id, $produtosAgrupados[$descricaoNormalizada]['orcamentos_ids'])) {
                    $produtosAgrupados[$descricaoNormalizada]['orcamentos_ids'][] = $item->orcamento_id;
                }

                if (!in_array($item->orcamento->orcamentista_razao_social, $produtosAgrupados[$descricaoNormalizada]['orgaos'])) {
                    $produtosAgrupados[$descricaoNormalizada]['orgaos'][] = $item->orcamento->orcamentista_razao_social;
                }

                if (!in_array($item->orcamento->orcamentista_uf, $produtosAgrupados[$descricaoNormalizada]['ufs'])) {
                    $produtosAgrupados[$descricaoNormalizada]['ufs'][] = $item->orcamento->orcamentista_uf;
                }
            }

            // Calcular estatísticas de cada produto
            $produtosFormatados = collect($produtosAgrupados)->map(function($produto) {
                $precos = array_column($produto['precos'], 'preco_unitario');

                return [
                    'descricao' => $produto['descricao'],
                    'medida_fornecimento' => $produto['medida_fornecimento'],
                    'quantidade_orcamentos' => count($produto['orcamentos_ids']),
                    'quantidade_total' => $produto['quantidade_total'],
                    'preco_minimo' => !empty($precos) ? min($precos) : 0,
                    'preco_maximo' => !empty($precos) ? max($precos) : 0,
                    'preco_medio' => !empty($precos) ? array_sum($precos) / count($precos) : 0,
                    'quantidade_registros' => count($produto['precos']),
                    'orgaos' => implode(', ', array_slice($produto['orgaos'], 0, 3)) . (count($produto['orgaos']) > 3 ? '...' : ''),
                    'ufs' => implode(', ', array_unique($produto['ufs'])),
                    'historico_precos' => $produto['precos'], // Histórico completo
                ];
            })->values();

            // Ordenar por quantidade de orçamentos (produtos mais usados primeiro)
            $produtosOrdenados = $produtosFormatados->sortByDesc('quantidade_orcamentos')->values();

            // Paginar manualmente
            $total = $produtosOrdenados->count();
            $offset = ($pagina - 1) * $porPagina;
            $produtosPaginados = $produtosOrdenados->slice($offset, $porPagina)->values();

            // Calcular estatísticas gerais (FILTRADO POR TENANT)
            $estatisticas = [
                'total_produtos' => $total,
                'total_registros' => $todosItens->count(),
                'estados' => count(array_unique($todosItens->pluck('orcamento.orcamentista_uf')->filter()->toArray())),
            ];

            return response()->json([
                'success' => true,
                'total' => $total,
                'pagina_atual' => $pagina,
                'total_paginas' => ceil($total / $porPagina),
                'produtos' => $produtosPaginados,
                'estatisticas' => $estatisticas
            ]);

        } catch (\Exception $e) {
            Log::error('[CatalogoController] Erro ao buscar produtos locais', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar produtos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ========================================================================
     * MÉTODO AUXILIAR: BUSCAR NO CATMAT LOCAL + API DE PREÇOS COMPRAS.GOV
     * ========================================================================
     *
     * Estratégia:
     * 1. Buscar materiais no CATMAT local (Full-Text Search PostgreSQL)
     * 2. Para cada código CATMAT encontrado, consultar API de Preços
     * 3. Retornar produtos com preços reais praticados
     *
     * @param string $termo Termo de busca
     * @return array Array de produtos formatados
     */
    private function buscarNoCATMATComPrecos($termo)
    {
        try {
            Log::info('🟢 [CatalogoController] CATMAT+API: Iniciando busca', ['termo' => $termo]);

            // Escapar termo para segurança
            $termoEscapado = preg_replace('/[^a-zA-Z0-9À-ÿ\s]/', '', $termo);

            // PASSO 1: Buscar no CATMAT local (Full-Text Search 'simple' sem stemming)
            $materiaisCandidatos = DB::table('cp_catmat')
                ->select('codigo', 'titulo', 'caminho_hierarquia')
                ->whereRaw(
                    "to_tsvector('simple', titulo) @@ plainto_tsquery('simple', ?)",
                    [$termoEscapado]
                )
                ->where('ativo', true)
                ->orderBy('contador_ocorrencias', 'desc')
                ->limit(100) // Buscar mais para filtrar depois
                ->get();

            // ✅ FILTRO DE PRECISÃO: Validar palavra COMPLETA no título
            $materiais = $materiaisCandidatos->filter(function($material) use ($termoEscapado) {
                $tituloNormalizado = mb_strtoupper($material->titulo, 'UTF-8');
                $termoNormalizado = mb_strtoupper($termoEscapado, 'UTF-8');
                $pattern = '/\b' . preg_quote($termoNormalizado, '/') . '\b/u';
                return preg_match($pattern, $tituloNormalizado);
            })->take(10); // Limitar a 10 após filtragem

            if ($materiais->isEmpty()) {
                Log::info('🟢 [CatalogoController] CATMAT+API: Nenhum material encontrado no CATMAT');
                return [];
            }

            Log::info('🟢 [CatalogoController] CATMAT+API: Encontrados ' . $materiais->count() . ' materiais no CATMAT');

            $produtos = [];
            $totalPrecos = 0;
            $maxPrecos = 50; // Limite total de preços retornados

            // PASSO 2: Para cada material CATMAT, buscar preços na API
            foreach ($materiais as $material) {
                if ($totalPrecos >= $maxPrecos) {
                    break;
                }

                usleep(200000); // Delay de 200ms entre requisições

                try {
                    $response = Http::withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'DattaTech-CestaPrecos/1.0'
                    ])
                    ->timeout(10)
                    ->get('https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial', [
                        'codigoItemCatalogo' => $material->codigo,
                        'pagina' => 1,
                        'tamanhoPagina' => 20
                    ]);

                    if (!$response->successful()) {
                        Log::warning('🟢 [CatalogoController] CATMAT+API: Erro na API', [
                            'codigo' => $material->codigo,
                            'status' => $response->status()
                        ]);
                        continue;
                    }

                    $dadosAPI = $response->json();
                    $precos = $dadosAPI['content'] ?? [];

                    if (empty($precos)) {
                        continue;
                    }

                    // PASSO 3: Processar cada preço retornado
                    foreach ($precos as $preco) {
                        if ($totalPrecos >= $maxPrecos) {
                            break 2;
                        }

                        $valorUnitario = floatval($preco['precoUnitario'] ?? 0);
                        if ($valorUnitario <= 0) {
                            continue;
                        }

                        $produtos[] = [
                            'id' => null,
                            'descricao' => $preco['descricaoItem'] ?? $material->titulo,
                            'valor_unitario' => $valorUnitario,
                            'unidade_medida' => $preco['siglaUnidadeFornecimento'] ?? 'UN',
                            'tipo' => 'preco_praticado',
                            'orgao' => $preco['nomeUasg'] ?? $preco['nomeOrgao'] ?? 'Não informado',
                            'orgao_uf' => $preco['estado'] ?? null,
                            'data_publicacao' => isset($preco['dataResultado'])
                                ? date('d/m/Y', strtotime($preco['dataResultado']))
                                : null,
                            'numero_controle_pncp' => null,
                            'fonte' => 'COMPRAS.GOV',
                            'codigo_catmat' => $material->codigo,
                            'categoria' => $material->caminho_hierarquia
                        ];

                        $totalPrecos++;
                    }

                } catch (\Exception $e) {
                    Log::warning('🟢 [CatalogoController] CATMAT+API: Erro ao consultar API para material', [
                        'codigo' => $material->codigo,
                        'erro' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            Log::info('🟢 [CatalogoController] CATMAT+API: Busca concluída', [
                'total_produtos' => count($produtos),
                'materiais_consultados' => $materiais->count()
            ]);

            return $produtos;

        } catch (\Exception $e) {
            Log::error('🟢 [CatalogoController] CATMAT+API: Erro geral na busca', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}
