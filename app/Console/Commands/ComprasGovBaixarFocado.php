<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ComprasGovBaixarFocado extends Command
{
    protected $signature = 'comprasgov:baixar-focado
                            {--limite-gb=3 : Limite de tamanho em GB}
                            {--workers=10 : Número de workers paralelos}';

    protected $description = '🎯 Download focado: Baixa preços APENAS dos códigos marcados como tendo preços (após scout)';

    public function handle()
    {
        $limiteGB = (int) $this->option('limite-gb');
        $workers = (int) $this->option('workers');

        $this->info("🎯 DOWNLOAD FOCADO - Compras.gov");
        $this->info("   Limite: {$limiteGB} GB");
        $this->info("   Workers: {$workers} processos simultâneos");
        $this->newLine();

        // Buscar APENAS códigos que TÊM preços (identificados pelo scout)
        $codigos = DB::connection('pgsql_main')
            ->table('cp_catmat')
            ->select('codigo')
            ->where('ativo', true)
            ->where('tem_preco_comprasgov', true) // ✨ APENAS os que têm preços!
            ->pluck('codigo')
            ->toArray();

        $total = count($codigos);

        if ($total === 0) {
            $this->warn("⚠️  Nenhum código identificado com preços!");
            $this->info("💡 Execute primeiro: php artisan comprasgov:scout");
            return 1;
        }

        $this->info("📊 {$total} códigos CATMAT COM PREÇOS identificados");
        $this->info("🎯 Baixando APENAS os códigos relevantes!");
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
            $arquivo = "{$tempDir}/focado_lote_{$index}.txt";
            file_put_contents($arquivo, implode("\n", $lote));
            $arquivosCodigos[] = $arquivo;
        }

        $this->info("⏳ Iniciando download focado...");
        $this->newLine();

        // Criar barra de progresso
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        // Executar workers em paralelo (reutilizando o worker existente)
        $processos = [];
        foreach ($arquivosCodigos as $index => $arquivo) {
            $logFile = "{$tempDir}/focado_worker_{$index}.log";
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
        $totalInicial = DB::connection('pgsql_main')
            ->table('cp_precos_comprasgov')
            ->count();

        $ultimoTotal = $totalInicial;

        while (true) {
            // Verificar total de registros no banco
            $totalAtual = DB::connection('pgsql_main')
                ->table('cp_precos_comprasgov')
                ->count();

            $novos = $totalAtual - $totalInicial;
            $progressBar->setProgress(min($novos, $total));

            // Verificar tamanho da tabela
            $tamanhoMB = DB::connection('pgsql_main')
                ->selectOne("SELECT pg_total_relation_size('cp_precos_comprasgov') / (1024*1024) as size_mb")
                ->size_mb;

            // Se atingiu o limite de tamanho, parar
            if ($tamanhoMB >= ($limiteGB * 1024)) {
                $this->newLine();
                $this->warn("⚠️  Limite de {$limiteGB} GB atingido!");
                break;
            }

            // Se não houve progresso por muito tempo, considerar concluído
            if ($totalAtual === $ultimoTotal) {
                // Aguardar 30 segundos sem progresso para considerar finalizado
                sleep(30);
                $totalAposEspera = DB::connection('pgsql_main')
                    ->table('cp_precos_comprasgov')
                    ->count();

                if ($totalAposEspera === $totalAtual) {
                    break; // Sem progresso, workers provavelmente finalizaram
                }
            }

            $ultimoTotal = $totalAtual;
            sleep(5);
        }

        $progressBar->finish();
        $this->newLine(2);

        // Estatísticas finais
        $totalFinal = DB::connection('pgsql_main')
            ->table('cp_precos_comprasgov')
            ->count();

        $codigosUnicos = DB::connection('pgsql_main')
            ->table('cp_precos_comprasgov')
            ->distinct('catmat_codigo')
            ->count('catmat_codigo');

        $tamanhoFinal = DB::connection('pgsql_main')
            ->selectOne("SELECT pg_size_pretty(pg_total_relation_size('cp_precos_comprasgov')) as size")
            ->size;

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📊 DOWNLOAD FOCADO CONCLUÍDO!");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("   📦 Total de preços: {$totalFinal}");
        $this->info("   🎯 Códigos únicos: {$codigosUnicos}");
        $this->info("   💾 Tamanho: {$tamanhoFinal}");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->newLine();

        return 0;
    }
}
