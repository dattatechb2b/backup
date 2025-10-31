<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditSnapshot extends Model
{
    protected $table = 'cp_audit_snapshots';

    protected $fillable = [
        'item_id',
        'snapshot_timestamp',
        'n_validas',
        'media',
        'mediana',
        'desvio_padrao',
        'coef_variacao',
        'limite_inferior',
        'limite_superior',
        'metodo',
        'hash_sha256',
    ];

    protected $casts = [
        'snapshot_timestamp' => 'datetime',
        'n_validas' => 'integer',
        'media' => 'decimal:4',
        'mediana' => 'decimal:4',
        'desvio_padrao' => 'decimal:4',
        'coef_variacao' => 'decimal:4',
        'limite_inferior' => 'decimal:4',
        'limite_superior' => 'decimal:4',
    ];

    /**
     * Relacionamento com Item do Orçamento
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemOrcamento::class, 'item_id');
    }

    /**
     * Gerar hash SHA256 do snapshot
     */
    public function generateHash(): string
    {
        $data = [
            'item_id' => $this->item_id,
            'timestamp' => $this->snapshot_timestamp->format('Y-m-d H:i:s'),
            'n_validas' => $this->n_validas,
            'media' => $this->media,
            'mediana' => $this->mediana,
            'desvio_padrao' => $this->desvio_padrao,
            'coef_variacao' => $this->coef_variacao,
            'metodo' => $this->metodo,
        ];

        return hash('sha256', json_encode($data));
    }

    /**
     * Boot do model para gerar hash automaticamente
     */
    protected static function booted()
    {
        static::creating(function ($snapshot) {
            if (empty($snapshot->hash_sha256)) {
                $snapshot->snapshot_timestamp = $snapshot->snapshot_timestamp ?? now();
                $snapshot->hash_sha256 = $snapshot->generateHash();
            }
        });
    }
}
