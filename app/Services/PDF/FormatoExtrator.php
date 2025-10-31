<?php

namespace App\Services\PDF;

interface FormatoExtrator
{
    /**
     * Extrai dados do PDF
     *
     * @param array $linhas Linhas do texto extraído do PDF
     * @return array Dados extraídos estruturados
     * [
     *     'itens' => [...],
     *     'fornecedores' => [...],
     *     'valor_total_geral' => float,
     *     'lotes' => [...]
     * ]
     */
    public function extrair(array $linhas): array;
}
