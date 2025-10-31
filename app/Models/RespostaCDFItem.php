<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespostaCDFItem extends Model
{
    

    protected $table = 'cp_resposta_cdf_itens';

    protected $fillable = [
        'resposta_cdf_id',
        'item_orcamento_id',
        'preco_unitario',
        'preco_total',
        'marca',
        'prazo_entrega',
        'observacoes',
    ];

    protected $casts = [
        'preco_unitario' => 'decimal:2',
        'preco_total' => 'decimal:2',
        'prazo_entrega' => 'integer',
    ];

    /**
     * Relacionamento com a resposta CDF
     */
    public function resposta()
    {
        return $this->belongsTo(RespostaCDF::class, 'resposta_cdf_id');
    }

    /**
     * Relacionamento com o item do orçamento
     */
    public function itemOrcamento()
    {
        return $this->belongsTo(OrcamentoItem::class, 'item_orcamento_id');
    }
}
