<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\NormalizadorHelper;

class ArpItem extends Model
{
    

    protected $table = 'cp_arp_itens';

    protected $fillable = [
        'ata_id',
        'catmat',
        'descricao',
        'unidade',
        'preco_unitario',
        'quantidade_registrada',
        'lote',
        'badge_confianca',
        'coletado_em',
    ];

    protected $casts = [
        'preco_unitario' => 'decimal:4',
        'quantidade_registrada' => 'decimal:4',
        'coletado_em' => 'datetime',
    ];

    /**
     * Relacionamento: Item pertence a uma ARP
     */
    public function ata()
    {
        return $this->belongsTo(ArpCabecalho::class, 'ata_id');
    }

    /**
     * Relacionamento: Item pode estar vinculado a um CATMAT
     */
    public function catmatRelacionado()
    {
        return $this->belongsTo(Catmat::class, 'catmat', 'codigo');
    }

    /**
     * Scope: busca por CATMAT
     */
    public function scopePorCatmat($query, $catmat)
    {
        return $query->where('catmat', $catmat);
    }

    /**
     * Scope: busca fulltext por descrição (PostgreSQL)
     */
    public function scopeBuscarDescricao($query, $termo)
    {
        return $query->whereRaw("to_tsvector('portuguese', descricao) @@ plainto_tsquery('portuguese', ?)", [$termo]);
    }

    /**
     * Scope: apenas itens de ATAs vigentes
     */
    public function scopeDeAtasVigentes($query)
    {
        return $query->whereHas('ata', function ($q) {
            $q->vigentes();
        });
    }

    /**
     * Scope: ordenar por preço (menor para maior)
     */
    public function scopeOrdenarPorPreco($query, $direcao = 'asc')
    {
        return $query->orderBy('preco_unitario', $direcao);
    }

    /**
     * Mutator: normaliza unidade ao salvar
     */
    public function setUnidadeAttribute($value)
    {
        $this->attributes['unidade'] = NormalizadorHelper::normalizarUnidade($value);
    }

    /**
     * Accessor: formata preço para exibição
     */
    public function getPrecoFormatadoAttribute()
    {
        return 'R$ ' . number_format($this->preco_unitario, 2, ',', '.');
    }

    /**
     * Retorna badge emoji baseado na confiança
     */
    public function getBadgeEmojiAttribute()
    {
        return match($this->badge_confianca) {
            'ALTA' => '🟢',
            'MEDIA' => '🟡',
            'BAIXA' => '🔴',
            default => '⚪',
        };
    }
}
