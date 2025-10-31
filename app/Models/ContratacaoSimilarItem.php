<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratacaoSimilarItem extends Model
{
    protected $table = 'cp_contratacao_similar_itens';

    protected $fillable = [
        'contratacao_similar_id',
        'orcamento_item_id',
        'descricao',
        'catmat',
        'unidade',
        'quantidade_referencia',
        'preco_unitario',
        'preco_total',
        'nivel_confianca',
    ];

    protected $casts = [
        'quantidade_referencia' => 'decimal:2',
        'preco_unitario' => 'decimal:2',
        'preco_total' => 'decimal:2',
    ];

    /**
     * Relacionamento com a contratação similar
     */
    public function contratacao()
    {
        return $this->belongsTo(ContratacaoSimilar::class, 'contratacao_similar_id');
    }

    /**
     * Relacionamento com o item do orçamento
     */
    public function item()
    {
        return $this->belongsTo(OrcamentoItem::class, 'orcamento_item_id');
    }
}
