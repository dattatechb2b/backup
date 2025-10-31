<?php

namespace App\Console\Commands;

use App\Models\MedicamentoCmed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportarCmed extends Command
{
    /**
     * Comando para importar medicamentos da Tabela CMED (Câmara de Regulação do Mercado de Medicamentos)
     *
     * @var string
     */
    protected $signature = 'cmed:import
                            {arquivo? : Caminho do arquivo Excel (opcional)}
                            {--mes= : Mês de referência (ex: Outubro 2025)}
                            {--limpar : Limpar tabela antes de importar}
                            {--teste=0 : Importar apenas N linhas para teste}';

    protected $description = 'Importa medicamentos da Tabela CMED (Excel) para o banco de dados';

    /**
     * Mapeamento de colunas Excel → Banco de Dados
     *
     * A planilha CMED tem 74 colunas (A até BV)
     */
    private $mapeamentoColunas = [
        'B' => 'substancia',          // Coluna B (não A)
        'C' => 'cnpj_laboratorio',
        'D' => 'laboratorio',
        'E' => 'codigo_ggrem',
        'F' => 'registro',
        'G' => 'ean1',
        'H' => 'ean2',
        'I' => 'ean3',
        'J' => 'produto',
        'K' => 'apresentacao',
        'L' => 'classe_terapeutica',
        'M' => 'tipo_produto',
        'N' => 'regime_preco',
        // Preços PF (Preço Fábrica) - Deslocados +1 coluna
        'O' => 'pf_sem_impostos',
        'P' => 'pf_0',
        'Q' => 'pf_12',
        'R' => 'pf_12_sem_icms',
        'S' => 'pf_13',
        'T' => 'pf_13_com_icms',
        'U' => 'pf_14',
        'V' => 'pf_15',
        'W' => 'pf_15_com_icms',
        'X' => 'pf_16',
        'Y' => 'pf_17',
        'Z' => 'pf_17_alagas',
        'AA' => 'pf_17_com_icms',
        'AB' => 'pf_18',
        'AC' => 'pf_18_com_icms',
        'AD' => 'pf_19',
        'AE' => 'pf_19_com_icms',
        'AF' => 'pf_20',
        'AG' => 'pf_20_com_icms',
        'AH' => 'pf_21',
        'AI' => 'pf_22',
        'AJ' => 'pf_23',
        // Preços PMC (Preço Máximo ao Consumidor) - Deslocados +1 coluna
        'AK' => 'pmc_sem_impostos',
        'AL' => 'pmc_0',
        'AM' => 'pmc_12',
        'AN' => 'pmc_12_sem_icms',
        'AO' => 'pmc_13',
        'AP' => 'pmc_13_com_icms',
        'AQ' => 'pmc_14',
        'AR' => 'pmc_15',
        'AS' => 'pmc_15_com_icms',
        'AT' => 'pmc_16',
        'AU' => 'pmc_17',
        'AV' => 'pmc_17_alagas',
        'AW' => 'pmc_17_com_icms',
        'AX' => 'pmc_18',
        'AY' => 'pmc_18_com_icms',
        'AZ' => 'pmc_19',
        'BA' => 'pmc_19_com_icms',
        'BB' => 'pmc_20',
        'BC' => 'pmc_20_com_icms',
        'BD' => 'pmc_21',
        'BE' => 'pmc_22',
        'BF' => 'pmc_23',
        // Dados Tributários e Regulatórios - Deslocados +1 coluna
        'BG' => 'restricao_hospitalar',
        'BH' => 'cap',
        'BI' => 'confaz',
        'BJ' => 'icms_0',
        'BK' => 'analise_recursal',
        'BL' => 'lista_concessao_credito',
        'BM' => 'comercializacao_2024',
        'BN' => 'taxa_anvisa',
    ];

    public function handle()
    {
        $this->info('💊 ========================================');
        $this->info('💊 IMPORTADOR DE MEDICAMENTOS CMED');
        $this->info('💊 ========================================');
        $this->newLine();

        // 1. Determinar arquivo a importar
        $arquivo = $this->argument('arquivo');

        if (!$arquivo) {
            // Usar arquivo mais recente (Outubro 2025)
            $arquivo = base_path('CMED Outubro 25 - Modificada.xlsx');
            $this->info("📁 Arquivo não especificado. Usando padrão:");
            $this->line("   {$arquivo}");
        }

        // Validar existência do arquivo
        if (!file_exists($arquivo)) {
            $this->error("❌ Arquivo não encontrado: {$arquivo}");
            return 1;
        }

        $tamanhoMB = round(filesize($arquivo) / 1024 / 1024, 2);
        $this->info("📊 Tamanho do arquivo: {$tamanhoMB} MB");
        $this->newLine();

        // 2. Determinar mês de referência
        $mesReferencia = $this->option('mes');
        if (!$mesReferencia) {
            // Extrair do nome do arquivo
            if (preg_match('/(Janeiro|Fevereiro|Março|Abril|Maio|Junho|Julho|Agosto|Setembro|Outubro|Novembro|Dezembro)\s+(\d{2})/i', $arquivo, $matches)) {
                $mesReferencia = $matches[1] . ' 20' . $matches[2];
            } else {
                $mesReferencia = now()->translatedFormat('F Y');
            }
        }
        $this->info("📅 Mês de referência: {$mesReferencia}");

        // 3. Opção de limpar tabela
        if ($this->option('limpar')) {
            if ($this->confirm('⚠️  Deseja realmente LIMPAR toda a tabela antes de importar?', false)) {
                $this->warn('🗑️  Limpando tabela cp_medicamentos_cmed...');
                MedicamentoCmed::truncate();
                $this->info('✅ Tabela limpa!');
            }
        }

        // 4. Carregar Excel
        $this->newLine();
        $this->info('📖 Carregando arquivo Excel...');

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($arquivo);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();

            $this->info("📊 Linhas encontradas: " . ($highestRow - 1) . " (excluindo cabeçalho)");
            $this->info("📊 Colunas encontradas: {$highestColumn}");

        } catch (\Exception $e) {
            $this->error("❌ Erro ao carregar arquivo: " . $e->getMessage());
            Log::error('Erro ao carregar CMED Excel: ' . $e->getMessage());
            return 1;
        }

        // 5. Importar dados
        $this->newLine();
        $this->info('💉 Iniciando importação...');
        $this->newLine();

        $totalLinhas = $highestRow - 5; // Cabeçalho na linha 5, dados começam na linha 6
        $limiteTeste = (int) $this->option('teste');

        if ($limiteTeste > 0) {
            $this->warn("⚠️  MODO TESTE: Importando apenas {$limiteTeste} linhas");
            $totalLinhas = min($totalLinhas, $limiteTeste);
        }

        $progressBar = $this->output->createProgressBar($totalLinhas);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $progressBar->setMessage('Iniciando...');
        $progressBar->start();

        $inseridos = 0;
        $erros = 0;
        $chunk = [];
        $chunkSize = 5000; // Aumentado de 1000 para 5000

        // Começar da linha 6 (cabeçalho está na linha 5)
        for ($linha = 6; $linha <= ($totalLinhas + 5); $linha++) {
            try {
                $dados = $this->extrairDadosLinha($worksheet, $linha);

                // Validações básicas
                if (empty($dados['produto']) || empty($dados['substancia'])) {
                    $erros++;
                    $progressBar->advance();
                    continue;
                }

                // Adicionar campos de controle
                $dados['mes_referencia'] = $mesReferencia;
                $dados['data_importacao'] = now()->format('Y-m-d');

                $chunk[] = $dados;

                // Inserir em lote quando atingir o tamanho do chunk
                if (count($chunk) >= $chunkSize) {
                    DB::connection('pgsql_main')->table('cp_medicamentos_cmed')->insert($chunk);
                    $inseridos += count($chunk);
                    $chunk = [];
                    $progressBar->setMessage("Inseridos: {$inseridos}");
                }

                // Atualizar barra apenas a cada 100 linhas (mais rápido)
                if ($linha % 100 == 0) {
                    $progressBar->setProgress($linha - 6);
                }

            } catch (\Exception $e) {
                $erros++;
                // Log silencioso (não logar cada erro para não travar)
                continue;
            }
        }

        // Inserir chunk restante
        if (count($chunk) > 0) {
            DB::connection('pgsql_main')->table('cp_medicamentos_cmed')->insert($chunk);
            $inseridos += count($chunk);
        }

        $progressBar->setMessage('Concluído!');
        $progressBar->finish();

        // 6. Resultados
        $this->newLine(2);
        $this->info('✅ ========================================');
        $this->info('✅ IMPORTAÇÃO CONCLUÍDA!');
        $this->info('✅ ========================================');
        $this->newLine();
        $this->info("📊 Medicamentos inseridos: {$inseridos}");
        $this->info("❌ Erros/Ignorados: {$erros}");
        $this->newLine();

        // 7. Estatísticas do banco
        $this->showEstatisticas();

        return 0;
    }

    /**
     * Extrai dados de uma linha da planilha Excel
     */
    private function extrairDadosLinha($worksheet, $linha): array
    {
        $dados = [];

        foreach ($this->mapeamentoColunas as $colExcel => $campoDb) {
            $valor = $worksheet->getCell($colExcel . $linha)->getValue();

            // Processar conforme o tipo de campo
            if (str_starts_with($campoDb, 'pf_') || str_starts_with($campoDb, 'pmc_') || $campoDb === 'taxa_anvisa') {
                // Preços: converter para decimal
                $dados[$campoDb] = $this->parseDecimal($valor);

            } elseif (in_array($campoDb, ['restricao_hospitalar', 'cap', 'confaz', 'icms_0'])) {
                // Booleanos: converter SIM/NÃO para true/false
                $dados[$campoDb] = $this->parseBoolean($valor);

            } else {
                // Strings: limpar e sanitizar
                $dados[$campoDb] = $this->sanitizeString($valor);
            }
        }

        return $dados;
    }

    /**
     * Converte valor para decimal (preços)
     */
    private function parseDecimal($valor): ?float
    {
        if (empty($valor) || $valor === '-' || $valor === 'N/A') {
            return null;
        }

        // Remover "R$", espaços, e converter vírgula para ponto
        $valor = str_replace(['R$', ' ', '.'], '', $valor);
        $valor = str_replace(',', '.', $valor);

        return is_numeric($valor) ? (float) $valor : null;
    }

    /**
     * Converte SIM/NÃO para boolean
     */
    private function parseBoolean($valor): bool
    {
        if (empty($valor)) {
            return false;
        }

        $valor = strtoupper(trim($valor));
        return in_array($valor, ['SIM', 'S', 'TRUE', '1', 'YES']);
    }

    /**
     * Sanitiza strings
     */
    private function sanitizeString($valor): ?string
    {
        if (empty($valor) || $valor === '-' || $valor === 'N/A') {
            return null;
        }

        return trim($valor);
    }

    /**
     * Exibe estatísticas do banco de dados
     */
    private function showEstatisticas()
    {
        $this->info('📊 ESTATÍSTICAS DO BANCO:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $total = MedicamentoCmed::count();
        $genericos = MedicamentoCmed::where('tipo_produto', 'LIKE', '%Genérico%')->count();
        $similares = MedicamentoCmed::where('tipo_produto', 'LIKE', '%Similar%')->count();
        $referencia = MedicamentoCmed::where('tipo_produto', 'LIKE', '%Referência%')->count();

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de medicamentos', number_format($total, 0, ',', '.')],
                ['Genéricos', number_format($genericos, 0, ',', '.')],
                ['Similares', number_format($similares, 0, ',', '.')],
                ['Referência', number_format($referencia, 0, ',', '.')],
            ]
        );

        // Laboratórios únicos
        $laboratorios = MedicamentoCmed::distinct('laboratorio')->count('laboratorio');
        $this->info("🏭 Laboratórios únicos: " . number_format($laboratorios, 0, ',', '.'));

        // Preço médio
        $precoMedio = MedicamentoCmed::whereNotNull('pmc_0')->avg('pmc_0');
        $this->info("💰 Preço médio (PMC_0): R$ " . number_format($precoMedio, 2, ',', '.'));

        $this->newLine();
    }
}
