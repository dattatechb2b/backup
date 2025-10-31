<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

/**
 * Service para consultas em tempo real na API do Comprasnet/Compras.gov.br
 *
 * APIs disponíveis:
 * - API Clássica SIASG: api.compras.dados.gov.br
 * - API Nova Swagger: dadosabertos.compras.gov.br
 */
class ComprasnetApiService
{
    // URLs base das APIs
    private const API_CLASSICA = 'https://api.compras.dados.gov.br';
    private const API_NOVA = 'https://dadosabertos.compras.gov.br';

    // Timeout padrão para requisições
    private const TIMEOUT = 30;

    // Cache padrão (15 minutos)
    private const CACHE_TTL = 900;

    /**
     * Busca PREÇOS PRATICADOS (min/méd/máx) na API Nova
     * Módulo: Pesquisa de Preços - Preços Praticados
     *
     * @param string $tipo 'material' ou 'servico'
     * @param string|int $codigo Código CATMAT/CATSER
     * @param array $filtros Filtros adicionais (opcional)
     * @return array
     */
    public function buscarPrecosPraticados(string $tipo, $codigo, array $filtros = []): array
    {
        $cacheKey = "comprasnet:precos_praticados:{$tipo}:{$codigo}:" . md5(json_encode($filtros));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tipo, $codigo, $filtros) {
            try {
                // Endpoint baseado no tipo
                $endpoint = $tipo === 'material'
                    ? '/precos-praticados/v1/materiais/consultar-material-detalhe'
                    : '/precos-praticados/v1/servicos/consultar-servico-detalhe';

                $url = self::API_NOVA . $endpoint;

                $params = array_merge([
                    'codigo' => $codigo,
                ], $filtros);

                $response = Http::timeout(self::TIMEOUT)
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'sucesso' => true,
                        'dados' => $this->formatarPrecosPraticados($data),
                        'fonte' => 'COMPRASNET-PRECOS-PRATICADOS',
                    ];
                }

                Log::warning("ComprasnetApi: Erro ao buscar preços praticados", [
                    'tipo' => $tipo,
                    'codigo' => $codigo,
                    'status' => $response->status(),
                ]);

                return ['sucesso' => false, 'erro' => 'Erro na API', 'dados' => []];

            } catch (\Exception $e) {
                Log::error("ComprasnetApi: Exceção ao buscar preços praticados", [
                    'tipo' => $tipo,
                    'codigo' => $codigo,
                    'erro' => $e->getMessage(),
                ]);

                return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
            }
        });
    }

    /**
     * Busca CONTRATOS na API Clássica SIASG
     *
     * @param array $filtros Filtros de busca
     * @param int $pagina Página (paginação)
     * @param int $limite Limite por página
     * @return array
     */
    public function buscarContratos(array $filtros = [], int $pagina = 1, int $limite = 50): array
    {
        try {
            $url = self::API_CLASSICA . '/contratos/v1/contratos.json';

            $params = array_merge([
                'offset' => ($pagina - 1) * $limite,
                // REMOVIDO: 'limit' - API não aceita esse parâmetro
            ], $filtros);

            $response = Http::timeout(self::TIMEOUT)
                ->retry(2, 100) // Retry: 2 tentativas, 100ms entre cada
                ->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();

                // Validar estrutura da resposta
                if (empty($data) || !is_array($data)) {
                    Log::warning("ComprasnetApi: Resposta vazia ou inválida", ['url' => $url]);
                    return ['sucesso' => false, 'erro' => 'Resposta inválida', 'dados' => []];
                }

                return [
                    'sucesso' => true,
                    'dados' => $data['_embedded']['contratos'] ?? [],
                    'total' => $data['count'] ?? 0,
                    'pagina' => $pagina,
                    'fonte' => 'COMPRASNET-CONTRATOS',
                ];
            }

            return ['sucesso' => false, 'erro' => 'Erro na API', 'dados' => []];

        } catch (ConnectionException $e) {
            Log::warning("ComprasnetApi: Timeout ao conectar", [
                'url' => $url ?? 'N/A',
                'erro' => $e->getMessage()
            ]);
            return ['sucesso' => false, 'erro' => 'Timeout', 'dados' => []];

        } catch (\Exception $e) {
            Log::error("ComprasnetApi: Erro ao buscar contratos", [
                'erro' => $e->getMessage(),
            ]);

            return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
        }
    }

    /**
     * Busca ITENS de contratos (preços unitários reais)
     *
     * @param string $termo Termo de busca (descrição do item)
     * @param array $filtros Filtros adicionais
     * @param int $limite Limite de resultados
     * @return array
     */
    public function buscarItensContratos(string $termo, array $filtros = [], int $limite = 100): array
    {
        $cacheKey = "comprasnet:itens:" . md5($termo . json_encode($filtros));

        Log::info('🟢 ComprasnetApi: buscarItensContratos()', [
            'termo' => $termo,
            'cache_key' => $cacheKey,
            'tem_cache' => Cache::has($cacheKey)
        ]);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($termo, $filtros, $limite) {
            try {
                // CORREÇÃO: API não aceita filtro 'descricao'
                // Buscar contratos RECENTES (último ano) sem filtro de descrição
                $dataMin = now()->subYear()->format('Ymd');
                $dataMax = now()->format('Ymd');

                $contratos = $this->buscarContratos(
                    array_merge([
                        'data_assinatura_min' => $dataMin,
                        'data_assinatura_max' => $dataMax,
                    ], $filtros),
                    1,
                    500 // Máximo da API
                );

                if (!$contratos['sucesso']) {
                    return $contratos;
                }

                // FILTRAR LOCALMENTE por termo no campo 'objeto'
                $contratosFiltrados = array_filter($contratos['dados'], function($contrato) use ($termo) {
                    $objeto = strtolower($contrato['objeto'] ?? '');
                    return stripos($objeto, $termo) !== false;
                });

                // Limitar a 10 contratos para evitar timeout
                $contratosFiltrados = array_slice($contratosFiltrados, 0, 10);

                // Para cada contrato filtrado, buscar os itens
                $itens = [];
                $itensDescartados = 0;
                $totalItensAnalisados = 0;

                Log::info('🟢 ComprasnetApi: Analisando contratos filtrados', [
                    'total_contratos' => count($contratosFiltrados),
                    'termo_busca' => $termo
                ]);

                foreach ($contratosFiltrados as $contrato) {
                    $itensContrato = $this->buscarItensPorContrato($contrato['id'] ?? null);

                    if ($itensContrato['sucesso']) {
                        foreach ($itensContrato['dados'] as $item) {
                            $totalItensAnalisados++;

                            // ✅ FILTRO ADICIONAL: Verificar se o ITEM também contém o termo de busca
                            $descricaoItem = strtolower($item['descricao'] ?? $item['descricaoDetalhada'] ?? '');

                            // Se o termo não está na descrição do item, pular
                            if (stripos($descricaoItem, strtolower($termo)) === false) {
                                $itensDescartados++;
                                continue;
                            }

                            // Enriquecer item com dados do contrato
                            $item['contrato'] = [
                                'numero' => $contrato['numero'] ?? null,
                                'orgao' => $contrato['orgao_nome'] ?? null,
                                'fornecedor' => $contrato['fornecedor_nome'] ?? null,
                                'data_assinatura' => $contrato['data_assinatura'] ?? null,
                            ];

                            $itens[] = $item;
                        }
                    }

                    // Limitar total de itens
                    if (count($itens) >= $limite) {
                        break;
                    }
                }

                Log::info('🟢 ComprasnetApi: Filtragem concluída', [
                    'termo' => $termo,
                    'itens_analisados' => $totalItensAnalisados,
                    'itens_descartados' => $itensDescartados,
                    'itens_retornados' => count($itens),
                    'taxa_rejeicao' => $totalItensAnalisados > 0
                        ? round(($itensDescartados / $totalItensAnalisados) * 100, 2) . '%'
                        : '0%'
                ]);

                return [
                    'sucesso' => true,
                    'dados' => $itens,
                    'total' => count($itens),
                    'fonte' => 'COMPRASNET-ITENS',
                ];

            } catch (\Exception $e) {
                Log::error("ComprasnetApi: Erro ao buscar itens", [
                    'termo' => $termo,
                    'erro' => $e->getMessage(),
                ]);

                return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
            }
        });
    }

    /**
     * Busca itens de um contrato específico
     *
     * @param int|string $contratoId
     * @return array
     */
    private function buscarItensPorContrato($contratoId): array
    {
        if (!$contratoId) {
            return ['sucesso' => false, 'dados' => []];
        }

        try {
            $url = self::API_CLASSICA . "/contratos/v1/contratos/{$contratoId}/itens.json";

            $response = Http::timeout(self::TIMEOUT)
                ->retry(2, 100)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();

                // Validar estrutura
                if (empty($data) || !is_array($data)) {
                    return ['sucesso' => false, 'dados' => []];
                }

                return [
                    'sucesso' => true,
                    'dados' => $data['_embedded']['itens'] ?? [],
                ];
            }

            return ['sucesso' => false, 'dados' => []];

        } catch (ConnectionException $e) {
            Log::debug("ComprasnetApi: Timeout ao buscar itens do contrato {$contratoId}");
            return ['sucesso' => false, 'dados' => []];

        } catch (\Exception $e) {
            return ['sucesso' => false, 'dados' => []];
        }
    }

    /**
     * Formata dados de Preços Praticados para padrão unificado
     *
     * @param array $data
     * @return array
     */
    private function formatarPrecosPraticados(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        // Adaptar estrutura conforme resposta real da API
        // (ajustar quando testar com dados reais)
        return [
            'codigo' => $data['codigo'] ?? null,
            'descricao' => $data['descricao'] ?? null,
            'unidade' => $data['unidade'] ?? null,
            'preco_minimo' => $data['precoMinimo'] ?? null,
            'preco_medio' => $data['precoMedio'] ?? null,
            'preco_maximo' => $data['precoMaximo'] ?? null,
            'quantidade_amostras' => $data['quantidadeAmostras'] ?? 0,
            'data_atualizacao' => $data['dataAtualizacao'] ?? null,
            'detalhes' => $data['detalhes'] ?? [],
        ];
    }

    /**
     * Busca por CATMAT/CATSER (catálogo de materiais/serviços)
     *
     * @param string $termo Termo de busca
     * @param int $limite Limite de resultados
     * @return array
     */
    public function buscarCatalogo(string $termo, int $limite = 50): array
    {
        $cacheKey = "comprasnet:catalogo:" . md5($termo);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($termo, $limite) {
            try {
                $url = self::API_NOVA . '/catalogo/v1/materiais';

                $params = [
                    'q' => $termo,
                    'limit' => $limite,
                ];

                $response = Http::timeout(self::TIMEOUT)->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'sucesso' => true,
                        'dados' => $data['dados'] ?? $data,
                        'fonte' => 'COMPRASNET-CATALOGO',
                    ];
                }

                return ['sucesso' => false, 'erro' => 'Erro na API', 'dados' => []];

            } catch (\Exception $e) {
                Log::error("ComprasnetApi: Erro ao buscar catálogo", [
                    'termo' => $termo,
                    'erro' => $e->getMessage(),
                ]);

                return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
            }
        });
    }

    /**
     * Limpa cache de uma chave específica ou tudo do Comprasnet
     *
     * @param string|null $chave
     * @return bool
     */
    public function limparCache(?string $chave = null): bool
    {
        if ($chave) {
            return Cache::forget($chave);
        }

        // Limpar todo cache do Comprasnet (prefixo)
        // Nota: Método pode variar dependendo do driver de cache
        Cache::flush(); // Simplificado - ajustar se necessário

        return true;
    }
}
