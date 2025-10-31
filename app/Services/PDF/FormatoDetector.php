<?php

namespace App\Services\PDF;

interface FormatoDetector
{
    /**
     * Detecta se o PDF corresponde a este formato
     *
     * @param array $linhas Linhas do texto extraído do PDF
     * @return bool True se for este formato
     */
    public function detectar(array $linhas): bool;

    /**
     * Retorna prioridade de detecção (maior = mais específico)
     *
     * @return int Prioridade (1-10)
     */
    public function getPrioridade(): int;

    /**
     * Retorna nome do formato
     *
     * @return string Nome do formato
     */
    public function getNome(): string;

    /**
     * Retorna extrator correspondente
     *
     * @return FormatoExtrator
     */
    public function getExtrator(): FormatoExtrator;
}
