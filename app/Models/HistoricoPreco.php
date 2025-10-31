<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoPreco extends Model
{
    

    protected $table = 'cp_historico_precos';

    protected $fillable = [
        'catalogo_produto_id',
        'catmat',
        'fonte',
        'fonte_url',
        'preco_unitario',
        'badge',
        'data_coleta',
    ];

    protected $casts = [
        'preco_unitario' => 'decimal:4',
        'data_coleta' => 'datetime',
    ];

    /**
     * Relacionamento: Histórico pertence a um produto do catálogo
     */
    public function catalogoProduto()
    {
        return $this->belongsTo(CatalogoProduto::class, 'catalogo_produto_id');
    }

    /**
     * Relacionamento: Histórico pode estar vinculado a um CATMAT
     */
    public function catmatRelacionado()
    {
        return $this->belongsTo(Catmat::class, 'catmat', 'codigo');
    }

    /**
     * Scope: apenas de ARPs
     */
    public function scopeDeArp($query)
    {
        return $query->where('fonte', 'ARP');
    }

    /**
     * Scope: apenas de contratos
     */
    public function scopeDeContrato($query)
    {
        return $query->where('fonte', 'CONTRATO');
    }

    /**
     * Scope: apenas manuais
     */
    public function scopeManual($query)
    {
        return $query->where('fonte', 'MANUAL');
    }

    /**
     * Scope: filtrar por badge
     */
    public function scopePorBadge($query, $badge)
    {
        return $query->where('badge', $badge);
    }

    /**
     * Scope: filtrar por período
     */
    public function scopePorPeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_coleta', [$dataInicio, $dataFim]);
    }

    /**
     * Scope: últimos N dias
     */
    public function scopeUltimosDias($query, $dias = 30)
    {
        return $query->where('data_coleta', '>=', now()->subDays($dias));
    }

    /**
     * Accessor: formata preço para exibição
     */
    public function getPrecoFormatadoAttribute()
    {
        return 'R$ ' . number_format($this->preco_unitario, 2, ',', '.');
    }

    /**
     * Retorna badge emoji
     */
    public function getBadgeEmojiAttribute()
    {
        return match($this->badge) {
            '🟢', 'ALTA' => '🟢',
            '🟡', 'MEDIA' => '🟡',
            '🔴', 'BAIXA' => '🔴',
            default => '⚪',
        };
    }

    /**
     * Retorna label da fonte
     */
    public function getFonteLabelAttribute()
    {
        return match($this->fonte) {
            'ARP' => 'Ata de Registro de Preços',
            'CONTRATO' => 'Contrato',
            'MANUAL' => 'Entrada Manual',
            default => $this->fonte,
        };
    }
}
