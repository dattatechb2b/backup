<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratacaoSimilar extends Model
{
    protected $table = 'cp_contratacoes_similares';

    protected $fillable = [
        'orcamento_id',
        'ente_publico',
        'tipo',
        'numero_processo',
        'eh_registro_precos',
        'data_publicacao',
        'local_publicacao',
        'link_oficial',
        'arquivo_pdf',
        'arquivo_hash',
        'arquivo_tamanho',
        'data_coleta',
        'usuario_coleta',
        // Campos adicionados em 14/10/2025 para PDF formal
        'tipo_fonte',
        'origem_sistema',
        'codigo_identificacao',
        'lote_item_fonte',
        'fornecedor_nome',
        'fornecedor_cnpj',
        'marca',
        'situacao',
        'justificativa_expurgo',
    ];

    protected $casts = [
        'eh_registro_precos' => 'boolean',
        'data_publicacao' => 'date',
        'data_coleta' => 'datetime',
        'arquivo_tamanho' => 'integer',
    ];

    /**
     * Relacionamento com Orçamento
     */
    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class);
    }

    /**
     * Relacionamento com itens da contratação
     */
    public function itens()
    {
        return $this->hasMany(ContratacaoSimilarItem::class, 'contratacao_similar_id');
    }
}
