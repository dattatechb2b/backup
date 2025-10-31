<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\ArpCabecalho;
use App\Models\ArpItem;
use App\Models\Catmat;
use App\Models\ConsultaPncpCache;
use App\Helpers\NormalizadorHelper;

class MapaAtasController extends Controller
{
    /**
     * URL base da API PNCP (Consulta)
     */
    private const API_BASE_URL = 'https://pncp.gov.br/api/consulta';

    /**
     * Buscar contratos/atas na API PNCP
     */
    public function buscar(Request $request)
    {
        try {
            $descricao = $request->get('descricao');
            $uasg = $request->get('uasg');
            $cnpjOrgao = $request->get('cnpj_orgao');
            $dataInicial = $request->get('data_inicial'); // Formato: YYYYMMDD
            $dataFinal = $request->get('data_final');     // Formato: YYYYMMDD
            $pagina = $request->get('pagina', 1);
            $tamanhoPagina = $request->get('tamanho_pagina', 100); // Máx 500

            // FILTROS AVANÇADOS (NOVOS)
            $uf = $request->get('uf');
            $municipio = $request->get('municipio');
            $valorMin = $request->get('valor_min');
            $valorMax = $request->get('valor_max');
            $periodo = $request->get('periodo'); // dias: 30, 90, 365

            // Validar campos obrigatórios
            if (empty($descricao) && empty($uasg) && empty($cnpjOrgao)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Digite ao menos um filtro: descrição, UASG ou CNPJ do órgão.'
                ], 400);
            }

            // Se não informou data, buscar conforme período filtrado
            if (empty($dataInicial) || empty($dataFinal)) {
                $dataFinal = date('Ymd'); // Hoje
                $diasAtras = $periodo ? (int)$periodo : 365; // Padrão 1 ANO (365 dias)
                $dataInicial = date('Ymd', strtotime("-{$diasAtras} days"));
            }

            // Construir URL da API
            $params = [
                'dataInicial' => $dataInicial,
                'dataFinal' => $dataFinal,
                'pagina' => $pagina,
                'tamanhoPagina' => min($tamanhoPagina, 500) // Limitar a 500
            ];

            // Adicionar CNPJ do órgão se fornecido
            if ($cnpjOrgao) {
                $cnpjLimpo = preg_replace('/\D/', '', $cnpjOrgao);
                $params['cnpjOrgao'] = $cnpjLimpo;
            }

            $url = self::API_BASE_URL . '/v1/contratos?' . http_build_query($params);

            Log::info('Buscando contratos na API PNCP', [
                'url' => $url,
                'filtros' => [
                    'descricao' => $descricao,
                    'uasg' => $uasg,
                    'cnpj_orgao' => $cnpjOrgao
                ]
            ]);

            // Fazer requisição para API PNCP
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'CestaDePrecos/1.0'
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::error('Erro na API PNCP', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao consultar API PNCP. Status: ' . $response->status()
                ], 500);
            }

            $data = $response->json();
            $contratos = $data['items'] ?? $data['data'] ?? [];

            // ============================================================
            // BUSCAR EM MÚLTIPLAS FONTES (não apenas PNCP!)
            // ============================================================

            $fontesExtras = [];

            // 1. Buscar no Compras.gov (se tiver descrição ou código CATMAT)
            if ($descricao) {
                try {
                    // Detectar se é código CATMAT (apenas números)
                    $isCatmat = preg_match('/^\d+$/', trim($descricao));
                    Log::info('Mapa de Atas: Buscando no Compras.gov', [
                        'termo' => $descricao,
                        'tipo' => $isCatmat ? 'CATMAT' : 'DESCRICAO'
                    ]);

                    $resultadosComprasGov = $this->buscarComprasGov($descricao, $dataInicial, $dataFinal, $isCatmat);
                    if (!empty($resultadosComprasGov)) {
                        $fontesExtras = array_merge($fontesExtras, $resultadosComprasGov);
                        Log::info('Mapa de Atas: Compras.gov retornou ' . count($resultadosComprasGov) . ' contratos');
                    }
                } catch (\Exception $e) {
                    Log::warning('Mapa de Atas: Erro ao buscar no Compras.gov: ' . $e->getMessage());
                }
            }

            // 2. Buscar no CMED (se tiver descrição e parecer medicamento)
            if ($descricao && $this->pareceMedicamento($descricao)) {
                try {
                    Log::info('Mapa de Atas: Buscando no CMED', ['termo' => $descricao]);
                    $resultadosCMED = $this->buscarCMED($descricao);
                    if (!empty($resultadosCMED)) {
                        $fontesExtras = array_merge($fontesExtras, $resultadosCMED);
                        Log::info('Mapa de Atas: CMED retornou ' . count($resultadosCMED) . ' medicamentos');
                    }
                } catch (\Exception $e) {
                    Log::warning('Mapa de Atas: Erro ao buscar no CMED: ' . $e->getMessage());
                }
            }

            // Mesclar resultados do PNCP + fontes extras
            $contratos = array_merge($contratos, $fontesExtras);

            Log::info('Mapa de Atas: Total após busca multi-fonte', [
                'total_pncp' => count($data['items'] ?? $data['data'] ?? []),
                'total_extras' => count($fontesExtras),
                'total_final' => count($contratos)
            ]);

            // Filtrar localmente por descrição/UASG se fornecidos
            // ATENÇÃO: NÃO filtrar por descrição pois a API PNCP não suporta busca por texto livre
            // O usuário pode usar os filtros avançados (UF, município, valor) para refinar
            // Fontes CMED e Compras.gov já vêm filtradas por descrição

            if ($uasg) {
                $contratos = array_filter($contratos, function($contrato) use ($uasg) {
                    $codigoUasg = $contrato['codigoUnidadeGestora'] ??
                                  $contrato['codigoUasgGestora'] ??
                                  $contrato['uasg'] ?? '';
                    return stripos((string)$codigoUasg, $uasg) !== false;
                });
                $contratos = array_values($contratos);
            }

            // APLICAR FILTROS AVANÇADOS
            if ($uf) {
                $contratos = array_filter($contratos, function($contrato) use ($uf) {
                    $contratoUf = $contrato['orgaoEntidade']['uf'] ?? $contrato['uf'] ?? '';
                    return strtoupper($contratoUf) === strtoupper($uf);
                });
                $contratos = array_values($contratos);
            }

            if ($municipio) {
                $contratos = array_filter($contratos, function($contrato) use ($municipio) {
                    $contratoMunicipio = $contrato['orgaoEntidade']['municipio'] ?? $contrato['municipio'] ?? '';
                    return stripos(strtolower($contratoMunicipio), strtolower($municipio)) !== false;
                });
                $contratos = array_values($contratos);
            }

            if ($valorMin !== null || $valorMax !== null) {
                $contratos = array_filter($contratos, function($contrato) use ($valorMin, $valorMax) {
                    $valor = (float)($contrato['valorGlobal'] ?? $contrato['valorInicial'] ?? 0);
                    if ($valorMin !== null && $valor < (float)$valorMin) return false;
                    if ($valorMax !== null && $valor > (float)$valorMax) return false;
                    return true;
                });
                $contratos = array_values($contratos);
            }

            // Processar resultados (21+ CAMPOS COMPLETOS para auditoria)
            $resultados = [];
            foreach ($contratos as $contrato) {
                // Se o contrato JÁ está formatado (tem chave 'fonte'), adicionar direto
                if (isset($contrato['fonte'])) {
                    $resultados[] = $contrato;
                    continue; // Pular processamento
                }

                // Extrair IDs para construir URLs (apenas para contratos PNCP não formatados)
                $numeroControle = $contrato['numeroControlePNCP'] ?? $contrato['numeroContrato'] ?? null;
                $cnpjOrgao = $contrato['orgaoEntidade']['cnpj'] ?? $contrato['cnpjOrgao'] ?? null;
                $anoCompra = $contrato['anoContrato'] ?? $contrato['anoCompra'] ?? null;
                $sequencialCompra = $contrato['sequencialContrato'] ?? $contrato['sequencialCompra'] ?? null;

                // Gerar hash SHA256 do documento (para auditoria)
                $hashDados = json_encode($contrato);
                $hashDocumento = hash('sha256', $hashDados);

                $resultados[] = [
                    // === IDENTIFICAÇÃO ===
                    'numero_controle_pncp' => $numeroControle,
                    'numero_contrato' => $contrato['numeroContratoEmpenho'] ?? $contrato['numeroContrato'] ?? 'N/A',
                    'ano_compra' => $anoCompra,
                    'sequencial_compra' => $sequencialCompra,
                    'tipo_documento' => $contrato['tipoContrato']['nome'] ?? $contrato['tipoInstrumento'] ?? 'CONTRATO',

                    // === OBJETO/DESCRIÇÃO ===
                    'objeto' => $contrato['objetoContrato'] ?? $contrato['objeto'] ?? 'Não informado',
                    'categoria' => $this->identificarCategoria($contrato),

                    // === MODALIDADE ===
                    'modalidade_nome' => $contrato['modalidade']['descricao'] ??
                                         $contrato['modalidadeNome'] ??
                                         'Não informado',
                    'modalidade_codigo' => $contrato['modalidade']['codigo'] ??
                                           $contrato['codigoModalidadeContratacao'] ??
                                           null,

                    // === VALORES ===
                    'valor_global' => (float) ($contrato['valorGlobal'] ?? $contrato['valorInicial'] ?? 0),
                    'valor_unitario' => (float) ($contrato['valorUnitario'] ??
                                                  $contrato['valorGlobal'] ??
                                                  $contrato['valorInicial'] ?? 0),

                    // === ÓRGÃO CONTRATANTE ===
                    'orgao_nome' => $contrato['orgaoEntidade']['razaoSocial'] ??
                                    $contrato['nomeOrgao'] ??
                                    'Não informado',
                    'orgao_cnpj' => $cnpjOrgao,
                    'orgao_uf' => $contrato['orgaoEntidade']['uf'] ?? $contrato['uf'] ?? null,
                    'orgao_municipio' => $contrato['orgaoEntidade']['municipio'] ??
                                         $contrato['municipio'] ?? null,
                    'uasg' => $contrato['codigoUnidadeGestora'] ??
                              $contrato['codigoUasgGestora'] ??
                              $contrato['uasg'] ?? null,

                    // === FORNECEDOR/CONTRATADO ===
                    'fornecedor_nome' => $contrato['nomeRazaoSocialFornecedor'] ??
                                         $contrato['fornecedor']['razaoSocial'] ??
                                         $contrato['fornecedor_nome'] ??
                                         'Não informado',
                    'fornecedor_cnpj' => $contrato['niFornecedor'] ??
                                         $contrato['cnpjContratado'] ??
                                         $contrato['fornecedor']['cnpj'] ??
                                         null,

                    // === DATAS ===
                    'data_publicacao_pncp' => $contrato['dataPublicacaoPncp'] ??
                                              $contrato['dataPublicacao'] ?? null,
                    'data_assinatura' => $contrato['dataAssinatura'] ?? null,
                    'data_vigencia_inicio' => $contrato['dataVigenciaInicio'] ?? null,
                    'data_vigencia_fim' => $contrato['dataVigenciaFim'] ?? null,

                    // === LOTE/ITEM (se disponível) ===
                    'numero_lote' => $contrato['numeroLote'] ?? null,
                    'numero_item' => $contrato['numeroItem'] ?? null,
                    'quantidade' => $contrato['quantidade'] ?? null,
                    'unidade_medida' => $contrato['unidadeMedida'] ?? 'CONTRATO',

                    // === STATUS ===
                    'situacao' => $contrato['situacao'] ??
                                  $contrato['statusContrato'] ??
                                  (isset($contrato['dataVigenciaFim']) && strtotime($contrato['dataVigenciaFim']) >= time() ? 'VIGENTE' : 'PUBLICADO'),

                    // === LINKS E DOCUMENTOS ===
                    'link_pncp' => $this->gerarLinkPNCP($numeroControle, $cnpjOrgao, $anoCompra, $sequencialCompra),
                    'link_edital' => $contrato['linkEdital'] ?? null,
                    'link_ata' => $contrato['linkAta'] ?? null,

                    // === AUDITORIA ===
                    'hash_sha256' => $hashDocumento,
                    'fonte' => 'PNCP',
                    'coletado_em' => now()->format('Y-m-d H:i:s'),

                    // === PAYLOAD COMPLETO (para detalhes) ===
                    'dados_completos' => $contrato
                ];
            }

            // Contar resultados por fonte
            $fontesPorTipo = [];
            foreach ($resultados as $resultado) {
                $fonte = $resultado['fonte'];
                if (!isset($fontesPorTipo[$fonte])) {
                    $fontesPorTipo[$fonte] = 0;
                }
                $fontesPorTipo[$fonte]++;
            }

            Log::info('Busca multi-fonte concluída', [
                'total_api_pncp' => count($data['items'] ?? $data['data'] ?? []),
                'total_filtrado' => count($resultados),
                'periodo' => "$dataInicial a $dataFinal",
                'fontes' => $fontesPorTipo
            ]);

            return response()->json([
                'success' => true,
                'contratos' => $resultados,
                'total' => count($resultados),
                'pagina' => $pagina,
                'periodo' => [
                    'inicio' => $dataInicial,
                    'fim' => $dataFinal,
                    'dias' => $diasAtras ?? 365
                ],
                'fontes_consultadas' => [
                    'PNCP' => $fontesPorTipo['PNCP'] ?? 0,
                    'COMPRAS.GOV' => $fontesPorTipo['COMPRAS.GOV'] ?? 0,
                    'CMED' => $fontesPorTipo['CMED'] ?? 0
                ],
                'info' => 'Busca integrada: PNCP + Compras.gov + CMED (medicamentos)'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar contratos na API PNCP: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar contratos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar ATAs DE REGISTRO DE PREÇOS (ARPs) no PNCP
     * Com filtros: UASG, vigência, UF, período
     * Salva no banco (cache persistente de 24h)
     *
     * GET /api/mapa-atas/buscar-arps
     */
    public function buscarArps(Request $request)
    {
        try {
            $uasg = $request->input('uasg');
            $uf = $request->input('uf');
            $vigentesApenas = $request->input('vigentes', true); // Default: apenas vigentes
            $dataInicio = $request->input('data_inicio'); // YYYY-MM-DD
            $dataFim = $request->input('data_fim'); // YYYY-MM-DD
            $termo = $request->input('termo'); // Busca por termo
            $pagina = $request->input('pagina', 1);
            $limite = $request->input('limite', 20);

            // Montar parâmetros para cache
            $cacheParams = [
                'tipo' => 'ARP',
                'uasg' => $uasg,
                'uf' => $uf,
                'vigentes' => $vigentesApenas,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'termo' => $termo,
            ];

            // Verificar cache (24h)
            $hashCache = ConsultaPncpCache::gerarHash($cacheParams);
            $cacheExistente = ConsultaPncpCache::buscarCache($hashCache);

            if ($cacheExistente) {
                Log::info('Cache HIT para busca de ARPs', ['hash' => $hashCache]);

                // Buscar ARPs salvas no banco com os mesmos critérios
                return $this->buscarArpsBanco($request);
            }

            // Cache MISS - buscar na API PNCP
            Log::info('Cache MISS - Buscando ARPs na API PNCP', $cacheParams);

            // Endpoint de ARPs do PNCP
            $urlBase = 'https://pncp.gov.br/api/consulta/v1/atas-registro-precos';

            $queryParams = [
                'pagina' => 1,
                'tamanhoPagina' => 500, // Max
            ];

            if ($dataInicio && $dataFim) {
                $queryParams['dataInicial'] = str_replace('-', '', $dataInicio);
                $queryParams['dataFinal'] = str_replace('-', '', $dataFim);
            } else {
                // Default: últimos 90 dias
                $queryParams['dataFinal'] = date('Ymd');
                $queryParams['dataInicial'] = date('Ymd', strtotime('-90 days'));
            }

            $url = $urlBase . '?' . http_build_query($queryParams);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'CestaDePrecos/1.0'
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception('Erro na API PNCP: ' . $response->status());
            }

            $dados = $response->json();
            $atas = $dados['data'] ?? $dados['items'] ?? [];

            // Salvar ARPs no banco
            $atasSalvas = [];
            foreach ($atas as $ataData) {
                $ata = $this->salvarArpNoBanco($ataData, auth()->id());
                if ($ata) {
                    $atasSalvas[] = $ata;
                }
            }

            // Salvar cache da consulta
            ConsultaPncpCache::salvarCache('ARP', $cacheParams, $dados, 24);

            Log::info('ARPs salvas no banco', ['total' => count($atasSalvas)]);

            // Retornar ARPs filtradas do banco
            return $this->buscarArpsBanco($request);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar ARPs: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar ARPs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar ARPs salvas no banco (com filtros)
     */
    private function buscarArpsBanco(Request $request)
    {
        $query = ArpCabecalho::query()->with('itens');

        // Filtro: UASG
        if ($request->filled('uasg')) {
            $query->porUasg($request->input('uasg'));
        }

        // Filtro: UF
        if ($request->filled('uf')) {
            $query->porUf($request->input('uf'));
        }

        // Filtro: Vigentes apenas
        if ($request->input('vigentes', true)) {
            $query->vigentes();
        }

        // Filtro: Período
        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->porPeriodo($request->input('data_inicio'), $request->input('data_fim'));
        }

        // Paginação
        $limite = $request->input('limite', 20);
        $atas = $query->orderBy('vigencia_inicio', 'desc')->paginate($limite);

        return response()->json([
            'sucesso' => true,
            'total' => $atas->total(),
            'pagina_atual' => $atas->currentPage(),
            'total_paginas' => $atas->lastPage(),
            'atas' => $atas->items(),
        ]);
    }

    /**
     * Salvar ARP no banco
     */
    private function salvarArpNoBanco(array $ataData, $userId = null)
    {
        try {
            $cnpjOrgao = NormalizadorHelper::normalizarCNPJ($ataData['cnpjOrgao'] ?? '');
            $numeroAta = $ataData['numeroAta'] ?? '';
            $anoCompra = $ataData['anoCompra'] ?? null;
            $sequencialCompra = $ataData['sequencialCompra'] ?? null;

            $ata = ArpCabecalho::updateOrCreate(
                [
                    'cnpj_orgao' => $cnpjOrgao,
                    'ano_compra' => $anoCompra,
                    'sequencial_compra' => $sequencialCompra,
                    'numero_ata' => $numeroAta,
                ],
                [
                    'orgao_gerenciador' => $ataData['orgaoGerenciador']['razaoSocial'] ?? 'N/A',
                    'uasg' => $ataData['uasg'] ?? null,
                    'vigencia_inicio' => $ataData['dataVigenciaInicio'] ?? null,
                    'vigencia_fim' => $ataData['dataVigenciaFim'] ?? null,
                    'situacao' => $ataData['situacao'] ?? 'Vigente',
                    'fornecedor_razao' => $ataData['fornecedor']['razaoSocial'] ?? null,
                    'fornecedor_cnpj' => isset($ataData['fornecedor']['cnpj'])
                        ? NormalizadorHelper::normalizarCNPJ($ataData['fornecedor']['cnpj'])
                        : null,
                    'fonte_url' => "https://pncp.gov.br/app/atas/{$cnpjOrgao}/{$anoCompra}/{$sequencialCompra}",
                    'payload_json' => $ataData,
                    'coletado_em' => now(),
                    'coletado_por' => $userId,
                ]
            );

            return $ata;
        } catch (\Exception $e) {
            Log::error('Erro ao salvar ARP: ' . $e->getMessage(), [
                'ata_data' => $ataData,
            ]);
            return null;
        }
    }

    /**
     * Buscar ITENS de uma ARP específica
     * Salva itens no banco (cache persistente)
     *
     * GET /api/mapa-atas/itens/{ataId}
     */
    public function itensDaAta($ataId)
    {
        try {
            $ata = ArpCabecalho::with('itens')->findOrFail($ataId);

            // Se já tem itens salvos e foram coletados há menos de 24h, retornar do banco
            $itemMaisRecente = $ata->itens()->orderBy('coletado_em', 'desc')->first();
            if ($itemMaisRecente && $itemMaisRecente->coletado_em->diffInHours(now()) < 24) {
                Log::info('Retornando itens do banco (cache válido)', ['ata_id' => $ataId]);

                return response()->json([
                    'sucesso' => true,
                    'ata' => $ata,
                    'itens' => $ata->itens,
                    'total_itens' => $ata->itens->count(),
                    'fonte' => 'BANCO (cache)',
                ]);
            }

            // Cache expirado ou não existe - buscar na API PNCP
            Log::info('Buscando itens da ARP na API PNCP', [
                'ata_id' => $ataId,
                'cnpj_orgao' => $ata->cnpj_orgao,
                'ano_compra' => $ata->ano_compra,
                'sequencial_compra' => $ata->sequencial_compra,
            ]);

            $url = "https://pncp.gov.br/api/consulta/v1/atas-registro-precos/{$ata->cnpj_orgao}/{$ata->ano_compra}/{$ata->sequencial_compra}/itens";

            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'CestaDePrecos/1.0'
                ])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception('Erro ao buscar itens na API PNCP: ' . $response->status());
            }

            $itensData = $response->json();

            // Salvar itens no banco
            $itensSalvos = [];
            foreach ($itensData as $itemData) {
                $item = $this->salvarItemArpNoBanco($ata->id, $itemData);
                if ($item) {
                    $itensSalvos[] = $item;
                }
            }

            Log::info('Itens da ARP salvos no banco', [
                'ata_id' => $ataId,
                'total_itens' => count($itensSalvos)
            ]);

            // Recarregar ARP com itens atualizados
            $ata->load('itens');

            return response()->json([
                'sucesso' => true,
                'ata' => $ata,
                'itens' => $ata->itens,
                'total_itens' => $ata->itens->count(),
                'fonte' => 'API PNCP (atualizado)',
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar itens da ARP: ' . $e->getMessage(), [
                'ata_id' => $ataId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Erro ao buscar itens da ARP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Salvar item de ARP no banco
     */
    private function salvarItemArpNoBanco($ataId, array $itemData)
    {
        try {
            $catmat = $itemData['codigoCATMAT'] ?? $itemData['catmat'] ?? null;
            $descricao = $itemData['descricao'] ?? $itemData['descricaoItem'] ?? '';
            $unidade = $itemData['unidadeMedida'] ?? $itemData['unidade'] ?? 'UN';
            $precoUnitario = $itemData['valorUnitario'] ?? $itemData['precoUnitario'] ?? 0;
            $quantidadeRegistrada = $itemData['quantidade'] ?? $itemData['quantidadeRegistrada'] ?? null;
            $lote = $itemData['numeroLote'] ?? $itemData['lote'] ?? null;

            // Auto-registrar CATMAT se não existir
            if ($catmat) {
                Catmat::updateOrCreate(
                    ['codigo' => $catmat],
                    [
                        'titulo' => $descricao,
                        'tipo' => 'CATMAT',
                        'unidade_padrao' => NormalizadorHelper::normalizarUnidade($unidade),
                        'fonte' => 'PNCP_AUTO',
                        'ativo' => true,
                    ]
                );
            }

            // Verificar se já existe (unique index)
            $hashDescricao = md5($descricao);
            $itemExistente = ArpItem::where('ata_id', $ataId)
                ->where('catmat', $catmat)
                ->where('lote', $lote)
                ->whereRaw('MD5(descricao) = ?', [$hashDescricao])
                ->first();

            if ($itemExistente) {
                // Atualizar preço se mudou
                $itemExistente->update([
                    'preco_unitario' => $precoUnitario,
                    'quantidade_registrada' => $quantidadeRegistrada,
                    'coletado_em' => now(),
                ]);
                return $itemExistente;
            }

            // Criar novo item
            $item = ArpItem::create([
                'ata_id' => $ataId,
                'catmat' => $catmat,
                'descricao' => $descricao,
                'unidade' => $unidade, // Será normalizado pelo mutator
                'preco_unitario' => $precoUnitario,
                'quantidade_registrada' => $quantidadeRegistrada,
                'lote' => $lote,
                'badge_confianca' => 'ALTA', // ARPs sempre ALTA
                'coletado_em' => now(),
            ]);

            return $item;

        } catch (\Exception $e) {
            Log::error('Erro ao salvar item de ARP: ' . $e->getMessage(), [
                'ata_id' => $ataId,
                'item_data' => $itemData,
            ]);
            return null;
        }
    }

    /**
     * Identificar categoria do contrato (Material ou Serviço)
     * Baseado em palavras-chave no objeto
     */
    private function identificarCategoria($contrato)
    {
        $objeto = strtolower($contrato['objetoContrato'] ?? $contrato['objeto'] ?? '');

        // Palavras-chave para SERVIÇOS
        $palavrasServico = [
            'serviço', 'servico', 'prestação', 'prestacao', 'manutenção', 'manutencao',
            'limpeza', 'vigilância', 'vigilancia', 'consultoria', 'assessoria',
            'treinamento', 'capacitação', 'capacitacao', 'locação', 'locacao',
            'terceirização', 'tercerizacao', 'suporte', 'instalação', 'instalacao'
        ];

        foreach ($palavrasServico as $palavra) {
            if (stripos($objeto, $palavra) !== false) {
                return 'SERVIÇO';
            }
        }

        // Palavras-chave para MATERIAIS
        $palavrasMaterial = [
            'aquisição', 'aquisicao', 'compra', 'fornecimento', 'material',
            'equipamento', 'produto', 'insumo', 'mercadoria', 'bem'
        ];

        foreach ($palavrasMaterial as $palavra) {
            if (stripos($objeto, $palavra) !== false) {
                return 'MATERIAL';
            }
        }

        // Default: tentar identificar pelo tipo de instrumento
        $tipo = $contrato['tipoInstrumento'] ?? '';
        if (stripos($tipo, 'ata') !== false) {
            return 'MATERIAL'; // ATAs geralmente são de material
        }

        return 'NÃO IDENTIFICADO';
    }

    /**
     * Gerar link correto do PNCP baseado no tipo de documento
     */
    private function gerarLinkPNCP($numeroControle, $cnpjOrgao, $anoCompra, $sequencialCompra)
    {
        // Se tem todos os dados, gerar link completo
        if ($cnpjOrgao && $anoCompra && $sequencialCompra) {
            $cnpjLimpo = preg_replace('/\D/', '', $cnpjOrgao);
            return "https://pncp.gov.br/app/contratos/{$cnpjLimpo}/{$anoCompra}/{$sequencialCompra}";
        }

        // Se tem número de controle, link simplificado
        if ($numeroControle) {
            return "https://pncp.gov.br/app/contratos/{$numeroControle}";
        }

        return null;
    }

    /**
     * Buscar contratos no Compras.gov (base local de preços)
     */
    private function buscarComprasGov($termo, $dataInicial, $dataFinal, $isCatmat = false)
    {
        try {
            // Buscar na tabela local de preços Compras.gov
            // ✅ CORRIGIDO: Usar prefixo cp_ explícito (não há mais prefixo automático)
            $query = \DB::connection('pgsql_main')
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

            // Buscar por CATMAT ou descrição
            if ($isCatmat) {
                $query->where('catmat_codigo', $termo);
            } else {
                // ✅ BUSCA PRECISA: Full-Text Search 'simple' (sem stemming)
                $termoEscapado = preg_replace('/[^a-zA-Z0-9À-ÿ\s]/', '', $termo);
                $query->whereRaw(
                    "to_tsvector('simple', descricao_item) @@ plainto_tsquery('simple', ?)",
                    [$termoEscapado]
                );
            }

            $precos = $query->where('preco_unitario', '>', 0)
                ->orderBy('data_compra', 'desc')
                ->limit(1000) // ✅ 31/10/2025: Aumentado de 200→1000
                ->get();

            if ($precos->isEmpty()) {
                return [];
            }

            // ✅ FILTRO DE PRECISÃO: Validar palavra COMPLETA (apenas para busca textual)
            if (!$isCatmat) {
                $precos = $precos->filter(function($preco) use ($termoEscapado) {
                    $descricaoNormalizada = mb_strtoupper($preco->descricao_item, 'UTF-8');
                    $termoNormalizado = mb_strtoupper($termoEscapado, 'UTF-8');
                    $pattern = '/\b' . preg_quote($termoNormalizado, '/') . '\b/u';
                    return preg_match($pattern, $descricaoNormalizada);
                });

                if ($precos->isEmpty()) {
                    return [];
                }
            }

            // Converter para formato padronizado
            $contratos = [];
            foreach ($precos as $preco) {
                // Gerar hash SHA256
                $hashDados = json_encode($preco);
                $hashDocumento = hash('sha256', $hashDados);

                $contratos[] = [
                    // === IDENTIFICAÇÃO ===
                    'numero_controle_pncp' => null,
                    'numero_contrato' => 'COMPRASGOV-' . $preco->catmat_codigo,
                    'ano_compra' => null,
                    'sequencial_compra' => null,
                    'tipo_documento' => 'PREÇO PRATICADO',

                    // === OBJETO/DESCRIÇÃO ===
                    'objetoContrato' => $preco->descricao_item, // Para filtro funcionar
                    'objeto' => $preco->descricao_item,
                    'categoria' => 'MATERIAL',

                    // === MODALIDADE ===
                    'modalidade_nome' => 'Compras.gov',
                    'modalidade_codigo' => null,

                    // === VALORES ===
                    'valor_global' => (float) ($preco->preco_unitario * $preco->quantidade),
                    'valor_unitario' => (float) $preco->preco_unitario,

                    // === ÓRGÃO CONTRATANTE ===
                    'orgao_nome' => $preco->orgao_nome ?? 'Órgão Federal',
                    'orgao_cnpj' => null,
                    'orgao_uf' => $preco->uf,
                    'orgao_municipio' => $preco->municipio,
                    'uasg' => null,

                    // === FORNECEDOR/CONTRATADO ===
                    'fornecedor_nome' => $preco->fornecedor_nome,
                    'fornecedor_cnpj' => $preco->fornecedor_cnpj,

                    // === DATAS ===
                    'data_publicacao_pncp' => $preco->data_compra,
                    'data_assinatura' => $preco->data_compra,
                    'data_vigencia_inicio' => $preco->data_compra,
                    'data_vigencia_fim' => null,

                    // === LOTE/ITEM ===
                    'numero_lote' => null,
                    'numero_item' => null,
                    'quantidade' => (float) $preco->quantidade,
                    'unidade_medida' => $preco->unidade_fornecimento ?? 'UN',

                    // === STATUS ===
                    'situacao' => 'CONCLUÍDO',

                    // === LINKS E DOCUMENTOS ===
                    // ATENÇÃO: Compras.gov não tem link direto para pregão/edital (dados agregados)
                    // Usar link_ata para Painel de Preços (análise estatística do CATMAT)
                    'link_pncp' => null, // Não tem pregão específico
                    'link_edital' => null, // Não tem edital específico
                    'link_ata' => $preco->catmat_codigo ? "https://paineldeprecos.planejamento.gov.br/analise-materiais/{$preco->catmat_codigo}" : null,

                    // === AUDITORIA ===
                    'hash_sha256' => $hashDocumento,
                    'fonte' => 'COMPRAS.GOV',
                    'coletado_em' => now()->format('Y-m-d H:i:s'),

                    // === PAYLOAD COMPLETO ===
                    'dados_completos' => $preco
                ];
            }

            return $contratos;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar no Compras.gov: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar medicamentos no CMED
     */
    private function buscarCMED($termo)
    {
        try {
            $medicamentos = \App\Models\MedicamentoCmed::buscarPorTermo($termo, 100);

            if ($medicamentos->isEmpty()) {
                return [];
            }

            // Converter para formato padronizado
            $contratos = [];
            foreach ($medicamentos as $med) {
                // Gerar hash SHA256
                $hashDados = json_encode($med);
                $hashDocumento = hash('sha256', $hashDados);

                // Criar descrição completa
                $descricao = trim($med->produto);
                if (empty($descricao) || $descricao === '-') {
                    $descricao = $med->substancia;
                } else {
                    $descricao = $med->substancia . ' - ' . $med->produto;
                }

                $contratos[] = [
                    // === IDENTIFICAÇÃO ===
                    'numero_controle_pncp' => null,
                    'numero_contrato' => 'CMED-' . $med->id,
                    'ano_compra' => null,
                    'sequencial_compra' => null,
                    'tipo_documento' => 'TABELA CMED',

                    // === OBJETO/DESCRIÇÃO ===
                    'objetoContrato' => $descricao, // Para filtro funcionar
                    'objeto' => $descricao,
                    'categoria' => 'MEDICAMENTO',

                    // === MODALIDADE ===
                    'modalidade_nome' => 'Tabela CMED/ANVISA',
                    'modalidade_codigo' => null,

                    // === VALORES ===
                    'valor_global' => (float) $med->pmc_0,
                    'valor_unitario' => (float) $med->pmc_0,

                    // === ÓRGÃO CONTRATANTE ===
                    'orgao_nome' => 'ANVISA - Câmara de Regulação do Mercado de Medicamentos',
                    'orgao_cnpj' => '26970615000145', // CNPJ ANVISA
                    'orgao_uf' => 'DF',
                    'orgao_municipio' => 'Brasília',
                    'uasg' => null,

                    // === FORNECEDOR/CONTRATADO ===
                    'fornecedor_nome' => $med->laboratorio,
                    'fornecedor_cnpj' => $med->cnpj_laboratorio,

                    // === DATAS ===
                    'data_publicacao_pncp' => $med->data_importacao ? $med->data_importacao->format('Y-m-d') : now()->format('Y-m-d'),
                    'data_assinatura' => null,
                    'data_vigencia_inicio' => $med->data_importacao ? $med->data_importacao->format('Y-m-d') : now()->format('Y-m-d'),
                    'data_vigencia_fim' => null,

                    // === LOTE/ITEM ===
                    'numero_lote' => null,
                    'numero_item' => null,
                    'quantidade' => 1,
                    'unidade_medida' => 'UN',

                    // === STATUS ===
                    'situacao' => 'VIGENTE',

                    // === LINKS E DOCUMENTOS ===
                    'link_pncp' => $med->ean1 ? "https://consultas.anvisa.gov.br/#/bulario/?numeroRegistro={$med->registro}" : null,
                    'link_edital' => 'https://www.gov.br/anvisa/pt-br/assuntos/medicamentos/cmed',
                    'link_ata' => $med->ean1 ? "https://consultas.anvisa.gov.br/#/medicamentos/q/?numEan={$med->ean1}" : null,

                    // === AUDITORIA ===
                    'hash_sha256' => $hashDocumento,
                    'fonte' => 'CMED',
                    'coletado_em' => now()->format('Y-m-d H:i:s'),

                    // === PAYLOAD COMPLETO ===
                    'dados_completos' => [
                        'ean' => $med->ean1,
                        'pmc_0' => (float) $med->pmc_0,
                        'pmc_12' => (float) $med->pmc_12,
                        'pmc_17' => (float) $med->pmc_17,
                        'pmc_18' => (float) $med->pmc_18,
                        'pmc_20' => (float) $med->pmc_20,
                        'mes_referencia' => $med->mes_referencia ?? 'Julho 2025',
                    ]
                ];
            }

            return $contratos;

        } catch (\Exception $e) {
            Log::error('Erro ao buscar no CMED: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Detectar se o termo parece ser medicamento
     */
    private function pareceMedicamento($termo)
    {
        $palavrasMedicamento = [
            'medicamento', 'remédio', 'remedio', 'farmaco', 'fármaco',
            'antibiótico', 'antibiotico', 'analgésico', 'analgesico',
            'dipirona', 'paracetamol', 'ibuprofeno', 'amoxicilina',
            'azitromicina', 'omeprazol', 'losartana', 'metformina',
            'insulina', 'vacina', 'soro', 'comprimido', 'capsula', 'cápsula',
            'injetável', 'injetavel', 'suspensão', 'suspensao', 'xarope',
            'pomada', 'creme', 'gel', 'solução', 'solucao'
        ];

        $termoLower = strtolower($termo);

        foreach ($palavrasMedicamento as $palavra) {
            if (stripos($termoLower, $palavra) !== false) {
                return true;
            }
        }

        return false;
    }
}
