<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ComprasGovScoutWorker extends Command
{
    protected $signature = 'comprasgov:scout-worker
                            {--arquivo= : Arquivo com lista de códigos}
                            {--timeout=5 : Timeout por requisição}';

    protected $description = 'Worker para verificar rapidamente se códigos têm preços';

    public function handle()
    {
        $arquivo = $this->option('arquivo');
        $timeout = (int) $this->option('timeout');

        if (!file_exists($arquivo)) {
            $this->error("Arquivo {$arquivo} não encontrado!");
            return 1;
        }

        $codigos = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->info("Worker iniciado: " . count($codigos) . " códigos para verificar");

        $url = 'https://dadosabertos.compras.gov.br/modulo-pesquisa-preco/1_consultarMaterial';

        foreach ($codigos as $codigo) {
            try {
                // Requisição rápida: apenas verifica se retorna dados
                // tamanhoPagina=10 (mínimo aceito pela API)
                $response = Http::timeout($timeout)->get($url, [
                    'codigoItemCatalogo' => $codigo,
                    'pagina' => 1,
                    'tamanhoPagina' => 10  // Mínimo aceito pela API Compras.gov
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $temPrecos = isset($data['resultado']) && count($data['resultado']) > 0;

                    // Atualizar flag na tabela cp_catmat
                    DB::connection('pgsql_main')
                        ->table('cp_catmat')
                        ->where('codigo', $codigo)
                        ->update([
                            'tem_preco_comprasgov' => $temPrecos,
                            'verificado_comprasgov_em' => now()
                        ]);

                } else {
                    // Em caso de erro, marcar como false para não ficar verificando eternamente
                    DB::connection('pgsql_main')
                        ->table('cp_catmat')
                        ->where('codigo', $codigo)
                        ->update([
                            'tem_preco_comprasgov' => false,
                            'verificado_comprasgov_em' => now()
                        ]);
                }

                // Pequeno delay para não sobrecarregar a API
                usleep(10000); // 10ms

            } catch (\Exception $e) {
                // Em caso de erro, marcar como false
                DB::connection('pgsql_main')
                    ->table('cp_catmat')
                    ->where('codigo', $codigo)
                    ->update([
                        'tem_preco_comprasgov' => false,
                        'verificado_comprasgov_em' => now()
                    ]);
            }
        }

        $this->info("Worker finalizado!");
        return 0;
    }
}
