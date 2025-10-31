<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

/**
 * Service para consultas em tempo real na API CKAN do TCE-RS
 *
 * API: https://dados.tce.rs.gov.br/api/3/action/
 * Portal: https://dados.tce.rs.gov.br/
 */
class TceRsApiService
{
    // URL base da API CKAN
    private const API_BASE = 'https://dados.tce.rs.gov.br/api/3/action';

    // Timeout padrão para requisições
    private const TIMEOUT = 30;

    // Cache padrão (15 minutos)
    private const CACHE_TTL = 900;

    /**
     * Busca datasets (packages) no catálogo CKAN
     *
     * @param string $query Termo de busca
     * @param int $limite Limite de resultados
     * @return array
     */
    public function buscarDatasets(string $query, int $limite = 20): array
    {
        $cacheKey = "tce_rs:datasets:" . md5($query);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $limite) {
            try {
                $url = self::API_BASE . '/package_search';

                $params = [
                    'q' => $query,
                    'rows' => $limite,
                ];

                $response = Http::timeout(self::TIMEOUT)
                    ->retry(2, 100)
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    // Validar estrutura
                    if (empty($data) || !isset($data['result'])) {
                        Log::warning("TceRsApi: Resposta inválida", ['url' => $url]);
                        return ['sucesso' => false, 'erro' => 'Resposta inválida', 'dados' => []];
                    }

                    return [
                        'sucesso' => true,
                        'dados' => $data['result']['results'] ?? [],
                        'total' => $data['result']['count'] ?? 0,
                        'fonte' => 'TCE-RS-CKAN',
                    ];
                }

                return ['sucesso' => false, 'erro' => 'Erro na API CKAN', 'dados' => []];

            } catch (ConnectionException $e) {
                Log::warning("TceRsApi: Timeout ao buscar datasets", [
                    'query' => $query,
                    'erro' => $e->getMessage()
                ]);
                return ['sucesso' => false, 'erro' => 'Timeout', 'dados' => []];

            } catch (\Exception $e) {
                Log::error("TceRsApi: Erro ao buscar datasets", [
                    'query' => $query,
                    'erro' => $e->getMessage(),
                ]);

                return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
            }
        });
    }

    /**
     * Busca em DataStore (quando resource tem datastore_active=true)
     * Permite busca SQL-like em dados estruturados
     *
     * @param string $resourceId ID da resource no CKAN
     * @param string $termo Termo de busca (q parameter)
     * @param array $filtros Filtros por campo (filters parameter)
     * @param int $limite Limite de resultados
     * @param int $offset Offset para paginação
     * @return array
     */
    public function buscarDataStore(
        string $resourceId,
        string $termo = '',
        array $filtros = [],
        int $limite = 100,
        int $offset = 0
    ): array {
        $cacheKey = "tce_rs:datastore:{$resourceId}:" . md5($termo . json_encode($filtros) . $offset);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($resourceId, $termo, $filtros, $limite, $offset) {
            try {
                $url = self::API_BASE . '/datastore_search';

                $params = [
                    'resource_id' => $resourceId,
                    'limit' => $limite,
                    'offset' => $offset,
                ];

                if ($termo) {
                    $params['q'] = $termo;
                }

                if (!empty($filtros)) {
                    $params['filters'] = json_encode($filtros);
                }

                $response = Http::timeout(self::TIMEOUT)
                    ->retry(2, 100)
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    // Validar estrutura
                    if (empty($data) || !isset($data['result'])) {
                        Log::warning("TceRsApi: Resposta DataStore inválida", ['url' => $url]);
                        return ['sucesso' => false, 'erro' => 'Resposta inválida', 'dados' => []];
                    }

                    return [
                        'sucesso' => true,
                        'dados' => $data['result']['records'] ?? [],
                        'total' => $data['result']['total'] ?? 0,
                        'campos' => $data['result']['fields'] ?? [],
                        'fonte' => 'TCE-RS-DATASTORE',
                    ];
                }

                return ['sucesso' => false, 'erro' => 'Erro na API DataStore', 'dados' => []];

            } catch (ConnectionException $e) {
                Log::warning("TceRsApi: Timeout ao buscar DataStore", [
                    'resource_id' => $resourceId,
                    'termo' => $termo,
                    'erro' => $e->getMessage()
                ]);
                return ['sucesso' => false, 'erro' => 'Timeout', 'dados' => []];

            } catch (\Exception $e) {
                Log::error("TceRsApi: Erro ao buscar no DataStore", [
                    'resource_id' => $resourceId,
                    'termo' => $termo,
                    'erro' => $e->getMessage(),
                ]);

                return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
            }
        });
    }

    /**
     * Busca ITENS de CONTRATOS no TCE-RS
     * ESTRATÉGIA HÍBRIDA:
     * 1. Primeiro busca no BANCO LOCAL (rápido, dados já importados)
     * 2. Se não encontrar suficiente, busca na API externa (lento)
     *
     * @param string $termo Descrição do item a buscar
     * @param int $limite Limite de resultados
     * @return array
     */
    public function buscarItensContratos(string $termo, int $limite = 100): array
    {
        try {
            // 🚀 PRIORIDADE 1: Buscar no BANCO LOCAL (dados importados)
            $itensLocais = $this->buscarItensContratosLocal($termo, $limite);

            // ✅ SE ENCONTROU ALGO NO LOCAL, RETORNA DIRETO (não precisa buscar na API externa)
            if ($itensLocais['sucesso'] && count($itensLocais['dados']) > 0) {
                Log::info("✅ TceRsApi: Retornando dados do banco local", [
                    'termo' => $termo,
                    'total' => count($itensLocais['dados'])
                ]);
                return $itensLocais;
            }

            // 🌐 PRIORIDADE 2: Se não achou nada localmente, buscar na API externa
            Log::info("⚠️ TceRsApi: Nada no local, buscando na API externa do TCE-RS", ['termo' => $termo]);

            // 1. Buscar datasets de contratos (REDUZIDO: 50 → 20)
            $datasetsContratos = $this->buscarDatasets('contratos', 20);

            if (!$datasetsContratos['sucesso']) {
                return $datasetsContratos;
            }

            $todosItens = [];

            // 2. Para cada dataset, buscar a resource ITEM_CON
            foreach ($datasetsContratos['dados'] as $dataset) {
                $resources = $dataset['resources'] ?? [];

                foreach ($resources as $resource) {
                    // Procurar resource ITEM_CON (itens de contratos)
                    $resourceName = strtoupper($resource['name'] ?? '');

                    if (
                        strpos($resourceName, 'ITEM_CON') !== false ||
                        strpos($resourceName, 'ITENS') !== false
                    ) {
                        // Verificar se tem DataStore ativo
                        if (!empty($resource['datastore_active'])) {
                            // Buscar no DataStore (REDUZIDO: limite menor para acelerar)
                            $itens = $this->buscarDataStore(
                                $resource['id'],
                                $termo,
                                [],
                                50 // REDUZIDO de 100 para 50
                            );

                            if ($itens['sucesso']) {
                                // Enriquecer itens com info do dataset (órgão)
                                foreach ($itens['dados'] as &$item) {
                                    $item['_dataset'] = [
                                        'orgao' => $dataset['organization']['title'] ?? 'Não informado',
                                        'dataset_name' => $dataset['title'] ?? '',
                                    ];
                                }

                                $todosItens = array_merge($todosItens, $itens['dados']);
                            }
                        }

                        // BREAK ANTECIPADO: Se já temos itens suficientes, parar
                        if (count($todosItens) >= $limite) {
                            break 2; // Sai dos 2 loops
                        }
                    }
                }
            }

            return [
                'sucesso' => true,
                'dados' => $this->formatarItensContratos(array_slice($todosItens, 0, $limite)),
                'total' => count($todosItens),
                'fonte' => 'TCE-RS-CONTRATOS',
            ];

        } catch (\Exception $e) {
            Log::error("TceRsApi: Erro ao buscar itens de contratos", [
                'termo' => $termo,
                'erro' => $e->getMessage(),
            ]);

            return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
        }
    }

    /**
     * Busca ITENS de LICITAÇÕES no TCE-RS
     * Procura pela resource "ITEM_PROPOSTA" nos datasets de licitações
     *
     * @param string $termo Descrição do item a buscar
     * @param int $limite Limite de resultados
     * @return array
     */
    public function buscarItensLicitacoes(string $termo, int $limite = 100): array
    {
        try {
            // 1. Buscar datasets de licitações (REDUZIDO: 50 → 20)
            $datasetsLicitacoes = $this->buscarDatasets('licitacoes', 20);

            if (!$datasetsLicitacoes['sucesso']) {
                return $datasetsLicitacoes;
            }

            $todosItens = [];

            // 2. Para cada dataset, buscar a resource ITEM_PROPOSTA
            foreach ($datasetsLicitacoes['dados'] as $dataset) {
                $resources = $dataset['resources'] ?? [];

                foreach ($resources as $resource) {
                    $resourceName = strtoupper($resource['name'] ?? '');

                    if (
                        strpos($resourceName, 'ITEM_PROPOSTA') !== false ||
                        strpos($resourceName, 'ITEM') !== false
                    ) {
                        if (!empty($resource['datastore_active'])) {
                            $itens = $this->buscarDataStore(
                                $resource['id'],
                                $termo,
                                [],
                                50 // REDUZIDO de 100 para 50
                            );

                            if ($itens['sucesso']) {
                                foreach ($itens['dados'] as &$item) {
                                    $item['_dataset'] = [
                                        'orgao' => $dataset['organization']['title'] ?? 'Não informado',
                                        'dataset_name' => $dataset['title'] ?? '',
                                    ];
                                }

                                $todosItens = array_merge($todosItens, $itens['dados']);
                            }
                        }

                        // BREAK ANTECIPADO
                        if (count($todosItens) >= $limite) {
                            break 2;
                        }
                    }
                }
            }

            return [
                'sucesso' => true,
                'dados' => $this->formatarItensLicitacoes(array_slice($todosItens, 0, $limite)),
                'total' => count($todosItens),
                'fonte' => 'TCE-RS-LICITACOES',
            ];

        } catch (\Exception $e) {
            Log::error("TceRsApi: Erro ao buscar itens de licitações", [
                'termo' => $termo,
                'erro' => $e->getMessage(),
            ]);

            return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
        }
    }

    /**
     * Busca ITENS de CONTRATOS no BANCO LOCAL (dados já importados)
     * Usa tabela cp_itens_contrato_externo
     *
     * @param string $termo Descrição do item a buscar
     * @param int $limite Limite de resultados
     * @return array
     */
    private function buscarItensContratosLocal(string $termo, int $limite = 100): array
    {
        try {
            Log::info('🔍 TCE-RS LOCAL: Iniciando busca', ['termo' => $termo]);

            // Busca simplificada usando LIKE ao invés de fulltext (mais compatível)
            $itens = \DB::table('cp_itens_contrato_externo as i')
                ->join('cp_contratos_externos as c', 'i.contrato_id', '=', 'c.id')
                ->where('i.descricao', 'ILIKE', "%{$termo}%")
                ->where('i.valor_unitario', '>', 0)
                ->where('i.qualidade_score', '>=', 70)
                ->where('c.fonte', 'LIKE', 'TCE-RS%')
                ->orderBy('c.data_assinatura', 'desc')
                ->limit($limite)
                ->select([
                    'i.descricao',
                    'i.valor_unitario',
                    'i.unidade',
                    'i.quantidade',
                    'i.catmat',
                    'i.qualidade_score',
                    'c.orgao_nome',
                    'c.orgao_municipio',
                    'c.numero_contrato',
                    'c.data_assinatura',
                ])
                ->get()
                ->toArray();

            if (empty($itens)) {
                Log::info('🔍 TCE-RS LOCAL: Nenhum resultado', ['termo' => $termo]);
                return ['sucesso' => true, 'dados' => [], 'total' => 0, 'fonte' => 'TCE-RS-LOCAL'];
            }

            Log::info('✅ TCE-RS LOCAL: Resultados encontrados!', [
                'termo' => $termo,
                'total' => count($itens)
            ]);

            // Formatar para padrão unificado
            $formatados = [];
            foreach ($itens as $item) {
                $itemObj = (array) $item; // Converter stdClass para array
                $formatados[] = [
                    'descricao' => $itemObj['descricao'],
                    'valor_unitario' => (float) $itemObj['valor_unitario'],
                    'quantidade' => (float) ($itemObj['quantidade'] ?? 1),
                    'unidade' => $itemObj['unidade'] ?? 'UN',
                    'catmat' => $itemObj['catmat'] ?? null,
                    'orgao' => $itemObj['orgao_nome'] ?? 'Não informado',
                    'fonte' => 'TCE-RS-LOCAL',
                    'tipo' => 'CONTRATO',
                ];
            }

            Log::info('✅ TCE-RS LOCAL: Retornando dados formatados', [
                'total_formatados' => count($formatados)
            ]);

            return [
                'sucesso' => true,
                'dados' => $formatados,
                'total' => count($formatados),
                'fonte' => 'TCE-RS-LOCAL',
            ];

        } catch (\Exception $e) {
            Log::warning("TceRsApi: Erro ao buscar no banco local", [
                'termo' => $termo,
                'erro' => $e->getMessage(),
            ]);

            return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
        }
    }

    /**
     * Formata itens de contratos para padrão unificado
     *
     * @param array $itens
     * @return array
     */
    private function formatarItensContratos(array $itens): array
    {
        $formatados = [];

        foreach ($itens as $item) {
            $formatados[] = [
                'descricao' => $item['DS_ITEM'] ?? $item['ds_item'] ?? '',
                'valor_unitario' => $this->normalizarValor($item['VL_ITEM_CONTRATO'] ?? $item['vl_item_contrato'] ?? 0),
                'quantidade' => $item['QT_ITEM_CONTRATO'] ?? $item['qt_item_contrato'] ?? 0,
                'unidade' => $item['DS_UNIDADE_FORNECIMENTO'] ?? $item['ds_unidade_fornecimento'] ?? 'UN',
                'catmat' => $item['NU_CATMATSERITEM'] ?? $item['nu_catmatseritem'] ?? null,
                'orgao' => $item['_dataset']['orgao'] ?? 'Não informado',
                'fonte' => 'TCE-RS',
                'tipo' => 'CONTRATO',
            ];
        }

        return $formatados;
    }

    /**
     * Formata itens de licitações para padrão unificado
     *
     * @param array $itens
     * @return array
     */
    private function formatarItensLicitacoes(array $itens): array
    {
        $formatados = [];

        foreach ($itens as $item) {
            $formatados[] = [
                'descricao' => $item['DS_ITEM'] ?? $item['ds_item'] ?? '',
                'valor_unitario' => $this->normalizarValor(
                    $item['VL_ITEM_UNITARIO'] ?? $item['vl_item_unitario'] ??
                    $item['VL_ITEM_PROPOSTA'] ?? $item['vl_item_proposta'] ?? 0
                ),
                'quantidade' => $item['QT_ITEM_LICITACAO'] ?? $item['qt_item_licitacao'] ?? 0,
                'unidade' => $item['DS_UNIDADE_FORNECIMENTO'] ?? $item['ds_unidade_fornecimento'] ?? 'UN',
                'catmat' => null,
                'orgao' => $item['_dataset']['orgao'] ?? 'Não informado',
                'fonte' => 'TCE-RS',
                'tipo' => 'LICITACAO',
            ];
        }

        return $formatados;
    }

    /**
     * Normaliza valor (pode vir como string com vírgula)
     *
     * @param mixed $valor
     * @return float
     */
    private function normalizarValor($valor): float
    {
        if (is_numeric($valor)) {
            return (float) $valor;
        }

        if (is_string($valor)) {
            // Remove pontos e troca vírgula por ponto
            $valor = str_replace(['.', ','], ['', '.'], $valor);
            return (float) $valor;
        }

        return 0.0;
    }

    /**
     * Obtém detalhes de um dataset específico
     *
     * @param string $datasetId
     * @return array
     */
    public function obterDataset(string $datasetId): array
    {
        try {
            $url = self::API_BASE . '/package_show';

            $response = Http::timeout(self::TIMEOUT)
                ->retry(2, 100)
                ->get($url, [
                    'id' => $datasetId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Validar estrutura
                if (empty($data) || !isset($data['result'])) {
                    Log::warning("TceRsApi: Dataset inválido", ['dataset_id' => $datasetId]);
                    return ['sucesso' => false, 'erro' => 'Resposta inválida'];
                }

                return [
                    'sucesso' => true,
                    'dados' => $data['result'] ?? null,
                ];
            }

            return ['sucesso' => false, 'erro' => 'Dataset não encontrado'];

        } catch (ConnectionException $e) {
            Log::warning("TceRsApi: Timeout ao obter dataset", [
                'dataset_id' => $datasetId,
                'erro' => $e->getMessage()
            ]);
            return ['sucesso' => false, 'erro' => 'Timeout'];

        } catch (\Exception $e) {
            Log::error("TceRsApi: Erro ao obter dataset", [
                'dataset_id' => $datasetId,
                'erro' => $e->getMessage()
            ]);
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Limpa cache do TCE-RS
     *
     * @param string|null $chave
     * @return bool
     */
    public function limparCache(?string $chave = null): bool
    {
        if ($chave) {
            return Cache::forget($chave);
        }

        Cache::flush();
        return true;
    }
}
