<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckpointImportacao extends Model
{
    protected $table = 'cp_checkpoint_importacao';

    protected $fillable = [
        'fonte',
        'arquivo',
        'checksum',
        'status',
        'total_registros',
        'registros_processados',
        'registros_novos',
        'registros_atualizados',
        'registros_erro',
        'ultima_linha_processada',
        'erro_mensagem',
        'iniciado_em',
        'finalizado_em',
    ];

    protected $casts = [
        'total_registros' => 'integer',
        'registros_processados' => 'integer',
        'registros_novos' => 'integer',
        'registros_atualizados' => 'integer',
        'registros_erro' => 'integer',
        'ultima_linha_processada' => 'integer',
        'iniciado_em' => 'datetime',
        'finalizado_em' => 'datetime',
    ];

    /**
     * Scope para filtrar por fonte
     */
    public function scopeFonte($query, string $fonte)
    {
        return $query->where('fonte', $fonte);
    }

    /**
     * Scope para importações concluídas
     */
    public function scopeConcluidas($query)
    {
        return $query->where('status', 'concluido');
    }

    /**
     * Scope para importações com erro
     */
    public function scopeComErro($query)
    {
        return $query->where('status', 'erro');
    }

    /**
     * Scope para importação em andamento
     */
    public function scopeEmAndamento($query)
    {
        return $query->where('status', 'em_processamento');
    }
}
