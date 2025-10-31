<?php

namespace App\Services;

/**
 * Service para cálculos estatísticos e saneamento de amostras
 * FASE 2 - Algoritmos Estatísticos
 */
class EstatisticaService
{
    /**
     * Aplica saneamento pelo método Desvio-Padrão (μ ± σ)
     *
     * @param array $amostras Array de objetos/arrays com preco_unitario_ajustado
     * @param string $metodoObtencao 'auto' (CV), 'media', 'mediana' ou 'menor'
     * @param int $casasDecimais Número de casas decimais (2 ou 4)
     * @return array ['amostras' => [...], 'snapshot' => [...]]
     * @throws \Exception Se menos de 3 amostras válidas
     */
    public function aplicarSaneamentoDP(
        array $amostras,
        string $metodoObtencao = 'auto',
        int $casasDecimais = 2
    ): array
    {
        // 1. Extrair preços
        $precos = array_map(function($amostra) {
            return (float) ($amostra['preco_unitario_ajustado'] ?? $amostra['valor_unitario'] ?? 0);
        }, $amostras);

        // Validar mínimo inicial
        if (count($precos) < 3) {
            throw new \Exception('Mínimo de 3 amostras necessárias para saneamento.');
        }

        // 2. Calcular média e DP iniciais
        $mu0 = $this->media($precos);
        $sigma0 = $this->desvioPadraoPopulacional($precos);
        $limInf = $mu0 - $sigma0;
        $limSup = $mu0 + $sigma0;

        // 3. Marcar situação de cada amostra
        foreach ($amostras as $key => $amostra) {
            $preco = (float) ($amostra['preco_unitario_ajustado'] ?? $amostra['valor_unitario'] ?? 0);

            if ($preco < $limInf) {
                $amostras[$key]['situacao'] = 'EXPURGADA';
                $amostras[$key]['motivo_expurgo'] = 'ABAIXO_MEDIA_DP';
                $amostras[$key]['regra_aplicada'] = 'DP±MÉDIA';
            } elseif ($preco > $limSup) {
                $amostras[$key]['situacao'] = 'EXPURGADA';
                $amostras[$key]['motivo_expurgo'] = 'ACIMA_MEDIA_DP';
                $amostras[$key]['regra_aplicada'] = 'DP±MÉDIA';
            } else {
                $amostras[$key]['situacao'] = 'VALIDA';
                $amostras[$key]['motivo_expurgo'] = null;
                $amostras[$key]['regra_aplicada'] = 'DP±MÉDIA';
            }
        }

        // 4. Filtrar válidas
        $saneadas = array_filter($amostras, fn($a) => ($a['situacao'] ?? '') === 'VALIDA');

        // Validar mínimo pós-saneamento
        if (count($saneadas) < 3) {
            throw new \Exception('Menos de 3 amostras válidas após saneamento. Necessário justificativa.');
        }

        // 5. Calcular estatísticas pós-saneamento
        $precosSaneados = array_map(function($a) {
            return (float) ($a['preco_unitario_ajustado'] ?? $a['valor_unitario'] ?? 0);
        }, $saneadas);

        $mu1 = $this->media($precosSaneados);
        $sigma1 = $this->desvioPadraoPopulacional($precosSaneados);
        $CV = $mu1 > 0 ? ($sigma1 / $mu1) * 100 : 0;

        // 6. Decidir método baseado na configuração da Etapa 2
        if ($metodoObtencao === 'auto') {
            // Lógica automática: CV ≤ 25% → média, > 25% → mediana
            $metodo = $CV <= 25 ? 'MEDIA' : 'MEDIANA';
        } elseif ($metodoObtencao === 'media') {
            $metodo = 'MEDIA';
        } elseif ($metodoObtencao === 'mediana') {
            $metodo = 'MEDIANA';
        } else { // 'menor'
            $metodo = 'MENOR';
        }

        // 7. Calcular hash (SHA-256 dos IDs ordenados das válidas)
        $idsOrdenados = array_map(fn($a) => $a['id'] ?? 0, $saneadas);
        sort($idsOrdenados);
        $hashAmostras = hash('sha256', json_encode($idsOrdenados));

        // 8. Retornar (usando casas decimais configuradas)
        return [
            'amostras' => $amostras,
            'snapshot' => [
                'calc_n_validas' => count($saneadas),
                'calc_media' => round($mu1, $casasDecimais),
                'calc_mediana' => round($this->mediana($precosSaneados), $casasDecimais),
                'calc_dp' => round($sigma1, $casasDecimais),
                'calc_cv' => round($CV, 4),
                'calc_menor' => round(min($precosSaneados), $casasDecimais),
                'calc_maior' => round(max($precosSaneados), $casasDecimais),
                'calc_lim_inf' => round($limInf, $casasDecimais),
                'calc_lim_sup' => round($limSup, $casasDecimais),
                'calc_metodo' => $metodo,
                'calc_carimbado_em' => null, // Só preenche ao clicar "Fixar"
                'calc_hash_amostras' => $hashAmostras
            ]
        ];
    }

    /**
     * Aplica saneamento pelo método Percentual da Mediana
     *
     * @param array $amostras Array de objetos/arrays com preco_unitario_ajustado
     * @param float $percInf Percentual inferior (padrão 70%)
     * @param float $percSup Percentual superior (padrão 30%)
     * @param string $metodoObtencao 'auto' (CV), 'media', 'mediana' ou 'menor'
     * @param int $casasDecimais Número de casas decimais (2 ou 4)
     * @return array ['amostras' => [...], 'snapshot' => [...]]
     * @throws \Exception Se menos de 3 amostras válidas
     */
    public function aplicarSaneamentoPercentual(
        array $amostras,
        float $percInf = 70,
        float $percSup = 30,
        string $metodoObtencao = 'auto',
        int $casasDecimais = 2
    ): array
    {
        // 1. Extrair preços
        $precos = array_map(function($amostra) {
            return (float) ($amostra['preco_unitario_ajustado'] ?? $amostra['valor_unitario'] ?? 0);
        }, $amostras);

        // Validar mínimo inicial
        if (count($precos) < 3) {
            throw new \Exception('Mínimo de 3 amostras necessárias para saneamento.');
        }

        // 2. Calcular mediana inicial
        $mediana0 = $this->mediana($precos);
        $limInf = $mediana0 * (1 - $percInf / 100);  // mediana - 70%
        $limSup = $mediana0 * (1 + $percSup / 100);  // mediana + 30%

        // 3. Marcar situação de cada amostra
        foreach ($amostras as $key => $amostra) {
            $preco = (float) ($amostra['preco_unitario_ajustado'] ?? $amostra['valor_unitario'] ?? 0);

            if ($preco < $limInf) {
                $amostras[$key]['situacao'] = 'EXPURGADA';
                $amostras[$key]['motivo_expurgo'] = 'ABAIXO_MEDIANA_PERC';
                $amostras[$key]['regra_aplicada'] = "MEDIANA±{$percInf}%/{$percSup}%";
            } elseif ($preco > $limSup) {
                $amostras[$key]['situacao'] = 'EXPURGADA';
                $amostras[$key]['motivo_expurgo'] = 'ACIMA_MEDIANA_PERC';
                $amostras[$key]['regra_aplicada'] = "MEDIANA±{$percInf}%/{$percSup}%";
            } else {
                $amostras[$key]['situacao'] = 'VALIDA';
                $amostras[$key]['motivo_expurgo'] = null;
                $amostras[$key]['regra_aplicada'] = "MEDIANA±{$percInf}%/{$percSup}%";
            }
        }

        // 4. Filtrar válidas
        $saneadas = array_filter($amostras, fn($a) => ($a['situacao'] ?? '') === 'VALIDA');

        // Validar mínimo pós-saneamento
        if (count($saneadas) < 3) {
            throw new \Exception('Menos de 3 amostras válidas após saneamento. Necessário justificativa.');
        }

        // 5. Calcular estatísticas pós-saneamento
        $precosSaneados = array_map(function($a) {
            return (float) ($a['preco_unitario_ajustado'] ?? $a['valor_unitario'] ?? 0);
        }, $saneadas);

        $mu1 = $this->media($precosSaneados);
        $sigma1 = $this->desvioPadraoPopulacional($precosSaneados);
        $mediana1 = $this->mediana($precosSaneados);
        $CV = $mu1 > 0 ? ($sigma1 / $mu1) * 100 : 0;

        // 6. Decidir método baseado na configuração da Etapa 2
        if ($metodoObtencao === 'auto') {
            // Lógica automática: CV ≤ 25% → média, > 25% → mediana
            $metodo = $CV <= 25 ? 'MEDIA' : 'MEDIANA';
        } elseif ($metodoObtencao === 'media') {
            $metodo = 'MEDIA';
        } elseif ($metodoObtencao === 'mediana') {
            $metodo = 'MEDIANA';
        } else { // 'menor'
            $metodo = 'MENOR';
        }

        // 7. Calcular hash
        $idsOrdenados = array_map(fn($a) => $a['id'] ?? 0, $saneadas);
        sort($idsOrdenados);
        $hashAmostras = hash('sha256', json_encode($idsOrdenados));

        // 8. Retornar (usando casas decimais configuradas)
        return [
            'amostras' => $amostras,
            'snapshot' => [
                'calc_n_validas' => count($saneadas),
                'calc_media' => round($mu1, $casasDecimais),
                'calc_mediana' => round($mediana1, $casasDecimais),
                'calc_dp' => round($sigma1, $casasDecimais),
                'calc_cv' => round($CV, 4),
                'calc_menor' => round(min($precosSaneados), $casasDecimais),
                'calc_maior' => round(max($precosSaneados), $casasDecimais),
                'calc_lim_inf' => round($limInf, $casasDecimais),
                'calc_lim_sup' => round($limSup, $casasDecimais),
                'calc_metodo' => $metodo,
                'calc_carimbado_em' => null,
                'calc_hash_amostras' => $hashAmostras
            ]
        ];
    }

    /**
     * Calcula média aritmética
     *
     * @param array $valores
     * @return float
     */
    private function media(array $valores): float
    {
        if (empty($valores)) {
            return 0;
        }
        return array_sum($valores) / count($valores);
    }

    /**
     * Calcula desvio-padrão populacional (÷ n, não n-1)
     *
     * @param array $valores
     * @return float
     */
    private function desvioPadraoPopulacional(array $valores): float
    {
        if (count($valores) < 2) {
            return 0;
        }

        $mu = $this->media($valores);
        $variancia = array_reduce($valores, function($carry, $x) use ($mu) {
            return $carry + pow($x - $mu, 2);
        }, 0) / count($valores);  // ÷ n (populacional)

        return sqrt($variancia);
    }

    /**
     * Calcula mediana
     *
     * @param array $valores
     * @return float
     */
    private function mediana(array $valores): float
    {
        if (empty($valores)) {
            return 0;
        }

        sort($valores);
        $count = count($valores);
        $mid = floor($count / 2);

        if ($count % 2 === 0) {
            // Par: média dos dois valores centrais
            return ($valores[$mid - 1] + $valores[$mid]) / 2;
        } else {
            // Ímpar: valor central
            return $valores[$mid];
        }
    }
}
