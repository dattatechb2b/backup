<?php

echo "🚀 IMPORTAÇÃO DIRETA CMED - Todas as colunas\n";
echo str_repeat("=", 60) . "\n\n";

set_time_limit(0);
ini_set('memory_limit', '2G');

require '/home/dattapro/modulos/cestadeprecos/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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

// Carregar Excel
echo "📖 Carregando Excel (aguarde)...\n";
$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load('/home/dattapro/modulos/cestadeprecos/CMED Outubro 25 - Modificada.xlsx');
$worksheet = $spreadsheet->getActiveSheet();
echo "✅ Excel carregado!\n\n";

// SQL preparado
$sql = "INSERT INTO cp_medicamentos_cmed (
    substancia, cnpj_laboratorio, laboratorio, codigo_ggrem, registro, ean1, ean2, ean3, produto, apresentacao,
    classe_terapeutica, tipo_produto, regime_preco,
    pf_sem_impostos, pf_0, pf_12, pf_12_sem_icms, pf_13, pf_13_com_icms, pf_14, pf_15, pf_15_com_icms,
    pf_16, pf_17, pf_17_alagas, pf_17_com_icms, pf_18, pf_18_com_icms, pf_19, pf_19_com_icms,
    pf_20, pf_20_com_icms, pf_21, pf_22, pf_23,
    pmc_sem_impostos, pmc_0, pmc_12, pmc_12_sem_icms, pmc_13, pmc_13_com_icms, pmc_14, pmc_15, pmc_15_com_icms,
    pmc_16, pmc_17, pmc_17_alagas, pmc_17_com_icms, pmc_18, pmc_18_com_icms, pmc_19, pmc_19_com_icms,
    pmc_20, pmc_20_com_icms, pmc_21, pmc_22, pmc_23,
    restricao_hospitalar, cap, confaz, icms_0, analise_recursal, lista_concessao_credito, comercializacao_2024, taxa_anvisa,
    mes_referencia, data_importacao
) VALUES (" . implode(',', array_fill(0, 67, '?')) . ")";

$stmt = $pdo->prepare($sql);

$totalLinhas = $worksheet->getHighestRow();
$inseridos = 0;
$erros = 0;

echo "💉 Importando $totalLinhas medicamentos (SEM LOGS - MÁXIMA VELOCIDADE)...\n\n";

$pdo->beginTransaction();
$batchSize = 500; // Commit a cada 500 registros para máxima velocidade

for ($linha = 6; $linha <= $totalLinhas; $linha++) {
    // Ler valores
    $dados = [];

    // Colunas B-BN
    $cols = ['B','C','D','E','F','G','H','I','J','K','L','M','N',
             'O','P','Q','R','S','T','U','V','W','X','Y','Z',
             'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ',
             'AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
             'BA','BB','BC','BD','BE','BF',
             'BG','BH','BI','BJ','BK','BL','BM','BN'];

    foreach ($cols as $idx => $col) {
        $valor = $worksheet->getCell($col . $linha)->getValue();

        // Processar valor
        if (empty($valor) || $valor === '-' || $valor === 'N/A' || trim($valor) === '') {
            // Campos booleanos não podem ser null, devem ser false
            if ($idx >= 57 && $idx <= 60) {
                $dados[] = 'f';
            } else {
                $dados[] = null;
            }
        } else {
            $valor = str_replace(["\n", "\r", "\t"], ' ', trim($valor));

            // Campos numéricos (índices 13-56 são preços pf_*, 57-60 não são)
            if ($idx >= 13 && $idx <= 56) {
                $valor = str_replace(['R$', ' ', ','], ['', '', '.'], $valor);
                $dados[] = is_numeric($valor) ? (float)$valor : null;
            }
            // Campos booleanos (índices 57-60: restricao_hospitalar, cap, confaz, icms_0)
            elseif ($idx >= 57 && $idx <= 60) {
                $dados[] = in_array(strtoupper($valor), ['SIM', 'S', 'TRUE', '1']) ? 't' : 'f';
            }
            // Campo taxa_anvisa (índice 64 é numérico)
            elseif ($idx == 64) {
                $valor = str_replace(['R$', ' ', ','], ['', '', '.'], $valor);
                $dados[] = is_numeric($valor) ? (float)$valor : null;
            }
            else {
                // Sem truncamento - campos já expandidos para 255
                $dados[] = $valor;
            }
        }
    }

    // Validação básica
    if (empty($dados[0]) || empty($dados[8])) { // substancia ou produto vazio
        $erros++;
        continue;
    }

    // Adicionar mes_referencia e data_importacao
    $dados[] = 'Outubro 2025';
    $dados[] = date('Y-m-d');

    try {
        $stmt->execute($dados);
        $inseridos++;

        if ($inseridos % $batchSize == 0) {
            $pdo->commit();
            $pdo->beginTransaction();
            // Mostrar apenas a cada 1000
            if ($inseridos % 1000 == 0) {
                echo "✅ $inseridos registros inseridos...\n";
            }
        }
    } catch (Exception $e) {
        $erros++;
        // Sem logs para máxima velocidade
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

echo "\n📊 BANCO DE DADOS:\n";
echo "   Total: " . number_format($result['total']) . " medicamentos\n";
echo "   Laboratórios: " . number_format($result['laboratorios']) . "\n";
echo "   Substâncias: " . number_format($result['substancias']) . "\n";
