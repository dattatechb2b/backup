<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BaixarPrecosComprasGov extends Command
{
    protected $signature = 'comprasgov:baixar-precos {--limite-gb=3}';
    protected $description = 'Baixa preços do Compras.gov (últimos 12 meses, limitado a X GB)';

    public function handle()
    {
        $limiteGB = (int) $this->option('limite-gb');
        $limiteBytes = $limiteGB * 1024 * 1024 * 1024; // Converter para bytes

        $this->info("🚀 Iniciando download de preços Compras.gov (limite: {$limiteGB} GB)...");

        // Criar tabela se não existir
        $this->criarTabela();

        $totalPrecos = 0;
        $totalErros = 0;
        $totalBytes = 0;
        $batchInsert = []; // Buffer para batch insert

        // Buscar códigos CATMAT (priorizando mais usados)
        $codigos = DB::connection('pgsql_main')->table('cp_catmat')
            ->select('codigo', 'titulo')
            ->where('ativo', true)
            ->orderBy('contador_ocorrencias', 'desc')
            ->limit(10000) // Top 10k códigos (mais rápido!)
            ->get();

        $this->info("📊 {$codigos->count()} códigos CATMAT para processar");

        $dataLimite = Carbon::now()->subMonths(12)->format('Y-m-d');

        foreach ($codigos as $index => $codigo) {
            // Verificar limite de tamanho
            $tamanhoAtual = DB::connection('pgsql_main')->select(
                "SELECT pg_total_relation_size('cp_precos_comprasgov') as size"
            )[0]->size ?? 0;

            if ($tamanhoAtual >= $limiteBytes) {
                $this->warn("\n⚠️  Limite de {$limiteGB} GB atingido!");
                break;
            }

            try {
                $url = 'https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial';

                $response = Http::timeout(15)->get($url, [
                    'codigoItemCatalogo' => $codigo->codigo,
                    'pagina' => 1,
                    'tamanhoPagina' => 500
                ]);

                if (!$response->successful()) {
                    $totalErros++;
                    continue;
                }

                $data = $response->json();
                $precos = $data['resultado'] ?? [];

                foreach ($precos as $preco) {
                    // Filtrar apenas últimos 12 meses
                    $dataPreco = $preco['dataCompra'] ?? $preco['dataResultado'] ?? null;
                    if ($dataPreco && $dataPreco < $dataLimite) {
                        continue;
                    }

                    try {
                        $batchInsert[] = [
                            'catmat_codigo' => $codigo->codigo,
                            'descricao_item' => substr($preco['descricaoItem'] ?? $codigo->titulo, 0, 1000),
                            'preco_unitario' => $preco['precoUnitario'] ?? 0,
                            'quantidade' => $preco['quantidade'] ?? 1,
                            'unidade_fornecimento' => $preco['siglaUnidadeFornecimento'] ?? 'UN',
                            'fornecedor_nome' => substr($preco['nomeFornecedor'] ?? '', 0, 255),
                            'fornecedor_cnpj' => $preco['niFornecedor'] ?? null,
                            'orgao_nome' => substr($preco['nomeOrgao'] ?? $preco['nomeUasg'] ?? '', 0, 255),
                            'orgao_codigo' => $preco['codigoOrgao'] ?? $preco['codigoUasg'] ?? null,
                            'orgao_uf' => $preco['ufOrgao'] ?? null,
                            'municipio' => $preco['municipioFornecedor'] ?? null,
                            'uf' => $preco['ufFornecedor'] ?? null,
                            'data_compra' => $dataPreco ? Carbon::parse($dataPreco)->format('Y-m-d') : null,
                            'sincronizado_em' => now(),
                            'created_at' => now(),
                        ];

                        $totalPrecos++;
                        $totalBytes += 300; // Estimativa por registro

                        // Inserir em lote a cada 100 registros
                        if (count($batchInsert) >= 100) {
                            DB::connection('pgsql_main')->table('cp_precos_comprasgov')->insert($batchInsert);
                            $batchInsert = [];
                        }

                    } catch (\Exception $e) {
                        $totalErros++;
                    }
                }

                // Inserir registros restantes do batch
                if (!empty($batchInsert)) {
                    try {
                        DB::connection('pgsql_main')->table('cp_precos_comprasgov')->insert($batchInsert);
                        $batchInsert = [];
                    } catch (\Exception $e) {
                        $totalErros++;
                    }
                }

                if (($index + 1) % 100 == 0) {
                    $tamanhoMB = round($tamanhoAtual / 1024 / 1024, 2);
                    $this->info("✅ Processados: " . ($index + 1) . " códigos | Preços: {$totalPrecos} | Tamanho: {$tamanhoMB} MB");
                }

                usleep(50000); // 0.05s entre requisições (mais rápido!)

            } catch (\Exception $e) {
                $totalErros++;
                continue;
            }
        }

        $this->info("\n");
        $this->info("═══════════════════════════════════════");
        $this->info("✅ DOWNLOAD COMPRAS.GOV CONCLUÍDO!");
        $this->info("═══════════════════════════════════════");
        $this->info("📊 Total preços baixados: {$totalPrecos}");
        $this->info("❌ Total erros: {$totalErros}");

        $total = DB::connection('pgsql_main')->table('precos_comprasgov')->count();
        $tamanho = DB::connection('pgsql_main')->select("SELECT pg_size_pretty(pg_total_relation_size('cp_precos_comprasgov')) as size")[0]->size ?? 'N/A';

        $this->info("💾 Total no banco: {$total}");
        $this->info("📦 Tamanho: {$tamanho}");

        return 0;
    }

    private function criarTabela()
    {
        DB::connection('pgsql_main')->statement("
            CREATE TABLE IF NOT EXISTS cp_precos_comprasgov (
                id BIGSERIAL PRIMARY KEY,
                catmat_codigo VARCHAR(20) NOT NULL,
                descricao_item TEXT NOT NULL,
                preco_unitario DECIMAL(15,2) NOT NULL,
                quantidade DECIMAL(15,3) DEFAULT 1,
                unidade_fornecimento VARCHAR(50),
                fornecedor_nome VARCHAR(255),
                fornecedor_cnpj VARCHAR(14),
                orgao_nome VARCHAR(255),
                orgao_codigo VARCHAR(50),
                orgao_uf VARCHAR(2),
                municipio VARCHAR(100),
                uf VARCHAR(2),
                data_compra DATE,
                sincronizado_em TIMESTAMP NOT NULL,
                created_at TIMESTAMP
            )
        ");

        DB::connection('pgsql_main')->statement("CREATE INDEX IF NOT EXISTS idx_precos_comprasgov_catmat ON cp_precos_comprasgov(catmat_codigo)");
        DB::connection('pgsql_main')->statement("CREATE INDEX IF NOT EXISTS idx_precos_comprasgov_data ON cp_precos_comprasgov(data_compra)");
        DB::connection('pgsql_main')->statement("CREATE INDEX IF NOT EXISTS idx_precos_comprasgov_uf ON cp_precos_comprasgov(uf)");
        DB::connection('pgsql_main')->statement("CREATE INDEX IF NOT EXISTS idx_precos_comprasgov_desc ON cp_precos_comprasgov USING gin(to_tsvector('portuguese', descricao_item))");
    }
}
