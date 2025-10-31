<?php

echo "📤 EXPORTANDO CMED PARA CSV - TODAS AS COLUNAS\n";
echo str_repeat("=", 60) . "\n\n";

require '/home/dattapro/modulos/cestadeprecos/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "📖 Carregando Excel (isso demora, aguarde)...\n";
$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load('/home/dattapro/modulos/cestadeprecos/CMED Outubro 25 - Modificada.xlsx');
$worksheet = $spreadsheet->getActiveSheet();

echo "✅ Excel carregado!\n\n";

// Mapeamento COMPLETO das 64 colunas (B até BN)
$colunas = [
    'B' => 'substancia',
    'C' => 'cnpj_laboratorio',
    'D' => 'laboratorio',
    'E' => 'codigo_ggrem',
    'F' => 'registro',
    'G' => 'ean1',
    'H' => 'ean2',
    'I' => 'ean3',
    'J' => 'produto',
    'K' => 'apresentacao',
    'L' => 'classe_terapeutica',
    'M' => 'tipo_produto',
    'N' => 'regime_preco',
    // Preços PF
    'O' => 'pf_sem_impostos',
    'P' => 'pf_0',
    'Q' => 'pf_12',
    'R' => 'pf_12_sem_icms',
    'S' => 'pf_13',
    'T' => 'pf_13_com_icms',
    'U' => 'pf_14',
    'V' => 'pf_15',
    'W' => 'pf_15_com_icms',
    'X' => 'pf_16',
    'Y' => 'pf_17',
    'Z' => 'pf_17_alagas',
    'AA' => 'pf_17_com_icms',
    'AB' => 'pf_18',
    'AC' => 'pf_18_com_icms',
    'AD' => 'pf_19',
    'AE' => 'pf_19_com_icms',
    'AF' => 'pf_20',
    'AG' => 'pf_20_com_icms',
    'AH' => 'pf_21',
    'AI' => 'pf_22',
    'AJ' => 'pf_23',
    // Preços PMC
    'AK' => 'pmc_sem_impostos',
    'AL' => 'pmc_0',
    'AM' => 'pmc_12',
    'AN' => 'pmc_12_sem_icms',
    'AO' => 'pmc_13',
    'AP' => 'pmc_13_com_icms',
    'AQ' => 'pmc_14',
    'AR' => 'pmc_15',
    'AS' => 'pmc_15_com_icms',
    'AT' => 'pmc_16',
    'AU' => 'pmc_17',
    'AV' => 'pmc_17_alagas',
    'AW' => 'pmc_17_com_icms',
    'AX' => 'pmc_18',
    'AY' => 'pmc_18_com_icms',
    'AZ' => 'pmc_19',
    'BA' => 'pmc_19_com_icms',
    'BB' => 'pmc_20',
    'BC' => 'pmc_20_com_icms',
    'BD' => 'pmc_21',
    'BE' => 'pmc_22',
    'BF' => 'pmc_23',
    // Dados Tributários
    'BG' => 'restricao_hospitalar',
    'BH' => 'cap',
    'BI' => 'confaz',
    'BJ' => 'icms_0',
    'BK' => 'analise_recursal',
    'BL' => 'lista_concessao_credito',
    'BM' => 'comercializacao_2024',
    'BN' => 'taxa_anvisa',
];

$csv = fopen('/tmp/cmed_completo.csv', 'w');

echo "💾 Exportando dados (linha 6 em diante)...\n\n";

$totalLinhas = $worksheet->getHighestRow();
$exportadas = 0;

for ($linha = 6; $linha <= $totalLinhas; $linha++) {
    $row = [];

    foreach ($colunas as $colExcel => $nomeCampo) {
        $valor = $worksheet->getCell($colExcel . $linha)->getValue();

        // Processar valor
        if (empty($valor) || $valor === '-' || $valor === 'N/A') {
            $row[] = '';
        } else {
            // Limpar quebras de linha e tabs
            $valor = str_replace(["\n", "\r", "\t"], ' ', $valor);
            // Trimmar espaços
            $valor = trim($valor);

            // Se for campo de preço, converter vírgula para ponto
            if (strpos($nomeCampo, 'pf_') === 0 || strpos($nomeCampo, 'pmc_') === 0 || $nomeCampo === 'taxa_anvisa') {
                $valor = str_replace(['R$', ' ', ','], ['', '', '.'], $valor);
                // Remover pontos de milhar se houver mais de um ponto
                $pontos = substr_count($valor, '.');
                if ($pontos > 1) {
                    // Tem ponto de milhar, remover todos exceto o último
                    $partes = explode('.', $valor);
                    $decimal = array_pop($partes);
                    $valor = implode('', $partes) . '.' . $decimal;
                }
            }

            $row[] = $valor;
        }
    }

    // Adicionar mes_referencia e data_importacao
    $row[] = 'Outubro 2025';
    $row[] = date('Y-m-d');

    fputcsv($csv, $row, ',', '"', '\\');
    $exportadas++;

    if ($exportadas % 1000 == 0) {
        echo "✅ $exportadas linhas exportadas...\n";
    }
}

fclose($csv);

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ EXPORTAÇÃO CONCLUÍDA!\n";
echo "📊 Total de linhas exportadas: $exportadas\n";
echo "📁 Arquivo: /tmp/cmed_completo.csv\n";
echo str_repeat("=", 60) . "\n";

// Verificar estrutura do CSV
echo "\n🔍 Verificando estrutura do CSV...\n";
$csvTest = fopen('/tmp/cmed_completo.csv', 'r');
$firstRow = fgetcsv($csvTest);
echo "   Colunas no CSV: " . count($firstRow) . " (esperado: 66)\n";
fclose($csvTest);
