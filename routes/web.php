<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\InternalOnly;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrcamentoController;
use App\Http\Controllers\CotacaoExternaController;
use App\Http\Controllers\CnpjController;

/*
|--------------------------------------------------------------------------
| Web Routes - Módulo Cesta de Preços
|--------------------------------------------------------------------------
*/

// Rotas públicas de autenticação
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Health check para o proxy verificar se o módulo está online
Route::get('/health', function () {
    return response()->json([
        'status' => 'online',
        'module' => 'cestadeprecos',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String()
    ]);
});

// Rota de teste simples
Route::get('/test-route', function () {
    return 'TEST ROUTE WORKS!';
});

// Rota para brasões
Route::get('/brasao/{filename}', function ($filename) {
    return 'Brasão route called: ' . $filename;
});

// Preview de orçamento (público - não precisa autenticação)
Route::get('/orcamentos/{id}/preview', [OrcamentoController::class, 'preview'])->name('orcamentos.preview.public');

// 📄 Gerar PDF do Orçamento Estimativo (layout completinho.pdf)
Route::get('/orcamentos/{id}/pdf', [OrcamentoController::class, 'gerarPDF'])->name('orcamentos.pdf');

// Busca de orçamentos via AJAX (público para funcionar no iframe)
Route::get('/orcamentos/buscar', [OrcamentoController::class, 'buscar'])->name('orcamentos.buscar.public');

// Busca de itens no PNCP via AJAX (público para funcionar no iframe)
// USA O MÉTODO ORIGINAL QUE FUNCIONA (com busca em 5 páginas + agrupamento)
Route::get('/pncp/buscar', [OrcamentoController::class, 'buscarPNCP'])->name('pncp.buscar.public');

// Busca no Compras.gov (CATMAT + API de Preços) - Público para funcionar no modal
Route::get('/compras-gov/buscar', function(\Illuminate\Http\Request $request) {
    $termo = $request->input('termo', '');

    if (strlen($termo) < 3) {
        return response()->json([
            'success' => false,
            'message' => 'Digite pelo menos 3 caracteres',
            'resultados' => []
        ]);
    }

    try {
        // PASSO 1: Buscar materiais no CATMAT usando conexão pgsql_main diretamente
        $termoNormalizado = preg_replace('/(\d+)(GB|TB|MB|KB|ML|MG|KG|CM|MM|POL)/i', '$1 $2', $termo);

        // Usar conexão direta via DB facade (sem Model)
        $query = \DB::connection('pgsql_main')->table('cp_catmat')
            ->select('codigo', 'titulo')
            ->where('ativo', true);
            // ✅ FIX 31/10/2025: Removido filtro tem_preco_comprasgov para buscar em TODOS os códigos
            // Motivo: Apenas 1% dos códigos tinham flag true, causando zero resultados
            // Agora busca em todos os 336k códigos e tenta obter preços da API

        // Detectar se o termo contém números ou unidades de medida
        $temNumeroOuUnidade = preg_match('/\d+|GB|TB|MB|KB|ML|MG|KG|CM|MM|POL/i', $termo);

        if ($temNumeroOuUnidade) {
            // Busca híbrida: Full-text OU ILIKE flexível
            // Para "arroz 5kg", buscar: (arroz AND kg) OR (arroz 5kg junto)
            $palavras = preg_split('/\s+/', $termoNormalizado);

            $query->where(function($q) use ($termo, $termoNormalizado, $palavras) {
                // Tentar full-text primeiro
                $q->whereRaw(
                    "to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', ?)",
                    [$termo]
                );

                // OU buscar termo completo (com e sem espaços)
                $q->orWhere('titulo', 'ILIKE', '%' . str_replace(' ', '', $termoNormalizado) . '%');
                $q->orWhere('titulo', 'ILIKE', '%' . $termoNormalizado . '%');

                // OU buscar onde PALAVRAS PRINCIPAIS aparecem (ignorar números isolados)
                $palavrasPrincipais = array_filter($palavras, function($p) {
                    $p = trim($p);
                    // Manter palavras com >2 caracteres OU que sejam unidades conhecidas
                    return strlen($p) > 2 || preg_match('/^(kg|gb|mb|tb|ml|mg|cm|mm|un)$/i', $p);
                });

                if (!empty($palavrasPrincipais)) {
                    $q->orWhere(function($subq) use ($palavrasPrincipais) {
                        foreach ($palavrasPrincipais as $palavra) {
                            $subq->where('titulo', 'ILIKE', '%' . trim($palavra) . '%');
                        }
                    });
                }
            });
        } else {
            // Busca apenas full-text
            $query->whereRaw(
                "to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', ?)",
                [$termo]
            );
        }

        $materiais = $query
            ->orderBy('contador_ocorrencias', 'desc')
            ->limit(100) // ✅ 31/10/2025: Aumentado de 30→100 para mais resultados Compras.gov
            ->get();

        if ($materiais->isEmpty()) {
            return response()->json([
                'success' => true,
                'total' => 0,
                'resultados' => []
            ]);
        }

        $resultados = [];

        // PASSO 2: Para cada material, buscar preços na API
        foreach ($materiais as $material) {
            try {
                $urlPrecos = 'https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial';

                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Accept' => '*/*',
                    'User-Agent' => 'DattaTech-CestaPrecos/1.0'
                ])
                ->timeout(10)
                ->get($urlPrecos, [
                    'codigoItemCatalogo' => $material->codigo,
                    'pagina' => 1,
                    'tamanhoPagina' => 500 // ✅ 31/10/2025: Aumentado de 100→500
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $precos = $data['resultado'] ?? [];

                    foreach ($precos as $preco) {
                        $resultados[] = [
                            'id' => 'comprasgov_' . uniqid(),
                            'descricao' => $material->titulo,
                            'laboratorio' => $preco['nomeFornecedor'] ?? 'Não informado',
                            'valor_unitario' => (float) ($preco['precoUnitario'] ?? 0),
                            'unidade_medida' => $preco['siglaUnidadeFornecimento'] ?? 'UN',
                            'medida_fornecimento' => $preco['siglaUnidadeFornecimento'] ?? 'UN',
                            'quantidade' => 1,
                            'fonte' => 'COMPRAS.GOV',
                            'orgao' => $preco['nomeOrgao'] ?? $preco['nomeUasg'] ?? null,
                            'orgao_nome' => $preco['nomeOrgao'] ?? $preco['nomeUasg'] ?? null,
                            'orgao_codigo' => $preco['codigoOrgao'] ?? $preco['codigoUasg'] ?? null,
                            'orgao_uf' => $preco['ufOrgao'] ?? null,
                            'data' => isset($preco['dataCompra']) ? date('d/m/Y', strtotime($preco['dataCompra'])) : null,
                            'data_publicacao' => isset($preco['dataCompra']) ? date('Y-m-d', strtotime($preco['dataCompra'])) : null,
                            'municipio' => $preco['municipioFornecedor'] ?? null,
                            'municipio_nome' => $preco['municipioFornecedor'] ?? null,
                            'uf' => $preco['ufFornecedor'] ?? null,
                            'uf_sigla' => $preco['ufFornecedor'] ?? null,
                            'marca' => $preco['nomeFornecedor'] ?? 'Não informado',
                            'razao_social_fornecedor' => $preco['nomeFornecedor'] ?? 'Não informado',
                            'cnpj_fornecedor' => $preco['niFornecedor'] ?? null,
                            'catmat' => $material->codigo,
                            'cnpj' => $preco['niFornecedor'] ?? null
                        ];

                        // ✅ 31/10/2025: Aumentado de 300→2000 para mais resultados
                        if (count($resultados) >= 2000) {
                            break 2;
                        }
                    }
                }

                usleep(200000); // 0.2 segundos entre requisições

            } catch (\Exception $e) {
                \Log::debug('Erro ao buscar preços do CATMAT ' . $material->codigo);
                continue;
            }
        }

        // FILTRAR valores zerados ANTES de retornar
        $totalAntes = count($resultados);
        $resultados = array_filter($resultados, function($resultado) {
            return ($resultado['valor_unitario'] ?? 0) > 0;
        });
        $resultados = array_values($resultados); // Reindexar array
        $totalRemovidos = $totalAntes - count($resultados);

        if ($totalRemovidos > 0) {
            \Log::info("🚫 Compras.gov: {$totalRemovidos} resultado(s) com valor zerado removido(s)");
        }

        return response()->json([
            'success' => true,
            'total' => count($resultados),
            'resultados' => $resultados
        ]);

    } catch (\Exception $e) {
        \Log::error('[Compras.gov API] Erro geral: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erro ao buscar no Compras.gov: ' . $e->getMessage(),
            'resultados' => []
        ], 500);
    }
})->name('compras-gov.buscar.public');

// ✅ TESTE DE DEBUG: Endpoint que sempre retorna dados mockados
Route::get('/pncp/teste-debug', function() {
    \Log::info('🧪 Endpoint de teste chamado!');
    return response()->json([
        'success' => true,
        'resultados' => [
            [
                'descricao' => 'TESTE: Item mockado 1',
                'valor_unitario' => 10.50,
                'unidade' => 'UN',
                'quantidade' => 1,
                'orgao' => 'Órgão Teste',
                'municipio' => 'Cidade Teste',
                'uf' => 'MG',
                'fonte' => 'TESTE'
            ],
            [
                'descricao' => 'TESTE: Item mockado 2',
                'valor_unitario' => 25.00,
                'unidade' => 'KG',
                'quantidade' => 1,
                'orgao' => 'Órgão Teste 2',
                'municipio' => 'Outra Cidade',
                'uf' => 'SP',
                'fonte' => 'TESTE'
            ]
        ],
        'total_encontrado' => 2,
        'fonte' => 'TESTE_DEBUG'
    ]);
});

// Busca multi-fonte (PNCP + Banco de Preços + ComprasNet + Local)
Route::get('/pesquisa/buscar', [App\Http\Controllers\PesquisaRapidaController::class, 'buscar'])->name('pesquisa.buscar.public');

// API de consulta CNPJ (público para funcionar no iframe com ProxyAuth)
Route::post('/api/cnpj/consultar', [CnpjController::class, 'consultar'])->name('cnpj.consultar');

// Rota raiz - mostrar dashboard se autenticado, senão login
Route::get('/', function () {
    if (auth()->check()) {
        // Não redirecionar, renderizar diretamente para evitar loop
        return app(AuthController::class)->dashboard();
    }
    return redirect()->route('login');
});

// Rotas protegidas por autenticação
Route::middleware(['ensure.authenticated'])->group(function () {

    // Dashboard principal
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    // Configurações do Órgão
    Route::get('/configuracoes', [App\Http\Controllers\ConfiguracaoController::class, 'index'])->name('configuracoes.index');
    Route::post('/configuracoes', [App\Http\Controllers\ConfiguracaoController::class, 'update'])->name('configuracoes.update');
    Route::post('/configuracoes/buscar-cnpj', [App\Http\Controllers\ConfiguracaoController::class, 'buscarCNPJ'])->name('configuracoes.buscarCNPJ');
    Route::post('/configuracoes/upload-brasao', [App\Http\Controllers\ConfiguracaoController::class, 'uploadBrasao'])->name('configuracoes.uploadBrasao');
    Route::delete('/configuracoes/deletar-brasao', [App\Http\Controllers\ConfiguracaoController::class, 'deletarBrasao'])->name('configuracoes.deletarBrasao');

    // Pesquisa Rápida
    Route::get('/pesquisa-rapida', function () {
        return view('pesquisa-rapida');
    })->name('pesquisa.rapida');

    // Criar Orçamento a partir de itens da Pesquisa Rápida
    Route::post('/pesquisa-rapida/criar-orcamento', [App\Http\Controllers\PesquisaRapidaController::class, 'criarOrcamento'])->name('pesquisa.rapida.criar.orcamento');

    // CDFs Enviadas
    Route::get('/cdfs-enviadas', [App\Http\Controllers\CdfRespostaController::class, 'listarCdfs'])->name('cdfs.enviadas');

    // Cotação de Preços (página dedicada)
    Route::get('/orcamentos/{orcamento}/item/{item}/cotar', [OrcamentoController::class, 'cotarPrecos'])->name('orcamento.item.cotar');

    // Mapa de Atas
    Route::get('/mapa-de-atas', function () {
        return view('mapa-de-atas');
    })->name('mapa.atas');

    // Mapa de Fornecedores
    Route::get('/mapa-de-fornecedores', function () {
        return view('mapa-de-fornecedores');
    })->name('mapa.fornecedores');

    // Catálogo de Produtos
    Route::get('/catalogo', function () {
        return view('catalogo');
    })->name('catalogo');

    // Rotas de API do Catálogo (sem prefixo api/)
    Route::get('/catalogo/produtos-locais', [App\Http\Controllers\CatalogoController::class, 'produtosLocais'])->name('catalogo.produtosLocais');
    Route::get('/catalogo/buscar-pncp', [App\Http\Controllers\CatalogoController::class, 'buscarPNCP'])->name('catalogo.buscarPNCP');

    // Mapa de Atas - Busca na API PNCP
    Route::get('/mapa-de-atas/buscar', [App\Http\Controllers\MapaAtasController::class, 'buscar'])->name('mapa.atas.buscar');

    // Fornecedores
    Route::prefix('fornecedores')->name('fornecedores.')->group(function () {
        // Listagem
        Route::get('/', [App\Http\Controllers\FornecedorController::class, 'index'])->name('index');

        // Cadastro
        Route::post('/', [App\Http\Controllers\FornecedorController::class, 'store'])->name('store');

        // Consultar CNPJ na Receita Federal
        Route::get('/consultar-cnpj/{cnpj}', [App\Http\Controllers\FornecedorController::class, 'consultarCNPJ'])->name('consultar-cnpj');

        // Download modelo planilha
        Route::get('/modelo-planilha', [App\Http\Controllers\FornecedorController::class, 'downloadModelo'])->name('modelo-planilha');

        // Importar planilha
        Route::post('/importar', [App\Http\Controllers\FornecedorController::class, 'importarPlanilha'])->name('importar');

        // Buscar fornecedores por item/serviço (Mapa de Fornecedores)
        Route::get('/buscar-por-item', [App\Http\Controllers\FornecedorController::class, 'buscarPorItem'])->name('buscar-por-item');

        // Listar todos os fornecedores locais (para modal de importação)
        Route::get('/listar-local', [App\Http\Controllers\FornecedorController::class, 'listarLocal'])->name('listar-local');

        // Buscar por código CATMAT (Pesquisa Rápida - local + API externa)
        Route::get('/buscar-por-codigo', [App\Http\Controllers\FornecedorController::class, 'buscarPorCodigo'])->name('buscar-por-codigo');

        // Visualizar, editar e excluir
        Route::get('/{id}', [App\Http\Controllers\FornecedorController::class, 'show'])->name('show');
        Route::put('/{id}', [App\Http\Controllers\FornecedorController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\FornecedorController::class, 'destroy'])->name('destroy');
    });

    // Rotas CMED (Medicamentos)
    Route::prefix('cmed')->name('cmed.')->group(function () {
        // Buscar medicamentos (para modal de cotação)
        Route::get('/buscar', function(\Illuminate\Http\Request $request) {
            $termo = $request->input('termo', '');

            if (strlen($termo) < 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Digite pelo menos 3 caracteres',
                    'medicamentos' => []
                ]);
            }

            $medicamentos = \App\Models\MedicamentoCmed::buscarPorTermo($termo, 100);

            $resultado = $medicamentos->map(function($med) {
                return [
                    'id' => $med->id,
                    'descricao' => $med->produto . ' - ' . $med->substancia,
                    'laboratorio' => $med->laboratorio,
                    'valor_unitario' => (float) $med->pmc_0,
                    'pmc_0' => (float) $med->pmc_0,
                    'pmc_12' => (float) $med->pmc_12,
                    'pmc_17' => (float) $med->pmc_17,
                    'pmc_18' => (float) $med->pmc_18,
                    'pmc_20' => (float) $med->pmc_20,
                    'unidade' => 'UN',
                    'quantidade' => 1,
                    'fonte' => 'CMED',
                    'orgao' => 'ANVISA/CMED - Brasília/DF',
                    'orgao_nome' => 'ANVISA/CMED - Brasília/DF',
                    'data' => $med->data_importacao ? $med->data_importacao->format('d/m/Y') : now()->format('d/m/Y'),
                    'municipio' => 'Brasília',
                    'uf' => 'DF',
                    'marca' => $med->laboratorio,
                    'mes_referencia' => $med->mes_referencia ?? 'Outubro 2025'
                ];
            });

            return response()->json([
                'success' => true,
                'total' => $resultado->count(),
                'resultados' => $resultado->toArray()
            ]);
        })->name('buscar');
    });

    // ❌ Rotas COMPRAS.GOV removidas daqui - Movidas para rotas públicas (linha 42)

    // Rotas de Cotação Externa
    Route::prefix('cotacao-externa')->name('cotacao-externa.')->group(function () {
        Route::get('/', [App\Http\Controllers\CotacaoExternaController::class, 'index'])->name('index');
        Route::post('/upload', [App\Http\Controllers\CotacaoExternaController::class, 'upload'])->name('upload');
        Route::post('/atualizar-dados/{id}', [App\Http\Controllers\CotacaoExternaController::class, 'atualizarDados'])->name('atualizarDados');
        Route::post('/salvar-orcamentista/{id}', [App\Http\Controllers\CotacaoExternaController::class, 'salvarOrcamentista'])->name('salvarOrcamentista');
        Route::get('/preview/{id}', [App\Http\Controllers\CotacaoExternaController::class, 'preview'])->name('preview');
        Route::post('/concluir/{id}', [App\Http\Controllers\CotacaoExternaController::class, 'concluir'])->name('concluir');
    });

    // Rotas de Orçamentos
    Route::prefix('orcamentos')->name('orcamentos.')->group(function () {
        // Formulário de criação
        Route::get('/novo', [OrcamentoController::class, 'create'])->name('create');
        Route::post('/novo', [OrcamentoController::class, 'store'])->name('store');

        // Importar documento e criar orçamento automaticamente
        Route::post('/processar-documento', [OrcamentoController::class, 'importarDocumento'])->name('processarDocumento');

        // Listagens
        Route::get('/pendentes', [OrcamentoController::class, 'pendentes'])->name('pendentes');
        Route::get('/realizados', [OrcamentoController::class, 'realizados'])->name('realizados');

        // Manter rota antiga "concluidos" redirecionando para "realizados"
        Route::get('/concluidos', function () {
            return redirect()->route('orcamentos.realizados');
        })->name('concluidos');

        // Elaboração do orçamento (DEVE VIR ANTES DE /{id} para não conflitar)
        Route::get('/{id}/elaborar', [OrcamentoController::class, 'elaborar'])->name('elaborar');

        // Imprimir orçamento (PDF)
        Route::get('/{id}/imprimir', [OrcamentoController::class, 'imprimir'])->name('imprimir');

        // Exportar orçamento para Excel
        Route::get('/{id}/exportar-excel', [OrcamentoController::class, 'exportarExcel'])->name('exportarExcel');

        // Rotas para gerenciar itens e lotes do orçamento
        Route::post('/{id}/itens', [OrcamentoController::class, 'storeItem'])->name('itens.store');
        Route::patch('/{id}/itens/{item_id}', [OrcamentoController::class, 'updateItem'])->name('itens.update');
        Route::patch('/{id}/itens/{item_id}/fornecedor', [OrcamentoController::class, 'updateItemFornecedor'])->name('itens.updateFornecedor');
        Route::post('/{id}/itens/{item_id}/criticas', [OrcamentoController::class, 'updateItemCriticas'])->name('itens.updateCriticas');
        Route::delete('/{id}/itens/{item_id}', [OrcamentoController::class, 'destroyItem'])->name('itens.destroy');
        Route::patch('/{id}/itens/{item_id}/renumerar', [OrcamentoController::class, 'renumerarItem'])->name('itens.renumerar');
        Route::post('/{id}/itens/{item_id}/salvar-amostras', [OrcamentoController::class, 'salvarAmostras'])->name('itens.salvarAmostras');

        // FASE 2: Estatísticas e Saneamento
        Route::post('/{id}/itens/{item_id}/aplicar-saneamento', [OrcamentoController::class, 'aplicarSaneamento'])->name('itens.aplicarSaneamento');
        Route::post('/{id}/itens/{item_id}/fixar-snapshot', [OrcamentoController::class, 'fixarSnapshot'])->name('itens.fixarSnapshot');
        Route::post('/{id}/calcular-e-salvar-curva-abc', [OrcamentoController::class, 'calcularESalvarCurvaABC'])->name('calcularESalvarCurvaABC');

        Route::get('/{id}/itens/{item_id}/amostras', [OrcamentoController::class, 'obterAmostras'])->name('itens.obterAmostras');
        Route::get('/{id}/itens/{item_id}/justificativas', [OrcamentoController::class, 'buscarJustificativasItem'])->name('itens.justificativas');
        Route::get('/{id}/itens/{item_id}/audit-logs', [OrcamentoController::class, 'getAuditLogs'])->name('itens.auditLogs');
        Route::get('/{id}/itens/{item_id}/snapshot', [OrcamentoController::class, 'getSnapshot'])->name('itens.getSnapshot');
        Route::post('/{id}/lotes', [OrcamentoController::class, 'storeLote'])->name('lotes.store');
        Route::post('/{id}/importar-planilha', [OrcamentoController::class, 'importPlanilha'])->name('importPlanilha');
        Route::post('/{id}/coleta-ecommerce', [OrcamentoController::class, 'storeColetaEcommerce'])->name('coletaEcommerce.store');
        Route::post('/{id}/solicitar-cdf', [OrcamentoController::class, 'storeSolicitarCDF'])->name('solicitarCDF.store');
        Route::post('/{id}/contratacoes-similares', [OrcamentoController::class, 'storeContratacoesSimilares'])->name('contratacoesSimilares.store');

        // Rota para salvar preço do item via AJAX (modal de cotação)
        Route::post('/{id}/salvar-preco-item', [OrcamentoController::class, 'salvarPrecoItem'])->name('salvarPrecoItem');

        // Salvar dados do orçamentista (Seção 6)
        Route::post('/{id}/salvar-orcamentista', [OrcamentoController::class, 'salvarOrcamentista'])->name('salvarOrcamentista');

        // Salvar preço de item após cotação (Modal de Cotação)
        Route::post('/{id}/salvar-preco-item', [OrcamentoController::class, 'salvarPrecoItem'])->name('salvarPrecoItem');

        // Consultar CNPJ na ReceitaWS via backend (evitar CORS)
        Route::get('/consultar-cnpj/{cnpj}', [OrcamentoController::class, 'consultarCNPJ'])->name('consultarCNPJ');

        // Salvar metodologias (Seção 2)
        Route::patch('/{id}/metodologias', [OrcamentoController::class, 'updateMetodologias'])->name('metodologias.update');

        // Rotas de CDF (Cotação Direta com Fornecedor)
        Route::get('/{id}/cdf/{cdf_id}', [OrcamentoController::class, 'getCDF'])->name('cdf.get');
        Route::delete('/{id}/cdf/{cdf_id}', [OrcamentoController::class, 'destroyCDF'])->name('cdf.destroy');
        Route::post('/{id}/cdf/{cdf_id}/primeiro-passo', [OrcamentoController::class, 'primeiroPassoCDF'])->name('cdf.primeiroPasso');
        Route::post('/{id}/cdf/{cdf_id}/segundo-passo', [OrcamentoController::class, 'segundoPassoCDF'])->name('cdf.segundoPasso');
        Route::get('/{id}/cdf/{cdf_id}/baixar-oficio', [OrcamentoController::class, 'baixarOficioCDF'])->name('cdf.baixarOficio');
        Route::get('/{id}/cdf/{cdf_id}/baixar-formulario', [OrcamentoController::class, 'baixarFormularioCDF'])->name('cdf.baixarFormulario');
        Route::get('/{id}/cdf/{cdf_id}/baixar-cnpj', [OrcamentoController::class, 'baixarEspelhoCNPJ'])->name('cdf.baixarCNPJ');
        Route::get('/{id}/cdf/{cdf_id}/baixar-comprovante', [OrcamentoController::class, 'baixarComprovanteCDF'])->name('cdf.baixarComprovante');
        Route::get('/{id}/cdf/{cdf_id}/baixar-cotacao', [OrcamentoController::class, 'baixarCotacaoCDF'])->name('cdf.baixarCotacao');

        // Concluir cotação (preview está fora do middleware - rota pública)
        Route::post('/{id}/concluir', [OrcamentoController::class, 'concluir'])->name('concluir');

        // Visualizar detalhes
        Route::get('/{id}', [OrcamentoController::class, 'show'])->name('show');

        // Edição
        Route::get('/{id}/editar', [OrcamentoController::class, 'edit'])->name('edit');
        Route::put('/{id}', [OrcamentoController::class, 'update'])->name('update');

        // Ações
        Route::post('/{id}/marcar-realizado', [OrcamentoController::class, 'marcarRealizado'])->name('marcarRealizado');
        Route::post('/{id}/marcar-pendente', [OrcamentoController::class, 'marcarPendente'])->name('marcarPendente');
        Route::delete('/{id}', [OrcamentoController::class, 'destroy'])->name('destroy');
    });

    // Rota para baixar espelho CNPJ (fora do grupo de orçamentos para acesso direto)
    Route::post('/baixar-espelho-cnpj', [OrcamentoController::class, 'baixarEspelhoCNPJSimples'])->name('baixarEspelhoCNPJSimples');

// Informações sobre o módulo (debug)
Route::get('/info', function () {
    if (config('app.env') !== 'local') {
        abort(404);
    }

    return response()->json([
        'module' => 'Cesta de Preços',
        'environment' => config('app.env'),
        'tenant' => request()->attributes->get('tenant'),
        'user' => request()->attributes->get('user'),
        'headers' => request()->headers->all(),
        'prefix' => config('database.connections.pgsql.prefix')
    ]);
});

/*
|--------------------------------------------------------------------------
| Rotas de Orientações Técnicas
|--------------------------------------------------------------------------
*/
Route::get('/orientacoes-tecnicas', [App\Http\Controllers\OrientacaoTecnicaController::class, 'index'])->name('orientacoes.index');
Route::get('/orientacoes-tecnicas/buscar', [App\Http\Controllers\OrientacaoTecnicaController::class, 'buscar'])->name('orientacoes.buscar');

/*
|--------------------------------------------------------------------------
| Rotas de Funcionalidades (A serem implementadas conforme especificação)
|--------------------------------------------------------------------------
*/

// API Routes
Route::prefix('api')->group(function () {

    Route::get('/status', function () {
        return response()->json([
            'message' => 'API do módulo Cesta de Preços',
            'status' => 'ready',
            'tenant' => request()->attributes->get('tenant')['subdomain'] ?? 'unknown'
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | API CATMAT - Autocomplete e Busca de Códigos
    |--------------------------------------------------------------------------
    */
    Route::prefix('catmat')->name('api.catmat.')->group(function () {
        Route::get('/suggest', [App\Http\Controllers\CatmatController::class, 'suggest'])->name('suggest');
        Route::get('/{codigo}', [App\Http\Controllers\CatmatController::class, 'show'])->name('show');
        Route::get('/', [App\Http\Controllers\CatmatController::class, 'index'])->name('index');
        Route::post('/auto-registro', [App\Http\Controllers\CatmatController::class, 'autoRegistro'])->name('autoRegistro');
    });

    /*
    |--------------------------------------------------------------------------
    | API Mapa de Atas - Busca de ARPs no PNCP
    |--------------------------------------------------------------------------
    */
    Route::prefix('mapa-atas')->name('api.mapa-atas.')->group(function () {
        Route::get('/buscar-arps', [App\Http\Controllers\MapaAtasController::class, 'buscarArps'])->name('buscarArps');
        Route::get('/itens/{ataId}', [App\Http\Controllers\MapaAtasController::class, 'itensDaAta'])->name('itensDaAta');
    });

    /*
    |--------------------------------------------------------------------------
    | API Catálogo de Produtos - CRUD + Referências de Preço
    |--------------------------------------------------------------------------
    */
    Route::prefix('catalogo')->name('api.catalogo.')->group(function () {
        Route::get('/', [App\Http\Controllers\CatalogoController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\CatalogoController::class, 'store'])->name('store');
        Route::get('/buscar-pncp', [App\Http\Controllers\CatalogoController::class, 'buscarPNCP'])->name('buscarPNCP');
        Route::get('/produtos-locais', [App\Http\Controllers\CatalogoController::class, 'produtosLocais'])->name('produtosLocais');
        Route::get('/orcamentos-realizados', [App\Http\Controllers\CatalogoController::class, 'orcamentosRealizados'])->name('orcamentosRealizados');
        Route::get('/{id}', [App\Http\Controllers\CatalogoController::class, 'show'])->name('show');
        Route::put('/{id}', [App\Http\Controllers\CatalogoController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\CatalogoController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/referencias-preco', [App\Http\Controllers\CatalogoController::class, 'referenciasPreco'])->name('referenciasPreco');
        Route::post('/{id}/adicionar-preco', [App\Http\Controllers\CatalogoController::class, 'adicionarPreco'])->name('adicionarPreco');
    });

    /*
    |--------------------------------------------------------------------------
    | API Fornecedores - Sugestões da Base Pública PNCP
    |--------------------------------------------------------------------------
    */
    Route::prefix('fornecedores')->name('api.fornecedores.')->group(function () {
        Route::get('/sugerir', [App\Http\Controllers\FornecedorController::class, 'sugerir'])->name('sugerir');
        Route::post('/atualizar-pncp', [App\Http\Controllers\FornecedorController::class, 'atualizarPNCP'])->name('atualizarPNCP');
        Route::get('/buscar-pncp', [App\Http\Controllers\FornecedorController::class, 'buscarPNCP'])->name('buscarPNCP');
        Route::get('/buscar-por-produto', [App\Http\Controllers\FornecedorController::class, 'buscarPorProduto'])->name('buscarPorProduto');
        Route::get('/buscar-progressivo', [App\Http\Controllers\FornecedorController::class, 'buscarPorProdutoProgressivo'])->name('buscarProgressivo');
    });

    /*
    |--------------------------------------------------------------------------
    | API CDF Respostas - Sistema de Resposta de Fornecedores (INTERNO)
    |--------------------------------------------------------------------------
    */
    Route::prefix('cdf')->name('api.cdf.')->group(function () {
        // Visualizar resposta CDF (usuário interno - REQUER autenticação)
        Route::get('/resposta/{id}', [App\Http\Controllers\CdfRespostaController::class, 'visualizarResposta'])->name('visualizarResposta');

        // Apagar CDF (usuário interno - REQUER autenticação)
        Route::delete('/{id}', [App\Http\Controllers\CdfRespostaController::class, 'apagarCDF'])->name('apagarCDF');
    });

    // ========================================
    // FASE 3.4: ROTAS ÓRGÃOS
    // ========================================
    Route::prefix('orgaos')->name('orgaos.')->group(function () {
        Route::get('/', [App\Http\Controllers\OrgaoController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\OrgaoController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\OrgaoController::class, 'show'])->name('show');
    });

});

}); // Fim do grupo com middleware auth

/*
|--------------------------------------------------------------------------
| API Notificações - Sistema de Notificações do Usuário (PÚBLICO)
|--------------------------------------------------------------------------
| Movido para FORA do middleware auth para permitir chamadas AJAX
*/
Route::prefix('api/notificacoes')->name('api.notificacoes.')->group(function () {
    Route::get('/contador', [App\Http\Controllers\NotificacaoController::class, 'contador'])->name('contador');
    Route::get('/', [App\Http\Controllers\NotificacaoController::class, 'index'])->name('index');
    Route::put('/{id}/marcar-lida', [App\Http\Controllers\NotificacaoController::class, 'marcarLida'])->name('marcarLida');
    Route::put('/marcar-todas-lidas', [App\Http\Controllers\NotificacaoController::class, 'marcarTodasLidas'])->name('marcarTodasLidas');
});

/*
|--------------------------------------------------------------------------
| Rotas Públicas - Resposta de CDF por Fornecedores (via token único)
|--------------------------------------------------------------------------
*/
// Formulário público de resposta CDF (não requer autenticação)
Route::get('/responder-cdf/{token}', [App\Http\Controllers\CdfRespostaController::class, 'exibirFormulario'])->name('cdf.responder');

// API pública para salvar resposta (não requer autenticação - usa validação por token)
Route::post('/api/cdf/responder', [App\Http\Controllers\CdfRespostaController::class, 'salvarResposta'])->name('api.cdf.salvarResposta');

// API pública para consultar CNPJ (não requer autenticação)
Route::get('/api/cdf/consultar-cnpj/{cnpj}', [App\Http\Controllers\CdfRespostaController::class, 'consultarCnpj'])->name('api.cdf.consultarCnpj');
// ROTA DE TESTE - REMOVER DEPOIS
Route::get('/teste-html-item/{orcamentoId}/{itemId}', function($orcamentoId, $itemId) {
    $orcamento = \App\Models\Orcamento::findOrFail($orcamentoId);
    $item = $orcamento->itens()->findOrFail($itemId);

    return response()->json([
        'orcamento_id' => $orcamento->id,
        'orcamento_nome' => $orcamento->nome,
        'item_id' => $item->id,
        'item_descricao' => $item->descricao,
        'item_quantidade' => $item->quantidade,
        'item_preco_unitario' => $item->preco_unitario,
        'preco_unitario_is_null' => is_null($item->preco_unitario),
        'preco_unitario_is_empty' => empty($item->preco_unitario),
        'html_simulado' => '<!-- DEBUG: ID=' . $item->id . ' | preco_unitario=' . ($item->preco_unitario ?? 'NULL') . ' -->' . "\n" .
                          '<input type="number" name="preco_unitario[' . $item->id . ']" ' .
                          'value="' . ($item->preco_unitario ?? '') . '" ' .
                          ($item->preco_unitario ? '' : 'disabled') . '>'
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

// ROTA DE DEBUG AVANÇADO - VER TODOS OS ITENS DO ORÇAMENTO
Route::get('/debug-orcamento/{orcamentoId}', function($orcamentoId) {
    $orcamento = \App\Models\Orcamento::with('itens')->findOrFail($orcamentoId);

    $itens = $orcamento->itens->map(function($item) {
        return [
            'id' => $item->id,
            'descricao' => substr($item->descricao, 0, 50),
            'quantidade' => $item->quantidade,
            'preco_unitario' => $item->preco_unitario,
            'preco_unitario_raw' => $item->getAttributes()['preco_unitario'] ?? 'NÃO EXISTE NO ARRAY',
            'preco_total' => $item->preco_unitario ? ($item->preco_unitario * $item->quantidade) : 0,
            'is_null' => is_null($item->preco_unitario),
            'is_empty' => empty($item->preco_unitario),
            'checkbox_deve_estar' => $item->preco_unitario ? 'MARCADO ☑' : 'DESMARCADO ☐',
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at
        ];
    });

    return response()->json([
        'orcamento_id' => $orcamento->id,
        'orcamento_nome' => $orcamento->nome,
        'total_itens' => $itens->count(),
        'itens_com_preco' => $itens->filter(fn($i) => !empty($i['preco_unitario']))->count(),
        'itens_sem_preco' => $itens->filter(fn($i) => empty($i['preco_unitario']))->count(),
        'itens' => $itens
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

/*
|--------------------------------------------------------------------------
| Sistema de Logs Detalhado
|--------------------------------------------------------------------------
| Rotas para captura e visualização de logs do browser e servidor
*/

use App\Http\Controllers\LogController;

// API para receber logs do navegador (público para funcionar sem autenticação)
Route::post('/api/logs/browser', [LogController::class, 'storeBrowserLog'])->name('logs.browser.store');

// Visualização de logs (protegido por autenticação)
Route::middleware(['auth'])->group(function () {
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/download', [LogController::class, 'download'])->name('logs.download');
    Route::post('/logs/clean', [LogController::class, 'cleanOldLogs'])->name('logs.clean');
});

/*
|--------------------------------------------------------------------------
| Sistema de Notificações
|--------------------------------------------------------------------------
| Rotas para notificações de CDF respondidas e outros eventos
*/

use App\Http\Controllers\NotificacaoController;

// API de notificações (públicas - autenticação via headers do ProxyAuth já aplicado globalmente)
Route::get('/api/notificacoes/nao-lidas', [NotificacaoController::class, 'naoLidas'])->name('api.notificacoes.naoLidas');
Route::post('/api/notificacoes/{id}/marcar-lida', [NotificacaoController::class, 'marcarComoLida'])->name('api.notificacoes.marcarLida');
Route::post('/api/notificacoes/marcar-todas-lidas', [NotificacaoController::class, 'marcarTodasComoLidas'])->name('api.notificacoes.marcarTodasLidas');

/*
|--------------------------------------------------------------------------
| API - Contratos Externos (TCE-RS, PNCP, etc)
|--------------------------------------------------------------------------
| Endpoints para consultar contratos e itens importados de fontes externas
*/

use App\Http\Controllers\ContratosExternosController;

// Buscar preços por descrição (fulltext)
Route::get('/api/contratos-externos/buscar', [ContratosExternosController::class, 'buscarPorDescricao'])->name('api.contratos-externos.buscar');

// Buscar preços por CATMAT
Route::get('/api/contratos-externos/catmat/{catmat}', [ContratosExternosController::class, 'buscarPorCatmat'])->name('api.contratos-externos.catmat');

// Estatísticas de preços
Route::get('/api/contratos-externos/estatisticas', [ContratosExternosController::class, 'estatisticas'])->name('api.contratos-externos.estatisticas');

// Listar contratos
Route::get('/api/contratos-externos', [ContratosExternosController::class, 'listarContratos'])->name('api.contratos-externos.listar');

// Detalhes de um contrato
Route::get('/api/contratos-externos/{id}', [ContratosExternosController::class, 'detalhes'])->name('api.contratos-externos.detalhes');

// TESTE: Buscar no TCE-RS via serviço (igual PNCP)
Route::get('/api/test-tce', function(Illuminate\Http\Request $request) {
    $termo = $request->input('termo', 'caneta');
    $service = new App\Services\TceRsApiService();
    $resultado = $service->buscarItensContratos($termo, 10);
    return response()->json($resultado);
});

// TESTE: Simular busca PNCP completa
Route::get('/api/test-busca-completa', function(Illuminate\Http\Request $request) {
    $termo = $request->input('termo', 'notebook');

    $controller = app()->make(App\Http\Controllers\OrcamentoController::class);
    $testRequest = new Illuminate\Http\Request(['termo' => $termo]);

    $response = $controller->buscarPNCP($testRequest);
    return $response;
});

// ============================================================================
// SERVIR ARQUIVOS ESTÁTICOS (CSS, JS, imagens, fontes)
// IMPORTANTE: Estas rotas devem estar NO FINAL para não capturar outras rotas
// ============================================================================

// Servir arquivos CSS
Route::get('/css/{filename}', function ($filename) {
    $path = public_path('css/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, ['Content-Type' => 'text/css']);
})->where('filename', '.*');

// Servir arquivos JavaScript
Route::get('/js/{filename}', function ($filename) {
    $path = public_path('js/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, ['Content-Type' => 'application/javascript']);
})->where('filename', '.*');

// Servir imagens
Route::get('/images/{filename}', function ($filename) {
    $path = public_path('images/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $mimeType = match($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        default => 'image/' . $extension
    };
    return response()->file($path, ['Content-Type' => $mimeType]);
})->where('filename', '.*');

// Servir fontes
Route::get('/fonts/{filename}', function ($filename) {
    $path = public_path('fonts/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $mimeType = match($extension) {
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
        default => 'application/octet-stream'
    };
    return response()->file($path, ['Content-Type' => $mimeType]);
})->where('filename', '.*');

// Servir arquivos build (assets compilados)
Route::get('/build/{filename}', function ($filename) {
    $path = public_path('build/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $mimeType = match($extension) {
        'js' => 'application/javascript',
        'css' => 'text/css',
        'map' => 'application/json',
        default => 'application/octet-stream'
    };
    return response()->file($path, ['Content-Type' => $mimeType]);
})->where('filename', '.*');
