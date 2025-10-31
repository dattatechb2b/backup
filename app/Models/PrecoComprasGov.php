<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Model para Preços do Compras.gov (API de Preços Praticados)
 * Tabela: cp_precos_comprasgov
 * Fonte: API Compras.gov - Últimos 12 meses
 * Sincronização híbrida: Base local (rápida) + API fallback (cobertura completa)
 */
class PrecoComprasGov extends Model
{
    // IMPORTANTE: Usar conexão 'pgsql_main' que SEMPRE aponta para o banco principal
    // onde estão os dados compartilhados (CATMAT, CMED, Compras.gov) independente do tenant
    protected $connection = 'pgsql_main';

    // Tabela COM o prefixo 'cp_' explícito (não há mais prefixo automático)
    protected $table = 'cp_precos_comprasgov';

    // Desabilitar timestamps automáticos do Eloquent
    public $timestamps = false;

    protected $fillable = [
        'catmat_codigo',
        'descricao_item',
        'preco_unitario',
        'quantidade',
        'unidade_fornecimento',
        'fornecedor_nome',
        'fornecedor_cnpj',
        'orgao_nome',
        'orgao_codigo',
        'orgao_uf',
        'municipio',
        'uf',
        'data_compra',
        'sincronizado_em',
        'created_at'
    ];

    protected $casts = [
        'preco_unitario' => 'decimal:2',
        'quantidade' => 'decimal:3',
        'data_compra' => 'date',
        'sincronizado_em' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Buscar preços por termo de descrição
     * SINCRONIZAÇÃO HÍBRIDA: Base local (rápida) + API fallback
     *
     * @param string $termo Termo de busca
     * @param int|null $limit Limite de resultados (null = sem limite)
     * @return \Illuminate\Support\Collection
     */
    public static function buscarPorTermo($termo, $limit = null)
    {
        if (strlen($termo) < 3) {
            return collect([]);
        }

        $query = self::where('descricao_item', 'ILIKE', "%{$termo}%")
            ->where('preco_unitario', '>', 0) // Excluir preços nulos E zerados
            ->orderBy('data_compra', 'DESC')
            ->orderBy('preco_unitario', 'ASC');

        // Aplicar limite apenas se especificado
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Buscar preços por código CATMAT
     * SINCRONIZAÇÃO HÍBRIDA: Base local (rápida) + API fallback
     *
     * @param string $catmatCodigo Código CATMAT
     * @param int|null $limit Limite de resultados (null = sem limite)
     * @return \Illuminate\Support\Collection
     */
    public static function buscarPorCATMAT($catmatCodigo, $limit = null)
    {
        $query = self::where('catmat_codigo', $catmatCodigo)
            ->where('preco_unitario', '>', 0) // Excluir preços nulos E zerados
            ->orderBy('data_compra', 'DESC')
            ->orderBy('preco_unitario', 'ASC');

        // Aplicar limite apenas se especificado
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Buscar preços por CNPJ do fornecedor
     *
     * @param string $cnpj CNPJ do fornecedor
     * @param int|null $limit Limite de resultados (null = sem limite)
     * @return \Illuminate\Support\Collection
     */
    public static function buscarPorFornecedor($cnpj, $limit = null)
    {
        $query = self::where('fornecedor_cnpj', $cnpj)
            ->where('preco_unitario', '>', 0) // Excluir preços nulos E zerados
            ->orderBy('data_compra', 'DESC');

        // Aplicar limite apenas se especificado
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Obter estatísticas da base Compras.gov
     */
    public static function obterEstatisticas()
    {
        return [
            'total_precos' => self::count(),
            'total_com_preco_valido' => self::where('preco_unitario', '>', 0)->count(),
            'total_fornecedores' => self::distinct('fornecedor_cnpj')->whereNotNull('fornecedor_cnpj')->count('fornecedor_cnpj'),
            'total_orgaos' => self::distinct('orgao_codigo')->whereNotNull('orgao_codigo')->count('orgao_codigo'),
            'total_codigos_catmat' => self::distinct('catmat_codigo')->count('catmat_codigo'),
            'preco_medio' => self::where('preco_unitario', '>', 0)->avg('preco_unitario'),
            'preco_minimo' => self::where('preco_unitario', '>', 0)->min('preco_unitario'),
            'preco_maximo' => self::where('preco_unitario', '>', 0)->max('preco_unitario'),
            'ultima_sincronizacao' => self::max('sincronizado_em'),
            'compra_mais_recente' => self::max('data_compra')
        ];
    }

    /**
     * Formatar dados para exibição no Mapa de Fornecedores
     * Retorna fornecedores que forneceram o produto pesquisado
     *
     * @param string $termo Termo de busca
     * @param int|null $limit Limite de resultados (null = sem limite)
     */
    public static function formatarParaMapaFornecedores($termo, $limit = null)
    {
        $precos = self::buscarPorTermo($termo, $limit);

        $resultado = [];

        foreach ($precos as $preco) {
            // Agrupar por fornecedor CNPJ
            $cnpj = $preco->fornecedor_cnpj ?: 'SEM_CNPJ_' . uniqid();

            if (!isset($resultado[$cnpj])) {
                $resultado[$cnpj] = [
                    'cnpj' => $preco->fornecedor_cnpj,
                    'razao_social' => $preco->fornecedor_nome,
                    'nome_fantasia' => $preco->fornecedor_nome,
                    'telefone' => null,
                    'email' => null,
                    'logradouro' => null,
                    'numero' => null,
                    'complemento' => null,
                    'bairro' => null,
                    'cidade' => $preco->municipio,
                    'uf' => $preco->uf,
                    'cep' => null,
                    'origem' => 'COMPRAS.GOV',
                    'produtos' => []
                ];
            }

            $resultado[$cnpj]['produtos'][] = [
                'descricao' => $preco->descricao_item,
                'valor' => $preco->preco_unitario,
                'unidade' => $preco->unidade_fornecimento ?? 'UN',
                'quantidade' => $preco->quantidade ?? 1,
                'data' => $preco->data_compra ? $preco->data_compra->format('Y-m-d') : null,
                'orgao' => $preco->orgao_nome,
                'orgao_codigo' => $preco->orgao_codigo,
                'municipio_orgao' => $preco->municipio,
                'uf_orgao' => $preco->orgao_uf,
                'catmat' => $preco->catmat_codigo,
            ];
        }

        return array_values($resultado);
    }

    /**
     * Formatar dados para exibição no Modal de Cotação
     * Retorna dados no formato esperado pelo frontend
     *
     * @param string $termo Termo de busca
     * @param int|null $limit Limite de resultados (null = sem limite)
     */
    public static function formatarParaModalCotacao($termo, $limit = null)
    {
        $precos = self::buscarPorTermo($termo, $limit);

        return $precos->map(function($preco) {
            return [
                'id' => 'comprasgov_local_' . $preco->id,
                'descricao' => $preco->descricao_item,
                'laboratorio' => $preco->fornecedor_nome,
                'valor_unitario' => (float) $preco->preco_unitario,
                'unidade' => $preco->unidade_fornecimento ?? 'UN',
                'unidade_medida' => $preco->unidade_fornecimento ?? 'UN',
                'medida_fornecimento' => $preco->unidade_fornecimento ?? 'UN',
                'quantidade' => (float) ($preco->quantidade ?? 1),
                'fonte' => 'COMPRAS.GOV',
                'orgao' => $preco->orgao_nome,
                'orgao_nome' => $preco->orgao_nome,
                'orgao_codigo' => $preco->orgao_codigo,
                'orgao_uf' => $preco->orgao_uf,
                'data' => $preco->data_compra ? $preco->data_compra->format('d/m/Y') : null,
                'data_publicacao' => $preco->data_compra ? $preco->data_compra->format('Y-m-d') : null,
                'municipio' => $preco->municipio,
                'municipio_nome' => $preco->municipio,
                'uf' => $preco->uf,
                'uf_sigla' => $preco->uf,
                'marca' => $preco->fornecedor_nome,
                'razao_social_fornecedor' => $preco->fornecedor_nome,
                'cnpj_fornecedor' => $preco->fornecedor_cnpj,
                'catmat' => $preco->catmat_codigo,
                'cnpj' => $preco->fornecedor_cnpj
            ];
        })->toArray();
    }

    /**
     * Verificar se há dados na base local para um termo específico
     * Usado para decidir se precisa fazer fallback para API
     *
     * @param string $termo Termo de busca
     * @return bool
     */
    public static function temDadosLocais($termo)
    {
        if (strlen($termo) < 3) {
            return false;
        }

        return self::where('descricao_item', 'ILIKE', "%{$termo}%")
            ->where('preco_unitario', '>', 0)
            ->exists();
    }

    /**
     * Verificar se há dados na base local para um código CATMAT
     * Usado para decidir se precisa fazer fallback para API
     *
     * @param string $catmatCodigo Código CATMAT
     * @return bool
     */
    public static function temDadosCATMAT($catmatCodigo)
    {
        return self::where('catmat_codigo', $catmatCodigo)
            ->where('preco_unitario', '>', 0)
            ->exists();
    }
}
