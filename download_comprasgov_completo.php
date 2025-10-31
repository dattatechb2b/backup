#!/usr/bin/env php
<?php
/**
 * Script para baixar TODOS os itens do catálogo Compras.gov
 *
 * API: https://dadosabertos.compras.gov.br/modulo-material/2_consultarGrupoCatalogo
 * Total estimado: 331.000 itens (~300 MB em JSON)
 *
 * APENAS BAIXA OS ARQUIVOS - NÃO FAZ ALTERAÇÕES NO BANCO
 */

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 0);

$BASE_URL = 'https://dadosabertos.compras.gov.br/modulo-material/2_consultarGrupoCatalogo';
$STORAGE_PATH = __DIR__ . '/storage/app/private/comprasgov_download';
$LOG_FILE = __DIR__ . '/storage/logs/download_comprasgov.log';

// Criar diretórios se não existirem
if (!is_dir($STORAGE_PATH)) {
    mkdir($STORAGE_PATH, 0775, true);
}
if (!is_dir(dirname($LOG_FILE))) {
    mkdir(dirname($LOG_FILE), 0775, true);
}

function log_message($message) {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] {$message}\n";
    echo $line;
    file_put_contents($LOG_FILE, $line, FILE_APPEND);
}

function make_request($url, $params = []) {
    $query = http_build_query($params);
    $full_url = $url . '?' . $query;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Sistema Cesta de Precos)'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Erro cURL: {$error}");
    }

    if ($http_code !== 200) {
        throw new Exception("HTTP {$http_code}");
    }

    return json_decode($response, true);
}

log_message("========================================");
log_message("INICIANDO DOWNLOAD COMPLETO - COMPRAS.GOV");
log_message("========================================");
log_message("URL Base: {$BASE_URL}");
log_message("Diretório: {$STORAGE_PATH}");
log_message("");

// ========================================
// ETAPA 1: Descobrir quantas páginas existem
// ========================================
log_message("ETAPA 1: Descobrindo total de páginas...");

try {
    $primeira_pagina = make_request($BASE_URL, [
        'pagina' => 1,
        'tamanhoPagina' => 10
    ]);

    $total_registros = $primeira_pagina['totalElementos'] ?? 0;
    $tamanho_pagina = 500; // Máximo permitido pela API
    $total_paginas = ceil($total_registros / $tamanho_pagina);

    log_message("✅ Total de registros: " . number_format($total_registros, 0, ',', '.'));
    log_message("✅ Tamanho da página: {$tamanho_pagina}");
    log_message("✅ Total de páginas: {$total_paginas}");
    log_message("");

} catch (Exception $e) {
    log_message("❌ ERRO ao consultar primeira página: " . $e->getMessage());
    exit(1);
}

// ========================================
// ETAPA 2: Baixar TODAS as páginas
// ========================================
log_message("ETAPA 2: Baixando todas as páginas...");
log_message("");

$total_itens_baixados = 0;
$erros = 0;
$inicio = time();

for ($pagina = 1; $pagina <= $total_paginas; $pagina++) {
    try {
        log_message("[{$pagina}/{$total_paginas}] Baixando página {$pagina}...");

        $dados = make_request($BASE_URL, [
            'pagina' => $pagina,
            'tamanhoPagina' => $tamanho_pagina
        ]);

        $itens = $dados['itens'] ?? [];
        $qtd_itens = count($itens);

        if ($qtd_itens === 0) {
            log_message("⚠️  Página {$pagina} vazia - Finalizando download");
            break;
        }

        // Salvar página em arquivo JSON
        $arquivo = sprintf('%s/pagina_%05d.json', $STORAGE_PATH, $pagina);
        file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $total_itens_baixados += $qtd_itens;

        log_message("✅ Página {$pagina}: {$qtd_itens} itens salvos ({$total_itens_baixados} total)");

        // Delay de 200ms para não sobrecarregar a API
        usleep(200000);

        // A cada 50 páginas, mostrar progresso
        if ($pagina % 50 === 0) {
            $tempo_decorrido = time() - $inicio;
            $minutos = floor($tempo_decorrido / 60);
            $segundos = $tempo_decorrido % 60;
            $porcentagem = round(($pagina / $total_paginas) * 100, 1);

            log_message("");
            log_message("📊 PROGRESSO: {$porcentagem}%");
            log_message("⏱️  Tempo decorrido: {$minutos}m {$segundos}s");
            log_message("📦 Itens baixados: " . number_format($total_itens_baixados, 0, ',', '.'));
            log_message("");
        }

    } catch (Exception $e) {
        log_message("❌ ERRO na página {$pagina}: " . $e->getMessage());
        $erros++;

        // Se muitos erros seguidos, pausar
        if ($erros >= 5) {
            log_message("⚠️  Muitos erros seguidos - Pausando 10 segundos...");
            sleep(10);
            $erros = 0;
        }
    }
}

// ========================================
// ETAPA 3: Criar arquivo consolidado
// ========================================
log_message("");
log_message("ETAPA 3: Consolidando todos os dados em um único arquivo...");

$todos_itens = [];
$arquivos = glob($STORAGE_PATH . '/pagina_*.json');
sort($arquivos);

foreach ($arquivos as $arquivo) {
    $dados = json_decode(file_get_contents($arquivo), true);
    $itens = $dados['itens'] ?? [];
    $todos_itens = array_merge($todos_itens, $itens);
}

$arquivo_consolidado = $STORAGE_PATH . '/comprasgov_completo_' . date('Y-m-d_H-i-s') . '.json';
file_put_contents($arquivo_consolidado, json_encode([
    'data_download' => date('Y-m-d H:i:s'),
    'total_itens' => count($todos_itens),
    'itens' => $todos_itens
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$tamanho_mb = round(filesize($arquivo_consolidado) / 1024 / 1024, 2);

log_message("✅ Arquivo consolidado criado: " . basename($arquivo_consolidado));
log_message("✅ Tamanho: {$tamanho_mb} MB");

// ========================================
// RESUMO FINAL
// ========================================
$tempo_total = time() - $inicio;
$minutos = floor($tempo_total / 60);
$segundos = $tempo_total % 60;

log_message("");
log_message("========================================");
log_message("DOWNLOAD COMPLETO FINALIZADO");
log_message("========================================");
log_message("✅ Total de itens baixados: " . number_format(count($todos_itens), 0, ',', '.'));
log_message("✅ Tempo total: {$minutos}m {$segundos}s");
log_message("✅ Arquivos salvos em: {$STORAGE_PATH}");
log_message("✅ Arquivo consolidado: " . basename($arquivo_consolidado));
log_message("✅ Tamanho total: {$tamanho_mb} MB");
log_message("");

log_message("📋 PRÓXIMOS PASSOS:");
log_message("1. Verificar arquivo consolidado");
log_message("2. Aguardar instruções para importação no banco");
log_message("3. NÃO FAZER COMMIT até receber instrução");
log_message("");
log_message("========================================");
