#!/usr/bin/env php
<?php
/**
 * Script de Coleta Híbrida de Preços - Compras.gov
 *
 * ESTRATÉGIA:
 * - Coleta os 50.000 materiais MAIS USADOS (contador_ocorrencias DESC)
 * - Para cada material, consulta API Compras.gov
 * - Marca tem_preco_comprasgov = TRUE/FALSE
 * - Salva preços encontrados em cp_historico_precos
 *
 * TEMPO ESTIMADO: 2-3 horas (0.2s por material)
 *
 * USO:
 *   php coleta_precos_comprasgov_hibrida.php
 *
 * EXECUÇÃO EM BACKGROUND:
 *   nohup php coleta_precos_comprasgov_hibrida.php > storage/logs/coleta_hibrida.log 2>&1 &
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Configurações
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 0);
date_default_timezone_set('America/Sao_Paulo');

$LIMITE_MATERIAIS = 50000;  // Top 50 mil mais usados
$DELAY_ENTRE_REQUESTS = 200000; // 0.2 segundos em microsegundos
$LOG_FILE = __DIR__ . '/storage/logs/coleta_precos_hibrida.log';
$API_URL = 'https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial';

// Criar diretório de logs se não existir
if (!is_dir(dirname($LOG_FILE))) {
    mkdir(dirname($LOG_FILE), 0775, true);
}

function log_message($message, $nivel = 'INFO') {
    global $LOG_FILE;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] [{$nivel}] {$message}\n";
    echo $line;
    file_put_contents($LOG_FILE, $line, FILE_APPEND);

    // Também registra no log do Laravel
    if ($nivel === 'ERROR') {
        Log::error($message);
    } else {
        Log::info($message);
    }
}

log_message("╔══════════════════════════════════════════════════════════════════════════════╗");
log_message("║  🎯 COLETA HÍBRIDA DE PREÇOS - COMPRAS.GOV                                   ║");
log_message("╚══════════════════════════════════════════════════════════════════════════════╝");
log_message("");
log_message("📊 Configuração:");
log_message("   → Materiais a coletar: {$LIMITE_MATERIAIS} (mais usados)");
log_message("   → Delay entre requests: " . ($DELAY_ENTRE_REQUESTS/1000) . "ms");
log_message("   → Arquivo de log: {$LOG_FILE}");
log_message("");

// ============================================================================
// ETAPA 1: Buscar materiais mais usados
// ============================================================================
log_message("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
log_message("📋 ETAPA 1: Buscando {$LIMITE_MATERIAIS} materiais mais usados...");
log_message("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

try {
    $materiais = DB::connection('pgsql_main')
        ->table('catmat')
        ->where('ativo', true)
        ->whereNull('tem_preco_comprasgov')  // Apenas os não verificados ainda
        ->orderBy('contador_ocorrencias', 'desc')
        ->orderBy('id', 'asc')
        ->limit($LIMITE_MATERIAIS)
        ->select('id', 'codigo', 'titulo', 'contador_ocorrencias')
        ->get();

    $total_materiais = $materiais->count();

    log_message("✅ {$total_materiais} materiais carregados para processamento");
    log_message("");

    if ($total_materiais === 0) {
        log_message("⚠️  Nenhum material não verificado encontrado. Finalizando.");
        exit(0);
    }

} catch (Exception $e) {
    log_message("❌ ERRO ao buscar materiais: " . $e->getMessage(), 'ERROR');
    exit(1);
}

// ============================================================================
// ETAPA 2: Processar cada material
// ============================================================================
log_message("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
log_message("⚙️  ETAPA 2: Processando materiais (consultando API Compras.gov)...");
log_message("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
log_message("");

$inicio = time();
$materiais_com_preco = 0;
$materiais_sem_preco = 0;
$total_precos_salvos = 0;
$erros = 0;

foreach ($materiais as $index => $material) {
    $numero_atual = $index + 1;

    try {
        // Consultar API Compras.gov
        $response = Http::timeout(10)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'DattaTech-CestaPrecos/1.0'
            ])
            ->get($API_URL, [
                'codigoItemCatalogo' => $material->codigo,
                'pagina' => 1,
                'tamanhoPagina' => 100  // Pegar até 100 preços por material
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $precos = $data['resultado'] ?? [];
            $qtd_precos = count($precos);

            if ($qtd_precos > 0) {
                // Material TEM preços
                $materiais_com_preco++;

                // Marcar no banco
                DB::connection('pgsql_main')
                    ->table('catmat')
                    ->where('id', $material->id)
                    ->update(['tem_preco_comprasgov' => true]);

                // Salvar preços em cp_historico_precos
                foreach ($precos as $preco) {
                    try {
                        DB::connection('pgsql_main')->table('historico_precos')->insert([
                            'catmat' => $material->codigo,
                            'fonte' => 'COMPRAS.GOV',
                            'fonte_url' => null,
                            'preco_unitario' => $preco['precoUnitario'] ?? 0,
                            'badge' => null,
                            'data_coleta' => now(),
                            'tenant_id' => 0,  // Sistema (não pertence a nenhum tenant)
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $total_precos_salvos++;
                    } catch (Exception $e) {
                        // Ignora erros individuais de inserção (pode ser duplicado)
                    }
                }

                if ($materiais_com_preco <= 5 || $numero_atual % 100 == 0) {
                    log_message("  ✅ [{$numero_atual}/{$total_materiais}] CATMAT {$material->codigo} → {$qtd_precos} preços salvos");
                }

            } else {
                // Material NÃO tem preços
                $materiais_sem_preco++;

                // Marcar no banco
                DB::connection('pgsql_main')
                    ->table('catmat')
                    ->where('id', $material->id)
                    ->update(['tem_preco_comprasgov' => false]);
            }

        } else {
            // Erro na API - não marcar nada
            $erros++;
            if ($erros <= 10) {
                log_message("  ⚠️  [{$numero_atual}/{$total_materiais}] CATMAT {$material->codigo} → Erro HTTP {$response->status()}", 'WARN');
            }
        }

        // Delay entre requests
        usleep($DELAY_ENTRE_REQUESTS);

        // Log de progresso a cada 500 materiais
        if ($numero_atual % 500 == 0) {
            $tempo_decorrido = time() - $inicio;
            $minutos = floor($tempo_decorrido / 60);
            $segundos = $tempo_decorrido % 60;
            $porcentagem = round(($numero_atual / $total_materiais) * 100, 1);
            $tempo_restante_estimado = round((($total_materiais - $numero_atual) * ($tempo_decorrido / $numero_atual)) / 60);

            log_message("");
            log_message("  📊 PROGRESSO: {$porcentagem}% ({$numero_atual}/{$total_materiais})");
            log_message("  ⏱️  Tempo decorrido: {$minutos}m {$segundos}s");
            log_message("  ⏳ Tempo restante estimado: ~{$tempo_restante_estimado} minutos");
            log_message("  ✅ Com preços: {$materiais_com_preco} ({$total_precos_salvos} registros salvos)");
            log_message("  ❌ Sem preços: {$materiais_sem_preco}");
            log_message("  ⚠️  Erros: {$erros}");
            log_message("");
        }

    } catch (Exception $e) {
        $erros++;
        if ($erros <= 10) {
            log_message("  ❌ [{$numero_atual}/{$total_materiais}] CATMAT {$material->codigo} → Erro: " . $e->getMessage(), 'ERROR');
        }

        // Se muitos erros, pausar
        if ($erros % 10 == 0 && $erros > 0) {
            log_message("  ⚠️  Muitos erros ({$erros}) - Pausando 30 segundos...", 'WARN');
            sleep(30);
        }
    }
}

// ============================================================================
// RESUMO FINAL
// ============================================================================
$tempo_total = time() - $inicio;
$horas = floor($tempo_total / 3600);
$minutos = floor(($tempo_total % 3600) / 60);
$segundos = $tempo_total % 60;

log_message("");
log_message("╔══════════════════════════════════════════════════════════════════════════════╗");
log_message("║  ✅ COLETA FINALIZADA COM SUCESSO!                                           ║");
log_message("╚══════════════════════════════════════════════════════════════════════════════╝");
log_message("");
log_message("📊 ESTATÍSTICAS FINAIS:");
log_message("   → Materiais processados: " . number_format($total_materiais, 0, ',', '.'));
log_message("   → Materiais COM preços: " . number_format($materiais_com_preco, 0, ',', '.') . " (" . round(($materiais_com_preco/$total_materiais)*100, 1) . "%)");
log_message("   → Materiais SEM preços: " . number_format($materiais_sem_preco, 0, ',', '.') . " (" . round(($materiais_sem_preco/$total_materiais)*100, 1) . "%)");
log_message("   → Total de preços salvos: " . number_format($total_precos_salvos, 0, ',', '.'));
log_message("   → Erros: {$erros}");
log_message("");
log_message("⏱️  TEMPO TOTAL:");
log_message("   → {$horas}h {$minutos}m {$segundos}s");
log_message("");
log_message("📋 PRÓXIMOS PASSOS:");
log_message("   1. Sistema agora filtra automaticamente apenas materiais com preços");
log_message("   2. Materiais raros (restantes) serão coletados sob demanda");
log_message("   3. Reexecutar este script periodicamente (mensal) para atualizar flags");
log_message("");
log_message("✅ Log completo salvo em: {$LOG_FILE}");
log_message("");
