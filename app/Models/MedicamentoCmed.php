<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model para Medicamentos CMED (Câmara de Regulação do Mercado de Medicamentos)
 * Tabela: cp_medicamentos_cmed
 * Fonte: Tabelas CMED (SimTax) - Julho 2025
 * Total de registros: ~26.000 medicamentos
 */
class MedicamentoCmed extends Model
{
    // IMPORTANTE: Usar conexão 'pgsql_main' que SEMPRE aponta para o banco principal
    // onde estão os dados compartilhados (CATMAT, CMED) independente do tenant
    protected $connection = 'pgsql_main';

    // Tabela SEM o prefixo 'cp_' pois o Laravel adiciona automaticamente via config
    protected $table = 'cp_medicamentos_cmed';

    // Desabilitar timestamps automáticos do Eloquent
    public $timestamps = true;

    protected $fillable = [
        'substancia',
        'cnpj_laboratorio',
        'laboratorio',
        'ean1',
        'produto',
        'pmc_0',
        'pmc_12',
        'pmc_17',
        'pmc_18',
        'pmc_20',
        'mes_referencia',
        'data_importacao'
    ];

    protected $casts = [
        'pmc_0' => 'decimal:2',
        'pmc_12' => 'decimal:2',
        'pmc_17' => 'decimal:2',
        'pmc_18' => 'decimal:2',
        'pmc_20' => 'decimal:2',
        'data_importacao' => 'datetime',
    ];

    /**
     * Buscar medicamentos por termo (nome do produto ou substância)
     *
     * @param string $termo Termo de busca
     * @param int|null $limit Limite de resultados (null = sem limite, retorna TODOS)
     */
    public static function buscarPorTermo($termo, $limit = null)
    {
        if (strlen($termo) < 3) {
            return collect([]);
        }

        $query = self::where(function($query) use ($termo) {
            $query->where('produto', 'ILIKE', "%{$termo}%")
                  ->orWhere('substancia', 'ILIKE', "%{$termo}%");
        })
        ->where('pmc_0', '>', 0) // Excluir preços nulos E zerados
        ->orderBy('produto', 'ASC');

        // Aplicar limite apenas se especificado
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Buscar medicamentos por EAN (código de barras)
     */
    public static function buscarPorEan($ean)
    {
        return self::where('ean1', $ean)
            ->where('pmc_0', '>', 0) // Excluir preços nulos E zerados
            ->get();
    }

    /**
     * Buscar medicamentos por laboratório (CNPJ ou nome)
     *
     * @param string $laboratorio Termo de busca
     * @param int|null $limit Limite de resultados (null = sem limite, retorna TODOS)
     */
    public static function buscarPorLaboratorio($laboratorio, $limit = null)
    {
        $query = self::where(function($query) use ($laboratorio) {
            $query->where('laboratorio', 'ILIKE', "%{$laboratorio}%")
                  ->orWhere('cnpj_laboratorio', 'LIKE', "%{$laboratorio}%");
        })
        ->where('pmc_0', '>', 0) // Excluir preços nulos E zerados
        ->orderBy('produto', 'ASC');

        // Aplicar limite apenas se especificado
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Obter estatísticas da base CMED
     */
    public static function obterEstatisticas()
    {
        return [
            'total_medicamentos' => self::count(),
            'total_com_preco' => self::whereNotNull('pmc_0')->count(),
            'total_laboratorios' => self::distinct('laboratorio')->count('laboratorio'),
            'total_substancias' => self::distinct('substancia')->count('substancia'),
            'preco_medio' => self::whereNotNull('pmc_0')->avg('pmc_0'),
            'preco_minimo' => self::whereNotNull('pmc_0')->min('pmc_0'),
            'preco_maximo' => self::whereNotNull('pmc_0')->max('pmc_0'),
            'mes_referencia' => self::first()->mes_referencia ?? 'N/A'
        ];
    }

    /**
     * Formatar dados para exibição no Mapa de Fornecedores
     * Retorna medicamentos no formato compatível com fornecedores
     *
     * @param string $termo Termo de busca
     * @param int|null $limit Limite de resultados (null = sem limite, retorna TODOS)
     */
    public static function formatarParaMapaFornecedores($termo, $limit = null)
    {
        $medicamentos = self::buscarPorTermo($termo, $limit);

        $resultado = [];

        foreach ($medicamentos as $medicamento) {
            // Agrupar por laboratório
            $cnpj = $medicamento->cnpj_laboratorio ?: 'SEM_CNPJ_' . uniqid();

            if (!isset($resultado[$cnpj])) {
                $resultado[$cnpj] = [
                    'cnpj' => $medicamento->cnpj_laboratorio,
                    'razao_social' => $medicamento->laboratorio,
                    'nome_fantasia' => $medicamento->laboratorio,
                    'telefone' => null,
                    'email' => null,
                    'logradouro' => null,
                    'numero' => null,
                    'complemento' => null,
                    'bairro' => null,
                    'cidade' => 'Brasília',
                    'uf' => 'DF',
                    'cep' => null,
                    'origem' => 'CMED',
                    'produtos' => []
                ];
            }

            $resultado[$cnpj]['produtos'][] = [
                'descricao' => $medicamento->produto . ' - ' . $medicamento->substancia,
                'valor' => $medicamento->pmc_0,
                'unidade' => 'UN',
                'data' => $medicamento->data_importacao ? $medicamento->data_importacao->format('Y-m-d') : null,
                'orgao' => 'ANVISA/CMED - ' . ($medicamento->mes_referencia ?? 'Julho 2025'),
                'municipio_orgao' => 'Brasília',
                'uf_orgao' => 'DF',
                'ean' => $medicamento->ean1,
                'pmc_12' => $medicamento->pmc_12,
                'pmc_17' => $medicamento->pmc_17,
                'pmc_18' => $medicamento->pmc_18,
                'pmc_20' => $medicamento->pmc_20
            ];
        }

        return array_values($resultado);
    }
}
