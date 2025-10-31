<?php

namespace App\Http\Controllers;

use App\Models\ContratoPNCP;
use App\Services\TceRsApiService;
use App\Services\ComprasnetApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;

/**
 * PESQUISA RÁPIDA - BUSCA COMPLETA NO PNCP
 *
 * ESTRATÉGIA:
 * 1. Baixa MUITOS contratos de MÚLTIPLAS APIs do PNCP
 * 2. Filtra LOCALMENTE por palavra-chave
 * 3. Retorna TODOS os resultados que contêm a palavra
 *
 * APIs do PNCP utilizadas:
 * - /api/consulta/v1/contratos (contratos principais)
 * - /api/consulta/v1/contratacoes/publicacao (licitações publicadas)
 * - /api/consulta/v1/contratacoes/proposta (propostas abertas)
 */
class PesquisaRapidaController extends Controller
{
    /**
     * Serviços de API em tempo real
     */
    private TceRsApiService $tceRsApi;
    private ComprasnetApiService $comprasnetApi;

    public function __construct(TceRsApiService $tceRsApi, ComprasnetApiService $comprasnetApi)
    {
        $this->tceRsApi = $tceRsApi;
        $this->comprasnetApi = $comprasnetApi;
    }
    /**
     * API de BUSCA TEXTUAL do PNCP (/api/search)
     *
     * ESTA É A API CORRETA QUE ACEITA BUSCA POR PALAVRA-CHAVE!
     *
     * Endpoint: https://pncp.gov.br/api/search/?q=TERMO&tipos_documento=TIPO
     *
     * Tipos de documento:
     * - contrato: Contratos assinados
     * - edital: Licitações/Contratações publicadas
     * - ata_registro_preco: Atas de registro de preço
     */
    private function pncpSearch(string $termo, string $tipoDocumento = 'contrato', int $pagina = 1, int $tamanhoPagina = 10)
    {
        $url = 'https://pncp.gov.br/api/search/';

        $params = [
            'q' => $termo,
            'tipos_documento' => $tipoDocumento,
            'pagina' => $pagina,
            'tamanhoPagina' => $tamanhoPagina,
        ];

        try {
            $resp = Http::withHeaders([
                        'Accept' => 'application/json',
                        'User-Agent' => 'DattaTech-PNCP/1.0',
                    ])
                    ->withOptions([
                        'http_errors' => false,
                        'curl' => [
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_IPRESOLVE    => CURL_IPRESOLVE_V4,
                            CURLOPT_TCP_KEEPALIVE => 1,
                        ],
                    ])
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->get($url, $params);

            if ($resp->successful()) {
                return $resp->json();
            }

            Log::warning('PNCP Search FAIL', [
                'url' => $url,
                'params' => $params,
                'status' => $resp->status(),
                'body' => mb_strimwidth($resp->body(), 0, 500, '…'),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('PNCP Search ERROR', [
                'url' => $url,
                'params' => $params,
                'erro' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function buscar(Request $request)
    {
        $termo = trim($request->get('termo', ''));

        // Validação
        if (strlen($termo) < 3) {
            return response()->json([
                'success' => false,
                'message' => 'Digite pelo menos 3 caracteres para buscar'
            ]);
        }

        Log::info('========== PESQUISA RAPIDA INICIADA ==========', ['termo' => $termo]);

        $resultados = [];
        $fontes = [];

        try {
            // ============================================================
            // BUSCA EM MÚLTIPLAS FONTES
            // PRIORIDADE: CMED (medicamentos) → CATMAT+API → PNCP
            // ============================================================

            // 1. CMED - Base ANVISA de Medicamentos (PRIMEIRO - prioridade para medicamentos)
            Log::info('PesquisaRapida: [1/7] Iniciando busca no CMED (medicamentos)...');
            try {
                $resultadosCMED = $this->buscarNoCMED($termo);
                if (!empty($resultadosCMED)) {
                    $resultados = array_merge($resultados, $resultadosCMED);
                    $fontes['CMED'] = count($resultadosCMED);
                    Log::info('PesquisaRapida: [1/7] CMED retornou ' . count($resultadosCMED) . ' medicamentos');
                } else {
                    Log::info('PesquisaRapida: [1/7] CMED NÃO retornou resultados');
                }
            } catch (\Exception $e) {
                Log::warning('PesquisaRapida: [1/7] Erro no CMED', ['erro' => $e->getMessage()]);
            }

            // 2. CATMAT Local + API de Preços Compras.gov
            Log::info('PesquisaRapida: [1/5] Iniciando busca no CATMAT+API Preços...');
            try {
                $resultadosComprasGov = $this->buscarNoCATMATComPrecos($termo);
                if (!empty($resultadosComprasGov)) {
                    $resultados = array_merge($resultados, $resultadosComprasGov);
                    $fontes['COMPRAS_GOV'] = count($resultadosComprasGov);
                    Log::info('PesquisaRapida: [1/5] CATMAT+API retornou ' . count($resultadosComprasGov) . ' preços reais');
                } else {
                    Log::info('PesquisaRapida: [1/5] CATMAT+API NÃO retornou resultados');
                }
            } catch (\Exception $e) {
                Log::warning('PesquisaRapida: [1/5] Erro no CATMAT+API', ['erro' => $e->getMessage()]);
            }

            // 2. Banco Local PNCP
            Log::info('PesquisaRapida: [2/5] Iniciando busca no banco local PNCP...');
            $resultadosLocal = $this->buscarNoBancoLocal($termo);
            if (!empty($resultadosLocal)) {
                $resultados = array_merge($resultados, $resultadosLocal);
                $fontes['LOCAL'] = count($resultadosLocal);
                Log::info('PesquisaRapida: [2/5] Banco local retornou ' . count($resultadosLocal) . ' resultados');
            } else {
                Log::info('PesquisaRapida: [2/5] Banco local NÃO retornou resultados');
            }

            // 3. API PNCP - Contratos
            Log::info('PesquisaRapida: [3/5] Iniciando busca na API PNCP Contratos...');
            $resultadosContratos = $this->buscarContratosPNCP($termo);
            if (!empty($resultadosContratos)) {
                $resultados = array_merge($resultados, $resultadosContratos);
                $fontes['PNCP_CONTRATOS'] = count($resultadosContratos);
                Log::info('PesquisaRapida: [3/5] API Contratos retornou ' . count($resultadosContratos) . ' resultados');
            } else {
                Log::info('PesquisaRapida: [3/5] API Contratos NÃO retornou resultados');
            }

            // ============================================================
            // 4. OUTRAS APIS
            // ============================================================

            // 4a. LicitaCon (TCE-RS) - Busca em tempo real via CKAN
            Log::info('PesquisaRapida: [4/5] Iniciando busca no LicitaCon (TCE-RS)...');
            try {
                $resultadosLicitaCon = $this->buscarNoLicitaCon($termo);
                if (!empty($resultadosLicitaCon)) {
                    $resultados = array_merge($resultados, $resultadosLicitaCon);
                    $fontes['LICITACON'] = count($resultadosLicitaCon);
                    Log::info('PesquisaRapida: [4/5] LicitaCon retornou ' . count($resultadosLicitaCon) . ' resultados');
                } else {
                    Log::info('PesquisaRapida: [4/5] LicitaCon NÃO retornou resultados');
                }
            } catch (\Exception $e) {
                Log::warning('PesquisaRapida: [4/5] Erro no LicitaCon', ['erro' => $e->getMessage()]);
            }

            // 4b. Comprasnet (Compras.gov.br) - Busca em tempo real via API SIASG
            Log::info('PesquisaRapida: [4b/5] Iniciando busca no Comprasnet...');
            try {
                $resultadosComprasnet = $this->buscarNoComprasnet($termo);
                if (!empty($resultadosComprasnet)) {
                    $resultados = array_merge($resultados, $resultadosComprasnet);
                    $fontes['COMPRASNET'] = count($resultadosComprasnet);
                    Log::info('PesquisaRapida: [4b/5] Comprasnet retornou ' . count($resultadosComprasnet) . ' resultados');
                } else {
                    Log::info('PesquisaRapida: [4b/5] Comprasnet NÃO retornou resultados');
                }
            } catch (\Exception $e) {
                Log::warning('PesquisaRapida: [4b/5] Erro no Comprasnet', ['erro' => $e->getMessage()]);
            }

            // 3c. Portal da Transparência (CGU) - API com Chave
            Log::info('PesquisaRapida: [3c/5] Iniciando busca no Portal da Transparência...');
            try {
                $resultadosPortalCGU = $this->buscarNoPortalTransparencia($termo);
                if (!empty($resultadosPortalCGU)) {
                    $resultados = array_merge($resultados, $resultadosPortalCGU);
                    $fontes['PORTAL_TRANSPARENCIA'] = count($resultadosPortalCGU);
                    Log::info('PesquisaRapida: [3c/5] Portal CGU retornou ' . count($resultadosPortalCGU) . ' resultados');
                } else {
                    Log::info('PesquisaRapida: [3c/5] Portal CGU NÃO retornou resultados');
                }
            } catch (\Exception $e) {
                Log::warning('PesquisaRapida: [3c/5] Erro no Portal CGU', ['erro' => $e->getMessage()]);
            }

            // FIM NOVAS APIS
            // ============================================================

            // DESABILITADO TEMPORARIAMENTE - Vamos usar só /api/search
            // // 3. API PNCP - Contratações Publicadas
            // Log::info('PesquisaRapida: [3/5] Iniciando busca na API PNCP Contratações...');
            // $resultadosContratacoes = $this->buscarContratacoesPNCP($termo);
            // if (!empty($resultadosContratacoes)) {
            //     $resultados = array_merge($resultados, $resultadosContratacoes);
            //     $fontes['PNCP_CONTRATACOES'] = count($resultadosContratacoes);
            //     Log::info('PesquisaRapida: [3/5] API Contratações retornou ' . count($resultadosContratacoes) . ' resultados');
            // } else {
            //     Log::info('PesquisaRapida: [3/5] API Contratações NÃO retornou resultados');
            // }

            // // 4. API PNCP - Propostas em Aberto
            // Log::info('PesquisaRapida: [4/5] Iniciando busca na API PNCP Propostas...');
            // $resultadosPropostas = $this->buscarPropostasPNCP($termo);
            // if (!empty($resultadosPropostas)) {
            //     $resultados = array_merge($resultados, $resultadosPropostas);
            //     $fontes['PNCP_PROPOSTAS'] = count($resultadosPropostas);
            //     Log::info('PesquisaRapida: [4/5] API Propostas retornou ' . count($resultadosPropostas) . ' resultados');
            // } else {
            //     Log::info('PesquisaRapida: [4/5] API Propostas NÃO retornou resultados');
            // }

            // // 5. API PNCP - Atas de Registro de Preço
            // Log::info('PesquisaRapida: [5/5] Iniciando busca na API PNCP Atas...');
            // $resultadosAtas = $this->buscarAtasPNCP($termo);
            // if (!empty($resultadosAtas)) {
            //     $resultados = array_merge($resultados, $resultadosAtas);
            //     $fontes['PNCP_ATAS'] = count($resultadosAtas);
            //     Log::info('PesquisaRapida: [5/5] API Atas retornou ' . count($resultadosAtas) . ' resultados');
            // } else {
            //     Log::info('PesquisaRapida: [5/5] API Atas NÃO retornou resultados');
            // }

            // Filtrar valores zerados ANTES de remover duplicatas
            $resultados = array_filter($resultados, function($item) {
                $valor = $item['valor_homologado_item'] ?? $item['valor_unitario'] ?? $item['valor_global'] ?? 0;
                return $valor > 0;
            });
            $resultados = array_values($resultados); // Reindexar array

            // Remover duplicatas
            $resultados = $this->removerDuplicatas($resultados);

            Log::info('========== PESQUISA RAPIDA CONCLUIDA ==========', [
                'termo' => $termo,
                'total_resultados' => count($resultados),
                'fontes' => $fontes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Busca concluída',
                'resultados' => $resultados,
                'total' => count($resultados),
                'termo' => $termo,
                'fontes' => $fontes
            ]);

        } catch (\Exception $e) {
            Log::error('PesquisaRapida: ERRO GERAL', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar: ' . $e->getMessage(),
                'resultados' => []
            ], 500);
        }
    }

    /**
     * Buscar no banco local
     */
    private function buscarNoBancoLocal($termo)
    {
        try {
            $contratos = ContratoPNCP::buscarPorTermo($termo, 12, 1000);

            if ($contratos->isEmpty()) {
                return [];
            }

            return $contratos->map(function($contrato) {
                return [
                    'numero_controle_pncp' => $contrato->numero_controle_pncp,
                    'tipo' => $contrato->tipo ?? 'contrato',
                    'objeto_contrato' => $contrato->objeto_contrato,
                    'valor_global' => (float) $contrato->valor_global,
                    'valor_unitario' => (float) ($contrato->valor_unitario_estimado ?? $contrato->valor_global),
                    'unidade_medida' => $contrato->unidade_medida ?? 'CONTRATO',
                    'orgao' => $contrato->orgao,
                    'orgao_uf' => $contrato->orgao_uf,
                    'data_publicacao' => $contrato->data_publicacao_pncp?->format('Y-m-d'),
                    'confiabilidade' => $contrato->confiabilidade ?? 'media',
                    'valor_estimado' => $contrato->valor_estimado ?? false,
                    'origem' => 'LOCAL'
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('BuscarNoBancoLocal: Erro', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar CONTRATOS usando API de Busca Textual (/api/search)
     * USA A API CORRETA QUE ACEITA BUSCA POR PALAVRA-CHAVE!
     */
    private function buscarContratosPNCP($termo)
    {
        try {
            $maxPaginas = 5; // Buscar 5 páginas = 50 contratos
            $itens = [];

            Log::info("API Search Contratos: Iniciando busca por '{$termo}'");

            for ($pagina = 1; $pagina <= $maxPaginas; $pagina++) {
                Log::info("API Search Contratos: Buscando página {$pagina}...");

                $resultado = $this->pncpSearch($termo, 'contrato', $pagina, 10);

                if ($resultado === null) {
                    Log::warning("API Search Contratos: Falha na página {$pagina}");
                    break;
                }

                $items = $resultado['items'] ?? [];

                if (empty($items)) {
                    Log::info("API Search Contratos: Página {$pagina} vazia - fim dos resultados");
                    break;
                }

                foreach ($items as $item) {
                    $itens[] = $this->formatarItemSearchPNCP($item, 'contrato');
                }

                Log::info("API Search Contratos: Página {$pagina} retornou " . count($items) . " contratos");

                sleep(1); // Delay entre páginas
            }

            $total = count($itens);
            Log::info("API Search Contratos: TOTAL FINAL = {$total} contratos");

            return $itens;

        } catch (\Exception $e) {
            Log::error('BuscarContratosPNCP: Erro', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar na API de Contratações Publicadas do PNCP
     *
     * IMPORTANTE: Esta API EXIGE codigoModalidadeContratacao
     * Buscando apenas Pregão Eletrônico (modalidade 1 = ~70% dos contratos)
     */
    private function buscarContratacoesPNCP($termo)
    {
        try {
            $dataInicial = now()->subMonths(11)->format('Ymd');
            $dataFinal = now()->format('Ymd');
            $pageSize = (int) env('PNCP_PAGE_SIZE_RAPIDA', 100);
            $maxPaginas = (int) env('PNCP_PAGINAS_RAPIDA', 3);

            $total = 0;
            $itens = [];

            // Buscar apenas modalidade 1 (Pregão Eletrônico - 70% dos contratos)
            for ($pagina = 1; $pagina <= $maxPaginas; $pagina++) {
                Log::info("API Contratações: Buscando página {$pagina}...");

                $res = $this->pncpGet('contratacoes/publicacao', [
                    'dataInicial'   => $dataInicial,
                    'dataFinal'     => $dataFinal,
                    'codigoModalidadeContratacao' => 1, // OBRIGATÓRIO - Pregão Eletrônico
                    'pagina'        => $pagina,
                    'tamanhoPagina' => $pageSize,
                ], 0); // SEM RETRY - falha rápido

                if ($res === null) {
                    Log::warning("API Contratações: Falha na página {$pagina}");
                    break;
                }

                foreach ($res['data'] ?? [] as $c) {
                    if (stripos($c['objetoContratacao'] ?? '', $termo) !== false) {
                        $itens[] = $c;
                        $total++;
                    }
                }

                Log::info("API Contratações: Página {$pagina} processada ({$total} matches)");

                if (($res['totalPaginas'] ?? $pagina) <= $pagina) break;
                sleep(2);
            }

            Log::info("API Contratações: TOTAL FINAL = {$total} contratos");

            return array_map(function($item) {
                return $this->formatarContratacaoPNCP($item);
            }, $itens);

        } catch (\Exception $e) {
            Log::error('BuscarContratacoesPNCP: Erro', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar na API de Propostas em Aberto do PNCP
     *
     * IMPORTANTE: Esta API EXIGE ao menos dataFinal + pagina
     * Busca propostas abertas (futuras) para licitações ainda não realizadas
     */
    private function buscarPropostasPNCP($termo)
    {
        try {
            $dataInicial = now()->format('Ymd');
            $dataFinal = now()->addDays(30)->format('Ymd'); // Próximos 30 dias
            $pageSize = (int) env('PNCP_PAGE_SIZE_RAPIDA', 100);
            $maxPaginas = (int) env('PNCP_PAGINAS_RAPIDA', 3);

            $total = 0;
            $itens = [];

            for ($pagina = 1; $pagina <= $maxPaginas; $pagina++) {
                Log::info("API Propostas: Buscando página {$pagina}...");

                $res = $this->pncpGet('contratacoes/proposta', [
                    'dataInicial'   => $dataInicial,
                    'dataFinal'     => $dataFinal, // OBRIGATÓRIO
                    'pagina'        => $pagina, // OBRIGATÓRIO
                    'tamanhoPagina' => $pageSize,
                ], 0); // SEM RETRY - falha rápido

                if ($res === null) {
                    Log::warning("API Propostas: Falha na página {$pagina}");
                    break;
                }

                foreach ($res['data'] ?? [] as $p) {
                    if (stripos($p['objetoContratacao'] ?? '', $termo) !== false) {
                        $itens[] = $p;
                        $total++;
                    }
                }

                Log::info("API Propostas: Página {$pagina} processada ({$total} matches)");

                if (($res['totalPaginas'] ?? $pagina) <= $pagina) break;
                sleep(2);
            }

            Log::info("API Propostas: TOTAL FINAL = {$total} propostas");

            return array_map(function($item) {
                return $this->formatarPropostaPNCP($item);
            }, $itens);

        } catch (\Exception $e) {
            Log::error('BuscarPropostasPNCP: Erro', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar na API de Atas de Registro de Preço do PNCP
     */
    private function buscarAtasPNCP($termo)
    {
        try {
            $dataInicial = now()->subMonths(11)->format('Ymd');
            $dataFinal = now()->format('Ymd');
            $pageSize = (int) env('PNCP_PAGE_SIZE_RAPIDA', 100);
            $maxPaginas = (int) env('PNCP_PAGINAS_RAPIDA', 3);

            $total = 0;
            $itens = [];

            for ($pagina = 1; $pagina <= $maxPaginas; $pagina++) {
                Log::info("API Atas: Buscando página {$pagina}...");

                $res = $this->pncpGet('atas', [
                    'dataInicial'   => $dataInicial,
                    'dataFinal'     => $dataFinal,
                    'pagina'        => $pagina,
                    'tamanhoPagina' => $pageSize,
                ], 0); // SEM RETRY - falha rápido

                if ($res === null) {
                    Log::warning("API Atas: Falha na página {$pagina}");
                    break;
                }

                foreach ($res['data'] ?? [] as $a) {
                    if (stripos($a['objetoAta'] ?? '', $termo) !== false) {
                        $itens[] = $a;
                        $total++;
                    }
                }

                Log::info("API Atas: Página {$pagina} processada ({$total} matches)");

                if (($res['totalPaginas'] ?? $pagina) <= $pagina) break;
                sleep(2);
            }

            Log::info("API Atas: TOTAL FINAL = {$total} atas");

            return array_map(function($item) {
                return $this->formatarAtaPNCP($item);
            }, $itens);

        } catch (\Exception $e) {
            Log::error('BuscarAtasPNCP: Erro', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Filtrar array de itens por palavra-chave
     * Busca em TODO o JSON convertido para string
     */
    private function filtrarPorPalavra($itens, $palavra)
    {
        $palavraLower = mb_strtolower($palavra, 'UTF-8');
        $palavraSemAcento = $this->removerAcentos($palavraLower);

        $filtrados = [];

        foreach ($itens as $item) {
            // Converter todo o item para JSON e buscar nele
            $jsonCompleto = mb_strtolower(json_encode($item, JSON_UNESCAPED_UNICODE), 'UTF-8');
            $jsonSemAcento = $this->removerAcentos($jsonCompleto);

            if (str_contains($jsonCompleto, $palavraLower) || str_contains($jsonSemAcento, $palavraSemAcento)) {
                $filtrados[] = $item;
            }
        }

        return $filtrados;
    }

    /**
     * Formatar item da API de Busca (/api/search)
     *
     * A API /search retorna um formato diferente das outras APIs
     */
    private function formatarItemSearchPNCP($item, $tipo = 'contrato')
    {
        return [
            'numero_controle_pncp' => $item['numero_controle_pncp'] ?? 'N/A',
            'tipo' => $tipo,
            'objeto_contrato' => $item['description'] ?? $item['title'] ?? 'Sem descrição',
            'valor_global' => (float) ($item['valor_global'] ?? 0),
            'valor_unitario' => (float) ($item['valor_global'] ?? 0),
            'unidade_medida' => 'CONTRATO',
            'orgao' => $item['orgao_nome'] ?? 'Órgão não informado',
            'orgao_uf' => $item['uf'] ?? null,
            'data_publicacao' => $item['data_assinatura'] ?? $item['data_publicacao_pncp'] ?? null,
            'confiabilidade' => 'alta', // API Search é confiável
            'valor_estimado' => false,
            'origem' => 'PNCP_SEARCH_' . strtoupper($tipo)
        ];
    }

    /**
     * Formatar contrato do PNCP
     */
    private function formatarContratoPNCP($item)
    {
        return [
            'numero_controle_pncp' => $item['numeroControlePNCP'] ?? 'N/A',
            'tipo' => 'contrato',
            'objeto_contrato' => $item['objetoContrato'] ?? 'Sem descrição',
            'valor_global' => (float) ($item['valorInicial'] ?? 0),
            'valor_unitario' => (float) ($item['valorInicial'] ?? 0),
            'unidade_medida' => 'CONTRATO',
            'orgao' => $item['orgaoEntidade']['razaoSocial'] ?? 'Órgão não informado',
            'orgao_uf' => $item['unidadeOrgao']['ufSigla'] ?? 'UF',
            'data_publicacao' => isset($item['dataPublicacaoPncp']) ? date('Y-m-d', strtotime($item['dataPublicacaoPncp'])) : null,
            'confiabilidade' => 'media',
            'valor_estimado' => false,
            'origem' => 'PNCP_CONTRATOS'
        ];
    }

    /**
     * Formatar contratação do PNCP
     */
    private function formatarContratacaoPNCP($item)
    {
        return [
            'numero_controle_pncp' => $item['numeroControlePNCP'] ?? 'N/A',
            'tipo' => 'contratacao',
            'objeto_contrato' => $item['objetoCompra'] ?? 'Sem descrição',
            'valor_global' => (float) ($item['valorEstimado'] ?? 0),
            'valor_unitario' => (float) ($item['valorEstimado'] ?? 0),
            'unidade_medida' => 'CONTRATACAO',
            'orgao' => $item['orgaoEntidade']['razaoSocial'] ?? 'Órgão não informado',
            'orgao_uf' => $item['unidadeOrgao']['ufSigla'] ?? 'UF',
            'data_publicacao' => isset($item['dataPublicacaoPncp']) ? date('Y-m-d', strtotime($item['dataPublicacaoPncp'])) : null,
            'confiabilidade' => 'media',
            'valor_estimado' => true,
            'origem' => 'PNCP_CONTRATACOES'
        ];
    }

    /**
     * Formatar proposta do PNCP
     */
    private function formatarPropostaPNCP($item)
    {
        return [
            'numero_controle_pncp' => $item['numeroControlePNCP'] ?? 'N/A',
            'tipo' => 'proposta',
            'objeto_contrato' => $item['objetoCompra'] ?? 'Sem descrição',
            'valor_global' => (float) ($item['valorEstimado'] ?? 0),
            'valor_unitario' => (float) ($item['valorEstimado'] ?? 0),
            'unidade_medida' => 'PROPOSTA',
            'orgao' => $item['orgaoEntidade']['razaoSocial'] ?? 'Órgão não informado',
            'orgao_uf' => $item['unidadeOrgao']['ufSigla'] ?? 'UF',
            'data_publicacao' => isset($item['dataAberturaProposta']) ? date('Y-m-d', strtotime($item['dataAberturaProposta'])) : null,
            'confiabilidade' => 'baixa',
            'valor_estimado' => true,
            'origem' => 'PNCP_PROPOSTAS'
        ];
    }

    /**
     * Formatar ata de registro de preço do PNCP
     */
    private function formatarAtaPNCP($item)
    {
        return [
            'numero_controle_pncp' => $item['numeroControlePNCP'] ?? 'N/A',
            'tipo' => 'ata',
            'objeto_contrato' => $item['objetoCompra'] ?? 'Sem descrição',
            'valor_global' => (float) ($item['valorInicial'] ?? 0),
            'valor_unitario' => (float) ($item['valorInicial'] ?? 0),
            'unidade_medida' => 'ATA',
            'orgao' => $item['orgaoEntidade']['razaoSocial'] ?? 'Órgão não informado',
            'orgao_uf' => $item['unidadeOrgao']['ufSigla'] ?? 'UF',
            'data_publicacao' => isset($item['dataPublicacaoPncp']) ? date('Y-m-d', strtotime($item['dataPublicacaoPncp'])) : null,
            'confiabilidade' => 'alta',
            'valor_estimado' => false,
            'origem' => 'PNCP_ATAS'
        ];
    }

    /**
     * Remover duplicatas
     */
    private function removerDuplicatas($resultados)
    {
        if (empty($resultados)) {
            return [];
        }

        $unicos = [];
        $vistos = [];

        foreach ($resultados as $resultado) {
            // Se tem numero_controle_pncp, usar como chave (itens PNCP)
            if (!empty($resultado['numero_controle_pncp'])) {
                $chave = 'pncp_' . $resultado['numero_controle_pncp'];
            }
            // Se NÃO tem (itens COMPRAS.GOV, CMED, etc), criar chave única
            else {
                $fonte = $resultado['fonte'] ?? 'OUTRO';
                $descricao = mb_strtolower(trim($resultado['objeto_contrato'] ?? $resultado['descricao'] ?? ''));
                $fornecedor = mb_strtolower(trim($resultado['fornecedor_vencedor'] ?? $resultado['fornecedor'] ?? ''));
                $valor = number_format(floatval($resultado['valor_homologado_item'] ?? $resultado['valor_unitario'] ?? 0), 2, '.', '');

                $chave = $fonte . '_' . md5($descricao . $fornecedor . $valor);
            }

            if (!isset($vistos[$chave])) {
                $unicos[] = $resultado;
                $vistos[$chave] = true;
            }
        }

        return $unicos;
    }

    /**
     * Remover acentos
     */
    private function removerAcentos($string)
    {
        $comAcentos = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ';
        $semAcentos = 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYTBsaaaaaaaceeeeiiiidnoooooouuuuyty';
        return strtr($string, $comAcentos, $semAcentos);
    }

    /**
     * Criar cliente HTTP com headers para evitar bloqueio
     */
    private function criarHttpClient($timeout = 30)
    {
        return Http::timeout($timeout)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
                'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
            ]);
    }

    /**
     * CRIAR ORÇAMENTO A PARTIR DE ITENS SELECIONADOS NA PESQUISA RÁPIDA
     *
     * Recebe uma lista de itens selecionados pelo usuário na tela de Pesquisa Rápida,
     * cria um novo orçamento e adiciona os itens na Etapa 3 (Cadastramento de Itens).
     *
     * POST /pesquisa-rapida/criar-orcamento
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function criarOrcamento(Request $request)
    {
        try {
            // Validar dados recebidos
            $validated = $request->validate([
                'itens' => 'required|array|min:1',
                'itens.*.descricao' => 'required|string',
                'itens.*.preco_unitario' => 'required|numeric|min:0',
                'itens.*.unidade_medida' => 'required|string',
                'itens.*.quantidade' => 'required|integer|min:1',
                'itens.*.origem' => 'nullable|string',
                'itens.*.orgao' => 'nullable|string',
                'itens.*.uf' => 'nullable|string',
            ]);

            Log::info('📋 Criando orçamento a partir da Pesquisa Rápida', [
                'user_id' => auth()->id(),
                'total_itens' => count($validated['itens'])
            ]);

            // Criar novo orçamento
            $orcamento = \App\Models\Orcamento::create([
                'user_id' => auth()->id(),
                'titulo' => 'Orçamento a partir de Pesquisa Rápida',
                'status' => 'em_elaboracao',
                'etapa_atual' => 1, // Começa na Etapa 1
                'data_criacao' => now(),
            ]);

            // Adicionar itens ao orçamento
            foreach ($validated['itens'] as $index => $item) {
                \App\Models\ItemOrcamento::create([
                    'orcamento_id' => $orcamento->id,
                    'numero_item' => $index + 1,
                    'descricao' => $item['descricao'],
                    'unidade_medida' => $item['unidade_medida'],
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario'],
                    'preco_total' => $item['quantidade'] * $item['preco_unitario'],
                    'origem_dados' => $item['origem'] ?? 'Pesquisa Rápida',
                    'orgao_referencia' => $item['orgao'] ?? null,
                    'uf_referencia' => $item['uf'] ?? null,
                ]);
            }

            Log::info('✅ Orçamento criado com sucesso', [
                'orcamento_id' => $orcamento->id,
                'total_itens' => count($validated['itens'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Orçamento criado com sucesso!',
                'orcamento_id' => $orcamento->id,
                'total_itens' => count($validated['itens'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('❌ Validação falhou ao criar orçamento', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos: ' . implode(', ', array_map(fn($err) => implode(', ', $err), $e->errors()))
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao criar orçamento a partir da Pesquisa Rápida', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar orçamento: ' . $e->getMessage()
            ], 500);
        }
    }

    // ============================================================
    // NOVAS APIS - INTEGRAÇÃO 15/10/2025
    // ============================================================

    /**
     * Buscar no LicitaCon (TCE-RS) via API CKAN em tempo real
     * ATUALIZADO: Usa TceRsApiService para buscar ITENS REAIS de contratos e licitações
     */
    private function buscarNoLicitaCon($termo)
    {
        try {
            Log::info('🟣 TCE-RS: Buscando itens de contratos e licitações via API', ['termo' => $termo]);

            $todoItens = [];

            // 1. Buscar ITENS DE CONTRATOS (preços reais contratados)
            $resultadoContratos = $this->tceRsApi->buscarItensContratos($termo, 50);

            if ($resultadoContratos['sucesso'] && !empty($resultadoContratos['dados'])) {
                Log::info('🟣 TCE-RS: Contratos encontrados', ['total' => count($resultadoContratos['dados'])]);

                foreach ($resultadoContratos['dados'] as $item) {
                    $todoItens[] = [
                        'numero_controle_pncp' => null,
                        'tipo' => 'contrato_tce_rs',
                        'objeto_contrato' => $item['descricao'],
                        'valor_global' => $item['valor_unitario'] * ($item['quantidade'] ?? 1),
                        'valor_unitario' => $item['valor_unitario'],
                        'quantidade_homologado' => $item['quantidade'] ?? 1,
                        'unidade_medida' => $item['unidade'] ?? 'UN',
                        'orgao' => $item['orgao'],
                        'orgao_uf' => 'RS',
                        'data_publicacao' => null,
                        'confiabilidade' => 'alta',
                        'valor_estimado' => false,
                        'origem' => 'TCE-RS-CONTRATOS',
                        'catmat' => $item['catmat'] ?? null,
                    ];
                }
            }

            // 2. Buscar ITENS DE LICITAÇÕES (valores de propostas)
            $resultadoLicitacoes = $this->tceRsApi->buscarItensLicitacoes($termo, 50);

            if ($resultadoLicitacoes['sucesso'] && !empty($resultadoLicitacoes['dados'])) {
                Log::info('🟣 TCE-RS: Licitações encontradas', ['total' => count($resultadoLicitacoes['dados'])]);

                foreach ($resultadoLicitacoes['dados'] as $item) {
                    $todoItens[] = [
                        'numero_controle_pncp' => null,
                        'tipo' => 'licitacao_tce_rs',
                        'objeto_contrato' => $item['descricao'],
                        'valor_global' => $item['valor_unitario'] * ($item['quantidade'] ?? 1),
                        'valor_unitario' => $item['valor_unitario'],
                        'quantidade_homologado' => $item['quantidade'] ?? 1,
                        'unidade_medida' => $item['unidade'] ?? 'UN',
                        'orgao' => $item['orgao'],
                        'orgao_uf' => 'RS',
                        'data_publicacao' => null,
                        'confiabilidade' => 'media',
                        'valor_estimado' => true,
                        'origem' => 'TCE-RS-LICITACOES',
                    ];
                }
            }

            if (empty($todoItens)) {
                Log::info('🟣 TCE-RS: Nenhum item encontrado');
                return [];
            }

            Log::info('🟣 TCE-RS OK', ['total' => count($todoItens)]);
            return $todoItens;

        } catch (\Exception $e) {
            Log::warning('🟣 Erro ao buscar no TCE-RS', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar no Comprasnet (Compras.gov.br) via API em tempo real
     * NOVO: Usa ComprasnetApiService para buscar itens de contratos
     */
    private function buscarNoComprasnet($termo)
    {
        try {
            Log::info('🟢 COMPRASNET: Buscando itens de contratos via API', ['termo' => $termo]);

            // Buscar itens de contratos
            $resultado = $this->comprasnetApi->buscarItensContratos($termo, [], 100);

            if (!$resultado['sucesso'] || empty($resultado['dados'])) {
                Log::info('🟢 COMPRASNET: Nenhum item encontrado');
                return [];
            }

            Log::info('🟢 COMPRASNET: Itens encontrados', ['total' => count($resultado['dados'])]);

            $itensPadronizados = [];

            foreach ($resultado['dados'] as $item) {
                // Extrair dados do contrato (enriquecidos pelo service)
                $contrato = $item['contrato'] ?? [];

                $itensPadronizados[] = [
                    'numero_controle_pncp' => null,
                    'tipo' => 'contrato_comprasnet',
                    'objeto_contrato' => $item['descricao'] ?? $item['descricaoDetalhada'] ?? 'Sem descrição',
                    'valor_global' => (float) ($item['valorTotal'] ?? 0),
                    'valor_unitario' => (float) ($item['valorUnitario'] ?? 0),
                    'quantidade_homologado' => (float) ($item['quantidade'] ?? 1),
                    'unidade_medida' => $item['unidadeMedida'] ?? 'UN',
                    'orgao' => $contrato['orgao'] ?? 'Órgão Federal',
                    'orgao_uf' => 'BR',
                    'fornecedor_vencedor' => $contrato['fornecedor'] ?? null,
                    'data_publicacao' => $contrato['data_assinatura'] ?? null,
                    'confiabilidade' => 'alta',
                    'valor_estimado' => false,
                    'origem' => 'COMPRASNET-SIASG',
                    'numero_contrato' => $contrato['numero'] ?? null,
                ];
            }

            Log::info('🟢 COMPRASNET OK', ['total' => count($itensPadronizados)]);
            return $itensPadronizados;

        } catch (\Exception $e) {
            Log::warning('🟢 Erro ao buscar no Comprasnet', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar no Compras.gov - API REST Pública
     * SEM cache local
     */
    /**
     * Buscar no CATMAT Local + API de Preços Compras.gov
     *
     * ESTRATÉGIA:
     * 1. Buscar materiais no CATMAT local (Full-Text Search em português)
     * 2. Para cada código encontrado, consultar API de Preços
     * 3. Retornar preços REAIS praticados recentemente
     *
     * API: https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial
     */
    private function buscarNoCATMATComPrecos($termo)
    {
        try {
            Log::info('🟢 COMPRAS.GOV LOCAL: Buscando preços localmente', ['termo' => $termo]);

            // ✅ BUSCA PRECISA: Usar word boundaries para match de palavra COMPLETA
            // Solução: Full-Text Search 'simple' (sem stemming) + validação adicional

            // Escapar caracteres especiais do termo
            $termoEscapado = preg_replace('/[^a-zA-Z0-9À-ÿ\s]/', '', $termo);

            // Buscar usando FTS 'simple' (sem stemming - mais preciso)
            $query = DB::connection('pgsql_main')
                ->table('cp_precos_comprasgov')
                ->select(
                    'catmat_codigo',
                    'descricao_item',
                    'preco_unitario',
                    'quantidade',
                    'unidade_fornecimento',
                    'fornecedor_nome',
                    'fornecedor_cnpj',
                    'orgao_nome',
                    'orgao_uf',
                    'municipio',
                    'uf',
                    'data_compra'
                );

            // ✅ 31/10/2025: FIX - Busca flexível para múltiplas palavras
            $palavras = preg_split('/\s+/', trim($termoEscapado));

            if (count($palavras) > 1) {
                // Múltiplas palavras: buscar cada palavra independentemente (OR)
                $query->where(function($q) use ($palavras) {
                    foreach ($palavras as $palavra) {
                        if (strlen($palavra) >= 2) {
                            $q->orWhere('descricao_item', 'ILIKE', "%{$palavra}%");
                        }
                    }
                });
            } else {
                // Uma palavra: usar Full-Text Search (mais rápido)
                $query->whereRaw(
                    "to_tsvector('simple', descricao_item) @@ plainto_tsquery('simple', ?)",
                    [$termoEscapado]
                );
            }

            $precos = $query
                ->where('preco_unitario', '>', 0)
                ->orderBy('data_compra', 'desc')
                ->limit(1000)
                ->get();

            if ($precos->isEmpty()) {
                Log::info('🟢 COMPRAS.GOV LOCAL: Nenhum preço encontrado na base local');
                Log::info('🔵 COMPRAS.GOV API: Tentando busca em tempo real...');
                return $this->buscarNaAPIComprasGovTempoReal($termo);
            }

            Log::info('🟢 COMPRAS.GOV LOCAL: ' . $precos->count() . ' preços encontrados (antes do filtro de precisão)');

            // ✅ 31/10/2025: FILTRO DE PRECISÃO melhorado - aceita múltiplas palavras
            $palavrasBusca = preg_split('/\s+/', trim($termoEscapado));
            $palavrasBusca = array_filter($palavrasBusca, function($p) {
                return strlen($p) >= 2; // Mínimo 2 caracteres
            });

            $precosValidados = $precos->filter(function($preco) use ($palavrasBusca, $termoEscapado) {
                $descricaoNormalizada = mb_strtoupper($preco->descricao_item, 'UTF-8');

                if (count($palavrasBusca) > 1) {
                    // Múltiplas palavras: TODAS devem aparecer (mas não necessariamente juntas)
                    foreach ($palavrasBusca as $palavra) {
                        $palavraNorm = mb_strtoupper($palavra, 'UTF-8');
                        if (!str_contains($descricaoNormalizada, $palavraNorm)) {
                            return false; // Se uma palavra não aparecer, rejeita
                        }
                    }
                    return true;
                } else {
                    // Uma palavra: match exato com word boundary
                    $termoNormalizado = mb_strtoupper($termoEscapado, 'UTF-8');
                    $pattern = '/\b' . preg_quote($termoNormalizado, '/') . '\b/u';
                    return preg_match($pattern, $descricaoNormalizada);
                }
            });

            if ($precosValidados->isEmpty()) {
                Log::info('🟢 COMPRAS.GOV LOCAL: Nenhum preço válido após filtro de precisão');
                Log::info('🔵 COMPRAS.GOV API: Tentando busca em tempo real...');
                return $this->buscarNaAPIComprasGovTempoReal($termo);
            }

            Log::info('✅ COMPRAS.GOV LOCAL: ' . $precosValidados->count() . ' preços validados (match exato)');

            $todosItens = [];

            foreach ($precosValidados as $preco) {
                $todosItens[] = [
                    'numero_controle_pncp' => null,
                    'tipo' => 'preco_praticado',
                    'objeto_contrato' => $preco->descricao_item,
                    'valor_homologado_item' => floatval($preco->preco_unitario),
                    'quantidade_homologado' => floatval($preco->quantidade ?? 1),
                    'unidade_medida' => $preco->unidade_fornecimento ?? 'UN',
                    'fornecedor_vencedor' => $preco->fornecedor_nome,
                    'cnpj' => $preco->fornecedor_cnpj,
                    'orgao_razao_social' => $preco->orgao_nome ?? 'Não informado',
                    'orgao_nome' => $preco->orgao_nome ?? 'Não informado',
                    'municipio' => $preco->municipio,
                    'uf' => $preco->uf,
                    'data_publicacao' => $preco->data_compra,
                    'fonte' => 'COMPRAS.GOV',
                    'codigo_catmat' => $preco->catmat_codigo,
                    'categoria' => null // Podemos fazer join com catmat se necessário
                ];
            }

            return $todosItens;

        } catch (\Exception $e) {
            Log::warning('🟢 COMPRAS.GOV LOCAL: Erro geral na busca', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 🔵 BUSCA EM TEMPO REAL NA API COMPRAS.GOV
     *
     * Quando não houver dados locais, busca diretamente na API:
     * 1. Busca CATMATs correspondentes ao termo no banco local
     * 2. Para cada CATMAT, consulta API de preços
     * 3. Retorna resultados no mesmo formato do método principal
     *
     * ESTRATÉGIA SEGURA:
     * - Se API falhar, retorna [] (não quebra nada)
     * - Formato de retorno idêntico ao método principal
     * - Limita a 3 CATMATs para não demorar muito
     * - Timeout de 10s para não travar
     */
    private function buscarNaAPIComprasGovTempoReal($termo)
    {
        try {
            // PASSO 1: Buscar CATMATs correspondentes ao termo
            $termoEscapado = preg_replace('/[^a-zA-Z0-9À-ÿ\s]/', '', $termo);

            // ✅ 31/10/2025: FIX - Busca flexível CATMAT para múltiplas palavras
            $palavras = preg_split('/\s+/', trim($termoEscapado));
            $palavrasPrincipais = array_filter($palavras, function($p) {
                return strlen($p) >= 3; // Palavras com 3+ caracteres
            });

            $catmats = DB::connection('pgsql_main')
                ->table('cp_catmat')
                ->select('codigo', 'titulo')
                ->where('ativo', true)
                ->where(function($q) use ($termoEscapado, $palavrasPrincipais) {
                    // Tentar Full-Text Search primeiro
                    $q->whereRaw(
                        "to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', ?)",
                        [$termoEscapado]
                    );

                    // OU buscar cada palavra principal separadamente
                    if (!empty($palavrasPrincipais)) {
                        foreach ($palavrasPrincipais as $palavra) {
                            $q->orWhere('titulo', 'ILIKE', "%{$palavra}%");
                        }
                    } else {
                        // Fallback: termo completo
                        $q->orWhere('titulo', 'ILIKE', "%{$termoEscapado}%");
                    }
                })
                ->orderBy('contador_ocorrencias', 'desc')
                ->limit(10) // ✅ 31/10/2025: Ajustado para 10 (balanço entre resultados e performance)
                ->get();

            if ($catmats->isEmpty()) {
                Log::info('🔵 COMPRAS.GOV API: Nenhum CATMAT encontrado para o termo');
                return [];
            }

            Log::info('🔵 COMPRAS.GOV API: ' . $catmats->count() . ' CATMATs encontrados', [
                'codigos' => $catmats->pluck('codigo')->toArray()
            ]);

            $todosItens = [];

            // PASSO 2: Para cada CATMAT, buscar preços na API
            foreach ($catmats as $catmat) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'Accept' => '*/*',
                            'User-Agent' => 'DattaTech-CestaPrecos/1.0'
                        ])
                        ->get('https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial', [
                            'codigoItemCatalogo' => $catmat->codigo,
                            'pagina' => 1,
                            'tamanhoPagina' => 500 // ✅ 31/10/2025: Aumentado de 100→500
                        ]);

                    if (!$response->successful()) {
                        Log::warning('🔵 COMPRAS.GOV API: Erro na requisição', [
                            'catmat' => $catmat->codigo,
                            'status' => $response->status()
                        ]);
                        continue;
                    }

                    $data = $response->json();
                    $precos = $data['resultado'] ?? [];

                    Log::info('🔵 COMPRAS.GOV API: CATMAT ' . $catmat->codigo . ' retornou ' . count($precos) . ' preços');

                    // PASSO 3: Formatar resultados no mesmo formato do método principal
                    foreach ($precos as $preco) {
                        // Filtrar apenas últimos 12 meses
                        $dataCompra = $preco['dataCompra'] ?? $preco['dataResultado'] ?? null;
                        if ($dataCompra) {
                            $dataLimite = Carbon::now()->subMonths(12);
                            $dataPreco = Carbon::parse($dataCompra);
                            if ($dataPreco->lt($dataLimite)) {
                                continue; // Pular se for mais antigo que 12 meses
                            }
                        }

                        // Verificar se tem preço válido
                        $precoUnitario = floatval($preco['precoUnitario'] ?? 0);
                        if ($precoUnitario <= 0) {
                            continue;
                        }

                        $todosItens[] = [
                            'numero_controle_pncp' => null,
                            'tipo' => 'preco_praticado',
                            'objeto_contrato' => $preco['descricaoItem'] ?? $catmat->titulo,
                            'valor_homologado_item' => $precoUnitario,
                            'quantidade_homologado' => floatval($preco['quantidade'] ?? 1),
                            'unidade_medida' => $preco['siglaUnidadeFornecimento'] ?? 'UN',
                            'fornecedor_vencedor' => $preco['nomeFornecedor'] ?? null,
                            'cnpj' => $preco['niFornecedor'] ?? null,
                            'orgao_razao_social' => $preco['nomeOrgao'] ?? $preco['nomeUasg'] ?? 'Não informado',
                            'orgao_nome' => $preco['nomeOrgao'] ?? $preco['nomeUasg'] ?? 'Não informado',
                            'municipio' => $preco['municipioFornecedor'] ?? null,
                            'uf' => $preco['ufFornecedor'] ?? null,
                            'data_publicacao' => $dataCompra ? Carbon::parse($dataCompra)->format('Y-m-d') : null,
                            'fonte' => 'COMPRAS.GOV',
                            'codigo_catmat' => $catmat->codigo,
                            'categoria' => null
                        ];

                        // ✅ 31/10/2025: Aumentado de 100→1000
                        if (count($todosItens) >= 1000) {
                            break 2; // Sair dos dois loops
                        }
                    }

                } catch (\Exception $e) {
                    Log::warning('🔵 COMPRAS.GOV API: Erro ao processar CATMAT', [
                        'catmat' => $catmat->codigo,
                        'erro' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            if (empty($todosItens)) {
                Log::info('🔵 COMPRAS.GOV API: Nenhum resultado válido encontrado');
                return [];
            }

            Log::info('✅ COMPRAS.GOV API: ' . count($todosItens) . ' preços obtidos em tempo real');
            return $todosItens;

        } catch (\Exception $e) {
            Log::error('🔵 COMPRAS.GOV API: Erro geral', ['erro' => $e->getMessage()]);
            return []; // Retornar vazio em caso de erro (não quebra nada)
        }
    }

    /**
     * MÉTODO ANTIGO - Endpoint /modulo-contratos não existe mais (404)
     * Mantido para referência, mas NÃO é chamado
     */
    private function buscarNoComprasGov_DESABILITADO($termo)
    {
        try {
            Log::info('🟢 Compras.gov: Buscando em CONTRATOS (endpoint correto)', ['termo' => $termo]);

            $todosItens = [];

            // Preparar termos de busca para filtro local
            $termosLimpos = array_filter(
                array_map('trim', explode(' ', strtolower($termo))),
                function($palavra) {
                    return strlen($palavra) >= 3;
                }
            );

            // Buscar contratos dos últimos 12 meses
            $dataFim = now()->format('Y-m-d');
            $dataInicio = now()->subMonths(12)->format('Y-m-d');

            $urlContratos = 'https://dadosabertos.compras.gov.br/modulo-contratos/2_consultarContratosItem';

            // Buscar múltiplas páginas (máx 500 itens por página)
            $maxPaginas = 5;

            for ($pagina = 1; $pagina <= $maxPaginas; $pagina++) {
                try {
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Accept' => '*/*',
                        'User-Agent' => 'DattaTech-CestaPrecos/1.0'
                    ])
                    ->timeout(30)
                    ->get($urlContratos, [
                        'dataVigenciaInicialMin' => $dataInicio,
                        'dataVigenciaInicialMax' => $dataFim,
                        'pagina' => $pagina,
                        'tamanhoPagina' => 500
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $itens = $data['resultado'] ?? [];

                        if (empty($itens)) {
                            break;
                        }

                        // Filtrar localmente por descricaoItem
                        foreach ($itens as $item) {
                            $descricaoItem = strtolower($item['descricaoItem'] ?? '');

                            $match = true;
                            foreach ($termosLimpos as $palavra) {
                                if (strpos($descricaoItem, $palavra) === false) {
                                    $match = false;
                                    break;
                                }
                            }

                            if ($match) {
                                $todosItens[] = [
                                    'numero_controle_pncp' => null,
                                    'tipo' => 'preco_praticado',
                                    'objeto_contrato' => $item['descricaoItem'] ?? 'Sem descrição',
                                    'valor_homologado_item' => floatval($item['valorUnitario'] ?? 0),
                                    'quantidade_homologado' => floatval($item['quantidade'] ?? 1),
                                    'unidade_medida' => $item['unidadeMedida'] ?? 'UN',
                                    'fornecedor_vencedor' => $item['nomeFornecedor'] ?? null,
                                    'cnpj' => $item['niFornecedor'] ?? null,
                                    'municipio' => null,
                                    'uf' => null,
                                    'data_publicacao' => $item['dataVigenciaInicial'] ?? null,
                                    'fonte' => 'COMPRAS.GOV'
                                ];
                            }
                        }

                        if (count($todosItens) >= 100) {
                            break;
                        }
                    } else {
                        break;
                    }

                } catch (\Exception $e) {
                    break;
                }

                usleep(500000); // 0.5 segundo
            }

            if (empty($todosItens)) {
                Log::info('🟢 Compras.gov: 0 resultados encontrados');
                return [];
            }

            // Limitar a 100 resultados
            if (count($todosItens) > 100) {
                $todosItens = array_slice($todosItens, 0, 100);
            }

            Log::info('✅ Compras.gov: Retornando resultados', ['total' => count($todosItens)]);
            return $todosItens;

        } catch (\Exception $e) {
            Log::warning('🟢 Erro geral ao buscar no Compras.gov', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar no Portal da Transparência (CGU) - API REST com Chave
     * SEM cache local
     *
     * ESTRATÉGIA:
     * 1. Buscar contratos recentes (/contratos)
     * 2. Para cada contrato, buscar itens contratados (/contratos/itens-contratados?id=...)
     * 3. Filtrar por termo de busca
     */
    private function buscarNoPortalTransparencia($termo)
    {
        try {
            Log::info('🟡 Buscando no Portal da Transparência (CGU) - Contratos', ['termo' => $termo]);

            $apiKey = env('PORTALTRANSPARENCIA_API_KEY', '319215bff3b6753f5e1e4105c58a55e9');

            if (empty($apiKey)) {
                Log::warning('🟡 Chave do Portal da Transparência não configurada');
                return [];
            }

            // ⚠️ AVISO: Endpoint /contratos exige parâmetro "codigoOrgao" (obrigatório)
            // Como não sabemos qual órgão buscar, vamos desabilitar temporariamente
            // TODO: Implementar busca por notas fiscais (/notas-fiscais) ou licitações

            Log::info('🟡 Portal Transparência: Temporariamente desabilitado (endpoint /contratos exige codigoOrgao)');
            return [];

            $contratos = $responseContratos->json();
            $listaContratos = is_array($contratos) ? $contratos : [];

            if (empty($listaContratos)) {
                Log::info('🟡 Portal Transparência: 0 contratos encontrados');
                return [];
            }

            Log::info('🟡 Portal Transparência: ' . count($listaContratos) . ' contratos encontrados');

            // ✅ ETAPA 2: Para cada contrato, buscar itens contratados
            foreach (array_slice($listaContratos, 0, 20) as $contrato) {
                $idContrato = $contrato['id'] ?? null;

                if (!$idContrato) {
                    continue;
                }

                try {
                    $urlItens = 'https://api.portaldatransparencia.gov.br/api-de-dados/contratos/itens-contratados';

                    $responseItens = \Illuminate\Support\Facades\Http::withHeaders([
                        'chave-api-dados' => $apiKey,
                        'Accept' => 'application/json'
                    ])
                    ->timeout(10)
                    ->get($urlItens, [
                        'id' => $idContrato,
                        'pagina' => 1
                    ]);

                    if ($responseItens->successful()) {
                        $itensContrato = $responseItens->json();
                        $listaItens = is_array($itensContrato) ? $itensContrato : [];

                        // Filtrar por termo de busca
                        foreach ($listaItens as $item) {
                            $descricao = strtolower($item['descricao'] ?? $item['descricaoItem'] ?? '');
                            if (stripos($descricao, strtolower($termo)) !== false) {
                                $todosItens[] = array_merge($item, [
                                    'contrato_id' => $idContrato,
                                    'orgao_contrato' => $contrato['nomeOrgao'] ?? 'Gov Federal'
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }

                // Limitar busca
                if (count($todosItens) >= 100) {
                    break;
                }
            }

            if (empty($todosItens)) {
                Log::info('🟡 Portal Transparência: 0 itens após filtro');
                return [];
            }

            $itensPadronizados = array_map(function($item) {
                return [
                    'numero_controle_pncp' => null,
                    'tipo' => 'contrato',
                    'objeto_contrato' => $item['descricao'] ?? $item['descricaoItem'] ?? '-',
                    'valor_global' => (float) ($item['valorUnitario'] ?? $item['valorItem'] ?? 0),
                    'valor_unitario' => (float) ($item['valorUnitario'] ?? $item['valorItem'] ?? 0),
                    'unidade_medida' => $item['unidadeFornecimento'] ?? $item['unidade'] ?? 'UN',
                    'orgao' => $item['orgao_contrato'] ?? 'Gov Federal',
                    'orgao_uf' => $item['uf'] ?? 'BR',
                    'data_publicacao' => $item['dataAssinatura'] ?? null,
                    'confiabilidade' => 'alta',
                    'valor_estimado' => false,
                    'origem' => 'PORTAL_TRANSPARENCIA'
                ];
            }, $todosItens);

            Log::info('🟡 Portal Transparência OK - Itens contratados', ['total' => count($itensPadronizados)]);
            return $itensPadronizados;

        } catch (\Exception $e) {
            Log::warning('🟡 Erro geral ao buscar no Portal da Transparência', ['erro' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Buscar medicamentos na base CMED (ANVISA)
     * Tabela local: cp_medicamentos_cmed
     * Total: 26.046 medicamentos (Julho 2025)
     */
    private function buscarNoCMED(string $termo)
    {
        try {
            Log::info('💊 Buscando medicamentos no CMED...', ['termo' => $termo]);

            // Buscar medicamentos por produto ou substância
            $medicamentos = \App\Models\MedicamentoCmed::buscarPorTermo($termo, 100);

            if ($medicamentos->isEmpty()) {
                Log::info('💊 CMED: Nenhum medicamento encontrado');
                return [];
            }

            Log::info('💊 CMED: Medicamentos encontrados', ['total' => $medicamentos->count()]);

            // Padronizar formato para a Pesquisa Rápida
            $resultados = $medicamentos->map(function($med) {
                // Criar descrição completa do medicamento
                $descricao = trim($med->produto);
                if (empty($descricao) || $descricao === '-') {
                    $descricao = $med->substancia;
                } else {
                    $descricao = $med->substancia . ' - ' . $med->produto;
                }

                return [
                    'descricao' => $descricao,
                    'unidade_fornecimento' => 'UN',
                    'valor_unitario' => (float) $med->pmc_0,
                    'valor_homologado_item' => (float) $med->pmc_0,
                    'valor_global' => (float) $med->pmc_0,
                    'quantidade' => 1,
                    'orgao' => 'ANVISA/CMED',
                    'uf_orgao' => 'DF',
                    'municipio_orgao' => 'Brasília',
                    'data_vigencia_inicio' => $med->data_importacao ? $med->data_importacao->format('d/m/Y') : now()->format('d/m/Y'),
                    'data_vigencia_fim' => null,
                    'fornecedor' => $med->laboratorio,
                    'cnpj_fornecedor' => $med->cnpj_laboratorio,
                    'modalidade' => 'Tabela CMED',
                    'numero_contrato' => 'CMED-' . $med->id,
                    'origem' => 'CMED',
                    'mes_referencia' => $med->mes_referencia ?? 'Julho 2025',
                    'ean' => $med->ean1,
                    'pmc_0' => (float) $med->pmc_0,
                    'pmc_12' => (float) $med->pmc_12,
                    'pmc_17' => (float) $med->pmc_17,
                    'pmc_18' => (float) $med->pmc_18,
                    'pmc_20' => (float) $med->pmc_20,
                ];
            })->toArray();

            Log::info('💊 CMED: Resultados padronizados', ['total' => count($resultados)]);
            return $resultados;

        } catch (\Exception $e) {
            Log::error('💊 Erro ao buscar no CMED', ['erro' => $e->getMessage()]);
            return [];
        }
    }
}
