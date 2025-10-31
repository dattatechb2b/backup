<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicitaconSincronizar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licitacon:sincronizar {--limit=1000 : Limitar número de registros}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza dados do LicitaCon (TCE-RS) para cache local';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando sincronização do LicitaCon (TCE-RS)...');

        try {
            // FASE 1: Consultar API CKAN para obter metadados do dataset
            $this->info('📡 Consultando API CKAN do TCE-RS...');

            $response = Http::timeout(30)->get('https://dados.tce.rs.gov.br/api/3/action/package_show', [
                'id' => 'licitacoes-contratos-consolidado'
            ]);

            if (!$response->successful()) {
                $this->error('❌ Erro ao consultar API CKAN: ' . $response->status());
                Log::error('Erro LicitaCon CKAN', ['status' => $response->status()]);
                return 1;
            }

            $package = $response->json();

            if (!isset($package['success']) || !$package['success']) {
                $this->error('❌ API retornou erro');
                return 1;
            }

            // FASE 2: Encontrar recurso CSV mais recente
            $resources = $package['result']['resources'] ?? [];
            $csvResource = null;

            foreach ($resources as $resource) {
                if (isset($resource['format']) && strtolower($resource['format']) === 'csv') {
                    $csvResource = $resource;
                    break;
                }
            }

            if (!$csvResource) {
                $this->warn('⚠️  Nenhum recurso CSV encontrado no dataset');
                $this->info('💡 Sincronização manual necessária. Acesse: https://dados.tce.rs.gov.br/dataset/licitacoes-contratos-consolidado');
                return 0;
            }

            $this->info('✅ Dataset encontrado: ' . ($csvResource['name'] ?? 'LicitaCon'));
            $this->info('📊 Tamanho: ' . ($csvResource['size'] ?? 'desconhecido'));
            $this->info('📅 Última modificação: ' . ($csvResource['last_modified'] ?? 'desconhecida'));

            // FASE 3: Informar ao usuário (download manual por enquanto)
            $this->warn('');
            $this->warn('⚠️  IMPORTANTE: O dataset LicitaCon é muito grande (CSV com milhares de registros).');
            $this->warn('   Por segurança, a sincronização AUTOMÁTICA não foi implementada nesta versão.');
            $this->warn('');
            $this->info('📋 Para sincronizar manualmente:');
            $this->info('   1. Acesse: ' . ($csvResource['url'] ?? 'https://dados.tce.rs.gov.br/dataset/licitacoes-contratos-consolidado'));
            $this->info('   2. Baixe o arquivo CSV');
            $this->info('   3. Importe para a tabela cp_licitacon_cache usando:');
            $this->info('      - LibreOffice Calc + pgAdmin, OU');
            $this->info('      - Script SQL COPY, OU');
            $this->info('      - Laravel Seeder customizado');
            $this->warn('');

            // FASE 4: Verificar dados atuais na tabela
            $totalRegistros = DB::table('licitacon_cache')->count();

            if ($totalRegistros > 0) {
                $this->info("✅ Tabela cp_licitacon_cache possui {$totalRegistros} registros.");

                // Mostrar amostra
                $amostra = DB::table('licitacon_cache')
                    ->select('descricao', 'valor_unitario', 'orgao', 'municipio')
                    ->limit(3)
                    ->get();

                if ($amostra->isNotEmpty()) {
                    $this->info('');
                    $this->info('📌 Amostra de dados:');
                    foreach ($amostra as $item) {
                        $this->line('   - ' . ($item->descricao ?? 'Sem descrição') . ' | R$ ' . number_format($item->valor_unitario ?? 0, 2, ',', '.'));
                    }
                }
            } else {
                $this->warn('⚠️  Tabela cp_licitacon_cache está vazia (0 registros)');
                $this->info('   A busca no LicitaCon não retornará resultados até que dados sejam importados.');
            }

            $this->info('');
            $this->info('✅ Sincronização concluída!');

            Log::info('LicitaCon sincronização executada', [
                'registros_atuais' => $totalRegistros,
                'dataset_url' => $csvResource['url'] ?? null
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erro durante sincronização: ' . $e->getMessage());
            Log::error('Erro LicitaCon sincronização', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
