<?php
/**
 * IMPORTAÇÃO APENAS OUTUBRO 2025 - CMED
 * Script ultra-otimizado para processar apenas 1 arquivo
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

ini_set('memory_limit', '2048M'); // 2GB
set_time_limit(0);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  IMPORTAÇÃO CMED - OUTUBRO 2025                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Conectar ao banco
try {
    $pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=minhadattatech_db', 'minhadattatech_user', 'MinhaDataTech2024SecureDB');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conectado ao banco!\n";
} catch (PDOException $e) {
    die("❌ ERRO: " . $e->getMessage() . "\n");
}

// Verificar registros atuais
$stmt = $pdo->query("SELECT COUNT(*) FROM cp_medicamentos_cmed");
$totalAntes = $stmt->fetchColumn();
echo "📊 Registros antes: $totalAntes\n\n";

$arquivo = 'CMED Outubro 25 - Modificada.xlsx';
$caminho = __DIR__ . '/' . $arquivo;

echo "📂 Arquivo: $arquivo\n";

if (!file_exists($caminho)) {
    die("❌ Arquivo não encontrado!\n");
}

echo "📦 Tamanho: " . round(filesize($caminho) / 1024 / 1024, 2) . " MB\n";
echo "⏳ Carregando arquivo... (pode demorar 1-2 minutos)\n\n";

try {
    // Ler com configurações otimizadas
    $reader = new Xlsx();
    $reader->setReadDataOnly(true);
    $reader->setReadEmptyCells(false); // Não ler células vazias

    $spreadsheet = $reader->load($caminho);
    $sheet = $spreadsheet->getActiveSheet();

    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();

    echo "✅ Arquivo carregado!\n";
    echo "📊 Linhas: $highestRow | Colunas: $highestCol\n\n";

    // Encontrar cabeçalho
    echo "🔍 Procurando cabeçalho...\n";
    $cabecalho = [];
    $linhaHeader = 0;

    for ($r = 1; $r <= 10; $r++) {
        $linha = [];
        for ($c = 'A'; $c <= $highestCol; $c++) {
            $valor = $sheet->getCell($c . $r)->getValue();
            $linha[] = $valor;
        }

        $temPMC = false;
        $temProduto = false;

        foreach ($linha as $cel) {
            $celUpper = mb_strtoupper(trim($cel ?? ''));
            if (strpos($celUpper, 'PMC') !== false) $temPMC = true;
            if (strpos($celUpper, 'PRODUTO') !== false ||
                strpos($celUpper, 'APRESENTAÇÃO') !== false ||
                strpos($celUpper, 'APRESENTACAO') !== false) {
                $temProduto = true;
            }
        }

        if ($temPMC && $temProduto) {
            $linhaHeader = $r;
            $cabecalho = $linha;
            echo "✅ Cabeçalho encontrado na linha $linhaHeader!\n\n";
            break;
        }
    }

    if (empty($cabecalho)) {
        die("❌ Cabeçalho não encontrado!\n");
    }

    // Mapear colunas importantes
    echo "🗺️  Mapeando colunas...\n";
    $map = [];

    foreach ($cabecalho as $idx => $nome) {
        $nomeUpper = mb_strtoupper(trim($nome ?? ''));

        if (strpos($nomeUpper, 'SUBSTÂNCIA') !== false ||
            strpos($nomeUpper, 'SUBSTANCIA') !== false ||
            strpos($nomeUpper, 'PRINCÍPIO') !== false) {
            $map['substancia'] = $idx;
        }
        if (strpos($nomeUpper, 'CNPJ') !== false) {
            $map['cnpj'] = $idx;
        }
        if (strpos($nomeUpper, 'LABORATÓRIO') !== false ||
            strpos($nomeUpper, 'LABORATORIO') !== false) {
            $map['laboratorio'] = $idx;
        }
        if (strpos($nomeUpper, 'EAN') !== false && !isset($map['ean'])) {
            $map['ean'] = $idx;
        }
        if (strpos($nomeUpper, 'PRODUTO') !== false ||
            strpos($nomeUpper, 'APRESENTAÇÃO') !== false ||
            strpos($nomeUpper, 'APRESENTACAO') !== false) {
            $map['produto'] = $idx;
        }
        if (preg_match('/PMC\s*0/', $nomeUpper)) {
            $map['pmc_0'] = $idx;
        }
        if (preg_match('/PMC.*12/', $nomeUpper)) {
            $map['pmc_12'] = $idx;
        }
        if (preg_match('/PMC.*17/', $nomeUpper)) {
            $map['pmc_17'] = $idx;
        }
        if (preg_match('/PMC.*18/', $nomeUpper)) {
            $map['pmc_18'] = $idx;
        }
        if (preg_match('/PMC.*20/', $nomeUpper)) {
            $map['pmc_20'] = $idx;
        }
    }

    echo "✅ Colunas mapeadas: " . count($map) . "\n";
    echo "   - " . implode(', ', array_keys($map)) . "\n\n";

    if (!isset($map['produto'])) {
        die("❌ Coluna PRODUTO não encontrada!\n");
    }

    // Preparar INSERT
    $insertSQL = "INSERT INTO cp_medicamentos_cmed (
        substancia, cnpj_laboratorio, laboratorio, ean1, produto,
        pmc_0, pmc_12, pmc_17, pmc_18, pmc_20,
        mes_referencia, data_importacao, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())";

    $stmtInsert = $pdo->prepare($insertSQL);

    echo "🚀 Iniciando importação...\n\n";

    $pdo->beginTransaction();

    $inseridos = 0;
    $erros = 0;
    $vazias = 0;
    $tempoInicio = microtime(true);

    // Processar linha por linha
    for ($r = $linhaHeader + 1; $r <= $highestRow; $r++) {
        $linha = [];
        for ($c = 'A'; $c <= $highestCol; $c++) {
            $linha[] = $sheet->getCell($c . $r)->getValue();
        }

        // Pular linhas vazias
        if (empty(array_filter($linha))) {
            $vazias++;
            continue;
        }

        // Extrair dados
        $substancia = isset($map['substancia']) ? trim($linha[$map['substancia']] ?? '') : '';
        $cnpj = isset($map['cnpj']) ? trim($linha[$map['cnpj']] ?? '') : '';
        $laboratorio = isset($map['laboratorio']) ? trim($linha[$map['laboratorio']] ?? '') : '';
        $ean = isset($map['ean']) ? trim($linha[$map['ean']] ?? '') : '';
        $produto = isset($map['produto']) ? trim($linha[$map['produto']] ?? '') : '';

        // Pular se não tiver produto OU substância
        if (empty($produto) && empty($substancia)) {
            continue;
        }

        // Extrair preços
        $pmc0 = isset($map['pmc_0']) ? ($linha[$map['pmc_0']] ?? null) : null;
        $pmc12 = isset($map['pmc_12']) ? ($linha[$map['pmc_12']] ?? null) : null;
        $pmc17 = isset($map['pmc_17']) ? ($linha[$map['pmc_17']] ?? null) : null;
        $pmc18 = isset($map['pmc_18']) ? ($linha[$map['pmc_18']] ?? null) : null;
        $pmc20 = isset($map['pmc_20']) ? ($linha[$map['pmc_20']] ?? null) : null;

        // Converter para float
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
                'Outubro 2025'
            ]);
            $inseridos++;
        } catch (PDOException $e) {
            $erros++;
            if ($erros <= 3) {
                echo "⚠️  Erro linha $r: " . substr($e->getMessage(), 0, 100) . "...\n";
            }
        }

        // Progress a cada 1000 linhas
        if ($r % 1000 == 0) {
            $tempoDecorrido = microtime(true) - $tempoInicio;
            $velocidade = $inseridos / $tempoDecorrido;
            $porcentagem = round(($r / $highestRow) * 100, 1);

            echo sprintf(
                "   ⏳ %d/%d (%s%%) | Inseridos: %d | Erros: %d | Vel: %.0f/s\n",
                $r,
                $highestRow,
                $porcentagem,
                $inseridos,
                $erros,
                $velocidade
            );
        }

        // Liberar memória a cada 500 linhas
        if ($r % 500 == 0) {
            unset($linha);
            gc_collect_cycles();
        }
    }

    // Commit
    $pdo->commit();

    $tempoTotal = microtime(true) - $tempoInicio;
    $velocidadeMedia = $inseridos / $tempoTotal;

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  RESULTADO DA IMPORTAÇÃO                                   ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "✅ Medicamentos inseridos: $inseridos\n";
    echo "⚠️  Erros: $erros\n";
    echo "⏭️  Linhas vazias: $vazias\n";
    echo "⏱️  Tempo total: " . round($tempoTotal, 2) . "s\n";
    echo "🚀 Velocidade média: " . round($velocidadeMedia, 0) . " registros/segundo\n\n";

    // Verificar total final
    $stmt = $pdo->query("SELECT COUNT(*) FROM cp_medicamentos_cmed");
    $totalDepois = $stmt->fetchColumn();

    echo "📊 Total no banco: $totalDepois (antes: $totalAntes)\n\n";

    // Liberar memória
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet, $sheet);
    gc_collect_cycles();

    echo "✅ Importação de OUTUBRO 2025 concluída com sucesso!\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
