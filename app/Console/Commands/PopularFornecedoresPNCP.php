<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContratoPNCP;
use App\Models\Fornecedor;
use App\Helpers\NormalizadorHelper;
use App\Helpers\TagsFornecedorHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PopularFornecedoresPNCP extends Command
{
    protected $signature = 'fornecedores:popular-pncp {--meses=6 : Número de meses para varrer (padrão: 6)}';
    protected $description = 'Popular tabela de fornecedores com dados únicos dos contratos PNCP (últimos 6 meses)';

    public function handle()
    {
        $meses = $this->option('meses');

        $this->info("🔄 Iniciando importação de fornecedores do PNCP (últimos {$meses} meses)...");

        // Calcular período
        $dataLimite = now()->subMonths($meses);

        $this->info("📅 Período: desde " . $dataLimite->format('d/m/Y'));
        $this->newLine();

        $importados = 0;
        $atualizados = 0;
        $erros = 0;
        $fornecedoresCache = []; // Cache para evitar duplicatas

        // Buscar contratos da base local (cp_contratos_pncp)
        $contratos = ContratoPNCP::where('data_vigencia_inicio', '>=', $dataLimite)
            ->orWhere('created_at', '>=', $dataLimite)
            ->get();

        $total = $contratos->count();
        $this->info("📦 Encontrados {$total} contratos PNCP na base local");
        $this->newLine();

        if ($total === 0) {
            $this->warn("⚠️ Nenhum contrato encontrado no período. Execute primeiro a importação de contratos PNCP.");
            return 1;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($contratos as $contrato) {
            try {
                $cnpjFornecedor = $contrato->fornecedor_cnpj;
                $razaoFornecedor = $contrato->fornecedor_razao_social;
                $objetoContrato = $contrato->objeto_contrato ?? '';
                $numeroControle = $contrato->numero_controle_pncp;

                if (!$cnpjFornecedor || !$razaoFornecedor) {
                    $bar->advance();
                    continue;
                }

                // Normalizar CNPJ
                $cnpjLimpo = NormalizadorHelper::normalizarCNPJ($cnpjFornecedor);

                // Evitar processar o mesmo CNPJ múltiplas vezes nesta execução
                if (isset($fornecedoresCache[$cnpjLimpo])) {
                    $fornecedoresCache[$cnpjLimpo]['ocorrencias']++;
                    $bar->advance();
                    continue;
                }

                // Gerar tags baseado no objeto do contrato
                $tags = TagsFornecedorHelper::gerarTags(null, $objetoContrato);

                // Buscar ou criar fornecedor
                $fornecedor = Fornecedor::where('numero_documento', $cnpjLimpo)->first();

                $fonteUrl = $numeroControle
                    ? "https://pncp.gov.br/app/contratos/{$numeroControle}"
                    : null;

                if ($fornecedor) {
                    // Atualizar fornecedor existente
                    $fornecedor->increment('ocorrencias');

                    // Merge tags (adicionar novas sem remover antigas)
                    $tagsAtuais = $fornecedor->tags_segmento ?? [];
                    $tagsNovas = array_unique(array_merge($tagsAtuais, $tags));

                    $fornecedor->update([
                        'tags_segmento' => $tagsNovas,
                        'ultima_atualizacao' => now(),
                    ]);

                    $atualizados++;
                    $fornecedoresCache[$cnpjLimpo] = ['ocorrencias' => $fornecedor->ocorrencias];
                } else {
                    // Criar novo fornecedor
                    Fornecedor::create([
                        'tipo_documento' => 'CNPJ',
                        'numero_documento' => $cnpjLimpo,
                        'razao_social' => substr($razaoFornecedor, 0, 255),
                        'logradouro' => 'Não informado',
                        'bairro' => 'Não informado',
                        'cidade' => 'Não informado',
                        'uf' => 'XX',
                        'tags_segmento' => $tags,
                        'ocorrencias' => 1,
                        'status' => 'publico_nao_verificado',
                        'fonte_url' => $fonteUrl,
                        'ultima_atualizacao' => now(),
                        'origem' => 'pncp'
                    ]);

                    $importados++;
                    $fornecedoresCache[$cnpjLimpo] = ['ocorrencias' => 1];
                }

            } catch (\Exception $e) {
                $erros++;
                Log::error("Erro ao processar contrato ID {$contrato->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);
        $this->info("✅ Importação concluída!");
        $this->info("📊 Estatísticas:");
        $this->info("   - Importados: {$importados}");
        $this->info("   - Atualizados: {$atualizados}");
        $this->info("   - Erros: {$erros}");
        $this->info("   - Total processado: " . ($importados + $atualizados));

        return 0;
    }

    private function consultarCNPJ($cnpj)
    {
        try {
            $response = Http::timeout(10)->get("https://www.receitaws.com.br/v1/cnpj/{$cnpj}");

            if ($response->successful()) {
                $data = $response->json();
                if ($data['status'] === 'OK') {
                    return $data;
                }
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }
}
