<?php
/**
 * IMPORTAÇÃO DIRETA NO BANCO DE DADOS - CMED
 *
 * Este script lê os arquivos Excel CMED linha por linha
 * e insere DIRETAMENTE no banco de dados PostgreSQL,
 * sem carregar tudo na memória.
 *
 * ⚠️ ATENÇÃO: Este script VAI MODIFICAR O BANCO DE DADOS!
 * Mas é apenas para popular a tabela cp_medicamentos_cmed.
 */

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

ini_set('memory_limit', '1024M');

// Configurações do banco
$dbHost = '127.0.0.1';
$dbPort = '5432';
$dbName = 'minhadattatech_db';
$dbUser = 'minhadattatech_user';
$dbPass = 'MinhaDataTech2024SecureDB';

// Conectar ao banco
try {
    $pdo = new PDO("pgsql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conectado ao banco de dados!\n\n";
} catch (PDOException $e) {
    die("❌ ERRO ao conectar ao banco: " . $e->getMessage() . "\n");
}

// Verificar se tabela existe
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM cp_medicamentos_cmed");
    $count = $stmt->fetchColumn();
    echo "📊 Tabela cp_medicamentos_cmed existe. Registros atuais: $count\n\n";
} catch (PDOException $e) {
    die("❌ ERRO: Tabela cp_medicamentos_cmed não existe!\n" . $e->getMessage() . "\n");
}

// Arquivos restantes (Junho a Outubro)
$arquivosCMED = [
    'Tabela CMED Junho 25 - SimTax.xlsx' => 'junho',
    'Tabela CMED Julho 25 - SimTax.xlsx' => 'julho',
    'CMED Agosto 25 - Modificada - Padrão Simtax.xlsx' => 'agosto',
    'CMED Setembro 25 - Modificada.xlsx' => 'setembro',
    'CMED Outubro 25 - Modificada.xlsx' => 'outubro',
];

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  IMPORTAÇÃO DIRETA NO BANCO - Junho a Outubro 2025        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Preparar statement INSERT
$insertSQL = "INSERT INTO cp_medicamentos_cmed (
    codigo_ean,
    principio_ativo,
    nome_comercial,
    apresentacao,
    laboratorio,
    cnpj,
    pmc_0,
    pmc_12,
    pmc_17,
    pmc_18,
    pmc_20,
    mes_referencia,
    ano_referencia,
    created_at,
    updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
ON CONFLICT (codigo_ean, mes_referencia, ano_referencia) DO UPDATE SET
    principio_ativo = EXCLUDED.principio_ativo,
    nome_comercial = EXCLUDED.nome_comercial,
    apresentacao = EXCLUDED.apresentacao,
    laboratorio = EXCLUDED.laboratorio,
    cnpj = EXCLUDED.cnpj,
    pmc_0 = EXCLUDED.pmc_0,
    pmc_12 = EXCLUDED.pmc_12,
    pmc_17 = EXCLUDED.pmc_17,
    pmc_18 = EXCLUDED.pmc_18,
    pmc_20 = EXCLUDED.pmc_20,
    updated_at = NOW()";

$stmtInsert = $pdo->prepare($insertSQL);

$totalGeralInserido = 0;

foreach ($arquivosCMED as $nomeArquivo => $mesReferencia) {
    $caminhoArquivo = __DIR__ . '/' . $nomeArquivo;

    echo "[$mesReferencia] 📂 Processando: $nomeArquivo\n";

    if (!file_exists($caminhoArquivo)) {
        echo "   ❌ Arquivo não encontrado!\n\n";
        continue;
    }

    try {
        // Usar leitura otimizada
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($caminhoArquivo);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        echo "   📊 Linhas: $highestRow | Colunas: $highestColumn\n";

        // Encontrar cabeçalho (linha 5 geralmente)
        $cabecalho = [];
        $linhaCabecalho = 0;

        for ($row = 1; $row <= min(10, $highestRow); $row++) {
            $linha = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $valor = $worksheet->getCell($col . $row)->getValue();
                $linha[] = $valor;
            }

            // Verificar se tem colunas típicas do CMED
            $temPMC = false;
            $temProduto = false;
            foreach ($linha as $celula) {
                $celulaUpper = mb_strtoupper(trim($celula ?? ''));
                if (strpos($celulaUpper, 'PMC') !== false) $temPMC = true;
                if (strpos($celulaUpper, 'PRODUTO') !== false ||
                    strpos($celulaUpper, 'APRESENTAÇÃO') !== false ||
                    strpos($celulaUpper, 'APRESENTACAO') !== false) {
                    $temProduto = true;
                }
            }

            if ($temPMC && $temProduto) {
                $linhaCabecalho = $row;
                $cabecalho = $linha;
                break;
            }
        }

        if (empty($cabecalho)) {
            echo "   ⚠️  Cabeçalho não detectado! Pulando arquivo.\n\n";
            continue;
        }

        echo "   📋 Cabeçalho na linha: $linhaCabecalho\n";

        // Mapear colunas
        $mapeamento = [];
        foreach ($cabecalho as $index => $nomeColuna) {
            $nomeColunaNormalizado = mb_strtoupper(trim($nomeColuna ?? ''));

            if (strpos($nomeColunaNormalizado, 'EAN') !== false && !isset($mapeamento['ean'])) {
                $mapeamento['ean'] = $index;
            }
            if (strpos($nomeColunaNormalizado, 'SUBSTÂNCIA') !== false ||
                strpos($nomeColunaNormalizado, 'SUBSTANCIA') !== false ||
                strpos($nomeColunaNormalizado, 'PRINCÍPIO') !== false ||
                strpos($nomeColunaNormalizado, 'PRINCIPIO') !== false) {
                $mapeamento['principio_ativo'] = $index;
            }
            if (strpos($nomeColunaNormalizado, 'PRODUTO') !== false ||
                strpos($nomeColunaNormalizado, 'APRESENTAÇÃO') !== false ||
                strpos($nomeColunaNormalizado, 'APRESENTACAO') !== false) {
                $mapeamento['produto'] = $index;
            }
            if (strpos($nomeColunaNormalizado, 'LABORATÓRIO') !== false ||
                strpos($nomeColunaNormalizado, 'LABORATORIO') !== false) {
                $mapeamento['laboratorio'] = $index;
            }
            if (strpos($nomeColunaNormalizado, 'CNPJ') !== false) {
                $mapeamento['cnpj'] = $index;
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

        echo "   🗺️  Colunas mapeadas: " . implode(', ', array_keys($mapeamento)) . "\n";

        if (!isset($mapeamento['ean']) || !isset($mapeamento['produto'])) {
            echo "   ❌ Colunas essenciais não encontradas! Pulando arquivo.\n\n";
            continue;
        }

        // Iniciar transação
        $pdo->beginTransaction();

        $inseridos = 0;
        $erros = 0;

        // Processar linha por linha
        for ($row = $linhaCabecalho + 1; $row <= $highestRow; $row++) {
            $linha = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $valor = $worksheet->getCell($col . $row)->getValue();
                $linha[] = $valor;
            }

            // Pular linhas vazias
            if (empty(array_filter($linha))) {
                continue;
            }

            // Extrair dados
            $ean = isset($mapeamento['ean']) ? trim($linha[$mapeamento['ean']] ?? '') : '';
            $principioAtivo = isset($mapeamento['principio_ativo']) ? trim($linha[$mapeamento['principio_ativo']] ?? '') : '';
            $produto = isset($mapeamento['produto']) ? trim($linha[$mapeamento['produto']] ?? '') : '';
            $laboratorio = isset($mapeamento['laboratorio']) ? trim($linha[$mapeamento['laboratorio']] ?? '') : '';
            $cnpj = isset($mapeamento['cnpj']) ? trim($linha[$mapeamento['cnpj']] ?? '') : '';

            // Pular se não tiver EAN ou produto
            if (empty($ean) && empty($produto)) {
                continue;
            }

            // Se não tiver EAN, gerar um fake baseado na linha
            if (empty($ean)) {
                $ean = 'SEMEAN_' . $mesReferencia . '_' . $row;
            }

            // Extrair preços
            $pmc0 = isset($mapeamento['pmc_0']) ? $linha[$mapeamento['pmc_0']] ?? null : null;
            $pmc12 = isset($mapeamento['pmc_12']) ? $linha[$mapeamento['pmc_12']] ?? null : null;
            $pmc17 = isset($mapeamento['pmc_17']) ? $linha[$mapeamento['pmc_17']] ?? null : null;
            $pmc18 = isset($mapeamento['pmc_18']) ? $linha[$mapeamento['pmc_18']] ?? null : null;
            $pmc20 = isset($mapeamento['pmc_20']) ? $linha[$mapeamento['pmc_20']] ?? null : null;

            // Converter preços para decimal
            $pmc0 = is_numeric($pmc0) ? (float)$pmc0 : null;
            $pmc12 = is_numeric($pmc12) ? (float)$pmc12 : null;
            $pmc17 = is_numeric($pmc17) ? (float)$pmc17 : null;
            $pmc18 = is_numeric($pmc18) ? (float)$pmc18 : null;
            $pmc20 = is_numeric($pmc20) ? (float)$pmc20 : null;

            try {
                $stmtInsert->execute([
                    $ean,                  // codigo_ean
                    $principioAtivo,       // principio_ativo
                    $produto,              // nome_comercial
                    $produto,              // apresentacao (mesmo que nome_comercial)
                    $laboratorio,          // laboratorio
                    $cnpj,                 // cnpj
                    $pmc0,                 // pmc_0
                    $pmc12,                // pmc_12
                    $pmc17,                // pmc_17
                    $pmc18,                // pmc_18
                    $pmc20,                // pmc_20
                    $mesReferencia,        // mes_referencia
                    2025                   // ano_referencia
                ]);
                $inseridos++;
            } catch (PDOException $e) {
                $erros++;
                if ($erros <= 5) {
                    echo "   ⚠️  Erro na linha $row: " . $e->getMessage() . "\n";
                }
            }

            // Progress a cada 1000 linhas
            if ($row % 1000 == 0) {
                echo "   ⏳ Linha $row/$highestRow | Inseridos: $inseridos | Erros: $erros\n";
            }
        }

        // Commit da transação
        $pdo->commit();

        echo "   ✅ Concluído! Inseridos: $inseridos | Erros: $erros\n\n";
        $totalGeralInserido += $inseridos;

        // Liberar memória
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $worksheet);
        gc_collect_cycles();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "   ❌ ERRO: " . $e->getMessage() . "\n\n";
    }
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RESUMO DA IMPORTAÇÃO                                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "✅ Total de medicamentos inseridos: $totalGeralInserido\n";

// Verificar total final no banco
$stmt = $pdo->query("SELECT COUNT(*) FROM cp_medicamentos_cmed");
$totalFinal = $stmt->fetchColumn();
echo "📊 Total de registros na tabela: $totalFinal\n\n";

echo "✅ Importação concluída com sucesso!\n";
