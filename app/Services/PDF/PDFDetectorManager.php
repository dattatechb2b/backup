<?php

namespace App\Services\PDF;

use App\Services\PDF\Detectores\MapaApuracaoDetector;
use App\Services\PDF\Detectores\TabelaHorizontalDetector;
use App\Services\PDF\Detectores\GenericoDetector;

/**
 * Gerenciador de detectores de formato de PDF
 * Testa cada detector por ordem de prioridade
 */
class PDFDetectorManager
{
    private array $detectores = [];

    public function __construct()
    {
        // Registrar detectores (ordem não importa, prioridade sim)
        $this->registrarDetector(new MapaApuracaoDetector());
        $this->registrarDetector(new TabelaHorizontalDetector());
        $this->registrarDetector(new GenericoDetector()); // Sempre por último
    }

    /**
     * Registra um novo detector
     */
    public function registrarDetector(FormatoDetector $detector): void
    {
        $this->detectores[] = $detector;
    }

    /**
     * Detecta formato e extrai dados
     *
     * @param array $linhas Linhas do texto extraído do PDF
     * @return array ['formato' => string, 'dados' => array]
     */
    public function detectarEExtrair(array $linhas): array
    {
        \Log::info('PDFDetectorManager: Iniciando detecção', [
            'total_linhas' => count($linhas),
            'total_detectores' => count($this->detectores)
        ]);

        // Ordenar detectores por prioridade (maior primeiro)
        usort($this->detectores, function($a, $b) {
            return $b->getPrioridade() - $a->getPrioridade();
        });

        // Testar cada detector
        foreach ($this->detectores as $detector) {
            \Log::info('PDFDetectorManager: Testando detector', [
                'nome' => $detector->getNome(),
                'prioridade' => $detector->getPrioridade()
            ]);

            if ($detector->detectar($linhas)) {
                \Log::info('PDFDetectorManager: Formato detectado!', [
                    'formato' => $detector->getNome()
                ]);

                // Usar extrator correspondente
                $extrator = $detector->getExtrator();
                $dados = $extrator->extrair($linhas);

                return [
                    'formato' => $detector->getNome(),
                    'dados' => $dados
                ];
            }
        }

        // Nunca deve chegar aqui (GenericoDetector sempre retorna true)
        \Log::warning('PDFDetectorManager: Nenhum detector reconheceu o formato!');

        return [
            'formato' => 'Desconhecido',
            'dados' => ['itens' => [], 'fornecedores' => [], 'valor_total_geral' => 0, 'lotes' => []]
        ];
    }

    /**
     * Lista formatos suportados
     */
    public function listarFormatos(): array
    {
        return array_map(function($detector) {
            return [
                'nome' => $detector->getNome(),
                'prioridade' => $detector->getPrioridade()
            ];
        }, $this->detectores);
    }
}
