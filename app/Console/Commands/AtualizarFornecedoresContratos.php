<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContratoPNCP;
use App\Models\Fornecedor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AtualizarFornecedoresContratos extends Command
{
    protected $signature = 'contratos:atualizar-fornecedores {--limit=100 : Número de contratos a processar por vez}';

    protected $description = 'Atualiza contratos existentes com dados de fornecedores extraídos da API PNCP';

    public function handle()
    {
        $this->info('🔄 Iniciando atualização de fornecedores nos contratos existentes...');

        $limit = $this->option('limit');

        // Buscar contratos sem dados de fornecedor
        $totalSemFornecedor = ContratoPNCP::whereNull('fornecedor_cnpj')->count();

        $this->info("📊 Total de contratos sem fornecedor: {$totalSemFornecedor}");

        if ($totalSemFornecedor === 0) {
            $this->info('✅ Todos os contratos já possuem dados de fornecedor!');
            return 0;
        }

        $this->info("🔍 Processando {$limit} contratos por vez...");

        $processados = 0;
        $atualizados = 0;
        $fornecedoresCriados = 0;
        $erros = 0;

        $bar = $this->output->createProgressBar(min($totalSemFornecedor, $limit));
        $bar->start();

        // Processar em lotes
        ContratoPNCP::whereNull('fornecedor_cnpj')
            ->limit($limit)
            ->chunk(50, function($contratos) use (&$processados, &$atualizados, &$fornecedoresCriados, &$erros, $bar) {

                foreach ($contratos as $contrato) {
                    try {
                        // Buscar detalhes do contrato na API PNCP
                        $numeroControle = $contrato->numero_controle_pncp;

                        // A API PNCP tem endpoint de detalhes: /v1/contratos/{ano}/{sequencial}
                        // Mas vamos buscar na lista mesmo, é mais rápido

                        // Extrair CNPJOrg, ano e sequencial do numeroControlePNCP
                        // Formato: 12345678000190-2-000123/2025
                        if (preg_match('/^(\d{14})-\d+-(\d+)\/(\d{4})$/', $numeroControle, $matches)) {
                            $cnpjOrgao = $matches[1];
                            $sequencial = (int)$matches[2];
                            $ano = (int)$matches[3];

                            $url = "https://pncp.gov.br/api/pncp/v1/orgaos/{$cnpjOrgao}/compras/{$ano}/{$sequencial}/contrato";

                            $response = Http::timeout(10)->get($url);

                            if ($response->successful()) {
                                $dados = $response->json();

                                $fornecedorCNPJ = $dados['niFornecedor'] ?? null;
                                $fornecedorNome = $dados['nomeRazaoSocialFornecedor'] ?? null;

                                if ($fornecedorCNPJ) {
                                    $fornecedorCNPJLimpo = preg_replace('/\D/', '', $fornecedorCNPJ);

                                    $fornecedorId = null;

                                    if (strlen($fornecedorCNPJLimpo) === 14) {
                                        // Criar ou buscar fornecedor
                                        $fornecedor = Fornecedor::firstOrCreate(
                                            ['numero_documento' => $fornecedorCNPJLimpo],
                                            [
                                                'tipo_pessoa' => 'juridica',
                                                'razao_social' => $fornecedorNome ?? 'Fornecedor PNCP',
                                                'situacao_cadastral' => 'Ativa'
                                            ]
                                        );

                                        if ($fornecedor->wasRecentlyCreated) {
                                            $fornecedoresCriados++;
                                        }

                                        $fornecedorId = $fornecedor->id;
                                    }

                                    // Atualizar contrato
                                    $contrato->update([
                                        'fornecedor_cnpj' => $fornecedorCNPJ,
                                        'fornecedor_razao_social' => $fornecedorNome ? substr($fornecedorNome, 0, 255) : null,
                                        'fornecedor_id' => $fornecedorId
                                    ]);

                                    $atualizados++;
                                }
                            }

                            // Pequeno delay para não sobrecarregar API
                            usleep(200000); // 200ms
                        }

                        $processados++;
                        $bar->advance();

                    } catch (\Exception $e) {
                        $erros++;
                        Log::error("Erro ao atualizar contrato {$contrato->numero_controle_pncp}", [
                            'erro' => $e->getMessage()
                        ]);
                        $bar->advance();
                    }
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Atualização concluída!");
        $this->info("📊 Estatísticas:");
        $this->info("   - Processados: {$processados}");
        $this->info("   - Atualizados: {$atualizados}");
        $this->info("   - Fornecedores criados: {$fornecedoresCriados}");
        $this->info("   - Erros: {$erros}");

        $restantes = ContratoPNCP::whereNull('fornecedor_cnpj')->count();

        if ($restantes > 0) {
            $this->newLine();
            $this->warn("⚠️  Ainda restam {$restantes} contratos sem fornecedor.");
            $this->info("💡 Execute o comando novamente para processar mais contratos:");
            $this->info("   php artisan contratos:atualizar-fornecedores --limit=1000");
        } else {
            $this->newLine();
            $this->info("🎉 Todos os contratos foram atualizados!");
        }

        return 0;
    }
}
