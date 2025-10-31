<?php
/**
 * Script para extrair UM arquivo CMED por vez
 * Uso: php extrair_cmed_individual.php nome_do_arquivo.xlsx mes
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

ini_set('memory_limit', '1024M');

if ($argc < 3) {
    echo "Uso: php extrair_cmed_individual.php <arquivo.xlsx> <mes>\n";
    echo "Exemplo: php extrair_cmed_individual.php 'Tabela CMED Junho 25 - SimTax.xlsx' junho\n";
    exit(1);
}

$nomeArquivo = $argv[1];
$mesReferencia = $argv[2];

$pastaBase = __DIR__;
$pastaDestino = $pastaBase . '/CMED_EXTRAIDO';
$caminhoArquivo = $pastaBase . '/' . $nomeArquivo;

echo "📂 Processando: $nomeArquivo ($mesReferencia)\n";

if (!file_exists($caminhoArquivo)) {
    echo "❌ Arquivo não encontrado!\n";
    exit(1);
}

try {
    // Usar leitura chunk by chunk
    $reader = new Xlsx();
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($caminhoArquivo);
    $worksheet = $spreadsheet->getActiveSheet();

    $highestRow = $worksheet->getHighestRow();
    echo "   📊 Total de linhas: $highestRow\n";

    // Ler apenas primeira linha para encontrar cabeçalho
    $cabecalho = [];
    $linhaCabecalho = 0;

    for ($row = 1; $row <= min(10, $highestRow); $row++) {
        $linha = [];
        $highestColumn = $worksheet->getHighestColumn();
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $linha[] = $worksheet->getCell($col . $row)->getValue();
        }

        $temPMC = false;
        $temProduto = false;
        foreach ($linha as $celula) {
            $celulaUpper = mb_strtoupper(trim($celula ?? ''));
            if (strpos($celulaUpper, 'PMC') !== false) $temPMC = true;
            if (strpos($celulaUpper, 'PRODUTO') !== false || strpos($celulaUpper, 'APRESENTAÇÃO') !== false) $temProduto = true;
        }

        if ($temPMC && $temProduto) {
            $linhaCabecalho = $row;
            $cabecalho = $linha;
            break;
        }
    }

    if (empty($cabecalho)) {
        echo "   ⚠️  Usando linha 1 como cabeçalho\n";
        $linhaCabecalho = 1;
        $highestColumn = $worksheet->getHighestColumn();
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $cabecalho[] = $worksheet->getCell($col . $linhaCabecalho)->getValue();
        }
    }

    echo "   📋 Cabeçalho: linha $linhaCabecalho | Colunas: " . count($cabecalho) . "\n";

    // Mapear colunas
    $mapeamento = [];
    foreach ($cabecalho as $index => $nomeColuna) {
        $nomeColunaNormalizado = mb_strtoupper(trim($nomeColuna ?? ''));
        if (strpos($nomeColunaNormalizado, 'PRODUTO') !== false || strpos($nomeColunaNormalizado, 'APRESENTAÇÃO') !== false) $mapeamento['produto'] = $index;
        if (strpos($nomeColunaNormalizado, 'SUBSTÂNCIA') !== false || strpos($nomeColunaNormalizado, 'PRINCÍPIO') !== false) $mapeamento['principio_ativo'] = $index;
        if (strpos($nomeColunaNormalizado, 'LABORATÓRIO') !== false) $mapeamento['laboratorio'] = $index;
        if (strpos($nomeColunaNormalizado, 'CNPJ') !== false) $mapeamento['cnpj'] = $index;
        if (strpos($nomeColunaNormalizado, 'EAN') !== false) $mapeamento['ean'] = $index;
        if (preg_match('/PMC\s*0/', $nomeColunaNormalizado)) $mapeamento['pmc_0'] = $index;
        if (preg_match('/PMC.*12/', $nomeColunaNormalizado)) $mapeamento['pmc_12'] = $index;
        if (preg_match('/PMC.*17/', $nomeColunaNormalizado)) $mapeamento['pmc_17'] = $index;
        if (preg_match('/PMC.*18/', $nomeColunaNormalizado)) $mapeamento['pmc_18'] = $index;
        if (preg_match('/PMC.*20/', $nomeColunaNormalizado)) $mapeamento['pmc_20'] = $index;
    }

    // Abrir arquivo JSON para escrita
    $nomeArquivoJSON = "cmed_{$mesReferencia}_2025.json";
    $caminhoJSON = $pastaDestino . '/' . $nomeArquivoJSON;
    $jsonFile = fopen($caminhoJSON, 'w');

    fwrite($jsonFile, "{\n");
    fwrite($jsonFile, "  \"mes\": \"$mesReferencia\",\n");
    fwrite($jsonFile, "  \"ano\": 2025,\n");
    fwrite($jsonFile, "  \"arquivo_origem\": \"$nomeArquivo\",\n");
    fwrite($jsonFile, "  \"data_extracao\": \"" . date('Y-m-d H:i:s') . "\",\n");
    fwrite($jsonFile, "  \"colunas_mapeadas\": " . json_encode(array_keys($mapeamento)) . ",\n");
    fwrite($jsonFile, "  \"medicamentos\": [\n");

    $linhasComDados = 0;
    $primeiraLinha = true;
    $highestColumn = $worksheet->getHighestColumn();

    // Processar linha por linha
    for ($row = $linhaCabecalho + 1; $row <= $highestRow; $row++) {
        $linha = [];
        for ($col = 'A'; $col <= $highestColumn; $col++) {
            $linha[] = $worksheet->getCell($col . $row)->getValue();
        }

        if (empty(array_filter($linha))) continue;

        $medicamento = [
            'linha' => $row,
            'produto' => isset($mapeamento['produto']) && isset($linha[$mapeamento['produto']]) ? trim($linha[$mapeamento['produto']] ?? '') : '',
            'principio_ativo' => isset($mapeamento['principio_ativo']) && isset($linha[$mapeamento['principio_ativo']]) ? trim($linha[$mapeamento['principio_ativo']] ?? '') : '',
            'laboratorio' => isset($mapeamento['laboratorio']) && isset($linha[$mapeamento['laboratorio']]) ? trim($linha[$mapeamento['laboratorio']] ?? '') : '',
            'cnpj' => isset($mapeamento['cnpj']) && isset($linha[$mapeamento['cnpj']]) ? trim($linha[$mapeamento['cnpj']] ?? '') : '',
            'ean' => isset($mapeamento['ean']) && isset($linha[$mapeamento['ean']]) ? trim($linha[$mapeamento['ean']] ?? '') : '',
            'pmc_0' => isset($mapeamento['pmc_0']) && isset($linha[$mapeamento['pmc_0']]) ? $linha[$mapeamento['pmc_0']] : null,
            'pmc_12' => isset($mapeamento['pmc_12']) && isset($linha[$mapeamento['pmc_12']]) ? $linha[$mapeamento['pmc_12']] : null,
            'pmc_17' => isset($mapeamento['pmc_17']) && isset($linha[$mapeamento['pmc_17']]) ? $linha[$mapeamento['pmc_17']] : null,
            'pmc_18' => isset($mapeamento['pmc_18']) && isset($linha[$mapeamento['pmc_18']]) ? $linha[$mapeamento['pmc_18']] : null,
            'pmc_20' => isset($mapeamento['pmc_20']) && isset($linha[$mapeamento['pmc_20']]) ? $linha[$mapeamento['pmc_20']] : null,
            'mes_referencia' => $mesReferencia,
            'ano_referencia' => 2025,
        ];

        if (!empty($medicamento['produto']) || !empty($medicamento['principio_ativo'])) {
            if (!$primeiraLinha) {
                fwrite($jsonFile, ",\n");
            }
            fwrite($jsonFile, "    " . json_encode($medicamento, JSON_UNESCAPED_UNICODE));
            $primeiraLinha = false;
            $linhasComDados++;
        }

        if ($row % 1000 == 0) {
            echo "   ⏳ Processadas: $row / $highestRow linhas ($linhasComDados medicamentos)\n";
        }
    }

    fwrite($jsonFile, "\n  ],\n");
    fwrite($jsonFile, "  \"total_medicamentos\": $linhasComDados\n");
    fwrite($jsonFile, "}\n");
    fclose($jsonFile);

    echo "   ✅ Concluído! Medicamentos: $linhasComDados | Arquivo: $nomeArquivoJSON\n";

    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);

} catch (Exception $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
