<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ComprasGovWorker extends Command
{
    protected $signature = 'comprasgov:worker
                            {--arquivo= : Arquivo com lista de códigos}
                            {--limite-gb=3 : Limite de tamanho em GB}';

    protected $description = 'Worker para processar lote de códigos CATMAT (uso interno)';

    public function handle()
    {
        $arquivo = $this->option('arquivo');
        $limiteGB = (int) $this->option('limite-gb');
        $limiteBytes = $limiteGB * 1024 * 1024 * 1024;

        if (!file_exists($arquivo)) {
            $this->error("Arquivo não encontrado: {$arquivo}");
            return Command::FAILURE;
        }

        // Ler códigos do arquivo
        $codigos = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $totalPrecos = 0;
        $totalErros = 0;
        $batchInsert = [];
        $dataLimite = Carbon::now()->subMonths(12)->format('Y-m-d');

        foreach ($codigos as $codigo) {
            // Verificar limite de tamanho
            try {
                $tamanhoAtual = DB::connection('pgsql_main')->select(
                    "SELECT pg_total_relation_size('cp_precos_comprasgov') as size"
                )[0]->size ?? 0;

                if ($tamanhoAtual >= $limiteBytes) {
                    break;
                }
            } catch (\Exception $e) {
                // Continuar mesmo se falhar verificação de tamanho
            }

            try {
                $url = 'https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial';

                $response = Http::timeout(10)->get($url, [
                    'codigoItemCatalogo' => $codigo,
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
                            'catmat_codigo' => $codigo,
                            'descricao_item' => substr($preco['descricaoItem'] ?? '', 0, 1000),
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

                        // Inserir em lote a cada 50 registros (mais rápido)
                        if (count($batchInsert) >= 50) {
                            DB::connection('pgsql_main')
                                ->table('cp_precos_comprasgov')
                                ->insert($batchInsert);
                            $batchInsert = [];
                        }

                    } catch (\Exception $e) {
                        // Continuar processando
                    }
                }

                // Inserir registros restantes do batch
                if (!empty($batchInsert)) {
                    try {
                        DB::connection('pgsql_main')
                            ->table('cp_precos_comprasgov')
                            ->insert($batchInsert);
                        $batchInsert = [];
                    } catch (\Exception $e) {
                        // Continuar processando
                    }
                }

                // Delay mínimo entre requisições
                usleep(20000); // 0.02s

            } catch (\Exception $e) {
                $totalErros++;
                continue;
            }
        }

        // Inserir últimos registros pendentes
        if (!empty($batchInsert)) {
            try {
                DB::connection('pgsql_main')
                    ->table('cp_precos_comprasgov')
                    ->insert($batchInsert);
            } catch (\Exception $e) {
                // Ignorar erro final
            }
        }

        // Deletar arquivo de controle para sinalizar conclusão
        @unlink($arquivo);

        return Command::SUCCESS;
    }
}
