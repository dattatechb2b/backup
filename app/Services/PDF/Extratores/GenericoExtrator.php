<?php

namespace App\Services\PDF\Extratores;

use App\Services\PDF\FormatoExtrator;

/**
 * Extrator genérico ultra-inteligente
 * Usa heurísticas para detectar itens em qualquer formato de PDF
 */
class GenericoExtrator implements FormatoExtrator
{
    public function extrair(array $linhas): array
    {
        \Log::info('GenericoExtrator: Iniciando extração inteligente');

        $itens = [];
        $numeroItem = 1;

        // ESTRATÉGIA 1: Detectar itens por padrões numéricos
        $itens = array_merge($itens, $this->detectarPorPadroesNumericos($linhas));

        // ESTRATÉGIA 2: Detectar por palavras-chave
        if (empty($itens)) {
            $itens = $this->detectarPorPalavrasChave($linhas);
        }

        // ESTRATÉGIA 3: Detectar por estrutura de linhas consecutivas
        if (empty($itens)) {
            $itens = $this->detectarPorEstrutura($linhas);
        }

        // Renumerar itens
        foreach ($itens as $index => &$item) {
            $item['numero'] = $index + 1;
        }

        $valorTotal = array_sum(array_column($itens, 'preco_total'));

        \Log::info('GenericoExtrator: Extração concluída', [
            'total_itens' => count($itens),
            'valor_total' => $valorTotal,
            'metodo' => empty($itens) ? 'FALHOU' : 'SUCESSO'
        ]);

        return [
            'itens' => $itens,
            'fornecedores' => [],
            'valor_total_geral' => $valorTotal,
            'lotes' => []
        ];
    }

    /**
     * Detecta itens por padrões numéricos
     * Procura linhas com sequência: número, texto, números
     */
    private function detectarPorPadroesNumericos(array $linhas): array
    {
        $itens = [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            // Padrão 1: "001 DESCRIÇÃO 10 UN 5,50 55,00"
            if (preg_match('/^(\d{1,4})\s+(.{10,}?)\s+(\d+[,.]?\d*)\s+(\w{1,10})\s+([\d,.]+)\s+([\d,.]+)$/i', $linha, $m)) {
                $itens[] = [
                    'numero' => (int)$m[1],
                    'descricao' => trim($m[2]),
                    'quantidade' => $this->converterNumero($m[3]),
                    'unidade' => strtoupper($m[4]),
                    'preco_unitario' => $this->converterMonetario($m[5]),
                    'preco_total' => $this->converterMonetario($m[6]),
                    'lote' => '1',
                    'fornecedor' => '',
                    'marca' => '',
                ];
                continue;
            }

            // Padrão 2: "1. Descrição do produto R$ 10,00"
            if (preg_match('/^(\d+)[.\)]\s+(.{10,}?)\s+R?\$?\s*([\d,.]+)$/i', $linha, $m)) {
                $preco = $this->converterMonetario($m[3]);
                $itens[] = [
                    'numero' => (int)$m[1],
                    'descricao' => trim($m[2]),
                    'quantidade' => 1,
                    'unidade' => 'UN',
                    'preco_unitario' => $preco,
                    'preco_total' => $preco,
                    'lote' => '1',
                    'fornecedor' => '',
                    'marca' => '',
                ];
                continue;
            }

            // Padrão 3: "ITEM 01 - DESCRIÇÃO - QTD: 10 - VALOR: R$ 100,00"
            if (preg_match('/ITEM\s+(\d+).*?[:–-]\s*(.+?)[:–-].*?QTD.*?(\d+).*?VALOR.*?([\d,.]+)/i', $linha, $m)) {
                $preco = $this->converterMonetario($m[4]);
                $qtd = $this->converterNumero($m[3]);
                $itens[] = [
                    'numero' => (int)$m[1],
                    'descricao' => trim($m[2]),
                    'quantidade' => $qtd,
                    'unidade' => 'UN',
                    'preco_unitario' => $qtd > 0 ? $preco / $qtd : $preco,
                    'preco_total' => $preco,
                    'lote' => '1',
                    'fornecedor' => '',
                    'marca' => '',
                ];
                continue;
            }
        }

        return $itens;
    }

    /**
     * Detecta itens por palavras-chave
     */
    private function detectarPorPalavrasChave(array $linhas): array
    {
        $itens = [];
        $itemAtual = null;

        foreach ($linhas as $index => $linha) {
            $linhaUpper = strtoupper(trim($linha));

            // Detectar início de novo item
            if (preg_match('/^(ITEM|PRODUTO|MATERIAL)\s*[:\-]?\s*(\d+)/i', $linhaUpper, $m)) {
                // Salvar item anterior se existir
                if ($itemAtual && !empty($itemAtual['descricao'])) {
                    $itens[] = $itemAtual;
                }

                // Iniciar novo item
                $itemAtual = [
                    'numero' => (int)$m[2],
                    'descricao' => '',
                    'quantidade' => 1,
                    'unidade' => 'UN',
                    'preco_unitario' => 0,
                    'preco_total' => 0,
                    'lote' => '1',
                    'fornecedor' => '',
                    'marca' => '',
                ];

                // Tentar pegar descrição na mesma linha
                $resto = trim(substr($linha, strlen($m[0])));
                if (!empty($resto)) {
                    $itemAtual['descricao'] = $resto;
                }
                continue;
            }

            // Se estamos dentro de um item, procurar dados
            if ($itemAtual) {
                // Quantidade
                if (preg_match('/QUANT.*?[:=]?\s*(\d+[,.]?\d*)/i', $linhaUpper, $m)) {
                    $itemAtual['quantidade'] = $this->converterNumero($m[1]);
                }

                // Unidade
                if (preg_match('/UNID.*?[:=]?\s*(\w+)/i', $linhaUpper, $m)) {
                    $itemAtual['unidade'] = strtoupper($m[1]);
                }

                // Preço
                if (preg_match('/(VALOR|PREÇO|PRECO).*?R?\$?\s*([\d,.]+)/i', $linhaUpper, $m)) {
                    $preco = $this->converterMonetario($m[2]);
                    $itemAtual['preco_total'] = $preco;
                    if ($itemAtual['quantidade'] > 0) {
                        $itemAtual['preco_unitario'] = $preco / $itemAtual['quantidade'];
                    }
                }

                // Se linha tem só texto (sem números), adiciona à descrição
                if (preg_match('/^[A-ZÀ-Ú\s]+$/i', $linha) && strlen($linha) > 10) {
                    if (!empty($itemAtual['descricao'])) {
                        $itemAtual['descricao'] .= ' ' . trim($linha);
                    } else {
                        $itemAtual['descricao'] = trim($linha);
                    }
                }
            }
        }

        // Adicionar último item
        if ($itemAtual && !empty($itemAtual['descricao'])) {
            $itens[] = $itemAtual;
        }

        return $itens;
    }

    /**
     * Detecta por estrutura (linhas consecutivas formam um item)
     */
    private function detectarPorEstrutura(array $linhas): array
    {
        $itens = [];
        $buffer = [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            // Pular linhas vazias
            if (empty($linha)) {
                // Se buffer tem conteúdo, tentar formar item
                if (count($buffer) >= 2) {
                    $item = $this->formarItemDoBuffer($buffer);
                    if ($item) {
                        $itens[] = $item;
                    }
                }
                $buffer = [];
                continue;
            }

            // Adicionar linha ao buffer
            $buffer[] = $linha;

            // Se buffer ficou muito grande, resetar
            if (count($buffer) > 10) {
                $buffer = [];
            }
        }

        return $itens;
    }

    /**
     * Tenta formar um item a partir de linhas consecutivas
     */
    private function formarItemDoBuffer(array $linhas): ?array
    {
        $item = [
            'numero' => 0,
            'descricao' => '',
            'quantidade' => 1,
            'unidade' => 'UN',
            'preco_unitario' => 0,
            'preco_total' => 0,
            'lote' => '1',
            'fornecedor' => '',
            'marca' => '',
        ];

        // Primeira linha geralmente é descrição
        $item['descricao'] = $linhas[0];

        // Procurar números nas outras linhas
        foreach ($linhas as $index => $linha) {
            if ($index == 0) continue;

            // Procurar quantidade (número pequeno, geralmente < 1000)
            if (preg_match('/^(\d{1,4})[,.]?\d{0,2}$/', trim($linha), $m)) {
                $num = (int)$m[1];
                if ($num < 1000) {
                    $item['quantidade'] = $num;
                }
            }

            // Procurar unidade (1-5 letras maiúsculas)
            if (preg_match('/^([A-Z]{1,5})$/i', trim($linha), $m)) {
                $item['unidade'] = strtoupper($m[1]);
            }

            // Procurar preço (formato monetário)
            if (preg_match('/([\d,.]+)/', $linha, $m)) {
                $valor = $this->converterMonetario($m[1]);
                if ($valor > 0 && $valor < 1000000) {
                    if ($item['preco_unitario'] == 0) {
                        $item['preco_unitario'] = $valor;
                    } else {
                        $item['preco_total'] = $valor;
                    }
                }
            }
        }

        // Calcular preço total se não foi encontrado
        if ($item['preco_total'] == 0 && $item['preco_unitario'] > 0) {
            $item['preco_total'] = $item['preco_unitario'] * $item['quantidade'];
        }

        // Só retorna se tem descrição e preço
        if (!empty($item['descricao']) && $item['preco_total'] > 0) {
            return $item;
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
