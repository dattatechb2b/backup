<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Process;

class BaixarPrecosComprasGovParalelo extends Command
{
    protected $signature = 'comprasgov:baixar-paralelo
                            {--limite-gb=3 : Limite de tamanho em GB}
                            {--workers=10 : Número de workers paralelos}
                            {--codigos=1000 : Número de códigos a processar}';

    protected $description = 'Baixa preços do Compras.gov com processamento paralelo (RÁPIDO!)';

    public function handle()
    {
        $limiteGB = (int) $this->option('limite-gb');
        $workers = (int) $this->option('workers');
        $totalCodigos = (int) $this->option('codigos');

        $this->info("🚀 DOWNLOAD PARALELO - Compras.gov");
        $this->info("   Limite: {$limiteGB} GB");
        $this->info("   Workers: {$workers} processos simultâneos");
        $this->info("   Códigos: {$totalCodigos}");
        $this->newLine();

        // Buscar TODOS os códigos CATMAT disponíveis
        $codigos = DB::connection('pgsql_main')
            ->table('cp_catmat')
            ->select('codigo')
            ->where('ativo', true)
            ->limit($totalCodigos)
            ->pluck('codigo')
            ->toArray();

        $this->info("📊 {$totalCodigos} códigos CATMAT selecionados");

        // Dividir códigos em lotes
        $loteSize = ceil(count($codigos) / $workers);
        $lotes = array_chunk($codigos, $loteSize);

        $this->info("📦 Divididos em " . count($lotes) . " lotes de ~{$loteSize} códigos cada");
        $this->newLine();

        // Exportar lista de códigos para arquivo temporário
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $arquivosCodigos = [];
        foreach ($lotes as $index => $lote) {
            $arquivo = "{$tempDir}/lote_{$index}.txt";
            file_put_contents($arquivo, implode("\n", $lote));
            $arquivosCodigos[] = $arquivo;
        }

        $this->info("⏳ Iniciando processamento paralelo...");
        $this->newLine();

        // Criar barra de progresso
        $progressBar = $this->output->createProgressBar($totalCodigos);
        $progressBar->start();

        // Executar workers em paralelo usando processos em background
        $processos = [];
        foreach ($arquivosCodigos as $index => $arquivo) {
            $logFile = "{$tempDir}/worker_{$index}.log";
            $comando = sprintf(
                'php %s comprasgov:worker --arquivo=%s --limite-gb=%d > %s 2>&1 &',
                base_path('artisan'),
                $arquivo,
                $limiteGB,
                $logFile
            );

            exec($comando, $output, $returnCode);
            $processos[] = [
                'index' => $index,
                'arquivo' => $arquivo,
                'log' => $logFile
            ];
        }

        $this->info("\n✅ {$workers} workers iniciados!");
        $this->newLine();

        // Monitorar progresso
        $totalProcessado = 0;
        $ultimoTotal = 0;

        while (true) {
            // Verificar total de registros no banco
            $total = DB::connection('pgsql_main')
                ->table('cp_precos_comprasgov')
                ->count();

            if ($total > $ultimoTotal) {
                $novos = $total - $ultimoTotal;
                $progressBar->advance(min($novos, $totalCodigos - $totalProcessado));
                $ultimoTotal = $total;
                $totalProcessado += $novos;
            }

            // Verificar se todos os workers terminaram
            $todosTerminaram = true;
            foreach ($processos as $processo) {
                if (file_exists($processo['arquivo'])) {
                    $todosTerminaram = false;
                    break;
                }
            }

            if ($todosTerminaram || $totalProcessado >= $totalCodigos) {
                break;
            }

            sleep(2);
        }

        $progressBar->finish();
        $this->newLine(2);

        // Estatísticas finais
        $total = DB::connection('pgsql_main')->table('cp_precos_comprasgov')->count();
        $tamanho = DB::connection('pgsql_main')
            ->select("SELECT pg_size_pretty(pg_total_relation_size('cp_precos_comprasgov')) as size")[0]->size ?? 'N/A';

        $this->info("═══════════════════════════════════════");
        $this->info("✅ DOWNLOAD PARALELO CONCLUÍDO!");
        $this->info("═══════════════════════════════════════");
        $this->info("📊 Total preços: " . number_format($total, 0, ',', '.'));
        $this->info("📦 Tamanho: {$tamanho}");
        $this->newLine();

        // Limpar arquivos temporários
        foreach ($processos as $processo) {
            @unlink($processo['arquivo']);
            @unlink($processo['log']);
        }

        return Command::SUCCESS;
    }
}
