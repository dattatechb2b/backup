<?php

echo "🚀 IMPORTAÇÃO SIMPLIFICADA CMED - Apenas Colunas Essenciais\n";
echo str_repeat("=", 60) . "\n\n";

require '/home/dattapro/modulos/cestadeprecos/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

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

// Carregar Excel SEM lazy loading - modo rápido
echo "📖 Carregando Excel (aguarde, isso demora mas é uma vez só)...\n";
$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
// Carregar APENAS as colunas que precisamos (B,D,J,K,AL) = substancia, laboratorio, produto, apresentacao, pmc_0
$reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool {
        return in_array($columnAddress, ['B','D','J','K','AL']) && $row >= 6;
    }
});

$spreadsheet = $reader->load('/home/dattapro/modulos/cestadeprecos/CMED Outubro 25 - Modificada.xlsx');
$worksheet = $spreadsheet->getActiveSheet();

echo "✅ Excel carregado!\n\n";

// Preparar INSERT
$sql = "INSERT INTO cp_medicamentos_cmed (substancia, laboratorio, produto, apresentacao, pmc_0, mes_referencia, data_importacao) VALUES (?,?,?,?,?,?,?)";
$stmt = $pdo->prepare($sql);

$inseridos = 0;
$erros = 0;
$totalLinhas = $worksheet->getHighestRow();

echo "💉 Iniciando importação de " . ($totalLinhas - 5) . " medicamentos...\n\n";

$pdo->beginTransaction();

for ($linha = 6; $linha <= $totalLinhas; $linha++) {
    $substancia = $worksheet->getCell('B' . $linha)->getValue();
    $laboratorio = $worksheet->getCell('D' . $linha)->getValue();
    $produto = $worksheet->getCell('J' . $linha)->getValue();
    $apresentacao = $worksheet->getCell('K' . $linha)->getValue();
    $pmc_0 = $worksheet->getCell('AL' . $linha)->getValue();

    // Validação básica
    if (empty($substancia) || empty($produto)) {
        $erros++;
        continue;
    }

    // Processar preço
    if (!empty($pmc_0)) {
        $pmc_0 = str_replace(['R$', ' ', ','], ['', '', '.'], $pmc_0);
        $pmc_0 = is_numeric($pmc_0) ? (float)$pmc_0 : null;
    } else {
        $pmc_0 = null;
    }

    try {
        $stmt->execute([
            trim($substancia),
            trim($laboratorio),
            trim($produto),
            trim($apresentacao),
            $pmc_0,
            'Outubro 2025',
            date('Y-m-d')
        ]);
        $inseridos++;

        if ($inseridos % 1000 == 0) {
            echo "✅ $inseridos registros inseridos...\n";
            $pdo->commit();
            $pdo->beginTransaction();
        }
    } catch (Exception $e) {
        $erros++;
        if ($erros < 5) {
            echo "⚠️  Erro na linha $linha: " . $e->getMessage() . "\n";
        }
    }
}

$pdo->commit();

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ IMPORTAÇÃO CONCLUÍDA!\n";
echo "📊 Registros inseridos: $inseridos\n";
echo "❌ Erros: $erros\n";
echo str_repeat("=", 60) . "\n";

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
