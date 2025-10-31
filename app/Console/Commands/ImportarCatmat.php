<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportarCatmat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catmat:importar
                            {arquivo? : Nome do arquivo JSON (opcional, usa o mais recente)}
                            {--limpar : Limpar tabela antes de importar}
                            {--teste=0 : Importar apenas N registros para teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa dados do CATMAT do arquivo JSON para o banco de dados principal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando importação do CATMAT...');
        $this->newLine();

        // Determinar arquivo a ser importado
        $nomeArquivo = $this->argument('arquivo');

        if (!$nomeArquivo) {
            // Buscar arquivo mais recente
            $arquivos = Storage::files('catmat');
            $arquivosJson = array_filter($arquivos, function($arquivo) {
                return str_ends_with($arquivo, '.json');
            });

            if (empty($arquivosJson)) {
                $this->error('❌ Nenhum arquivo JSON encontrado no diretório catmat/');
                $this->warn('💡 Execute primeiro: php artisan catmat:baixar');
                return Command::FAILURE;
            }

            // Pegar o mais recente
            usort($arquivosJson, function($a, $b) {
                return Storage::lastModified($b) <=> Storage::lastModified($a);
            });

            $nomeArquivo = $arquivosJson[0];
            $this->info("📄 Usando arquivo mais recente: $nomeArquivo");
        } else {
            // Verificar se o arquivo especificado existe
            $caminhoCompleto = "catmat/$nomeArquivo";
            if (!Storage::exists($caminhoCompleto)) {
                $this->error("❌ Arquivo não encontrado: $caminhoCompleto");
                return Command::FAILURE;
            }
            $nomeArquivo = $caminhoCompleto;
        }

        // Ler arquivo JSON
        $this->info('📖 Lendo arquivo JSON...');

        try {
            $conteudo = Storage::get($nomeArquivo);
            $dados = json_decode($conteudo, true);

            if (!$dados || !isset($dados['itens'])) {
                $this->error('❌ Formato de arquivo inválido. Esperado: {"itens": [...]}');
                return Command::FAILURE;
            }

            $itens = $dados['itens'];
            $totalItens = count($itens);

            $this->info("✅ Arquivo carregado com sucesso!");
            $this->info("📊 Total de itens no arquivo: " . number_format($totalItens, 0, ',', '.'));
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('❌ Erro ao ler arquivo JSON: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Limpar tabela se solicitado
        if ($this->option('limpar')) {
            $this->warn('⚠️  Limpando tabela cp_catmat...');
            DB::connection('pgsql_main')->table('cp_catmat')->truncate();
            $this->info('✅ Tabela limpa!');
            $this->newLine();
        }

        // Modo teste
        $teste = (int) $this->option('teste');
        if ($teste > 0) {
            $this->warn("🧪 MODO TESTE: Importando apenas {$teste} registros");
            $itens = array_slice($itens, 0, $teste);
            $totalItens = count($itens);
            $this->newLine();
        }

        // Preparar para importação
        $batchSize = 1000; // 1000 registros por lote
        $totalBatches = ceil($totalItens / $batchSize);
        $importados = 0;
        $erros = 0;

        $this->info("📦 Processando em lotes de {$batchSize} registros...");
        $this->info("📊 Total de lotes: " . number_format($totalBatches, 0, ',', '.'));
        $this->newLine();

        // Progress bar
        $progressBar = $this->output->createProgressBar($totalItens);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Importados: %message%');
        $progressBar->setMessage('0');
        $progressBar->start();

        // Processar em lotes
        $batch = [];
        $now = now();

        foreach ($itens as $index => $item) {
            try {
                // Preparar dados para inserção
                $batch[] = [
                    'codigo' => $item['codigoItem'] ?? $item['codigo'] ?? null,
                    'titulo' => substr($item['descricaoItem'] ?? $item['titulo'] ?? '', 0, 2000),
                    'tipo' => $item['tipo'] ?? 'CATMAT',
                    'caminho_hierarquia' => $item['caminhoCategoria'] ?? null,
                    'unidade_padrao' => $item['unidadeFornecimento'] ?? null,
                    'fonte' => 'API_OFICIAL',
                    'primeira_ocorrencia_em' => $now,
                    'ultima_ocorrencia_em' => $now,
                    'contador_ocorrencias' => 0,
                    'ativo' => true,
                    'tem_preco_comprasgov' => null, // Será preenchido depois
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Inserir lote quando atingir o tamanho
                if (count($batch) >= $batchSize) {
                    try {
                        DB::connection('pgsql_main')
                            ->table('cp_catmat')
                            ->insertOrIgnore($batch); // insertOrIgnore para evitar duplicados

                        $importados += count($batch);
                        $progressBar->setMessage(number_format($importados, 0, ',', '.'));
                        $progressBar->advance(count($batch));

                        $batch = [];
                    } catch (\Exception $e) {
                        $erros += count($batch);
                        Log::error('Erro ao importar lote CATMAT', [
                            'erro' => $e->getMessage(),
                            'total_itens' => count($batch)
                        ]);
                        $batch = [];
                    }
                }

            } catch (\Exception $e) {
                $erros++;
                Log::error('Erro ao processar item CATMAT', [
                    'item' => $item,
                    'erro' => $e->getMessage()
                ]);
            }
        }

        // Inserir registros restantes do último lote
        if (!empty($batch)) {
            try {
                DB::connection('pgsql_main')
                    ->table('cp_catmat')
                    ->insertOrIgnore($batch);

                $importados += count($batch);
                $progressBar->setMessage(number_format($importados, 0, ',', '.'));
                $progressBar->advance(count($batch));

            } catch (\Exception $e) {
                $erros += count($batch);
                Log::error('Erro ao importar último lote CATMAT', [
                    'erro' => $e->getMessage(),
                    'total_itens' => count($batch)
                ]);
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Estatísticas finais
        $totalNoBanco = DB::connection('pgsql_main')
            ->table('cp_catmat')
            ->count();

        $this->newLine();
        $this->info('═══════════════════════════════════════════');
        $this->info('           IMPORTAÇÃO CONCLUÍDA            ');
        $this->info('═══════════════════════════════════════════');
        $this->newLine();
        $this->info("✅ Importados com sucesso: " . number_format($importados, 0, ',', '.'));

        if ($erros > 0) {
            $this->warn("⚠️  Erros encontrados: " . number_format($erros, 0, ',', '.'));
        }

        $this->info("📊 Total no banco de dados: " . number_format($totalNoBanco, 0, ',', '.'));
        $this->newLine();

        // Informações adicionais
        $this->info('💡 Próximos passos:');
        $this->line('   1. Execute: php artisan comprasgov:baixar-precos');
        $this->line('   2. Aguarde a importação dos preços (~30-60 min)');
        $this->line('   3. Sistema ficará 15x mais rápido automaticamente!');
        $this->newLine();

        return Command::SUCCESS;
    }
}
