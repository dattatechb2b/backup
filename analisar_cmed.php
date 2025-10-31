<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

echo "🔍 ANALISANDO ARQUIVOS CMED\n";
echo str_repeat("=", 80) . "\n\n";

$arquivos = glob(__DIR__ . "/*CMED*.xlsx");

foreach ($arquivos as $arquivo) {
    $nomeArquivo = basename($arquivo);
    echo "📄 ARQUIVO: {$nomeArquivo}\n";
    echo "   Tamanho: " . number_format(filesize($arquivo) / 1024 / 1024, 2) . " MB\n";

    try {
        $spreadsheet = IOFactory::load($arquivo);

        // Listar abas
        $sheetNames = $spreadsheet->getSheetNames();
        echo "   Abas: " . count($sheetNames) . "\n";

        foreach ($sheetNames as $index => $sheetName) {
            $sheet = $spreadsheet->getSheet($index);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            echo "\n   🗂️  ABA {$index}: \"{$sheetName}\"\n";
            echo "      Linhas: {$highestRow}\n";
            echo "      Colunas: {$highestColumn}\n";

            // Ler cabeçalho (primeira linha)
            echo "      Cabeçalho:\n";
            $headerRow = $sheet->rangeToArray('A1:' . $highestColumn . '1')[0];
            foreach ($headerRow as $colIndex => $value) {
                if (!empty($value)) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                    echo "         {$colLetter}: {$value}\n";
                }
            }

            // Ler 3 primeiras linhas de dados (linhas 2-4)
            if ($highestRow >= 2) {
                echo "\n      📊 PRIMEIRAS 3 LINHAS DE DADOS:\n";
                $maxLinhas = min(4, $highestRow);
                for ($row = 2; $row <= $maxLinhas; $row++) {
                    $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row)[0];
                    echo "         Linha {$row}: ";
                    $nonEmpty = array_filter($rowData, fn($v) => !empty($v));
                    if (count($nonEmpty) > 0) {
                        echo json_encode(array_slice($rowData, 0, 5), JSON_UNESCAPED_UNICODE) . "...\n";
                    } else {
                        echo "(vazia)\n";
                    }
                }
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

    } catch (Exception $e) {
        echo "   ❌ ERRO: " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("-", 80) . "\n\n";
}

echo "\n✅ ANÁLISE CONCLUÍDA!\n";
