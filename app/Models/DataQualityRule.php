<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataQualityRule extends Model
{
    protected $table = 'cp_data_quality_rules';

    protected $fillable = [
        'fonte',
        'entidade',
        'campo',
        'regra',
        'parametros',
        'severidade',
        'mensagem_erro',
        'ativa',
        'ordem',
    ];

    protected $casts = [
        'parametros' => 'json',
        'ativa' => 'boolean',
        'ordem' => 'integer',
    ];

    /**
     * Scope para regras ativas
     */
    public function scopeAtivas($query)
    {
        return $query->where('ativa', true);
    }

    /**
     * Scope por fonte
     */
    public function scopePorFonte($query, string $fonte)
    {
        return $query->where(function($q) use ($fonte) {
            $q->where('fonte', $fonte)
              ->orWhere('fonte', 'ALL');
        });
    }

    /**
     * Scope por entidade
     */
    public function scopePorEntidade($query, string $entidade)
    {
        return $query->where('entidade', $entidade);
    }

    /**
     * Scope por severidade
     */
    public function scopePorSeveridade($query, string $severidade)
    {
        return $query->where('severidade', $severidade);
    }
}
