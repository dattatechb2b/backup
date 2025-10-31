<?php

namespace App\Helpers;

class NormalizadorHelper
{
    /**
     * Normaliza CNPJ para 14 dígitos com zeros à esquerda
     *
     * @param string $cnpj
     * @return string CNPJ com 14 dígitos
     */
    public static function normalizarCNPJ(string $cnpj): string
    {
        // Remove tudo que não é dígito
        $cnpjLimpo = preg_replace('/\D/', '', $cnpj);

        // Garante 14 dígitos com zeros à esquerda
        return str_pad($cnpjLimpo, 14, '0', STR_PAD_LEFT);
    }

    /**
     * Normaliza número da ata
     * Aceita: "001/2025", "1/2025", "001-2025" → retorna: "001"
     *
     * @param string $numeroAta
     * @return string Somente o número da ata (sem ano)
     */
    public static function normalizarNumeroAta(string $numeroAta): string
    {
        // Remove espaços
        $numeroAta = trim($numeroAta);

        // Se tem separador (/, -, \), pega só o primeiro bloco
        if (preg_match('/^(\d+)[\/\-\\\\]/', $numeroAta, $matches)) {
            return str_pad($matches[1], 3, '0', STR_PAD_LEFT);
        }

        // Se é só número, retorna com zeros à esquerda
        if (is_numeric($numeroAta)) {
            return str_pad($numeroAta, 3, '0', STR_PAD_LEFT);
        }

        // Se não conseguiu normalizar, retorna original
        return $numeroAta;
    }

    /**
     * Normaliza unidade de medida
     * UN, UNID, UNIDADE, UND → UN
     * KG, KILO, QUILOGRAMA → KG
     * etc.
     *
     * @param string $unidade
     * @return string Unidade normalizada
     */
    public static function normalizarUnidade(string $unidade): string
    {
        $unidade = strtoupper(trim($unidade));

        // Mapeamento de normalizações
        $mapa = [
            // Unidade
            'UNIDADE' => 'UN',
            'UNID' => 'UN',
            'UNID.' => 'UN',
            'UND' => 'UN',
            'UND.' => 'UN',
            'UNIDADES' => 'UN',

            // Quilograma
            'QUILOGRAMA' => 'KG',
            'QUILOGRAMAS' => 'KG',
            'QUILO' => 'KG',
            'KILOGRAMA' => 'KG',

            // Grama
            'GRAMA' => 'G',
            'GRAMAS' => 'G',

            // Litro
            'LITRO' => 'L',
            'LITROS' => 'L',
            'LT' => 'L',
            'LTS' => 'L',

            // Metro
            'METRO' => 'M',
            'METROS' => 'M',
            'MT' => 'M',

            // Caixa
            'CAIXA' => 'CX',
            'CAIXAS' => 'CX',

            // Pacote
            'PACOTE' => 'PCT',
            'PACOTES' => 'PCT',
            'PC' => 'PCT',

            // Peça
            'PECA' => 'PC',
            'PECAS' => 'PC',
            'PEÇA' => 'PC',
            'PEÇAS' => 'PC',
            'PÇ' => 'PC',
            'PÇS' => 'PC',

            // Par
            'PAR' => 'PR',
            'PARES' => 'PR',

            // Jogo
            'JOGO' => 'JG',
            'JOGOS' => 'JG',

            // Resma
            'RESMA' => 'RESMA',
            'RESMAS' => 'RESMA',

            // Fardo
            'FARDO' => 'FD',
            'FARDOS' => 'FD',

            // Bloco
            'BLOCO' => 'BL',
            'BLOCOS' => 'BL',

            // Rolo
            'ROLO' => 'RL',
            'ROLOS' => 'RL',

            // Kit
            'KIT' => 'KIT',
            'KITS' => 'KIT',

            // Dúzia
            'DUZIA' => 'DZ',
            'DUZIAS' => 'DZ',
            'DZ' => 'DZ',
        ];

        return $mapa[$unidade] ?? $unidade;
    }

    /**
     * Extrai o ano do número da ata
     * "001/2025" → 2025
     * "1-2024" → 2024
     *
     * @param string $numeroAta
     * @return int|null Ano ou null
     */
    public static function extrairAnoAta(string $numeroAta): ?int
    {
        if (preg_match('/[\/\-\\\\](\d{4})/', $numeroAta, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
