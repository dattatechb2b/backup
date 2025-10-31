<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColetaEcommerceItem extends Model
{
    protected $table = 'cp_coleta_ecommerce_itens';

    protected $fillable = [
        'coleta_ecommerce_id',
        'orcamento_item_id',
        'preco_unitario',
        'preco_total',
    ];

    protected $casts = [
        'preco_unitario' => 'decimal:2',
        'preco_total' => 'decimal:2',
    ];

    // Relacionamentos
    public function coleta()
    {
        return $this->belongsTo(ColetaEcommerce::class, 'coleta_ecommerce_id');
    }

    public function item()
    {
        return $this->belongsTo(OrcamentoItem::class, 'orcamento_item_id');
    }
}
