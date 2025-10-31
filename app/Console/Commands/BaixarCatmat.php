<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BaixarCatmat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catmat:baixar
                            {--paginas-inicial=1 : Página inicial para começar o download}
                            {--paginas-final= : Página final (padrão: todas as páginas)}
                            {--tamanho-pagina=500 : Itens por página (10-500)}
                            {--delay=500 : Delay em milissegundos entre requisições}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Baixar catálogo CATMAT completo da API Compras.gov (336.192 itens)';

    /**
     * URL base da API
     */
    private const API_URL = 'https://dadosabertos.compras.gov.br/modulo-material/4_consultarItemMaterial';

    /**
     * Total de itens esperados (conforme documentação)
     */
    private const TOTAL_ITENS_ESPERADOS = 336192;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando download do catálogo CATMAT...');
        $this->newLine();

        // Parâmetros
        $paginaInicial = (int) $this->option('paginas-inicial');
        $tamanhoPagina = (int) $this->option('tamanho-pagina');
        $delayMs = (int) $this->option('delay');

        // Validações
        if ($tamanhoPagina < 10 || $tamanhoPagina > 500) {
            $this->error('❌ Tamanho de página deve estar entre 10 e 500.');
            return Command::FAILURE;
        }

        // Descobrir total de páginas primeiro
        $this->info('🔍 Descobrindo total de páginas...');
        $primeiraRequisicao = $this->fazerRequisicao(1, $tamanhoPagina);

        if (!$primeiraRequisicao) {
            $this->error('❌ Erro ao buscar primeira página da API.');
            return Command::FAILURE;
        }

        $totalItens = $primeiraRequisicao['totalRegistros'] ?? 0;
        $totalPaginas = (int) ceil($totalItens / $tamanhoPagina);
        $paginaFinal = $this->option('paginas-final')
            ? (int) $this->option('paginas-final')
            : $totalPaginas;

        // Informações gerais
        $this->info("📊 Total de itens: " . number_format($totalItens, 0, ',', '.'));
        $this->info("📄 Total de páginas: " . number_format($totalPaginas, 0, ',', '.'));
        $this->info("📦 Itens por página: $tamanhoPagina");
        $this->info("⏱️  Delay entre requisições: {$delayMs}ms");
        $this->info("🎯 Baixando páginas: $paginaInicial até $paginaFinal");
        $this->newLine();

        // Estimativa de tempo
        $totalRequisicoes = $paginaFinal - $paginaInicial + 1;
        $tempoEstimadoSegundos = ($totalRequisicoes * ($delayMs / 1000)) + ($totalRequisicoes * 2); // 2s por requisição em média
        $tempoEstimadoMinutos = round($tempoEstimadoSegundos / 60, 1);

        $this->warn("⏰ Tempo estimado: ~{$tempoEstimadoMinutos} minutos");
        $this->newLine();

        // Confirmação do usuário
        if (!$this->confirm('Deseja continuar com o download?', true)) {
            $this->info('❌ Download cancelado pelo usuário.');
            return Command::FAILURE;
        }

        $this->newLine();

        // Inicializar arquivo
        $diretorio = 'catmat';
        $nomeArquivo = 'catmat_completo_' . now()->format('Y-m-d_H-i-s') . '.json';
        $caminhoCompleto = "$diretorio/$nomeArquivo";

        if (!Storage::exists($diretorio)) {
            Storage::makeDirectory($diretorio);
        }

        // Array para armazenar todos os itens
        $todosItens = [];
        $erros = [];
        $estatisticas = [
            'itens_baixados' => 0,
            'paginas_sucesso' => 0,
            'paginas_erro' => 0,
            'tempo_inicio' => now(),
        ];

        // Progress bar
        $progressBar = $this->output->createProgressBar($totalRequisicoes);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Itens: %message%');
        $progressBar->setMessage('0');
        $progressBar->start();

        // Loop de download
        for ($pagina = $paginaInicial; $pagina <= $paginaFinal; $pagina++) {
            try {
                $dados = $this->fazerRequisicao($pagina, $tamanhoPagina);

                if ($dados && isset($dados['resultado'])) {
                    $itensPagina = $dados['resultado'];
                    $todosItens = array_merge($todosItens, $itensPagina);

                    $estatisticas['itens_baixados'] += count($itensPagina);
                    $estatisticas['paginas_sucesso']++;

                    $progressBar->setMessage(number_format($estatisticas['itens_baixados'], 0, ',', '.'));
                    $progressBar->advance();

                    // Se página retornou vazia, provavelmente chegou ao fim
                    if (empty($itensPagina)) {
                        $this->newLine(2);
                        $this->warn("⚠️  Página $pagina retornou vazia. Finalizando download...");
                        break;
                    }
                } else {
                    $estatisticas['paginas_erro']++;
                    $erros[] = [
                        'pagina' => $pagina,
                        'erro' => 'Resposta inválida da API'
                    ];
                }

                // Delay entre requisições
                if ($pagina < $paginaFinal) {
                    usleep($delayMs * 1000); // converter ms para microsegundos
                }

            } catch (\Exception $e) {
                $estatisticas['paginas_erro']++;
                $erros[] = [
                    'pagina' => $pagina,
                    'erro' => $e->getMessage()
                ];

                Log::error('Erro ao baixar página CATMAT', [
                    'pagina' => $pagina,
                    'erro' => $e->getMessage()
                ]);
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Salvar arquivo
        $this->info('💾 Salvando arquivo JSON...');

        $estatisticas['tempo_fim'] = now();
        $estatisticas['duracao_segundos'] = $estatisticas['tempo_inicio']->diffInSeconds($estatisticas['tempo_fim']);
        $estatisticas['duracao_formatada'] = $this->formatarDuracao($estatisticas['duracao_segundos']);

        $arquivoFinal = [
            'metadata' => [
                'data_download' => now()->toDateTimeString(),
                'total_itens' => count($todosItens),
                'total_itens_esperados' => self::TOTAL_ITENS_ESPERADOS,
                'cobertura_percentual' => round((count($todosItens) / self::TOTAL_ITENS_ESPERADOS) * 100, 2),
                'pagina_inicial' => $paginaInicial,
                'pagina_final' => $pagina - 1, // Última página processada
                'tamanho_pagina' => $tamanhoPagina,
                'estatisticas' => $estatisticas,
                'erros' => $erros,
            ],
            'itens' => $todosItens
        ];

        Storage::put($caminhoCompleto, json_encode($arquivoFinal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Relatório final
        $this->newLine();
        $this->info('✅ Download concluído com sucesso!');
        $this->newLine();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Itens baixados', number_format($estatisticas['itens_baixados'], 0, ',', '.')],
                ['Páginas com sucesso', $estatisticas['paginas_sucesso']],
                ['Páginas com erro', $estatisticas['paginas_erro']],
                ['Tempo total', $estatisticas['duracao_formatada']],
                ['Itens por segundo', round($estatisticas['itens_baixados'] / max($estatisticas['duracao_segundos'], 1), 2)],
            ]
        );

        $this->newLine();
        $tamanhoArquivo = Storage::size($caminhoCompleto);
        $tamanhoMB = round($tamanhoArquivo / 1024 / 1024, 2);

        $this->info("📁 Arquivo salvo: storage/app/$caminhoCompleto");
        $this->info("📊 Tamanho do arquivo: {$tamanhoMB} MB");

        if ($estatisticas['itens_baixados'] >= self::TOTAL_ITENS_ESPERADOS * 0.95) {
            $this->info('🎉 Download completo! Todos os itens foram baixados.');
        } else {
            $cobertura = round(($estatisticas['itens_baixados'] / self::TOTAL_ITENS_ESPERADOS) * 100, 2);
            $this->warn("⚠️  Download parcial: {$cobertura}% dos itens esperados.");
        }

        if (count($erros) > 0) {
            $this->newLine();
            $this->warn("⚠️  Ocorreram " . count($erros) . " erros durante o download.");
            $this->warn("   Veja os detalhes no arquivo JSON ou nos logs.");
        }

        Log::info('Download CATMAT concluído', [
            'arquivo' => $caminhoCompleto,
            'estatisticas' => $estatisticas
        ]);

        return Command::SUCCESS;
    }

    /**
     * Fazer requisição à API
     */
    private function fazerRequisicao(int $pagina, int $tamanhoPagina): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => '*/*',
                    'User-Agent' => 'DattaTech-CestaPrecos/1.0'
                ])
                ->get(self::API_URL, [
                    'pagina' => $pagina,
                    'tamanhoPagina' => $tamanhoPagina
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Erro na requisição CATMAT', [
                'status' => $response->status(),
                'pagina' => $pagina
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exceção na requisição CATMAT', [
                'erro' => $e->getMessage(),
                'pagina' => $pagina
            ]);

            return null;
        }
    }

    /**
     * Formatar duração em segundos para formato legível
     */
    private function formatarDuracao(int $segundos): string
    {
        if ($segundos < 60) {
            return "{$segundos}s";
        }

        $minutos = floor($segundos / 60);
        $segundosRestantes = $segundos % 60;

        if ($minutos < 60) {
            return "{$minutos}m {$segundosRestantes}s";
        }

        $horas = floor($minutos / 60);
        $minutosRestantes = $minutos % 60;

        return "{$horas}h {$minutosRestantes}m {$segundosRestantes}s";
    }
}
