<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FornecedorItem extends Model
{
    protected $table = 'cp_fornecedor_itens';

    protected $fillable = [
        'fornecedor_id',
        'descricao',
        'codigo_catmat',
        'unidade',
        'preco_referencia',
    ];

    protected $casts = [
        'preco_referencia' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento: Item pertence a um Fornecedor
     */
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }
}
