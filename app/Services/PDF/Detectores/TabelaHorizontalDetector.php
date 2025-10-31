<?php

namespace App\Services\PDF\Detectores;

use App\Services\PDF\FormatoDetector;
use App\Services\PDF\FormatoExtrator;
use App\Services\PDF\Extratores\TabelaHorizontalExtrator;

/**
 * Detecta PDFs com formato de tabela horizontal
 * Exemplo: ITEM | DESCRIÇÃO | QTD | UN | PREÇO UNIT. | TOTAL
 */
class TabelaHorizontalDetector implements FormatoDetector
{
    public function detectar(array $linhas): bool
    {
        $pontuacao = 0;

        foreach ($linhas as $linha) {
            $linha = strtoupper(trim($linha));

            // Detectar cabeçalho de tabela com palavras-chave
            $palavrasChaveCabecalho = [
                'ITEM', 'DESCRIÇÃO', 'DESCRICAO', 'QUANTIDADE', 'QTD', 'QTDE',
                'UNIDADE', 'UND', 'PREÇO', 'PRECO', 'VALOR', 'TOTAL'
            ];

            $palavrasEncontradas = 0;
            foreach ($palavrasChaveCabecalho as $palavra) {
                if (strpos($linha, $palavra) !== false) {
                    $palavrasEncontradas++;
                }
            }

            // Se encontrou 3+ palavras-chave de cabeçalho, provavelmente é tabela
            if ($palavrasEncontradas >= 3) {
                $pontuacao += 50;
            }

            // Detectar linhas com múltiplos valores numéricos separados por TAB ou espaços
            if (preg_match('/\d+.*\t+.*\d+[,.]?\d*/', $linha)) {
                $pontuacao += 5;
            }

            // Detectar padrão típico de item: número + texto + números
            if (preg_match('/^\d{1,4}\s+.{10,}\s+\d+[,.]?\d*/', $linha)) {
                $pontuacao += 10;
            }
        }

        // Se pontuação > 60, consideramos que é tabela horizontal
        return $pontuacao >= 60;
    }

    public function getPrioridade(): int
    {
        return 6; // Prioridade média (formato comum)
    }

    public function getNome(): string
    {
        return 'Tabela Horizontal';
    }

    public function getExtrator(): FormatoExtrator
    {
        return new TabelaHorizontalExtrator();
    }
}
