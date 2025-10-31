<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BaixarContratosPNCP extends Command
{
    protected $signature = 'pncp:baixar-contratos {--meses=12}';
    protected $description = 'Baixa contratos do PNCP dos últimos X meses';

    public function handle()
    {
        $meses = (int) $this->option('meses');
        $this->info("🚀 Iniciando download de contratos PNCP dos últimos {$meses} meses...");

        $dataFinal = Carbon::now();
        $dataInicial = Carbon::now()->subMonths($meses);

        $this->info("📅 Período: {$dataInicial->format('d/m/Y')} até {$dataFinal->format('d/m/Y')}");

        $totalBaixados = 0;
        $totalErros = 0;
        $batchInsert = []; // Buffer para batch insert

        // Processar mês a mês para evitar timeout
        $mesAtual = $dataInicial->copy();

        while ($mesAtual->lte($dataFinal)) {
            $mesInicio = $mesAtual->copy()->startOfMonth();
            $mesFim = $mesAtual->copy()->endOfMonth();

            if ($mesFim->gt($dataFinal)) {
                $mesFim = $dataFinal;
            }

            $this->info("\n📆 Processando: {$mesInicio->format('m/Y')}");

            $pagina = 1;
            $temMaisDados = true;

            while ($temMaisDados) {
                try {
                    $url = 'https://pncp.gov.br/api/consulta/v1/contratos';

                    $response = Http::timeout(30)->get($url, [
                        'dataInicial' => $mesInicio->format('Ymd'),
                        'dataFinal' => $mesFim->format('Ymd'),
                        'pagina' => $pagina,
                        'tamanhoPagina' => 100
                    ]);

                    if (!$response->successful()) {
                        $this->error("❌ Erro HTTP {$response->status()} na página {$pagina}");
                        $totalErros++;
                        break;
                    }

                    $data = $response->json();
                    $contratos = $data['data'] ?? $data['items'] ?? [];

                    if (empty($contratos)) {
                        $temMaisDados = false;
                        break;
                    }

                    foreach ($contratos as $contrato) {
                        try {
                            $batchInsert[] = [
                                'numero_controle_pncp' => $contrato['numeroControlePNCP'] ?? $contrato['id'] ?? uniqid(),
                                'tipo' => $contrato['tipoInstrumento'] ?? 'contrato',
                                'objeto_contrato' => substr($contrato['objetoContrato'] ?? $contrato['objeto'] ?? '', 0, 5000),
                                'valor_global' => $contrato['valorGlobal'] ?? $contrato['valorInicial'] ?? 0,
                                'orgao_cnpj' => $contrato['orgaoEntidade']['cnpj'] ?? null,
                                'orgao_razao_social' => $contrato['orgaoEntidade']['razaoSocial'] ?? null,
                                'orgao_uf' => $contrato['unidadeOrgao']['ufSigla'] ?? null,
                                'data_publicacao_pncp' => isset($contrato['dataPublicacaoPncp']) ? Carbon::parse($contrato['dataPublicacaoPncp'])->format('Y-m-d') : null,
                                'data_vigencia_inicio' => isset($contrato['dataVigenciaInicio']) ? Carbon::parse($contrato['dataVigenciaInicio'])->format('Y-m-d') : null,
                                'data_vigencia_fim' => isset($contrato['dataVigenciaFim']) ? Carbon::parse($contrato['dataVigenciaFim'])->format('Y-m-d') : null,
                                'fornecedor_cnpj' => $contrato['niFornecedor'] ?? null,
                                'fornecedor_razao_social' => $contrato['nomeRazaoSocialFornecedor'] ?? null,
                                'confiabilidade' => 'alta',
                                'valor_estimado' => false,
                                'sincronizado_em' => now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];

                            $totalBaixados++;

                            // Inserir em lote a cada 100 registros
                            if (count($batchInsert) >= 100) {
                                DB::connection('pgsql_main')->table('contratos_pncp')->insertOrIgnore($batchInsert);
                                $batchInsert = [];
                                $this->info("✅ {$totalBaixados} contratos baixados...");
                            }

                        } catch (\Exception $e) {
                            $totalErros++;
                        }
                    }

                    // Inserir registros restantes do batch
                    if (!empty($batchInsert)) {
                        try {
                            DB::connection('pgsql_main')->table('contratos_pncp')->insertOrIgnore($batchInsert);
                            $batchInsert = [];
                        } catch (\Exception $e) {
                            $totalErros++;
                        }
                    }

                    $pagina++;
                    usleep(50000); // 0.05s entre páginas (mais rápido!)

                    // Verificar se tem próxima página
                    if (count($contratos) < 100) {
                        $temMaisDados = false;
                    }

                } catch (\Exception $e) {
                    $this->error("❌ Erro ao baixar página {$pagina}: {$e->getMessage()}");
                    $totalErros++;
                    break;
                }
            }

            $mesAtual->addMonth();
        }

        $this->info("\n");
        $this->info("═══════════════════════════════════════");
        $this->info("✅ DOWNLOAD PNCP CONCLUÍDO!");
        $this->info("═══════════════════════════════════════");
        $this->info("📊 Total baixados: {$totalBaixados}");
        $this->info("❌ Total erros: {$totalErros}");

        $total = DB::connection('pgsql_main')->table('contratos_pncp')->count();
        $tamanho = DB::connection('pgsql_main')->select("SELECT pg_size_pretty(pg_total_relation_size('cp_contratos_pncp')) as size")[0]->size ?? 'N/A';

        $this->info("💾 Total no banco: {$total}");
        $this->info("📦 Tamanho: {$tamanho}");

        return 0;
    }
}
