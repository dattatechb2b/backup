<?php

namespace App\Services\PDF\Detectores;

use App\Services\PDF\FormatoDetector;
use App\Services\PDF\FormatoExtrator;
use App\Services\PDF\Extratores\GenericoExtrator;

/**
 * Detector genérico - SEMPRE retorna true (fallback)
 * Usa extrator inteligente para tentar extrair de qualquer formato
 */
class GenericoDetector implements FormatoDetector
{
    public function detectar(array $linhas): bool
    {
        // Sempre retorna true (é o fallback)
        return true;
    }

    public function getPrioridade(): int
    {
        return 1; // Prioridade MÍNIMA (último a ser testado)
    }

    public function getNome(): string
    {
        return 'Genérico (Detecção Automática)';
    }

    public function getExtrator(): FormatoExtrator
    {
        return new GenericoExtrator();
    }
}
