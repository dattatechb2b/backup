<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ComprasGovScout extends Command
{
    protected $signature = 'comprasgov:scout
                            {--workers=20 : Número de workers paralelos}
                            {--timeout=5 : Timeout por requisição em segundos}';

    protected $description = '🔍 SCOUT RÁPIDO: Identifica quais códigos CATMAT têm preços no Compras.gov (sem baixar dados)';

    public function handle()
    {
        $workers = (int) $this->option('workers');
        $timeout = (int) $this->option('timeout');

        $this->info("🔍 SCOUT COMPRAS.GOV - Identificação Rápida");
        $this->info("   Workers: {$workers} processos simultâneos");
        $this->info("   Timeout: {$timeout}s por código");
        $this->newLine();

        // Buscar todos os códigos CATMAT que ainda não foram verificados
        $codigos = DB::connection('pgsql_main')
            ->table('cp_catmat')
            ->select('codigo')
            ->where('ativo', true)
            ->whereNull('tem_preco_comprasgov') // Apenas os não verificados
            ->pluck('codigo')
            ->toArray();

        $total = count($codigos);

        if ($total === 0) {
            $this->info("✅ Todos os códigos já foram verificados!");
            return 0;
        }

        $this->info("📊 {$total} códigos CATMAT para verificar");
        $this->newLine();

        // Dividir códigos em lotes
        $loteSize = ceil($total / $workers);
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
            $arquivo = "{$tempDir}/scout_lote_{$index}.txt";
            file_put_contents($arquivo, implode("\n", $lote));
            $arquivosCodigos[] = $arquivo;
        }

        $this->info("⏳ Iniciando SCOUT paralelo...");
        $this->newLine();

        // Criar barra de progresso
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        // Executar workers em paralelo
        $processos = [];
        foreach ($arquivosCodigos as $index => $arquivo) {
            $logFile = "{$tempDir}/scout_worker_{$index}.log";
            $comando = sprintf(
                'php %s comprasgov:scout-worker --arquivo=%s --timeout=%d > %s 2>&1 &',
                base_path('artisan'),
                $arquivo,
                $timeout,
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
        $ultimoVerificado = 0;

        while (true) {
            // Verificar quantos códigos já foram verificados
            $verificados = DB::connection('pgsql_main')
                ->table('cp_catmat')
                ->whereNotNull('tem_preco_comprasgov')
                ->count();

            $progressBar->setProgress($verificados);

            // Se processou tudo, encerrar
            if ($verificados >= $total) {
                break;
            }

            sleep(2);
        }

        $progressBar->finish();
        $this->newLine(2);

        // Estatísticas finais
        $comPrecos = DB::connection('pgsql_main')
            ->table('cp_catmat')
            ->where('tem_preco_comprasgov', true)
            ->count();

        $semPrecos = DB::connection('pgsql_main')
            ->table('cp_catmat')
            ->where('tem_preco_comprasgov', false)
            ->count();

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📊 RESULTADO DO SCOUT");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("   ✅ COM preços: {$comPrecos} códigos");
        $this->info("   ❌ SEM preços: {$semPrecos} códigos");
        $this->info("   📈 Taxa de sucesso: " . round(($comPrecos / $total) * 100, 2) . "%");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->newLine();
        $this->info("🎯 Próximo passo: Execute o download focado:");
        $this->info("   php artisan comprasgov:baixar-focado");
        $this->newLine();

        return 0;
    }
}
