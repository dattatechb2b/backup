<?php

namespace App\Services\PDF\Detectores;

use App\Services\PDF\FormatoDetector;
use App\Services\PDF\FormatoExtrator;
use App\Services\PDF\Extratores\MapaApuracaoExtrator;

/**
 * Detecta formato "MAPA DE APURAÇÃO DE PREÇOS"
 * Formato vertical onde cada campo está em uma linha separada
 */
class MapaApuracaoDetector implements FormatoDetector
{
    public function detectar(array $linhas): bool
    {
        $pontuacao = 0;

        foreach ($linhas as $linha) {
            $linhaUpper = strtoupper(trim($linha));

            // Palavras-chave muito específicas deste formato
            if (stripos($linhaUpper, 'MAPA DE APURAÇÃO') !== false ||
                stripos($linhaUpper, 'MAPA DE APURACAO') !== false) {
                $pontuacao += 100; // Muito específico
            }

            if (preg_match('/Anexo\s+I\s+Lote\s+\d+/i', $linhaUpper)) {
                $pontuacao += 50;
            }

            if (preg_match('/AnexoI/i', $linhaUpper)) {
                $pontuacao += 30;
            }

            if (preg_match('/Lote\d{3}/i', $linhaUpper)) {
                $pontuacao += 20;
            }

            if (preg_match('/Item\d{3}/i', $linhaUpper)) {
                $pontuacao += 15;
            }
        }

        // Se pontuação >= 80, consideramos que é Mapa de Apuração
        return $pontuacao >= 80;
    }

    public function getPrioridade(): int
    {
        return 10; // Prioridade MÁXIMA (mais específico)
    }

    public function getNome(): string
    {
        return 'Mapa de Apuração de Preços';
    }

    public function getExtrator(): FormatoExtrator
    {
        return new MapaApuracaoExtrator();
    }
}
