<?php

namespace App\Services;

class DataNormalizationService
{
    /**
     * Detecta e converte encoding para UTF-8
     */
    public function normalizeEncoding(string $text): string
    {
        // Detectar encoding
        $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }

        return $text;
    }

    /**
     * Limpa e normaliza texto
     */
    public function normalizeText(string $text): string
    {
        // Encoding
        $text = $this->normalizeEncoding($text);

        // Trim
        $text = trim($text);

        // Múltiplos espaços → um espaço
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove caracteres de controle
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);

        // Remove BOM se existir
        $text = str_replace("\xEF\xBB\xBF", '', $text);

        return $text;
    }

    /**
     * Normaliza CNPJ/CPF (apenas dígitos)
     */
    public function normalizeCnpjCpf(?string $value): ?string
    {
        if (!$value) return null;

        // Remove tudo que não é dígito
        $value = preg_replace('/\D/', '', $value);

        // Valida comprimento
        if (!in_array(strlen($value), [11, 14])) {
            return null;
        }

        return $value;
    }

    /**
     * Normaliza CATMAT (apenas dígitos, zero-pad)
     */
    public function normalizeCatmat(?string $value): ?string
    {
        if (!$value) return null;

        $value = preg_replace('/\D/', '', $value);

        // Garante 6 dígitos (zero-pad left)
        return str_pad($value, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Normaliza data (converte para ISO YYYY-MM-DD)
     */
    public function normalizeDate(?string $value): ?string
    {
        if (!$value) return null;

        // Tenta vários formatos
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
            'd/m/Y H:i:s',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Normaliza valor monetário (string → decimal)
     */
    public function normalizeDecimal(?string $value): ?float
    {
        if (!$value) return null;

        // Remove símbolos de moeda
        $value = str_replace(['R$', '$', '€'], '', $value);

        // Remove espaços
        $value = trim($value);

        // Se usa vírgula como decimal (BR): 1.234,56
        if (preg_match('/\d+\.\d{3},\d{2}/', $value)) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        }
        // Se usa ponto como separador de milhares: 1,234.56
        else if (preg_match('/\d+,\d{3}\.\d{2}/', $value)) {
            $value = str_replace(',', '', $value);
        }
        // Apenas vírgula: 1234,56
        else {
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    /**
     * Normaliza unidade de medida
     */
    public function normalizeUnit(?string $value): ?string
    {
        if (!$value) return null;

        $value = strtoupper(trim($value));

        // Mapeamento de variações
        $map = [
            'UNIDADE' => 'UN',
            'UND' => 'UN',
            'KILO' => 'KG',
            'QUILOGRAMA' => 'KG',
            'LITRO' => 'L',
            'METRO' => 'M',
            'METRO QUADRADO' => 'M2',
            'METRO CÚBICO' => 'M3',
            'CAIXA' => 'CX',
        ];

        return $map[$value] ?? $value;
    }

    /**
     * Remove acentos e converte para minúsculas
     */
    public function removeAccents(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        $unwanted = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];

        return strtr($text, $unwanted);
    }

    /**
     * Gera hash normalizado para deduplicação
     */
    public function generateHash(array $fields): string
    {
        // Ordena campos alfabeticamente
        ksort($fields);

        // Normaliza valores
        $normalized = array_map(function($value) {
            if (is_string($value)) {
                return $this->removeAccents(strtolower(trim($value)));
            }
            return $value;
        }, $fields);

        // Gera hash
        return hash('sha256', json_encode($normalized));
    }

    /**
     * Normaliza linha CSV completa
     */
    public function normalizeRow(array $row, array $columnMap = []): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            // Mapeia nome da coluna se necessário
            $mappedKey = $columnMap[$key] ?? $key;

            // Normaliza valor baseado no tipo da coluna
            if (is_string($value)) {
                $normalized[$mappedKey] = $this->normalizeText($value);
            } else {
                $normalized[$mappedKey] = $value;
            }
        }

        return $normalized;
    }
}
