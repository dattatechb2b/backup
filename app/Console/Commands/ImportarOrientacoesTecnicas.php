<?php

namespace App\Console\Commands;

use App\Models\OrientacaoTecnica;
use Illuminate\Console\Command;
use DOMDocument;
use DOMXPath;

class ImportarOrientacoesTecnicas extends Command
{
    protected $signature = 'orientacoes:importar {--limpar : Limpar orientações existentes antes de importar}';

    protected $description = 'Importa Orientações Técnicas do arquivo HTML para o banco de dados';

    public function handle()
    {
        $this->info('🚀 Iniciando importação de Orientações Técnicas...');

        // Limpar dados existentes se solicitado
        if ($this->option('limpar')) {
            $this->warn('⚠️  Limpando orientações existentes...');
            OrientacaoTecnica::truncate();
        }

        $arquivoHTML = base_path('orientacao/Orientações técnicas.html');

        if (!file_exists($arquivoHTML)) {
            $this->error('❌ Arquivo não encontrado: ' . $arquivoHTML);
            return 1;
        }

        $this->info('📂 Lendo arquivo HTML...');
        $html = file_get_contents($arquivoHTML);

        // Criar DOMDocument
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        // Buscar todos os itens de lista (considerando atributos Vue.js)
        $items = $xpath->query("//div[contains(@class, 'lista-item')]");

        if ($items->length == 0) {
            $this->error('❌ Nenhuma orientação encontrada no HTML');
            return 1;
        }

        $this->info("📋 Encontradas {$items->length} orientações no arquivo");

        $progressBar = $this->output->createProgressBar($items->length);
        $progressBar->start();

        $importadas = 0;
        $erros = 0;

        foreach ($items as $index => $item) {
            try {
                // Extrair header (título) - usando contains para suportar atributos Vue.js
                $headerNode = $xpath->query(".//div[contains(@class, 'lista-item-header')]", $item)->item(0);
                if (!$headerNode) {
                    $this->newLine();
                    $this->warn("⚠️  Item {$index}: Header não encontrado, pulando...");
                    $erros++;
                    continue;
                }

                $headerText = trim($headerNode->textContent);

                // Extrair número e título usando regex
                if (!preg_match('/OT\s+(\d{3})\s+-\s+(.+)/', $headerText, $matches)) {
                    $this->newLine();
                    $this->warn("⚠️  Item {$index}: Formato de header inválido: {$headerText}");
                    $erros++;
                    continue;
                }

                $numero = 'OT ' . $matches[1];
                $titulo = trim($matches[2]);

                // Extrair conteúdo - usando contains para suportar atributos Vue.js
                $contentNode = $xpath->query(".//div[contains(@class, 'lista-item-content-inner')]", $item)->item(0);
                if (!$contentNode) {
                    $this->newLine();
                    $this->warn("⚠️  {$numero}: Conteúdo não encontrado, pulando...");
                    $erros++;
                    continue;
                }

                // Salvar conteúdo HTML completo
                $conteudo = '';
                foreach ($contentNode->childNodes as $child) {
                    $conteudo .= $dom->saveHTML($child);
                }

                // Limpar conteúdo (remover espaços extras)
                $conteudo = trim($conteudo);

                // Criar ou atualizar orientação
                OrientacaoTecnica::updateOrCreate(
                    ['numero' => $numero],
                    [
                        'titulo' => $titulo,
                        'conteudo' => $conteudo,
                        'ordem' => $index + 1,
                        'ativo' => true
                    ]
                );

                $importadas++;

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Erro ao importar item {$index}: " . $e->getMessage());
                $erros++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Resumo
        $this->info("✅ Importação concluída!");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Orientações importadas', $importadas],
                ['Erros', $erros],
                ['Total no banco', OrientacaoTecnica::count()]
            ]
        );

        return 0;
    }
}
