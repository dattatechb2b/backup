<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Service para cálculo da Curva ABC (Pareto)
 * FASE 2 - Algoritmos Estatísticos
 */
class CurvaABCService
{
    /**
     * Calcula Curva ABC para um conjunto de itens
     *
     * Classificação:
     * - Classe A: até 80% do valor acumulado
     * - Classe B: 80% a 95% do valor acumulado
     * - Classe C: acima de 95% do valor acumulado
     *
     * @param Collection $itens Collection de OrcamentoItem
     * @return array Itens ordenados com classificação ABC
     */
    public function calcular($itens): array
    {
        // 1. Calcular valor total de cada item
        $itensComValor = $itens->map(function($item) {
            $valorTotal = ($item->preco_unitario ?? 0) * ($item->quantidade ?? 0);
            return [
                'item' => $item,
                'valor_total' => $valorTotal
            ];
        });

        // 2. Ordenar por valor total decrescente
        $itensOrdenados = $itensComValor->sortByDesc('valor_total')->values();

        // 3. Calcular soma total
        $somaTotal = $itensOrdenados->sum('valor_total');

        // 4. Calcular % acumulada e classe
        $acumulado = 0;
        $resultado = [];

        foreach ($itensOrdenados as $dados) {
            $item = $dados['item'];
            $valorTotal = $dados['valor_total'];

            $acumulado += $valorTotal;

            // Calcular participação percentual
            $participacao = $somaTotal > 0 ? ($valorTotal / $somaTotal) * 100 : 0;

            // Calcular acumulada percentual
            $acumulada = $somaTotal > 0 ? ($acumulado / $somaTotal) * 100 : 0;

            // Classificação ABC (Princípio de Pareto)
            if ($acumulada <= 80) {
                $classe = 'A';  // 20% dos itens = 80% do valor
            } elseif ($acumulada <= 95) {
                $classe = 'B';  // Próximos itens até 95%
            } else {
                $classe = 'C';  // Últimos itens acima de 95%
            }

            $resultado[] = [
                'item_id' => $item->id,
                'descricao' => $item->descricao,
                'quantidade' => $item->quantidade ?? 0,
                'preco_unitario' => $item->preco_unitario ?? 0,
                'valor_total' => round($valorTotal, 2),
                'participacao' => round($participacao, 6),
                'acumulada' => round($acumulada, 6),
                'classe' => $classe
            ];
        }

        return $resultado;
    }

    /**
     * Salva snapshot ABC em cada item do orçamento
     *
     * @param Collection $itens Collection de OrcamentoItem
     * @param array $curvaABC Array retornado por calcular()
     * @return void
     */
    public function salvarSnapshot($itens, array $curvaABC): void
    {
        foreach ($curvaABC as $dados) {
            $item = $itens->firstWhere('id', $dados['item_id']);
            if ($item) {
                $item->update([
                    'abc_valor_total' => $dados['valor_total'],
                    'abc_participacao' => $dados['participacao'],
                    'abc_acumulada' => $dados['acumulada'],
                    'abc_classe' => $dados['classe']
                ]);
            }
        }
    }

    /**
     * Gera estatísticas resumidas da Curva ABC
     *
     * @param array $curvaABC Array retornado por calcular()
     * @return array Estatísticas por classe
     */
    public function gerarEstatisticas(array $curvaABC): array
    {
        $classes = ['A' => [], 'B' => [], 'C' => []];

        foreach ($curvaABC as $item) {
            $classes[$item['classe']][] = $item;
        }

        return [
            'A' => [
                'quantidade_itens' => count($classes['A']),
                'valor_total' => array_sum(array_column($classes['A'], 'valor_total')),
                'percentual_itens' => count($curvaABC) > 0 ? (count($classes['A']) / count($curvaABC)) * 100 : 0,
                'percentual_valor' => count($classes['A']) > 0 ? $classes['A'][count($classes['A']) - 1]['acumulada'] : 0
            ],
            'B' => [
                'quantidade_itens' => count($classes['B']),
                'valor_total' => array_sum(array_column($classes['B'], 'valor_total')),
                'percentual_itens' => count($curvaABC) > 0 ? (count($classes['B']) / count($curvaABC)) * 100 : 0,
                'percentual_valor' => count($classes['B']) > 0 ? $classes['B'][count($classes['B']) - 1]['acumulada'] - (count($classes['A']) > 0 ? $classes['A'][count($classes['A']) - 1]['acumulada'] : 0) : 0
            ],
            'C' => [
                'quantidade_itens' => count($classes['C']),
                'valor_total' => array_sum(array_column($classes['C'], 'valor_total')),
                'percentual_itens' => count($curvaABC) > 0 ? (count($classes['C']) / count($curvaABC)) * 100 : 0,
                'percentual_valor' => count($classes['C']) > 0 ? 100 - (count($classes['B']) > 0 ? $classes['B'][count($classes['B']) - 1]['acumulada'] : 0) : 0
            ]
        ];
    }
}
