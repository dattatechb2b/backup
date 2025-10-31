<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

/**
 * Service para consultas na API NOVA do Compras.gov.br
 *
 * API: https://dadosabertos.compras.gov.br
 * Swagger: https://dadosabertos.compras.gov.br/swagger-ui/index.html
 *
 * Módulos disponíveis:
 * - Pesquisa de Preços (Preços Praticados) - PRINCIPAL
 * - Contratos (Contexto, não preço)
 */
class ComprasnetApiNovaService
{
    // URL base da API Nova
    private const API_BASE = 'https://dadosabertos.compras.gov.br';

    // Timeout padrão
    private const TIMEOUT = 30;

    // Cache: 24 horas
    private const CACHE_TTL = 86400;

    /**
     * Busca materiais (lista paginada)
     * Endpoint: /modulo-pesquisa-preco/1_consultarMaterial
     *
     * @param int $pagina Número da página (obrigatório)
     * @param int $tamanhoPagina Tamanho da página (obrigatório, máx 500)
     * @return array
     */
    public function buscarMateriais(int $pagina = 1, int $tamanhoPagina = 100): array
    {
        $cacheKey = "comprasnet_nova:materiais:" . md5($pagina . $tamanhoPagina);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pagina, $tamanhoPagina) {
            try {
                $url = self::API_BASE . '/modulo-pesquisa-preco/1_consultarMaterial';

                // Parâmetros OBRIGATÓRIOS
                $params = [
                    'pagina' => $pagina,
                    'tamanhoPagina' => min($tamanhoPagina, 500), // Máximo 500
                ];

                $response = Http::timeout(self::TIMEOUT)
                    ->retry(2, 100)
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'sucesso' => true,
                        'dados' => $data ?? [],
                        'total' => is_array($data) ? count($data) : 0,
                        'pagina' => $pagina,
                        'fonte' => 'COMPRASNET-API-NOVA',
                    ];
                }

                Log::warning("ComprasnetApiNova: Erro ao buscar materiais", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['sucesso' => false, 'erro' => 'Erro na API: ' . $response->status(), 'dados' => []];

            } catch (ConnectionException $e) {
                Log::warning("ComprasnetApiNova: Timeout ao buscar materiais", [
                    'erro' => $e->getMessage()
                ]);
                return ['sucesso' => false, 'erro' => 'Timeout', 'dados' => []];

            } catch (\Exception $e) {
                Log::error("ComprasnetApiNova: Erro ao buscar materiais", [
                    'erro' => $e->getMessage(),
                ]);

                return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
            }
        });
    }

    /**
     * Busca detalhe de material com PREÇOS PRATICADOS (min/méd/máx)
     * Endpoint: /modulo-pesquisa-preco/2_consultarMaterialDetalhe
     *
     * ESTE É O MÉTODO PRINCIPAL PARA OBTER PREÇOS!
     *
     * @param int|string $catmat Código CATMAT
     * @param int $pagina Número da página (obrigatório)
     * @param int $tamanhoPagina Tamanho da página (obrigatório)
     * @return array
     */
    public function buscarMaterialDetalhe($catmat, int $pagina = 1, int $tamanhoPagina = 100): array
    {
        $cacheKey = "comprasnet_nova:material_detalhe:{$catmat}:{$pagina}:{$tamanhoPagina}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($catmat, $pagina, $tamanhoPagina) {
            try {
                $url = self::API_BASE . '/modulo-pesquisa-preco/2_consultarMaterialDetalhe';

                // Parâmetros OBRIGATÓRIOS
                $params = [
                    'codigoItemCatalogo' => $catmat, // Nome correto do parâmetro!
                    'pagina' => $pagina,
                    'tamanhoPagina' => min($tamanhoPagina, 500),
                ];

                $response = Http::timeout(self::TIMEOUT)
                    ->retry(2, 100)
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    if (empty($data)) {
                        return ['sucesso' => false, 'erro' => 'Material não encontrado', 'dados' => []];
                    }

                    // Formatar resposta com preços praticados
                    return [
                        'sucesso' => true,
                        'dados' => $this->formatarPrecosPraticados($data, 'material'),
                        'fonte' => 'COMPRASNET-PRECOS-PRATICADOS',
                    ];
                }

                Log::warning("ComprasnetApiNova: Erro ao buscar detalhe material", [
                    'catmat' => $catmat,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['sucesso' => false, 'erro' => 'Erro na API: ' . $response->status(), 'dados' => []];

            } catch (ConnectionException $e) {
                Log::warning("ComprasnetApiNova: Timeout ao buscar detalhe material", [
                    'catmat' => $catmat,
                    'erro' => $e->getMessage()
                ]);
                return ['sucesso' => false, 'erro' => 'Timeout', 'dados' => []];

            } catch (\Exception $e) {
                Log::error("ComprasnetApiNova: Erro ao buscar detalhe material", [
                    'catmat' => $catmat,
                    'erro' => $e->getMessage(),
                ]);

                return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
            }
        });
    }

    /**
     * Busca serviços por termo (lista)
     * Endpoint: /modulo-pesquisa-preco/3_consultarServico
     *
     * @param string $termo Termo de busca
     * @param int $pagina Número da página
     * @param int $tamanhoPagina Tamanho da página
     * @return array
     */
    public function buscarServicos(string $termo, int $pagina = 1, int $tamanhoPagina = 100): array
    {
        $cacheKey = "comprasnet_nova:servicos:" . md5($termo . $pagina . $tamanhoPagina);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($termo, $pagina, $tamanhoPagina) {
            try {
                $url = self::API_BASE . '/modulo-pesquisa-preco/3_consultarServico';

                $params = [
                    'termo' => $termo,
                    'pagina' => $pagina,
                    'tamanhoPagina' => min($tamanhoPagina, 500),
                ];

                $response = Http::timeout(self::TIMEOUT)
                    ->retry(2, 100)
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'sucesso' => true,
                        'dados' => $data['dados'] ?? $data['data'] ?? [],
                        'total' => $data['total'] ?? count($data['dados'] ?? []),
                        'pagina' => $pagina,
                        'fonte' => 'COMPRASNET-API-NOVA',
                    ];
                }

                return ['sucesso' => false, 'erro' => 'Erro na API', 'dados' => []];

            } catch (ConnectionException $e) {
                Log::warning("ComprasnetApiNova: Timeout ao buscar serviços", [
                    'termo' => $termo,
                    'erro' => $e->getMessage()
                ]);
                return ['sucesso' => false, 'erro' => 'Timeout', 'dados' => []];

            } catch (\Exception $e) {
                Log::error("ComprasnetApiNova: Erro ao buscar serviços", [
                    'termo' => $termo,
                    'erro' => $e->getMessage(),
                ]);

                return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
            }
        });
    }

    /**
     * Busca detalhe de serviço com PREÇOS PRATICADOS
     * Endpoint: /modulo-pesquisa-preco/4_consultarServicoDetalhe
     *
     * @param int|string $catser Código CATSER
     * @param array $filtros Filtros adicionais
     * @return array
     */
    public function buscarServicoDetalhe($catser, array $filtros = []): array
    {
        $cacheKey = "comprasnet_nova:servico_detalhe:{$catser}:" . md5(json_encode($filtros));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($catser, $filtros) {
            try {
                $url = self::API_BASE . '/modulo-pesquisa-preco/4_consultarServicoDetalhe';

                $params = array_merge([
                    'codigoServico' => $catser,
                ], $filtros);

                $response = Http::timeout(self::TIMEOUT)
                    ->retry(2, 100)
                    ->get($url, $params);

                if ($response->successful()) {
                    $data = $response->json();

                    if (empty($data)) {
                        return ['sucesso' => false, 'erro' => 'Serviço não encontrado', 'dados' => []];
                    }

                    return [
                        'sucesso' => true,
                        'dados' => $this->formatarPrecosPraticados($data, 'servico'),
                        'fonte' => 'COMPRASNET-PRECOS-PRATICADOS',
                    ];
                }

                return ['sucesso' => false, 'erro' => 'Erro na API', 'dados' => []];

            } catch (ConnectionException $e) {
                Log::warning("ComprasnetApiNova: Timeout ao buscar detalhe serviço", [
                    'catser' => $catser,
                    'erro' => $e->getMessage()
                ]);
                return ['sucesso' => false, 'erro' => 'Timeout', 'dados' => []];

            } catch (\Exception $e) {
                Log::error("ComprasnetApiNova: Erro ao buscar detalhe serviço", [
                    'catser' => $catser,
                    'erro' => $e->getMessage(),
                ]);

                return ['sucesso' => false, 'erro' => $e->getMessage(), 'dados' => []];
            }
        });
    }

    /**
     * Busca PREÇOS PRATICADOS por CATMAT específico
     *
     * MÉTODO PRINCIPAL PARA USAR NA PESQUISA RÁPIDA!
     *
     * @param int|string $catmat Código CATMAT
     * @return array
     */
    public function buscarPrecosPraticados($catmat): array
    {
        return $this->buscarMaterialDetalhe($catmat, 1, 100);
    }

    /**
     * Formata dados de Preços Praticados para padrão unificado
     *
     * @param array $data Dados da API
     * @param string $tipo 'material' ou 'servico'
     * @return array
     */
    private function formatarPrecosPraticados(array $data, string $tipo): array
    {
        return [
            'catmat' => $data['codigo'] ?? $data['codigoMaterial'] ?? null,
            'catser' => $data['codigoServico'] ?? null,
            'descricao' => $data['descricao'] ?? null,
            'unidade' => $data['unidade'] ?? $data['unidadeFornecimento'] ?? 'UN',

            // PREÇOS - CAMPOS PRINCIPAIS!
            'preco_minimo' => (float) ($data['precoMinimo'] ?? $data['valorMinimo'] ?? 0),
            'preco_medio' => (float) ($data['precoMedio'] ?? $data['valorMedio'] ?? 0),
            'preco_maximo' => (float) ($data['precoMaximo'] ?? $data['valorMaximo'] ?? 0),

            'quantidade_amostras' => (int) ($data['quantidadeAmostras'] ?? $data['totalContratacoes'] ?? 0),
            'data_atualizacao' => $data['dataAtualizacao'] ?? $data['dataBase'] ?? now()->format('Y-m-d'),
            'periodo_referencia' => $data['periodoReferencia'] ?? $data['periodo'] ?? '12 meses',

            // Metadata
            'tipo' => $tipo,
            'detalhes' => $data['detalhes'] ?? [],
        ];
    }

    /**
     * Limpa cache
     *
     * @param string|null $chave Chave específica ou null para limpar tudo
     * @return bool
     */
    public function limparCache(?string $chave = null): bool
    {
        if ($chave) {
            return Cache::forget($chave);
        }

        // Limpar com prefixo
        Cache::flush();
        return true;
    }
}
