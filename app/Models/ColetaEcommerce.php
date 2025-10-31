<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColetaEcommerce extends Model
{
    protected $table = 'cp_coletas_ecommerce';

    protected $fillable = [
        'orcamento_id',
        'nome_site',
        'url_site',
        'eh_intermediacao',
        'data_consulta',
        'hora_consulta',
        'inclui_frete',
        'arquivo_print',
    ];

    protected $casts = [
        'eh_intermediacao' => 'boolean',
        'inclui_frete' => 'boolean',
        'data_consulta' => 'date',
    ];

    // Relacionamentos
    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function itens()
    {
        return $this->hasMany(ColetaEcommerceItem::class);
    }
}
