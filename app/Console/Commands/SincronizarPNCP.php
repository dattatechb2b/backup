<?php

namespace App\Console\Commands;

use App\Models\ContratoPNCP;
use App\Models\Fornecedor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SincronizarPNCP extends Command
{
    protected $signature = 'pncp:sincronizar {--meses=6 : Meses atrás para sincronizar} {--paginas=50 : Número de páginas para buscar}';

    protected $description = 'Sincroniza contratos e atas do PNCP para o banco local (permite busca por qualquer palavra)';

    public function handle()
    {
        $this->info('🚀 Iniciando sincronização do PNCP...');

        $meses = $this->option('meses');
        $paginasMax = $this->option('paginas');

        $dataFinal = now()->format('Ymd');
        $dataInicial = now()->subMonths($meses)->format('Ymd');

        $this->info("📅 Período: {$dataInicial} até {$dataFinal}");
        $this->info("📄 Páginas máximas: {$paginasMax}");

        // Sincronizar contratos
        $this->newLine();
        $this->line('📝 Sincronizando CONTRATOS...');
        $totalContratos = $this->sincronizarContratos($dataInicial, $dataFinal, $paginasMax);

        // Sincronizar atas (quando disponível)
        // $this->newLine();
        // $this->line('📋 Sincronizando ATAS...');
        // $totalAtas = $this->sincronizarAtas($dataInicial, $dataFinal, $paginasMax);

        $this->newLine();
        $this->info("✅ Sincronização concluída!");
        $this->info("📊 Total de contratos sincronizados: {$totalContratos}");
        // $this->info("📊 Total de atas sincronizadas: {$totalAtas}");

        // Estatísticas do banco
        $this->newLine();
        $this->showEstatisticas();

        return 0;
    }

    private function sincronizarContratos($dataInicial, $dataFinal, $paginasMax)
    {
        $totalInseridos = 0;
        $totalAtualizados = 0;
        $totalErros = 0;

        $progressBar = $this->output->createProgressBar($paginasMax);
        $progressBar->start();

        for ($pagina = 1; $pagina <= $paginasMax; $pagina++) {
            try {
                $url = "https://pncp.gov.br/api/consulta/v1/contratos?" . http_build_query([
                    'dataInicial' => $dataInicial,
                    'dataFinal' => $dataFinal,
                    'pagina' => $pagina
                ]);

                $response = Http::timeout(20)->get($url);

                if (!$response->successful()) {
                    $this->warn("\n⚠️  Página {$pagina}: Erro HTTP {$response->status()}");
                    $totalErros++;
                    $progressBar->advance();
                    continue;
                }

                $data = $response->json();

                if (!isset($data['data']) || empty($data['data'])) {
                    $this->info("\n✋ Página {$pagina}: Sem mais dados. Parando...");
                    break;
                }

                foreach ($data['data'] as $contrato) {
                    try {
                        $numeroControle = $contrato['numeroControlePNCP'] ?? null;

                        if (!$numeroControle) {
                            continue; // Sem identificação, skip
                        }

                        $objetoContrato = $contrato['objetoContrato'] ?? '';
                        $valorGlobal = $contrato['valorGlobal'] ?? 0;

                        if (empty($objetoContrato) || $valorGlobal <= 0) {
                            continue; // Sem descrição ou valor, skip
                        }

                        $numeroParcelas = $contrato['numeroParcelas'] ?? 1;
                        $confiabilidade = 'baixa'; // Padrão: valor global

                        if ($numeroParcelas > 1) {
                            $confiabilidade = 'media'; // Estimado com parcelas
                        }

                        $orgao = $contrato['orgaoEntidade'] ?? [];
                        $uf = $this->extrairUF($orgao['razaoSocial'] ?? '');

                        // Extrair dados do fornecedor
                        $fornecedorCNPJ = $contrato['niFornecedor'] ?? $contrato['cnpjContratado'] ?? null;
                        $fornecedorNome = $contrato['nomeRazaoSocialFornecedor'] ?? $contrato['razaoSocialFornecedor'] ?? null;
                        $fornecedorId = null;

                        // Log para debug (primeiro contrato apenas)
                        if (!isset($loggedFirst)) {
                            Log::info('DEBUG - Dados de fornecedor do primeiro contrato', [
                                'numeroControle' => $numeroControle,
                                'niFornecedor' => $fornecedorCNPJ,
                                'nomeRazaoSocialFornecedor' => $fornecedorNome,
                                'contrato_keys' => array_keys($contrato)
                            ]);
                            $loggedFirst = true;
                        }

                        // Se tem CNPJ do fornecedor, buscar ou criar
                        if ($fornecedorCNPJ) {
                            $fornecedorCNPJLimpo = preg_replace('/\D/', '', $fornecedorCNPJ);

                            if (strlen($fornecedorCNPJLimpo) === 14) {
                                $fornecedor = Fornecedor::firstOrCreate(
                                    ['numero_documento' => $fornecedorCNPJLimpo],
                                    [
                                        'tipo_pessoa' => 'juridica',
                                        'razao_social' => $fornecedorNome ?? 'Fornecedor PNCP',
                                        'situacao_cadastral' => 'Ativa'
                                    ]
                                );
                                $fornecedorId = $fornecedor->id;
                            }
                        }

                        $dados = [
                            'numero_controle_pncp' => $numeroControle,
                            'tipo' => 'contrato',
                            'objeto_contrato' => substr($objetoContrato, 0, 5000), // Limite de texto
                            'valor_global' => $valorGlobal,
                            'numero_parcelas' => $numeroParcelas,
                            'valor_unitario_estimado' => $numeroParcelas > 1 ? $valorGlobal / $numeroParcelas : null,
                            'unidade_medida' => 'CONTRATO',
                            'orgao_cnpj' => $orgao['cnpj'] ?? null,
                            'orgao_razao_social' => substr($orgao['razaoSocial'] ?? '', 0, 255),
                            'orgao_uf' => $uf,
                            'orgao_municipio' => $orgao['municipio']['nome'] ?? null,
                            'fornecedor_cnpj' => $fornecedorCNPJ,
                            'fornecedor_razao_social' => $fornecedorNome ? substr($fornecedorNome, 0, 255) : null,
                            'fornecedor_id' => $fornecedorId,
                            'data_publicacao_pncp' => $contrato['dataPublicacaoPncp'] ?? now(),
                            'data_vigencia_inicio' => $contrato['dataVigenciaInicio'] ?? null,
                            'data_vigencia_fim' => $contrato['dataVigenciaFim'] ?? null,
                            'confiabilidade' => $confiabilidade,
                            'valor_estimado' => $numeroParcelas > 1,
                            'sincronizado_em' => now(),
                        ];

                        // Upsert: Atualiza se existir, insere se não existir
                        ContratoPNCP::updateOrCreate(
                            ['numero_controle_pncp' => $numeroControle],
                            $dados
                        );

                        $totalInseridos++;

                    } catch (\Exception $e) {
                        $totalErros++;
                        Log::error('Erro ao sincronizar contrato', [
                            'erro' => $e->getMessage(),
                            'contrato' => $numeroControle ?? 'N/A'
                        ]);
                    }
                }

                $progressBar->advance();

                // Pequeno delay para não sobrecarregar a API
                usleep(100000); // 100ms

            } catch (\Exception $e) {
                $this->error("\n❌ Erro na página {$pagina}: {$e->getMessage()}");
                $totalErros++;
                $progressBar->advance();
                continue;
            }
        }

        $progressBar->finish();
        $this->newLine();

        return $totalInseridos;
    }

    private function extrairUF($razaoSocial)
    {
        // Tentar extrair UF do nome do órgão
        // Ex: "PREFEITURA MUNICIPAL DE SÃO PAULO - SP"
        $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

        foreach ($ufs as $uf) {
            if (preg_match("/[-\/\s]{$uf}[\s\]]/i", $razaoSocial) || str_ends_with(strtoupper($razaoSocial), $uf)) {
                return $uf;
            }
        }

        return null;
    }

    private function showEstatisticas()
    {
        $total = ContratoPNCP::count();
        $ultimos7Dias = ContratoPNCP::where('sincronizado_em', '>=', now()->subDays(7))->count();
        $porTipo = DB::table('contratos_pncp')
            ->select('tipo', DB::raw('count(*) as total'))
            ->groupBy('tipo')
            ->get();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de registros', number_format($total, 0, ',', '.')],
                ['Sincronizados (últimos 7 dias)', number_format($ultimos7Dias, 0, ',', '.')],
            ]
        );

        $this->table(
            ['Tipo', 'Quantidade'],
            $porTipo->map(fn($item) => [
                $item->tipo,
                number_format($item->total, 0, ',', '.')
            ])->toArray()
        );
    }
}
