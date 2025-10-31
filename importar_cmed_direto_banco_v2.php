<?php
/**
 * IMPORTAÇÃO DIRETA NO BANCO - CMED (Junho a Outubro)
 * Versão 2 - Ajustado para estrutura da tabela
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

ini_set('memory_limit', '1024M');
set_time_limit(0);

// Conectar ao banco
try {
    $pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=minhadattatech_db', 'minhadattatech_user', 'MinhaDataTech2024SecureDB');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conectado ao banco!\n\n";
} catch (PDOException $e) {
    die("❌ ERRO: " . $e->getMessage() . "\n");
}

// Verificar total atual
$stmt = $pdo->query("SELECT COUNT(*) FROM cp_medicamentos_cmed");
echo "📊 Registros atuais: " . $stmt->fetchColumn() . "\n\n";

// Arquivos restantes
$arquivos = [
    'Tabela CMED Junho 25 - SimTax.xlsx' => 'Junho 2025',
    'Tabela CMED Julho 25 - SimTax.xlsx' => 'Julho 2025',
    'CMED Agosto 25 - Modificada - Padrão Simtax.xlsx' => 'Agosto 2025',
    'CMED Setembro 25 - Modificada.xlsx' => 'Setembro 2025',
    'CMED Outubro 25 - Modificada.xlsx' => 'Outubro 2025',
];

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  IMPORTAÇÃO - Junho a Outubro 2025                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// SQL Insert
$insertSQL = "INSERT INTO cp_medicamentos_cmed (
    substancia, cnpj_laboratorio, laboratorio, ean1, produto,
    pmc_0, pmc_12, pmc_17, pmc_18, pmc_20,
    mes_referencia, data_importacao, created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())";

$stmtInsert = $pdo->prepare($insertSQL);

$totalInserido = 0;

foreach ($arquivos as $arquivo => $mes) {
    $caminho = __DIR__ . '/' . $arquivo;

    echo "[$mes] 📂 $arquivo\n";

    if (!file_exists($caminho)) {
        echo "   ❌ Não encontrado!\n\n";
        continue;
    }

    try {
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($caminho);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        echo "   📊 Linhas: $highestRow | Colunas: $highestCol\n";

        // Encontrar cabeçalho (linha 5 geralmente)
        $cabecalho = [];
        $linhaHeader = 0;

        for ($r = 1; $r <= 10; $r++) {
            $linha = [];
            for ($c = 'A'; $c <= $highestCol; $c++) {
                $linha[] = $sheet->getCell($c . $r)->getValue();
            }

            $temPMC = false;
            $temProduto = false;
            foreach ($linha as $cel) {
                $celUpper = mb_strtoupper(trim($cel ?? ''));
                if (strpos($celUpper, 'PMC') !== false) $temPMC = true;
                if (strpos($celUpper, 'PRODUTO') !== false || strpos($celUpper, 'APRESENTAÇÃO') !== false) $temProduto = true;
            }

            if ($temPMC && $temProduto) {
                $linhaHeader = $r;
                $cabecalho = $linha;
                break;
            }
        }

        if (empty($cabecalho)) {
            echo "   ⚠️  Cabeçalho não encontrado!\n\n";
            continue;
        }

        echo "   📋 Cabeçalho: linha $linhaHeader\n";

        // Mapear colunas
        $map = [];
        foreach ($cabecalho as $idx => $nome) {
            $nomeUpper = mb_strtoupper(trim($nome ?? ''));
            if (strpos($nomeUpper, 'SUBSTÂNCIA') !== false || strpos($nomeUpper, 'PRINCÍPIO') !== false) $map['substancia'] = $idx;
            if (strpos($nomeUpper, 'CNPJ') !== false) $map['cnpj'] = $idx;
            if (strpos($nomeUpper, 'LABORATÓRIO') !== false) $map['laboratorio'] = $idx;
            if (strpos($nomeUpper, 'EAN') !== false && !isset($map['ean'])) $map['ean'] = $idx;
            if (strpos($nomeUpper, 'PRODUTO') !== false || strpos($nomeUpper, 'APRESENTAÇÃO') !== false) $map['produto'] = $idx;
            if (preg_match('/PMC\s*0/', $nomeUpper)) $map['pmc_0'] = $idx;
            if (preg_match('/PMC.*12/', $nomeUpper)) $map['pmc_12'] = $idx;
            if (preg_match('/PMC.*17/', $nomeUpper)) $map['pmc_17'] = $idx;
            if (preg_match('/PMC.*18/', $nomeUpper)) $map['pmc_18'] = $idx;
            if (preg_match('/PMC.*20/', $nomeUpper)) $map['pmc_20'] = $idx;
        }

        echo "   🗺️  Mapeadas: " . count($map) . " colunas\n";

        $pdo->beginTransaction();
        $inseridos = 0;
        $erros = 0;

        for ($r = $linhaHeader + 1; $r <= $highestRow; $r++) {
            $linha = [];
            for ($c = 'A'; $c <= $highestCol; $c++) {
                $linha[] = $sheet->getCell($c . $r)->getValue();
            }

            if (empty(array_filter($linha))) continue;

            $substancia = isset($map['substancia']) ? trim($linha[$map['substancia']] ?? '') : '';
            $cnpj = isset($map['cnpj']) ? trim($linha[$map['cnpj']] ?? '') : '';
            $laboratorio = isset($map['laboratorio']) ? trim($linha[$map['laboratorio']] ?? '') : '';
            $ean = isset($map['ean']) ? trim($linha[$map['ean']] ?? '') : '';
            $produto = isset($map['produto']) ? trim($linha[$map['produto']] ?? '') : '';

            if (empty($produto) && empty($substancia)) continue;

            $pmc0 = isset($map['pmc_0']) ? ($linha[$map['pmc_0']] ?? null) : null;
            $pmc12 = isset($map['pmc_12']) ? ($linha[$map['pmc_12']] ?? null) : null;
            $pmc17 = isset($map['pmc_17']) ? ($linha[$map['pmc_17']] ?? null) : null;
            $pmc18 = isset($map['pmc_18']) ? ($linha[$map['pmc_18']] ?? null) : null;
            $pmc20 = isset($map['pmc_20']) ? ($linha[$map['pmc_20']] ?? null) : null;

            $pmc0 = is_numeric($pmc0) ? (float)$pmc0 : null;
            $pmc12 = is_numeric($pmc12) ? (float)$pmc12 : null;
            $pmc17 = is_numeric($pmc17) ? (float)$pmc17 : null;
            $pmc18 = is_numeric($pmc18) ? (float)$pmc18 : null;
            $pmc20 = is_numeric($pmc20) ? (float)$pmc20 : null;

            try {
                $stmtInsert->execute([
                    $substancia,
                    $cnpj,
                    $laboratorio,
                    $ean,
                    $produto,
                    $pmc0,
                    $pmc12,
                    $pmc17,
                    $pmc18,
                    $pmc20,
                    $mes
                ]);
                $inseridos++;
            } catch (PDOException $e) {
                $erros++;
            }

            if ($r % 1000 == 0) {
                echo "   ⏳ $r/$highestRow | Inseridos: $inseridos | Erros: $erros\n";
            }
        }

        $pdo->commit();
        echo "   ✅ Inseridos: $inseridos | Erros: $erros\n\n";
        $totalInserido += $inseridos;

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        gc_collect_cycles();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "   ❌ ERRO: " . $e->getMessage() . "\n\n";
    }
}

$stmt = $pdo->query("SELECT COUNT(*) FROM cp_medicamentos_cmed");
$totalFinal = $stmt->fetchColumn();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMO                                                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "✅ Inseridos nesta execução: $totalInserido\n";
echo "📊 Total no banco: $totalFinal\n\n";
echo "✅ Importação concluída!\n";
