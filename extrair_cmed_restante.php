<?php
/**
 * Script OTIMIZADO para extrair dados CMED restantes (Junho a Outubro)
 *
 * Otimizações:
 * - Não salva todos os medicamentos em memória
 * - Escreve direto em arquivo (streaming)
 * - Libera memória após cada linha
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

ini_set('memory_limit', '512M'); // Aumentar limite de memória

$pastaBase = __DIR__;
$pastaDestino = $pastaBase . '/CMED_EXTRAIDO';

// Arquivos restantes (Junho a Outubro)
$arquivosCMED = [
    'Tabela CMED Junho 25 - SimTax.xlsx' => 'junho',
    'Tabela CMED Julho 25 - SimTax.xlsx' => 'julho',
    'CMED Agosto 25 - Modificada - Padrão Simtax.xlsx' => 'agosto',
    'CMED Setembro 25 - Modificada.xlsx' => 'setembro',
    'CMED Outubro 25 - Modificada.xlsx' => 'outubro',
];

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  EXTRAÇÃO CMED - Junho a Outubro 2025 (OTIMIZADO)         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

foreach ($arquivosCMED as $nomeArquivo => $mesReferencia) {
    $caminhoArquivo = $pastaBase . '/' . $nomeArquivo;

    echo "📂 Processando: $nomeArquivo\n";

    if (!file_exists($caminhoArquivo)) {
        echo "   ❌ Arquivo não encontrado!\n\n";
        continue;
    }

    try {
        $spreadsheet = IOFactory::load($caminhoArquivo);
        $worksheet = $spreadsheet->getActiveSheet();
        $dados = $worksheet->toArray();

        // Encontrar cabeçalho
        $linhaCabecalho = 0;
        $cabecalho = [];
        for ($i = 0; $i < 5; $i++) {
            $linha = $dados[$i];
            $temPMC = false;
            $temProduto = false;
            foreach ($linha as $celula) {
                $celulaUpper = mb_strtoupper(trim($celula ?? ''));
                if (strpos($celulaUpper, 'PMC') !== false) $temPMC = true;
                if (strpos($celulaUpper, 'PRODUTO') !== false || strpos($celulaUpper, 'APRESENTAÇÃO') !== false) $temProduto = true;
            }
            if ($temPMC && $temProduto) {
                $linhaCabecalho = $i;
                $cabecalho = $linha;
                break;
            }
        }

        if (empty($cabecalho)) {
            $linhaCabecalho = 0;
            $cabecalho = $dados[0];
        }

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

        echo "   📊 Cabeçalho: linha " . ($linhaCabecalho + 1) . " | Colunas: " . count($cabecalho) . "\n";

        // Processar linha por linha E ESCREVER DIRETO NO JSON
        $nomeArquivoJSON = "cmed_{$mesReferencia}_2025.json";
        $caminhoJSON = $pastaDestino . '/' . $nomeArquivoJSON;

        $jsonFile = fopen($caminhoJSON, 'w');

        // Escrever metadados
        fwrite($jsonFile, "{\n");
        fwrite($jsonFile, "  \"mes\": \"$mesReferencia\",\n");
        fwrite($jsonFile, "  \"ano\": 2025,\n");
        fwrite($jsonFile, "  \"arquivo_origem\": \"$nomeArquivo\",\n");
        fwrite($jsonFile, "  \"data_extracao\": \"" . date('Y-m-d H:i:s') . "\",\n");
        fwrite($jsonFile, "  \"colunas_mapeadas\": " . json_encode(array_keys($mapeamento)) . ",\n");
        fwrite($jsonFile, "  \"medicamentos\": [\n");

        $linhasComDados = 0;
        $primeiraLinha = true;

        for ($i = $linhaCabecalho + 1; $i < count($dados); $i++) {
            $linha = $dados[$i];
            if (empty(array_filter($linha))) continue;

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

            if (!empty($medicamento['produto']) || !empty($medicamento['principio_ativo'])) {
                if (!$primeiraLinha) {
                    fwrite($jsonFile, ",\n");
                }
                fwrite($jsonFile, "    " . json_encode($medicamento, JSON_UNESCAPED_UNICODE));
                $primeiraLinha = false;
                $linhasComDados++;
            }

            // Liberar memória a cada 100 linhas
            if ($i % 100 == 0) {
                unset($linha, $medicamento);
                gc_collect_cycles();
            }
        }

        // Finalizar JSON
        fwrite($jsonFile, "\n  ],\n");
        fwrite($jsonFile, "  \"total_medicamentos\": $linhasComDados\n");
        fwrite($jsonFile, "}\n");
        fclose($jsonFile);

        echo "   ✅ Medicamentos: $linhasComDados | Salvo em: $nomeArquivoJSON\n";

        // CSV resumo (primeiras 1000 linhas)
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $worksheet, $dados);
        gc_collect_cycles();

        echo "   ✅ Concluído!\n\n";

    } catch (Exception $e) {
        echo "   ❌ ERRO: " . $e->getMessage() . "\n\n";
    }
}

echo "✅ Extração dos meses restantes concluída!\n";
