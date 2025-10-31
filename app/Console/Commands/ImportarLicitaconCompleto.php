<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ImportarLicitaconCompleto extends Command
{
    protected $signature = 'licitacon:importar-completo
                            {--anos=2020,2021,2022,2023,2024,2025 : Anos para importar (separados por vírgula)}
                            {--limpar : Limpar dados existentes antes de importar}';

    protected $description = 'Importa dados completos do Licitacon TCE-RS (~1GB) para o banco local';

    private const BASE_URL = 'https://dados.tce.rs.gov.br/dados/licitacon/licitacao/ano/';

    private $totalItens = 0;
    private $totalLicitacoes = 0;

    public function handle()
    {
        $this->info('🚀 IMPORTAÇÃO COMPLETA DO LICITACON TCE-RS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $anos = explode(',', $this->option('anos'));
        $limpar = $this->option('limpar');

        // Criar tabela se não existir
        $this->criarTabelas();

        // Limpar se solicitado
        if ($limpar) {
            $this->warn('🗑️  Limpando dados existentes...');
            DB::table('licitacon_itens')->truncate();
            DB::table('licitacon_licitacoes')->truncate();
            $this->info('✓ Dados limpos');
        }

        $tempoInicio = microtime(true);

        foreach ($anos as $ano) {
            $ano = trim($ano);
            $this->newLine();
            $this->info("📅 PROCESSANDO ANO: {$ano}");
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            $this->processarAno($ano);
        }

        $tempoTotal = round(microtime(true) - $tempoInicio, 2);

        $this->newLine(2);
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ IMPORTAÇÃO CONCLUÍDA COM SUCESSO!');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->table(['Métrica', 'Valor'], [
            ['Total de Licitações', number_format($this->totalLicitacoes, 0, ',', '.')],
            ['Total de Itens', number_format($this->totalItens, 0, ',', '.')],
            ['Tempo Total', $tempoTotal . 's'],
            ['Tamanho Estimado', '~' . round(($this->totalItens * 500) / 1024 / 1024, 2) . ' MB']
        ]);
        $this->newLine();

        return Command::SUCCESS;
    }

    private function criarTabelas()
    {
        $this->info('📊 Criando tabelas no banco...');

        // Tabela de Licitações
        DB::statement("
            CREATE TABLE IF NOT EXISTS licitacon_licitacoes (
                id SERIAL PRIMARY KEY,
                ano INT NOT NULL,
                codigo_licitacao VARCHAR(50) NOT NULL UNIQUE,
                numero_processo VARCHAR(100),
                modalidade VARCHAR(100),
                objeto TEXT,
                orgao VARCHAR(255),
                orgao_codigo VARCHAR(50),
                data_homologacao DATE,
                data_publicacao DATE,
                valor_total DECIMAL(15, 2),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_licitacoes_codigo ON licitacon_licitacoes(codigo_licitacao)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_licitacoes_ano ON licitacon_licitacoes(ano)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_licitacoes_orgao ON licitacon_licitacoes(orgao)");

        // Tabela de Itens
        DB::statement("
            CREATE TABLE IF NOT EXISTS licitacon_itens (
                id SERIAL PRIMARY KEY,
                ano INT NOT NULL,
                codigo_licitacao VARCHAR(50) NOT NULL,
                numero_item INT,
                descricao TEXT,
                unidade VARCHAR(20),
                quantidade DECIMAL(15, 2),
                valor_unitario DECIMAL(15, 2),
                valor_total DECIMAL(15, 2),
                marca VARCHAR(255),
                vencedor VARCHAR(255),
                vencedor_cnpj VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (codigo_licitacao) REFERENCES licitacon_licitacoes(codigo_licitacao) ON DELETE CASCADE
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_itens_licitacao ON licitacon_itens(codigo_licitacao)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_itens_descricao ON licitacon_itens USING gin(to_tsvector('portuguese', descricao))");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_itens_ano ON licitacon_itens(ano)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_itens_vencedor ON licitacon_itens(vencedor)");

        $this->info('✓ Tabelas criadas/verificadas');
    }

    private function processarAno($ano)
    {
        $zipUrl = self::BASE_URL . "{$ano}.csv.zip";
        $zipPath = storage_path("app/licitacon_{$ano}.zip");
        $extractPath = storage_path("app/licitacon_{$ano}");

        // 1. Download
        $this->info("⬇️  Baixando arquivo ZIP...");
        $bar = $this->output->createProgressBar(100);
        $bar->start();

        try {
            $response = Http::timeout(300)->withOptions([
                'progress' => function($downloadTotal, $downloadedBytes) use ($bar) {
                    if ($downloadTotal > 0) {
                        $progress = ($downloadedBytes / $downloadTotal) * 100;
                        $bar->setProgress((int)$progress);
                    }
                },
            ])->get($zipUrl);

            if (!$response->successful()) {
                $this->error("\n✗ Erro ao baixar: HTTP {$response->status()}");
                return;
            }

            file_put_contents($zipPath, $response->body());
            $bar->finish();
            $this->newLine();
            $this->info("✓ Download concluído: " . round(filesize($zipPath) / 1024 / 1024, 2) . " MB");

        } catch (\Exception $e) {
            $this->error("\n✗ Erro no download: " . $e->getMessage());
            return;
        }

        // 2. Extrair ZIP
        $this->info("📦 Extraindo arquivos...");
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            $this->info("✓ Arquivos extraídos");
        } else {
            $this->error("✗ Erro ao extrair ZIP");
            return;
        }

        // 3. Processar LICITACAO.csv
        $licitacoesFile = $extractPath . '/LICITACAO.csv';
        if (file_exists($licitacoesFile)) {
            $this->info("📄 Processando licitações...");
            $countLicitacoes = $this->processarLicitacoes($licitacoesFile, $ano);
            $this->info("✓ {$countLicitacoes} licitações importadas");
            $this->totalLicitacoes += $countLicitacoes;
        }

        // 4. Processar ITEM.csv
        $itensFile = $extractPath . '/ITEM.csv';
        if (file_exists($itensFile)) {
            $this->info("📄 Processando itens...");
            $countItens = $this->processarItens($itensFile, $ano);
            $this->info("✓ {$countItens} itens importados");
            $this->totalItens += $countItens;
        }

        // 5. Limpar arquivos temporários
        $this->info("🧹 Limpando arquivos temporários...");
        unlink($zipPath);
        $this->deletarDiretorio($extractPath);
        $this->info("✓ Limpeza concluída");
    }

    private function processarLicitacoes($arquivo, $ano)
    {
        $handle = fopen($arquivo, 'r');
        if (!$handle) {
            $this->error("✗ Erro ao abrir arquivo de licitações");
            return 0;
        }

        // Ler cabeçalho
        $header = fgetcsv($handle, 0, ',');

        $count = 0;
        $batch = [];
        $batchSize = 500;

        $totalLines = count(file($arquivo)) - 1; // -1 para o cabeçalho
        $bar = $this->output->createProgressBar($totalLines);
        $bar->start();

        while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
            if (count($data) < 10) continue; // Linha inválida

            // Mapear colunas (ajuste conforme estrutura real do CSV)
            $licitacao = [
                'ano' => $ano,
                'codigo_licitacao' => $data[0] ?? '',
                'numero_processo' => $data[1] ?? '',
                'modalidade' => $data[2] ?? '',
                'objeto' => $data[3] ?? '',
                'orgao' => $data[4] ?? '',
                'orgao_codigo' => $data[5] ?? '',
                'data_homologacao' => $this->converterData($data[6] ?? ''),
                'data_publicacao' => $this->converterData($data[7] ?? ''),
                'valor_total' => $this->converterValor($data[8] ?? '0'),
            ];

            $batch[] = $licitacao;

            if (count($batch) >= $batchSize) {
                DB::table('licitacon_licitacoes')->upsert($batch, ['codigo_licitacao'], [
                    'numero_processo', 'modalidade', 'objeto', 'orgao', 'data_homologacao',
                    'data_publicacao', 'valor_total', 'updated_at'
                ]);
                $count += count($batch);
                $bar->advance(count($batch));
                $batch = [];
            }
        }

        // Inserir último lote
        if (!empty($batch)) {
            DB::table('licitacon_licitacoes')->upsert($batch, ['codigo_licitacao'], [
                'numero_processo', 'modalidade', 'objeto', 'orgao', 'data_homologacao',
                'data_publicacao', 'valor_total', 'updated_at'
            ]);
            $count += count($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();
        fclose($handle);

        return $count;
    }

    private function processarItens($arquivo, $ano)
    {
        $handle = fopen($arquivo, 'r');
        if (!$handle) {
            $this->error("✗ Erro ao abrir arquivo de itens");
            return 0;
        }

        // Ler cabeçalho
        $header = fgetcsv($handle, 0, ',');

        $count = 0;
        $batch = [];
        $batchSize = 1000;

        $totalLines = count(file($arquivo)) - 1;
        $bar = $this->output->createProgressBar($totalLines);
        $bar->start();

        while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
            if (count($data) < 8) continue; // Linha inválida

            // Mapear colunas (ajuste conforme estrutura real do CSV)
            $item = [
                'ano' => $ano,
                'codigo_licitacao' => $data[0] ?? '',
                'numero_item' => (int)($data[1] ?? 0),
                'descricao' => $data[2] ?? '',
                'unidade' => $data[3] ?? 'UN',
                'quantidade' => $this->converterValor($data[4] ?? '1'),
                'valor_unitario' => $this->converterValor($data[5] ?? '0'),
                'valor_total' => $this->converterValor($data[6] ?? '0'),
                'marca' => $data[7] ?? '',
                'vencedor' => $data[8] ?? '',
                'vencedor_cnpj' => $data[9] ?? '',
            ];

            $batch[] = $item;

            if (count($batch) >= $batchSize) {
                DB::table('licitacon_itens')->insert($batch);
                $count += count($batch);
                $bar->advance(count($batch));
                $batch = [];
            }
        }

        // Inserir último lote
        if (!empty($batch)) {
            DB::table('licitacon_itens')->insert($batch);
            $count += count($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();
        fclose($handle);

        return $count;
    }

    private function converterData($data)
    {
        if (empty($data)) return null;

        // Formatos possíveis: DD/MM/YYYY, YYYY-MM-DD, etc
        try {
            $dt = \DateTime::createFromFormat('d/m/Y', $data);
            if ($dt) return $dt->format('Y-m-d');

            $dt = \DateTime::createFromFormat('Y-m-d', $data);
            if ($dt) return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            // Ignorar
        }

        return null;
    }

    private function converterValor($valor)
    {
        if (empty($valor)) return 0;

        // Remover pontos e substituir vírgula por ponto
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);

        return (float)$valor;
    }

    private function deletarDiretorio($dir)
    {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deletarDiretorio($path) : unlink($path);
        }

        rmdir($dir);
    }
}
