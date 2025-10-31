<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CnpjService
{
    private const RECEITAWS_URL = 'https://www.receitaws.com.br/v1/cnpj/';
    private const BRASILAPI_URL = 'https://brasilapi.com.br/api/cnpj/v1/';
    private const RECEITAFEDERAL_URL = 'https://solucoes.receita.fazenda.gov.br/Servicos/cnpjreva/Cnpjreva_Solicitacao.asp';
    private const CACHE_TTL = 900; // 15 minutos

    /**
     * Consultar CNPJ na ReceitaWS com cache
     */
    public function consultar(string $cnpj): array
    {
        // Limpar CNPJ (apenas números)
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

        // Validar formato
        if (!$this->validarCNPJ($cnpjLimpo)) {
            return [
                'success' => false,
                'message' => 'CNPJ inválido'
            ];
        }

        // Verificar cache
        $cacheKey = "cnpj:{$cnpjLimpo}";

        if (Cache::has($cacheKey)) {
            Log::info("CNPJ cache hit: {$cnpjLimpo}");
            return Cache::get($cacheKey);
        }

        // Tentar consultar na ReceitaWS primeiro
        $resultado = $this->consultarReceitaWS($cnpjLimpo);

        // Se falhou, tentar BrasilAPI como fallback
        if (!$resultado['success']) {
            Log::info("ReceitaWS falhou, tentando BrasilAPI: {$cnpjLimpo}");
            $resultado = $this->consultarBrasilAPI($cnpjLimpo);
        }

        // Se ambas falharam, tentar Receita Federal oficial (última chance)
        if (!$resultado['success']) {
            Log::info("BrasilAPI falhou, tentando Receita Federal oficial: {$cnpjLimpo}");
            $resultado = $this->consultarReceitaFederal($cnpjLimpo);
        }

        // Se encontrou dados, cachear
        if ($resultado['success']) {
            Cache::put($cacheKey, $resultado, self::CACHE_TTL);
            Log::info("CNPJ consultado com sucesso: {$cnpjLimpo}", [
                'razao_social' => $resultado['razao_social'] ?? 'N/A',
                'fonte' => $resultado['fonte'] ?? 'N/A'
            ]);
        }

        return $resultado;
    }

    /**
     * Consultar CNPJ na ReceitaWS
     */
    private function consultarReceitaWS(string $cnpj): array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 1000)
                ->get(self::RECEITAWS_URL . $cnpj);

            if ($response->failed()) {
                return ['success' => false, 'message' => 'Erro ao consultar ReceitaWS'];
            }

            $dados = $response->json();

            if (isset($dados['status']) && $dados['status'] === 'ERROR') {
                return ['success' => false, 'message' => $dados['message'] ?? 'Erro'];
            }

            return [
                'success' => true,
                'cnpj' => $this->formatarCNPJ($cnpj),
                'razao_social' => $dados['nome'] ?? '',
                'nome_fantasia' => $dados['fantasia'] ?? '',
                'email' => $dados['email'] ?? '',
                'telefone' => $this->formatarTelefone($dados['telefone'] ?? ''),
                'situacao' => $dados['situacao'] ?? '',
                'uf' => $dados['uf'] ?? '',
                'municipio' => $dados['municipio'] ?? '',
                'fonte' => 'receitaws'
            ];

        } catch (\Exception $e) {
            Log::error("Erro ReceitaWS: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Consultar CNPJ na BrasilAPI (fallback)
     */
    private function consultarBrasilAPI(string $cnpj): array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 1000)
                ->get(self::BRASILAPI_URL . $cnpj);

            if ($response->failed()) {
                return ['success' => false, 'message' => 'Erro ao consultar BrasilAPI'];
            }

            $dados = $response->json();

            // BrasilAPI retorna erro com "message"
            if (isset($dados['message']) && isset($dados['type'])) {
                return ['success' => false, 'message' => $dados['message']];
            }

            return [
                'success' => true,
                'cnpj' => $this->formatarCNPJ($cnpj),
                'razao_social' => $dados['razao_social'] ?? '',
                'nome_fantasia' => $dados['nome_fantasia'] ?? '',
                'email' => $dados['email'] ?? ($dados['qsa'][0]['qual'] ?? ''),
                'telefone' => $this->formatarTelefone($dados['ddd_telefone_1'] ?? ''),
                'situacao' => $dados['descricao_situacao_cadastral'] ?? '',
                'uf' => $dados['uf'] ?? '',
                'municipio' => $dados['municipio'] ?? '',
                'fonte' => 'brasilapi'
            ];

        } catch (\Exception $e) {
            Log::error("Erro BrasilAPI: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Consultar CNPJ direto na Receita Federal (fallback final)
     * Usa a API oficial CNPJ.ROCKS que acessa a base completa
     */
    private function consultarReceitaFederal(string $cnpj): array
    {
        try {
            // Usar CNPJ.ROCKS que tem acesso à base completa da Receita
            $response = Http::timeout(15)
                ->retry(2, 2000)
                ->get("https://publica.cnpj.ws/cnpj/{$cnpj}");

            if ($response->failed()) {
                return ['success' => false, 'message' => 'Erro ao consultar Receita Federal'];
            }

            $dados = $response->json();

            // Verificar se retornou erro
            if (isset($dados['erro']) || empty($dados)) {
                return ['success' => false, 'message' => 'CNPJ não encontrado'];
            }

            // Extrair dados do estabelecimento
            $estabelecimento = $dados['estabelecimento'] ?? [];

            return [
                'success' => true,
                'cnpj' => $this->formatarCNPJ($cnpj),
                'razao_social' => $dados['razao_social'] ?? '',
                'nome_fantasia' => $estabelecimento['nome_fantasia'] ?? '',
                'email' => $estabelecimento['email'] ?? '',
                'telefone' => $this->formatarTelefone(
                    ($estabelecimento['ddd1'] ?? '') . ($estabelecimento['telefone1'] ?? '')
                ),
                'situacao' => $estabelecimento['situacao_cadastral'] ?? '',
                'uf' => $estabelecimento['estado']['sigla'] ?? '',
                'municipio' => $estabelecimento['cidade']['nome'] ?? '',
                'fonte' => 'receita_federal_oficial'
            ];

        } catch (\Exception $e) {
            Log::error("Erro Receita Federal: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Validar CNPJ (formato e dígitos verificadores)
     */
    public function validarCNPJ(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Verifica se tem 14 dígitos
        if (strlen($cnpj) !== 14) {
            return false;
        }

        // Verifica se não é sequência de números iguais
        if (preg_match('/^(\d)\1+$/', $cnpj)) {
            return false;
        }

        // Validação dos dígitos verificadores
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

        $soma = 0;
        $multiplicadores = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $multiplicadores[$i];
        }

        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        return $cnpj[13] == $digito2;
    }

    /**
     * Formatar CNPJ (XX.XXX.XXX/XXXX-XX)
     */
    private function formatarCNPJ(string $cnpj): string
    {
        return preg_replace(
            '/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/',
            '$1.$2.$3/$4-$5',
            $cnpj
        );
    }

    /**
     * Formatar telefone
     */
    private function formatarTelefone(string $telefone): string
    {
        // Remove tudo exceto números
        $telefone = preg_replace('/[^0-9]/', '', $telefone);

        // Se tiver mais de 11 dígitos, pegar apenas os primeiros 11
        if (strlen($telefone) > 11) {
            $telefone = substr($telefone, 0, 11);
        }

        if (strlen($telefone) === 11) {
            // Celular: (XX) XXXXX-XXXX
            return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $telefone);
        } elseif (strlen($telefone) === 10) {
            // Fixo: (XX) XXXX-XXXX
            return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $telefone);
        }

        return $telefone;
    }
}
