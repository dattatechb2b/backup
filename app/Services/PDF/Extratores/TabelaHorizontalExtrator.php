<?php

namespace App\Services\PDF\Extratores;

use App\Services\PDF\FormatoExtrator;

/**
 * Extrator para PDFs com formato de tabela horizontal
 * Detecta automaticamente colunas e extrai dados
 */
class TabelaHorizontalExtrator implements FormatoExtrator
{
    public function extrair(array $linhas): array
    {
        $itens = [];
        $fornecedores = [];
        $colunas = null;
        $linhaCabecalho = null;

        // PASSO 1: Encontrar linha de cabeçalho
        foreach ($linhas as $index => $linha) {
            $linhaUpper = strtoupper(trim($linha));

            // Detectar cabeçalho (linha com múltiplas palavras-chave)
            $palavrasChave = ['ITEM', 'DESCRI', 'QUANT', 'QTD', 'UNID', 'PREÇO', 'PRECO', 'VALOR', 'TOTAL'];
            $encontradas = 0;
            foreach ($palavrasChave as $palavra) {
                if (strpos($linhaUpper, $palavra) !== false) {
                    $encontradas++;
                }
            }

            if ($encontradas >= 3) {
                $linhaCabecalho = $index;
                $colunas = $this->detectarColunas($linha);
                \Log::info('TabelaHorizontal: Cabeçalho detectado', [
                    'linha' => $index,
                    'colunas' => $colunas
                ]);
                break;
            }
        }

        // Se não encontrou cabeçalho, tenta detectar por padrão de dados
        if (!$colunas) {
            \Log::info('TabelaHorizontal: Cabeçalho não encontrado, usando detecção automática');
            $colunas = $this->detectarColunasPorPadrao($linhas);
            $linhaCabecalho = 0;
        }

        // PASSO 2: Extrair itens
        $numeroItem = 1;
        for ($i = $linhaCabecalho + 1; $i < count($linhas); $i++) {
            $linha = $linhas[$i];

            // Pular linhas vazias
            if (trim($linha) === '') {
                continue;
            }

            // Pular rodapé (linhas com "TOTAL GERAL", "VALOR TOTAL", etc.)
            if (preg_match('/TOTAL\s+(GERAL|GLOBAL)/i', $linha)) {
                break;
            }

            // Tentar extrair item
            $item = $this->extrairItemDaLinha($linha, $colunas, $numeroItem);

            if ($item && !empty($item['descricao'])) {
                $itens[] = $item;

                // Extrair fornecedor se disponível
                if (!empty($item['fornecedor']) && !in_array($item['fornecedor'], $fornecedores)) {
                    $fornecedores[] = $item['fornecedor'];
                }

                $numeroItem++;
            }
        }

        // PASSO 3: Calcular valor total
        $valorTotal = array_sum(array_column($itens, 'preco_total'));

        \Log::info('TabelaHorizontal: Extração concluída', [
            'total_itens' => count($itens),
            'valor_total' => $valorTotal
        ]);

        return [
            'itens' => $itens,
            'fornecedores' => array_map(function($nome) {
                return ['nome' => $nome, 'cnpj' => '', 'valor_total' => 0];
            }, $fornecedores),
            'valor_total_geral' => $valorTotal,
            'lotes' => []
        ];
    }

    /**
     * Detecta colunas pela linha de cabeçalho
     */
    private function detectarColunas(string $linhaCabecalho): array
    {
        $colunas = [
            'item' => null,
            'descricao' => null,
            'quantidade' => null,
            'unidade' => null,
            'preco_unitario' => null,
            'preco_total' => null,
            'fornecedor' => null,
            'marca' => null,
        ];

        // Separar por TAB (preferencial) ou múltiplos espaços
        $partes = preg_split('/\t+/', $linhaCabecalho);
        if (count($partes) < 3) {
            // Tenta separar por múltiplos espaços (2+)
            $partes = preg_split('/\s{2,}/', $linhaCabecalho);
        }

        foreach ($partes as $index => $parte) {
            $parteUpper = strtoupper(trim($parte));

            if (preg_match('/^(ITEM|IT|N[ºO]|NUM)/i', $parteUpper)) {
                $colunas['item'] = $index;
            } elseif (preg_match('/(DESCRI|ESPECIFICA|PRODUTO|MATERIAL)/i', $parteUpper)) {
                $colunas['descricao'] = $index;
            } elseif (preg_match('/(QUANT|QTD|QTDE)/i', $parteUpper)) {
                $colunas['quantidade'] = $index;
            } elseif (preg_match('/(UNID|UND|UN)/i', $parteUpper)) {
                $colunas['unidade'] = $index;
            } elseif (preg_match('/(UNIT|UNITÁ|P\.?\s*UNIT)/i', $parteUpper)) {
                $colunas['preco_unitario'] = $index;
            } elseif (preg_match('/(TOTAL|SUBTOTAL|P\.?\s*TOTAL)/i', $parteUpper)) {
                $colunas['preco_total'] = $index;
            } elseif (preg_match('/(FORNEC|EMPRESA|CONTRAT)/i', $parteUpper)) {
                $colunas['fornecedor'] = $index;
            } elseif (preg_match('/MARCA/i', $parteUpper)) {
                $colunas['marca'] = $index;
            }
        }

        return $colunas;
    }

    /**
     * Detecta colunas por padrão de dados (quando não há cabeçalho)
     */
    private function detectarColunasPorPadrao(array $linhas): array
    {
        // Estratégia: primeira coluna é item, última é total, meio é descrição
        // Colunas com números pequenos são quantidade/unidade
        // Colunas com R$ ou valores altos são preços

        return [
            'item' => 0,
            'descricao' => 1,
            'quantidade' => 2,
            'unidade' => 3,
            'preco_unitario' => 4,
            'preco_total' => 5,
            'fornecedor' => null,
            'marca' => null,
        ];
    }

    /**
     * Extrai item de uma linha
     */
    private function extrairItemDaLinha(string $linha, array $colunas, int $numeroItem): ?array
    {
        // Separar por TAB ou múltiplos espaços
        $partes = preg_split('/\t+/', $linha);
        if (count($partes) < 3) {
            $partes = preg_split('/\s{2,}/', $linha);
        }

        // Se ainda não conseguiu separar, tenta regex para padrão comum
        if (count($partes) < 3) {
            return $this->extrairPorRegex($linha, $numeroItem);
        }

        $item = [
            'numero' => $numeroItem,
            'descricao' => '',
            'quantidade' => 0,
            'unidade' => 'UN',
            'preco_unitario' => 0,
            'preco_total' => 0,
            'lote' => '1',
            'fornecedor' => '',
            'marca' => '',
        ];

        // Extrair valores das colunas detectadas
        if ($colunas['item'] !== null && isset($partes[$colunas['item']])) {
            $numeroExtraido = preg_replace('/\D/', '', $partes[$colunas['item']]);
            if (!empty($numeroExtraido)) {
                $item['numero'] = (int)$numeroExtraido;
            }
        }

        if ($colunas['descricao'] !== null && isset($partes[$colunas['descricao']])) {
            $item['descricao'] = trim($partes[$colunas['descricao']]);
        }

        if ($colunas['quantidade'] !== null && isset($partes[$colunas['quantidade']])) {
            $item['quantidade'] = $this->converterNumero($partes[$colunas['quantidade']]);
        }

        if ($colunas['unidade'] !== null && isset($partes[$colunas['unidade']])) {
            $item['unidade'] = strtoupper(trim($partes[$colunas['unidade']]));
        }

        if ($colunas['preco_unitario'] !== null && isset($partes[$colunas['preco_unitario']])) {
            $item['preco_unitario'] = $this->converterMonetario($partes[$colunas['preco_unitario']]);
        }

        if ($colunas['preco_total'] !== null && isset($partes[$colunas['preco_total']])) {
            $item['preco_total'] = $this->converterMonetario($partes[$colunas['preco_total']]);
        }

        if ($colunas['fornecedor'] !== null && isset($partes[$colunas['fornecedor']])) {
            $item['fornecedor'] = trim($partes[$colunas['fornecedor']]);
        }

        if ($colunas['marca'] !== null && isset($partes[$colunas['marca']])) {
            $item['marca'] = trim($partes[$colunas['marca']]);
        }

        // Se não tem preço total mas tem unitário e quantidade, calcular
        if ($item['preco_total'] == 0 && $item['preco_unitario'] > 0 && $item['quantidade'] > 0) {
            $item['preco_total'] = $item['preco_unitario'] * $item['quantidade'];
        }

        return $item;
    }

    /**
     * Extrai item por regex (fallback)
     */
    private function extrairPorRegex(string $linha, int $numeroItem): ?array
    {
        // Padrão comum: NÚMERO DESCRIÇÃO QUANTIDADE UNIDADE PREÇO_UNIT PREÇO_TOTAL
        if (preg_match('/^(\d+)\s+(.+?)\s+(\d+[,.]?\d*)\s+(\w+)\s+([\d,.]+)\s+([\d,.]+)$/i', trim($linha), $matches)) {
            return [
                'numero' => (int)$matches[1],
                'descricao' => trim($matches[2]),
                'quantidade' => $this->converterNumero($matches[3]),
                'unidade' => strtoupper($matches[4]),
                'preco_unitario' => $this->converterMonetario($matches[5]),
                'preco_total' => $this->converterMonetario($matches[6]),
                'lote' => '1',
                'fornecedor' => '',
                'marca' => '',
            ];
        }

        return null;
    }

    private function converterNumero($valor): float
    {
        $valor = preg_replace('/[^\d,.]/', '', $valor);
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
        return (float)$valor;
    }

    private function converterMonetario($valor): float
    {
        $valor = preg_replace('/[^\d,.]/', '', $valor);
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
        return (float)$valor;
    }
}
