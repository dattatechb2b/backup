<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogImportacao extends Model
{
    protected $table = 'cp_logs_importacao';

    public $timestamps = false; // Usa apenas created_at

    protected $fillable = [
        'fonte',
        'tipo',
        'dataset_id',
        'arquivo',
        'ano',
        'checksum_arquivo',
        'schema_version',
        'checkpoint_offset',
        'checkpoint_batch',
        'registros_total',
        'registros_lidos',
        'registros_processados',
        'registros_inseridos',
        'registros_atualizados',
        'registros_ignorados',
        'registros_rejeitados',
        'registros_deduplicados',
        'dq_erros_encoding',
        'dq_erros_validacao',
        'dq_outliers_detectados',
        'dq_score_medio',
        'inicio',
        'fim',
        'duracao_segundos',
        'throughput_registros_por_segundo',
        'memoria_pico_mb',
        'cpu_uso_medio',
        'status',
        'mensagem',
        'erro',
        'stacktrace',
    ];

    protected $casts = [
        'ano' => 'integer',
        'checkpoint_offset' => 'integer',
        'checkpoint_batch' => 'integer',
        'registros_total' => 'integer',
        'registros_lidos' => 'integer',
        'registros_processados' => 'integer',
        'registros_inseridos' => 'integer',
        'registros_atualizados' => 'integer',
        'registros_ignorados' => 'integer',
        'registros_rejeitados' => 'integer',
        'registros_deduplicados' => 'integer',
        'dq_erros_encoding' => 'integer',
        'dq_erros_validacao' => 'integer',
        'dq_outliers_detectados' => 'integer',
        'dq_score_medio' => 'decimal:2',
        'inicio' => 'datetime',
        'fim' => 'datetime',
        'duracao_segundos' => 'integer',
        'throughput_registros_por_segundo' => 'decimal:2',
        'memoria_pico_mb' => 'integer',
        'cpu_uso_medio' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Scope para importações com sucesso
     */
    public function scopeSucesso($query)
    {
        return $query->where('status', 'SUCESSO');
    }

    /**
     * Scope para importações com erro
     */
    public function scopeErro($query)
    {
        return $query->where('status', 'ERRO');
    }

    /**
     * Scope para última importação de uma fonte
     */
    public function scopeUltimaPorFonte($query, string $fonte)
    {
        return $query->where('fonte', $fonte)
                     ->orderBy('inicio', 'desc')
                     ->limit(1);
    }
}
