<?php

echo "🚀 IMPORTAÇÃO RÁPIDA CMED - Usando PDO Puro\n";
echo str_repeat("=", 50) . "\n\n";

// Conectar ao banco
$pdo = new PDO(
    'pgsql:host=127.0.0.1;port=5432;dbname=minhadattatech_db',
    'minhadattatech_user',
    'MinhaDataTech2024SecureDB',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Limpar tabela
echo "🗑️  Limpando tabela...\n";
$pdo->exec("TRUNCATE TABLE cp_medicamentos_cmed");
echo "✅ Tabela limpa!\n\n";

// Ler CSV
$csv = fopen('/tmp/cmed_import_fixed.csv', 'r');
if (!$csv) {
    die("❌ Erro ao abrir CSV\n");
}

echo "📖 Lendo CSV...\n";

$colunas = [
    'substancia', 'cnpj_laboratorio', 'laboratorio', 'codigo_ggrem', 'registro',
    'ean1', 'ean2', 'ean3', 'produto', 'apresentacao', 'classe_terapeutica', 'tipo_produto', 'regime_preco',
    'pf_sem_impostos', 'pf_0', 'pf_12', 'pf_12_sem_icms', 'pf_13', 'pf_13_com_icms',
    'pf_14', 'pf_15', 'pf_15_com_icms', 'pf_16', 'pf_17', 'pf_17_alagas', 'pf_17_com_icms',
    'pf_18', 'pf_18_com_icms', 'pf_19', 'pf_19_com_icms', 'pf_20', 'pf_20_com_icms',
    'pf_21', 'pf_22', 'pf_23',
    'pmc_sem_impostos', 'pmc_0', 'pmc_12', 'pmc_12_sem_icms', 'pmc_13', 'pmc_13_com_icms',
    'pmc_14', 'pmc_15', 'pmc_15_com_icms', 'pmc_16', 'pmc_17', 'pmc_17_alagas', 'pmc_17_com_icms',
    'pmc_18', 'pmc_18_com_icms', 'pmc_19', 'pmc_19_com_icms', 'pmc_20', 'pmc_20_com_icms',
    'pmc_21', 'pmc_22', 'pmc_23',
    'restricao_hospitalar', 'cap', 'confaz', 'icms_0', 'analise_recursal',
    'lista_concessao_credito', 'comercializacao_2024', 'taxa_anvisa',
    'mes_referencia', 'data_importacao'
];

$placeholders = implode(',', array_fill(0, count($colunas), '?'));
$sql = "INSERT INTO cp_medicamentos_cmed (" . implode(',', $colunas) . ") VALUES ($placeholders)";
$stmt = $pdo->prepare($sql);

$inseridos = 0;
$erros = 0;
$lote = [];
$tamanhoLote = 1000;

$pdo->beginTransaction();

while (($row = fgetcsv($csv)) !== false) {
    // Validação básica
    if (empty($row[0]) || empty($row[8])) { // substancia e produto
        $erros++;
        continue;
    }

    // Adicionar mes_referencia e data_importacao
    $row[] = 'Outubro 2025';
    $row[] = date('Y-m-d');

    // Processar valores vazios
    for ($i = 0; $i < count($row); $i++) {
        if ($row[$i] === '' || $row[$i] === '-' || $row[$i] === 'N/A' || trim($row[$i]) === '') {
            $row[$i] = null;
        }
    }

    try {
        $stmt->execute($row);
        $inseridos++;

        if ($inseridos % 1000 == 0) {
            echo "✅ $inseridos registros inseridos...\n";
            $pdo->commit();
            $pdo->beginTransaction();
        }
    } catch (Exception $e) {
        $erros++;
        if ($erros < 5) {
            echo "⚠️  Erro: " . $e->getMessage() . "\n";
        }
    }
}

$pdo->commit();
fclose($csv);

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ IMPORTAÇÃO CONCLUÍDA!\n";
echo "📊 Registros inseridos: $inseridos\n";
echo "❌ Erros: $erros\n";
echo str_repeat("=", 50) . "\n";

// Estatísticas
$result = $pdo->query("
    SELECT
        COUNT(*) as total,
        COUNT(DISTINCT laboratorio) as laboratorios,
        COUNT(DISTINCT substancia) as substancias
    FROM cp_medicamentos_cmed
")->fetch(PDO::FETCH_ASSOC);

echo "\n📊 ESTATÍSTICAS DO BANCO:\n";
echo "   Total: " . number_format($result['total']) . " medicamentos\n";
echo "   Laboratórios: " . number_format($result['laboratorios']) . "\n";
echo "   Substâncias: " . number_format($result['substancias']) . "\n";
