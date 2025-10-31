<?php
/**
 * Script para EXTRAIR dados das tabelas CMED (Janeiro a Outubro 2025)
 *
 * ⚠️ ATENÇÃO: Este script APENAS LÊ e EXTRAI dados dos arquivos Excel.
 * NÃO FAZ NENHUMA MODIFICAÇÃO NO SISTEMA OU BANCO DE DADOS.
 *
 * Data: 17/10/2025
 * Objetivo: Baixar/extrair dados para análise posterior
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Configurações
$pastaBase = __DIR__;
$pastaDestino = $pastaBase . '/CMED_EXTRAIDO';

// Criar pasta de destino se não existir
if (!is_dir($pastaDestino)) {
    mkdir($pastaDestino, 0755, true);
    echo "✅ Pasta criada: $pastaDestino\n\n";
}

// Lista de arquivos CMED
$arquivosCMED = [
    'Tabela CMED Janeiro 25 - SimTax.xlsx' => 'janeiro',
    'Tabela CMED Fevereiro 25 - SimTax.xlsx' => 'fevereiro',
    'Tabela CMED Março 25 - SimTax.xlsx' => 'marco',
    'Tabela CMED Abril 25 - SimTax.xlsx' => 'abril',
    'Tabela CMED Maio 25 - SimTax.xlsx' => 'maio',
    'Tabela CMED Junho 25 - SimTax.xlsx' => 'junho',
    'Tabela CMED Julho 25 - SimTax.xlsx' => 'julho',
    'CMED Agosto 25 - Modificada - Padrão Simtax.xlsx' => 'agosto',
    'CMED Setembro 25 - Modificada.xlsx' => 'setembro',
    'CMED Outubro 25 - Modificada.xlsx' => 'outubro',
];

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  EXTRAÇÃO DE DADOS CMED - Janeiro a Outubro 2025          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$totalArquivos = count($arquivosCMED);
$arquivosProcessados = 0;
$errosEncontrados = 0;

foreach ($arquivosCMED as $nomeArquivo => $mesReferencia) {
    $arquivosProcessados++;
    $caminhoArquivo = $pastaBase . '/' . $nomeArquivo;

    echo "[$arquivosProcessados/$totalArquivos] 📂 Processando: $nomeArquivo\n";

    if (!file_exists($caminhoArquivo)) {
        echo "   ❌ ERRO: Arquivo não encontrado!\n\n";
        $errosEncontrados++;
        continue;
    }

    try {
        // Carregar planilha
        $spreadsheet = IOFactory::load($caminhoArquivo);
        $worksheet = $spreadsheet->getActiveSheet();

        // Obter dados brutos
        $dados = $worksheet->toArray();

        // Encontrar linha do cabeçalho (geralmente linha 1 ou 2)
        $linhaCabecalho = 0;
        $cabecalho = [];

        for ($i = 0; $i < min(5, count($dados)); $i++) {
            $linha = $dados[$i];
            // Procurar por colunas típicas do CMED
            $temPMC = false;
            $temProduto = false;

            foreach ($linha as $celula) {
                $celulaUpper = mb_strtoupper(trim($celula ?? ''));
                if (strpos($celulaUpper, 'PMC') !== false) {
                    $temPMC = true;
                }
                if (strpos($celulaUpper, 'PRODUTO') !== false ||
                    strpos($celulaUpper, 'APRESENTAÇÃO') !== false ||
                    strpos($celulaUpper, 'APRESENTACAO') !== false) {
                    $temProduto = true;
                }
            }

            if ($temPMC && $temProduto) {
                $linhaCabecalho = $i;
                $cabecalho = $linha;
                break;
            }
        }

        if (empty($cabecalho)) {
            echo "   ⚠️  AVISO: Cabeçalho não identificado, usando linha 0\n";
            $linhaCabecalho = 0;
            $cabecalho = $dados[0];
        }

        echo "   📊 Cabeçalho encontrado na linha: " . ($linhaCabecalho + 1) . "\n";
        echo "   📋 Colunas: " . count($cabecalho) . "\n";

        // Mapear índices das colunas importantes
        $mapeamento = [];
        foreach ($cabecalho as $index => $nomeColuna) {
            $nomeColunaNormalizado = mb_strtoupper(trim($nomeColuna ?? ''));

            // Mapeamento de colunas conhecidas
            if (strpos($nomeColunaNormalizado, 'PRODUTO') !== false ||
                strpos($nomeColunaNormalizado, 'APRESENTAÇÃO') !== false ||
                strpos($nomeColunaNormalizado, 'APRESENTACAO') !== false) {
                $mapeamento['produto'] = $index;
            }
            if (strpos($nomeColunaNormalizado, 'SUBSTÂNCIA') !== false ||
                strpos($nomeColunaNormalizado, 'SUBSTANCIA') !== false ||
                strpos($nomeColunaNormalizado, 'PRINCÍPIO') !== false ||
                strpos($nomeColunaNormalizado, 'PRINCIPIO') !== false) {
                $mapeamento['principio_ativo'] = $index;
            }
            if (strpos($nomeColunaNormalizado, 'LABORATÓRIO') !== false ||
                strpos($nomeColunaNormalizado, 'LABORATORIO') !== false) {
                $mapeamento['laboratorio'] = $index;
            }
            if (strpos($nomeColunaNormalizado, 'CNPJ') !== false) {
                $mapeamento['cnpj'] = $index;
            }
            if (strpos($nomeColunaNormalizado, 'EAN') !== false) {
                $mapeamento['ean'] = $index;
            }
            if (preg_match('/PMC\s*0/', $nomeColunaNormalizado)) {
                $mapeamento['pmc_0'] = $index;
            }
            if (preg_match('/PMC.*12/', $nomeColunaNormalizado)) {
                $mapeamento['pmc_12'] = $index;
            }
            if (preg_match('/PMC.*17/', $nomeColunaNormalizado)) {
                $mapeamento['pmc_17'] = $index;
            }
            if (preg_match('/PMC.*18/', $nomeColunaNormalizado)) {
                $mapeamento['pmc_18'] = $index;
            }
            if (preg_match('/PMC.*20/', $nomeColunaNormalizado)) {
                $mapeamento['pmc_20'] = $index;
            }
        }

        echo "   🗺️  Colunas mapeadas: " . count($mapeamento) . "\n";

        // Extrair dados das linhas
        $medicamentos = [];
        $linhasComDados = 0;

        for ($i = $linhaCabecalho + 1; $i < count($dados); $i++) {
            $linha = $dados[$i];

            // Pular linhas vazias
            if (empty(array_filter($linha))) {
                continue;
            }

            // Extrair dados conforme mapeamento
            $medicamento = [
                'linha' => $i + 1,
                'produto' => isset($mapeamento['produto']) ? trim($linha[$mapeamento['produto']] ?? '') : '',
                'principio_ativo' => isset($mapeamento['principio_ativo']) ? trim($linha[$mapeamento['principio_ativo']] ?? '') : '',
                'laboratorio' => isset($mapeamento['laboratorio']) ? trim($linha[$mapeamento['laboratorio']] ?? '') : '',
                'cnpj' => isset($mapeamento['cnpj']) ? trim($linha[$mapeamento['cnpj']] ?? '') : '',
                'ean' => isset($mapeamento['ean']) ? trim($linha[$mapeamento['ean']] ?? '') : '',
                'pmc_0' => isset($mapeamento['pmc_0']) ? $linha[$mapeamento['pmc_0']] ?? null : null,
                'pmc_12' => isset($mapeamento['pmc_12']) ? $linha[$mapeamento['pmc_12']] ?? null : null,
                'pmc_17' => isset($mapeamento['pmc_17']) ? $linha[$mapeamento['pmc_17']] ?? null : null,
                'pmc_18' => isset($mapeamento['pmc_18']) ? $linha[$mapeamento['pmc_18']] ?? null : null,
                'pmc_20' => isset($mapeamento['pmc_20']) ? $linha[$mapeamento['pmc_20']] ?? null : null,
                'mes_referencia' => $mesReferencia,
                'ano_referencia' => 2025,
            ];

            // Só adicionar se tiver produto ou princípio ativo
            if (!empty($medicamento['produto']) || !empty($medicamento['principio_ativo'])) {
                $medicamentos[] = $medicamento;
                $linhasComDados++;
            }
        }

        echo "   ✅ Medicamentos extraídos: $linhasComDados\n";

        // Salvar em arquivo JSON
        $nomeArquivoJSON = "cmed_{$mesReferencia}_2025.json";
        $caminhoJSON = $pastaDestino . '/' . $nomeArquivoJSON;

        file_put_contents($caminhoJSON, json_encode([
            'mes' => $mesReferencia,
            'ano' => 2025,
            'arquivo_origem' => $nomeArquivo,
            'data_extracao' => date('Y-m-d H:i:s'),
            'total_medicamentos' => $linhasComDados,
            'colunas_mapeadas' => array_keys($mapeamento),
            'medicamentos' => $medicamentos
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        echo "   💾 Salvo em: $nomeArquivoJSON\n";

        // Criar também um resumo CSV para fácil visualização
        $nomeArquivoCSV = "cmed_{$mesReferencia}_2025_resumo.csv";
        $caminhoCSV = $pastaDestino . '/' . $nomeArquivoCSV;

        $csv = fopen($caminhoCSV, 'w');
        // Cabeçalho CSV
        fputcsv($csv, ['Linha', 'Produto', 'Princípio Ativo', 'Laboratório', 'EAN', 'PMC 0%', 'PMC 12%', 'PMC 17%', 'PMC 18%', 'PMC 20%']);

        // Dados (limitar a 1000 primeiras linhas para não ficar muito grande)
        foreach (array_slice($medicamentos, 0, 1000) as $med) {
            fputcsv($csv, [
                $med['linha'],
                $med['produto'],
                $med['principio_ativo'],
                $med['laboratorio'],
                $med['ean'],
                $med['pmc_0'],
                $med['pmc_12'],
                $med['pmc_17'],
                $med['pmc_18'],
                $med['pmc_20']
            ]);
        }
        fclose($csv);

        echo "   💾 CSV resumo salvo: $nomeArquivoCSV (primeiras 1000 linhas)\n";
        echo "   ✅ Concluído!\n\n";

    } catch (Exception $e) {
        echo "   ❌ ERRO ao processar: " . $e->getMessage() . "\n\n";
        $errosEncontrados++;
    }
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMO DA EXTRAÇÃO                                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "📊 Total de arquivos: $totalArquivos\n";
echo "✅ Processados com sucesso: " . ($totalArquivos - $errosEncontrados) . "\n";
echo "❌ Erros encontrados: $errosEncontrados\n";
echo "📁 Pasta de destino: $pastaDestino\n\n";

echo "💡 Próximos passos:\n";
echo "   1. Verificar os arquivos JSON gerados em: $pastaDestino\n";
echo "   2. Validar a estrutura dos dados extraídos\n";
echo "   3. Aguardar instrução para integração com o sistema\n\n";

echo "✅ Extração concluída!\n";
