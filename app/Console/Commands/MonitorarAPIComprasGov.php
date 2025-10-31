<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

/**
 * 🤖 MONITORAMENTO AUTOMÁTICO DA API COMPRAS.GOV
 *
 * FUNCIONALIDADE:
 * - Verifica periodicamente se a API Compras.gov voltou online
 * - Quando detectar que voltou, executa download paralelo automaticamente
 * - Registra tudo em logs detalhados
 *
 * USO:
 * php artisan comprasgov:monitorar --auto-download
 *
 * SEGURANÇA:
 * - Timeout de 10s por tentativa
 * - Limite máximo de tentativas (padrão: 100)
 * - Intervalo configurável (padrão: 15 minutos)
 *
 * CRIADO: 29/10/2025
 * AUTOR: Claude + Cláudio
 */
class MonitorarAPIComprasGov extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comprasgov:monitorar
                            {--intervalo=15 : Intervalo entre verificações em minutos}
                            {--max-tentativas=100 : Número máximo de tentativas}
                            {--auto-download : Executar download automaticamente quando API voltar}
                            {--testar-agora : Testar uma única vez sem loop}
                            {--workers=20 : Número de workers paralelos para download (padrão: 20 = RÁPIDO)}
                            {--codigos=5000 : Quantidade de códigos CATMAT para baixar (padrão: 5000 = RÁPIDO)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🤖 Monitora API Compras.gov e executa download automático quando voltar online';

    /**
     * Códigos CATMAT de teste (produtos comuns)
     */
    private const CODIGOS_TESTE = [
        '243756', // COMPUTADOR COMPLETO
        '399016', // IMPRESSORA LASER
        '52850',  // PAPEL A4
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $intervalo = (int) $this->option('intervalo');
        $maxTentativas = (int) $this->option('max-tentativas');
        $autoDownload = $this->option('auto-download');
        $testarAgora = $this->option('testar-agora');

        // Validações
        if ($intervalo < 1 || $intervalo > 120) {
            $this->error('❌ Intervalo deve estar entre 1 e 120 minutos');
            return 1;
        }

        if ($maxTentativas < 1 || $maxTentativas > 1000) {
            $this->error('❌ Max tentativas deve estar entre 1 e 1000');
            return 1;
        }

        // Banner
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║  🤖 MONITORAMENTO AUTOMÁTICO - API COMPRAS.GOV           ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Configurações
        $workers = (int) $this->option('workers');
        $codigos = (int) $this->option('codigos');

        $this->info('⚙️  CONFIGURAÇÕES:');
        $this->line("   • Intervalo: {$intervalo} minutos");
        $this->line("   • Máx tentativas: {$maxTentativas}");
        $this->line("   • Auto-download: " . ($autoDownload ? '✅ SIM' : '❌ NÃO'));
        if ($autoDownload) {
            $this->line("   • Workers paralelos: {$workers} (⚡ MODO RÁPIDO)");
            $this->line("   • Códigos CATMAT: {$codigos} (⚡ DOWNLOAD RÁPIDO)");
        }
        $this->line("   • Modo: " . ($testarAgora ? '🔍 Teste único' : '🔄 Loop contínuo'));
        $this->info('');

        // Log inicial
        Log::channel('stack')->info('🤖 MONITORAMENTO INICIADO', [
            'intervalo' => $intervalo,
            'max_tentativas' => $maxTentativas,
            'auto_download' => $autoDownload,
            'data_inicio' => now()->format('d/m/Y H:i:s')
        ]);

        // Se é apenas teste, executa uma vez e sai
        if ($testarAgora) {
            $online = $this->testarAPI();
            $this->info('');
            if ($online) {
                $this->info('✅ API ONLINE - Disponível para download');
                return 0;
            } else {
                $this->error('❌ API OFFLINE - Ainda indisponível');
                return 1;
            }
        }

        // Loop de monitoramento
        $tentativa = 1;

        while ($tentativa <= $maxTentativas) {
            $this->info("╔════════════════════════════════════════════════════════════╗");
            $this->info("║  🔍 TENTATIVA {$tentativa}/{$maxTentativas} - " . now()->format('d/m/Y H:i:s') . "  ║");
            $this->info("╚════════════════════════════════════════════════════════════╝");
            $this->info('');

            // Testar API
            $online = $this->testarAPI();

            if ($online) {
                // 🎉 API VOLTOU ONLINE!
                $this->info('');
                $this->info('╔════════════════════════════════════════════════════════════╗');
                $this->info('║  🎉 API COMPRAS.GOV VOLTOU ONLINE!                       ║');
                $this->info('╚════════════════════════════════════════════════════════════╝');
                $this->info('');

                Log::channel('stack')->info('🎉 API COMPRAS.GOV VOLTOU ONLINE!', [
                    'tentativa' => $tentativa,
                    'data_deteccao' => now()->format('d/m/Y H:i:s')
                ]);

                // Executar download se solicitado
                if ($autoDownload) {
                    $this->info('🚀 Iniciando download automático dos dados...');
                    $this->info('');

                    $sucesso = $this->executarDownload();

                    if ($sucesso) {
                        $this->info('');
                        $this->info('╔════════════════════════════════════════════════════════════╗');
                        $this->info('║  ✅ DOWNLOAD CONCLUÍDO COM SUCESSO!                      ║');
                        $this->info('╚════════════════════════════════════════════════════════════╝');
                        $this->info('');
                        return 0; // Sucesso!
                    } else {
                        $this->error('');
                        $this->error('╔════════════════════════════════════════════════════════════╗');
                        $this->error('║  ⚠️  DOWNLOAD FALHOU - Verifique os logs                 ║');
                        $this->error('╚════════════════════════════════════════════════════════════╝');
                        $this->error('');
                        return 1; // Erro
                    }
                } else {
                    $this->info('ℹ️  Auto-download não habilitado (use --auto-download)');
                    $this->info('   Execute manualmente: php artisan comprasgov:baixar-paralelo');
                    $this->info('');
                    return 0; // Sucesso (API voltou)
                }
            } else {
                // API ainda offline
                $this->warn('⏳ API ainda offline - Próxima verificação em ' . $intervalo . ' minutos...');
                $this->info('');

                Log::channel('stack')->info('⏳ API ainda offline', [
                    'tentativa' => $tentativa,
                    'proximo_teste' => now()->addMinutes($intervalo)->format('d/m/Y H:i:s')
                ]);

                // Aguardar intervalo (se não for a última tentativa)
                if ($tentativa < $maxTentativas) {
                    $this->aguardarComContador($intervalo * 60);
                }
            }

            $tentativa++;
        }

        // Atingiu limite de tentativas
        $this->error('');
        $this->error('╔════════════════════════════════════════════════════════════╗');
        $this->error('║  ⚠️  LIMITE DE TENTATIVAS ATINGIDO                       ║');
        $this->error('╚════════════════════════════════════════════════════════════╝');
        $this->error('');
        $this->error("   API ainda offline após {$maxTentativas} tentativas");
        $this->error('   Execute novamente quando desejar continuar monitorando');
        $this->info('');

        Log::channel('stack')->warning('⚠️ Limite de tentativas atingido', [
            'total_tentativas' => $maxTentativas,
            'data_fim' => now()->format('d/m/Y H:i:s')
        ]);

        return 1;
    }

    /**
     * Testar se a API Compras.gov está online
     *
     * @return bool
     */
    private function testarAPI(): bool
    {
        $this->line('🔍 Testando API Compras.gov...');

        try {
            // Testar com múltiplos códigos CATMAT (mais robusto)
            $sucessos = 0;
            $total = count(self::CODIGOS_TESTE);

            foreach (self::CODIGOS_TESTE as $codigo) {
                try {
                    $response = Http::timeout(10)
                        ->withHeaders([
                            'Accept' => '*/*',
                            'User-Agent' => 'DattaTech-CestaPrecos-Monitor/1.0'
                        ])
                        ->get('https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial', [
                            'codigoItemCatalogo' => $codigo,
                            'pagina' => 1,
                            'tamanhoPagina' => 5
                        ]);

                    if ($response->successful() && $response->status() === 200) {
                        $sucessos++;
                        $this->line("   ✅ CATMAT {$codigo}: OK");
                    } else {
                        $this->line("   ❌ CATMAT {$codigo}: HTTP {$response->status()}");
                    }

                    // Delay entre requests
                    usleep(200000); // 0.2s

                } catch (\Exception $e) {
                    $this->line("   ❌ CATMAT {$codigo}: " . $e->getMessage());
                }
            }

            $this->info('');
            $this->line("   📊 Resultado: {$sucessos}/{$total} testes bem-sucedidos");

            // Considerar online se pelo menos 2 de 3 testes passarem
            $online = $sucessos >= 2;

            if ($online) {
                $this->info('   ✅ STATUS: ONLINE');
            } else {
                $this->warn('   ❌ STATUS: OFFLINE');
            }

            return $online;

        } catch (\Exception $e) {
            $this->error('   ❌ ERRO: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Executar download paralelo dos dados
     *
     * @return bool
     */
    private function executarDownload(): bool
    {
        try {
            $workers = (int) $this->option('workers');
            $codigos = (int) $this->option('codigos');

            $this->line("📦 Executando: php artisan comprasgov:baixar-paralelo --workers={$workers} --codigos={$codigos}");
            $this->info('');
            $this->info("⚡ MODO RÁPIDO:");
            $this->line("   • Workers paralelos: {$workers}");
            $this->line("   • Códigos CATMAT: {$codigos}");
            $this->line("   • Tempo estimado: 15-30 minutos");
            $this->info('');

            // Executar comando com parâmetros otimizados
            $exitCode = Artisan::call('comprasgov:baixar-paralelo', [
                '--workers' => $workers,
                '--codigos' => $codigos,
                '--limite-gb' => 3,
            ], $this->getOutput());

            // Verificar resultado
            if ($exitCode === 0) {
                Log::channel('stack')->info('✅ Download paralelo concluído com sucesso', [
                    'exit_code' => $exitCode,
                    'data_conclusao' => now()->format('d/m/Y H:i:s')
                ]);
                return true;
            } else {
                Log::channel('stack')->error('❌ Download paralelo falhou', [
                    'exit_code' => $exitCode,
                    'data_falha' => now()->format('d/m/Y H:i:s')
                ]);
                return false;
            }

        } catch (\Exception $e) {
            $this->error('❌ Erro ao executar download: ' . $e->getMessage());

            Log::channel('stack')->error('❌ Erro ao executar download', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Aguardar com contador regressivo
     *
     * @param int $segundos
     * @return void
     */
    private function aguardarComContador(int $segundos): void
    {
        $fim = now()->addSeconds($segundos);

        while (now()->lt($fim)) {
            $restante = now()->diffInSeconds($fim);

            // Formatar tempo restante
            $horas = floor($restante / 3600);
            $minutos = floor(($restante % 3600) / 60);
            $segs = $restante % 60;

            $tempo = sprintf('%02d:%02d:%02d', $horas, $minutos, $segs);

            // Mostrar contador (sobrescreve a linha)
            echo "\r   ⏰ Aguardando: {$tempo} | Próximo teste: " . $fim->format('H:i:s') . "   ";

            sleep(1);

            // Verificar se usuário pressionou Ctrl+C
            if (connection_aborted()) {
                $this->info('');
                $this->warn('⚠️  Monitoramento interrompido pelo usuário');
                exit(1);
            }
        }

        echo "\r" . str_repeat(' ', 100) . "\r"; // Limpar linha
    }
}
