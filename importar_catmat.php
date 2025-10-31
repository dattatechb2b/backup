#!/usr/bin/env php
<?php
/**
 * Script para importar dados do CATMAT no banco de dados
 *
 * Arquivo: catmat_completo_2025-10-16_08-52-34.json (336.225 itens)
 * Tabela: catmat
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

ini_set('memory_limit', '1G');
ini_set('max_execution_time', 0);

$arquivo = __DIR__ . '/storage/app/private/catmat/catmat_completo_2025-10-16_08-52-34.json';
$log_file = __DIR__ . '/storage/logs/importacao_catmat.log';

function log_msg($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] {$msg}\n";
    echo $line;
    file_put_contents($log_file, $line, FILE_APPEND);
}

log_msg("========================================");
log_msg("INICIANDO IMPORTAÇÃO CATMAT");
log_msg("========================================");
log_msg("Arquivo: " . basename($arquivo));
log_msg("");

// Verificar se arquivo existe
if (!file_exists($arquivo)) {
    log_msg("❌ ERRO: Arquivo não encontrado!");
    exit(1);
}

$tamanho_mb = round(filesize($arquivo) / 1024 / 1024, 2);
log_msg("📦 Tamanho do arquivo: {$tamanho_mb} MB");
log_msg("");

// Ler arquivo JSON
log_msg("📖 Lendo arquivo JSON...");
$conteudo = file_get_contents($arquivo);
$dados = json_decode($conteudo, true);

if (!$dados || !isset($dados['itens'])) {
    log_msg("❌ ERRO: Falha ao decodificar JSON!");
    exit(1);
}

$total_itens = count($dados['itens']);
log_msg("✅ Total de itens no JSON: " . number_format($total_itens, 0, ',', '.'));
log_msg("");

// Verificar se tabela está vazia
$count_atual = DB::table('catmat')->count();
if ($count_atual > 0) {
    log_msg("⚠️  Tabela já possui {$count_atual} registros!");
    log_msg("⚠️  Limpando tabela antes de importar...");
    DB::table('catmat')->truncate();
    log_msg("✅ Tabela limpa!");
    log_msg("");
}

// Importar em lotes
log_msg("📥 Iniciando importação em lotes...");
log_msg("");

$batch_size = 500; // 500 itens por lote
$total_lotes = ceil($total_itens / $batch_size);
$importados = 0;
$erros = 0;
$inicio = microtime(true);

for ($i = 0; $i < $total_itens; $i += $batch_size) {
    $lote_num = floor($i / $batch_size) + 1;
    $batch = array_slice($dados['itens'], $i, $batch_size);

    try {
        $dados_inserir = [];

        foreach ($batch as $item) {
            $dados_inserir[] = [
                'codigo' => $item['codigoItem'] ?? null,
                'titulo' => $item['descricaoItem'] ?? '',
                'tipo' => 'CATMAT',
                'caminho_hierarquia' => ($item['nomeGrupo'] ?? '') . ' > ' . ($item['nomeClasse'] ?? ''),
                'unidade_padrao' => null, // CATMAT não tem unidade padrão
                'fonte' => 'JSON_OFICIAL',
                'ativo' => $item['statusItem'] ?? true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('catmat')->insert($dados_inserir);

        $importados += count($dados_inserir);

        // Log a cada 50 lotes
        if ($lote_num % 50 === 0 || $lote_num === $total_lotes) {
            $porcentagem = round(($importados / $total_itens) * 100, 1);
            $tempo_decorrido = round(microtime(true) - $inicio, 1);
            $itens_por_seg = round($importados / $tempo_decorrido, 0);

            log_msg("✅ Lote {$lote_num}/{$total_lotes} - {$porcentagem}% - {$importados} itens - {$itens_por_seg} itens/s");
        }

    } catch (\Exception $e) {
        log_msg("❌ ERRO no lote {$lote_num}: " . $e->getMessage());
        $erros++;
    }
}

$tempo_total = round(microtime(true) - $inicio, 2);
$minutos = floor($tempo_total / 60);
$segundos = $tempo_total % 60;

log_msg("");
log_msg("========================================");
log_msg("IMPORTAÇÃO CONCLUÍDA");
log_msg("========================================");
log_msg("✅ Itens importados: " . number_format($importados, 0, ',', '.'));
log_msg("❌ Erros: {$erros}");
log_msg("⏱️  Tempo total: {$minutos}m " . round($segundos, 1) . "s");
log_msg("");

// Verificar importação
$count_final = DB::table('catmat')->count();
log_msg("📊 Registros na tabela: " . number_format($count_final, 0, ',', '.'));

if ($count_final === $total_itens) {
    log_msg("✅ SUCESSO TOTAL! Todos os itens foram importados!");
} else {
    $diferenca = $total_itens - $count_final;
    log_msg("⚠️  ATENÇÃO: Faltaram {$diferenca} itens!");
}

log_msg("");
log_msg("========================================");
