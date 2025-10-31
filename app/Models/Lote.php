<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lote extends Model
{
    

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cp_lotes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'orcamento_id',
        'numero',
        'nome',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'numero' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento: Lote pertence a um orçamento
     */
    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }

    /**
     * Relacionamento: Lote tem muitos itens
     */
    public function itens()
    {
        return $this->hasMany(OrcamentoItem::class, 'lote_id');
    }
}
