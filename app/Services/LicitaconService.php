<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LicitaconService
{
    /**
     * URL base dos dados abertos do TCE-RS
     */
    private const BASE_URL = 'https://dados.tce.rs.gov.br/dados/licitacon/licitacao/ano/';

    /**
     * Tempo de cache (24 horas)
     */
    private const CACHE_TTL = 86400; // 24 horas em segundos

    /**
     * Diretório de cache
     */
    private const CACHE_DIR = 'licitacon/cache';

    /**
     * Buscar itens no Licitacon por termo
     *
     * @param string $termo
     * @param int $limite
     * @return array
     */
    public function buscar($termo, $limite = 50)
    {
        try {
            Log::info('[LicitaconService] Buscando por termo:', ['termo' => $termo]);

            // Ano atual
            $ano = date('Y');

            // 1. Obter arquivo ITEM.csv
            $csvItens = $this->obterCSV($ano, 'ITEM');
            if (!$csvItens) {
                Log::warning('[LicitaconService] Não foi possível obter CSV de itens');
                return [];
            }

            // 2. Obter arquivo LICITACAO.csv para completar dados
            $csvLicitacoes = $this->obterCSV($ano, 'LICITACAO');

            // 3. Parsear e buscar itens
            $itensEncontrados = $this->buscarNoCSV($csvItens, $termo, $limite);

            // 4. Enriquecer com dados das licitações
            if ($csvLicitacoes) {
                $itensEncontrados = $this->enriquecerComDadosLicitacao($itensEncontrados, $csvLicitacoes);
            }

            Log::info('[LicitaconService] Encontrados ' . count($itensEncontrados) . ' itens');

            return $itensEncontrados;

        } catch (\Exception $e) {
            Log::error('[LicitaconService] Erro ao buscar:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Obter CSV (com cache inteligente)
     *
     * @param int $ano
     * @param string $tipo (ITEM, LICITACAO, etc)
     * @return string|null Conteúdo do CSV
     */
    private function obterCSV($ano, $tipo)
    {
        $cacheKey = "licitacon_csv_{$ano}_{$tipo}";

        // Verificar cache
        if (Cache::has($cacheKey)) {
            Log::info("[LicitaconService] Usando cache para {$tipo}.csv");
            return Cache::get($cacheKey);
        }

        Log::info("[LicitaconService] Baixando {$tipo}.csv do ano {$ano}");

        try {
            // Baixar ZIP
            $zipUrl = self::BASE_URL . "{$ano}.csv.zip";

            $response = Http::timeout(60)->get($zipUrl);

            if (!$response->successful()) {
                Log::error("[LicitaconService] Erro ao baixar ZIP", [
                    'url' => $zipUrl,
                    'status' => $response->status()
                ]);
                return null;
            }

            // Salvar ZIP temporariamente
            $zipPath = storage_path('app/' . self::CACHE_DIR . "/{$ano}.zip");
            $zipDir = dirname($zipPath);

            if (!file_exists($zipDir)) {
                mkdir($zipDir, 0755, true);
            }

            file_put_contents($zipPath, $response->body());

            // Extrair ZIP
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                Log::error("[LicitaconService] Erro ao abrir ZIP");
                return null;
            }

            // Procurar arquivo dentro do ZIP
            $csvFilename = "{$tipo}.csv";
            $csvContent = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (stripos($filename, $csvFilename) !== false) {
                    $csvContent = $zip->getFromIndex($i);
                    break;
                }
            }

            $zip->close();

            // Limpar ZIP
            @unlink($zipPath);

            if (!$csvContent) {
                Log::error("[LicitaconService] Arquivo {$csvFilename} não encontrado no ZIP");
                return null;
            }

            // Salvar em cache (24 horas)
            Cache::put($cacheKey, $csvContent, self::CACHE_TTL);

            Log::info("[LicitaconService] {$tipo}.csv baixado e cacheado com sucesso", [
                'tamanho' => strlen($csvContent) . ' bytes'
            ]);

            return $csvContent;

        } catch (\Exception $e) {
            Log::error("[LicitaconService] Erro ao obter CSV", [
                'tipo' => $tipo,
                'erro' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Buscar no conteúdo do CSV
     *
     * @param string $csvContent
     * @param string $termo
     * @param int $limite
     * @return array
     */
    private function buscarNoCSV($csvContent, $termo, $limite)
    {
        $resultados = [];
        $termoLower = mb_strtolower($termo);

        // Parsear CSV linha por linha
        $linhas = explode("\n", $csvContent);
        $headers = null;

        foreach ($linhas as $index => $linha) {
            if ($index === 0) {
                // Primeira linha = cabeçalhos
                $headers = str_getcsv($linha, ';');
                continue;
            }

            if (empty(trim($linha))) {
                continue;
            }

            // Parsear linha
            $colunas = str_getcsv($linha, ';');

            if (count($colunas) < count($headers)) {
                continue; // Linha inválida
            }

            // Criar array associativo
            $item = array_combine($headers, $colunas);

            // Buscar no campo DS_ITEM (descrição do item)
            $descricao = isset($item['DS_ITEM']) ? mb_strtolower($item['DS_ITEM']) : '';

            if (stripos($descricao, $termoLower) !== false) {
                $resultados[] = $item;

                if (count($resultados) >= $limite) {
                    break;
                }
            }
        }

        return $resultados;
    }

    /**
     * Enriquecer itens com dados das licitações
     *
     * @param array $itens
     * @param string $csvLicitacoes
     * @return array
     */
    private function enriquecerComDadosLicitacao($itens, $csvLicitacoes)
    {
        // Criar índice de licitações por NR_LICITACAO
        $licitacoes = [];
        $linhas = explode("\n", $csvLicitacoes);
        $headers = null;

        foreach ($linhas as $index => $linha) {
            if ($index === 0) {
                $headers = str_getcsv($linha, ';');
                continue;
            }

            if (empty(trim($linha))) {
                continue;
            }

            $colunas = str_getcsv($linha, ';');

            if (count($colunas) < count($headers)) {
                continue;
            }

            $licitacao = array_combine($headers, $colunas);
            $nrLicitacao = $licitacao['NR_LICITACAO'] ?? null;

            if ($nrLicitacao) {
                $licitacoes[$nrLicitacao] = $licitacao;
            }
        }

        // Enriquecer itens
        foreach ($itens as &$item) {
            $nrLicitacao = $item['NR_LICITACAO'] ?? null;

            if ($nrLicitacao && isset($licitacoes[$nrLicitacao])) {
                $item['_licitacao'] = $licitacoes[$nrLicitacao];
            }
        }

        return $itens;
    }

    /**
     * Formatar resultado do Licitacon para o padrão da Pesquisa Rápida
     *
     * @param array $item Item do Licitacon
     * @return array
     */
    public function formatarParaPesquisaRapida($item)
    {
        $licitacao = $item['_licitacao'] ?? [];

        $valor = $this->parseFloat($item['VL_UNITARIO_ITEM'] ?? $item['VL_REFERENCIA'] ?? 0);
        $quantidade = $this->parseFloat($item['QT_ITEM'] ?? 0);

        return [
            'descricao' => $item['DS_ITEM'] ?? 'Sem descrição',
            'valor' => $valor,
            'preco_unitario' => $valor,
            'preco_medio' => $valor,
            'preco_minimo' => $valor,
            'preco_maximo' => $valor,
            'quantidade' => $quantidade,
            'unidade' => $item['DS_UNIDADE'] ?? 'UN',
            'orgao' => $licitacao['NM_ORGAO'] ?? 'Órgão não informado',
            'exemplo_orgao' => $licitacao['NM_ORGAO'] ?? 'Órgão não informado',
            'tipo_origem' => 'licitacon',
            'confiabilidade' => 'alta',
            'data_publicacao' => $this->formatarData($licitacao['DT_HOMOLOGACAO'] ?? $licitacao['DT_PUBLICACAO_EDITAL'] ?? null),
            'quantidade_amostras' => 1,

            // Dados extras para modal "Detalhes da Fonte"
            'licitacon_fonte' => 'LICITACON (TCE/RS)',
            'licitacon_identificacao' => $licitacao['CD_ORGAO'] ?? null,
            'licitacon_numero_pregao' => $licitacao['NR_LICITACAO'] ?? null,
            'licitacon_numero_ata' => $item['NR_ATA'] ?? 'n/a',
            'licitacon_data_homologacao' => $this->formatarData($licitacao['DT_HOMOLOGACAO'] ?? null),
            'licitacon_orgao' => $licitacao['NM_ORGAO'] ?? 'Não informado',
            'licitacon_objeto' => $licitacao['DS_OBJETO'] ?? 'Não informado',
            'licitacon_lote' => $item['NR_LOTE'] ?? '-',
            'licitacon_item' => $item['NR_ITEM'] ?? '-',
            'licitacon_vencedor' => $licitacao['NM_LICITANTE_VENCEDOR'] ?? 'Não informado',
            'licitacon_descricao' => $item['DS_ITEM'] ?? 'Não informado',
            'licitacon_marca' => $item['DS_MARCA'] ?? '-',
            'licitacon_fonte_url' => $this->gerarUrlPortal($licitacao),

            'amostras_detalhadas' => [[
                'orgao' => $licitacao['NM_ORGAO'] ?? 'Órgão não informado',
                'valor' => $valor,
                'confiabilidade' => 'alta',
                'data_publicacao' => $this->formatarData($licitacao['DT_HOMOLOGACAO'] ?? null),
                'numero_controle_pncp' => null
            ]]
        ];
    }

    /**
     * Parse float seguro
     */
    private function parseFloat($value)
    {
        if (empty($value)) {
            return 0;
        }

        // Converter vírgula para ponto
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);

        return floatval($value);
    }

    /**
     * Formatar data
     */
    private function formatarData($data)
    {
        if (empty($data)) {
            return null;
        }

        try {
            // Formato esperado: YYYYMMDD ou DD/MM/YYYY
            if (strlen($data) === 8 && is_numeric($data)) {
                // YYYYMMDD
                $ano = substr($data, 0, 4);
                $mes = substr($data, 4, 2);
                $dia = substr($data, 6, 2);
                return "{$ano}-{$mes}-{$dia}";
            } elseif (strpos($data, '/') !== false) {
                // DD/MM/YYYY
                $partes = explode('/', $data);
                if (count($partes) === 3) {
                    return "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                }
            }

            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Gerar URL do portal Licitacon Cidadão
     */
    private function gerarUrlPortal($licitacao)
    {
        // URL base do portal
        $baseUrl = 'https://portal.tce.rs.gov.br/aplicprod/f?p=50500:1';

        // Adicionar parâmetros se disponíveis
        if (!empty($licitacao['CD_ORGAO'])) {
            $baseUrl .= '::::::P1_CD_ORGAO:' . $licitacao['CD_ORGAO'];
        }

        return $baseUrl;
    }

    /**
     * Limpar cache manualmente
     */
    public function limparCache($ano = null)
    {
        if ($ano) {
            Cache::forget("licitacon_csv_{$ano}_ITEM");
            Cache::forget("licitacon_csv_{$ano}_LICITACAO");
            Log::info("[LicitaconService] Cache limpo para ano {$ano}");
        } else {
            Cache::flush();
            Log::info("[LicitaconService] Todo cache limpo");
        }
    }
}
