<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemContratoExterno extends Model
{
    // IMPORTANTE: Usar conexão 'pgsql_main' pois itens de contratos externos são dados
    // compartilhados entre todos os tenants (relacionados a ContratoExterno)
    protected $connection = 'pgsql_main';

    protected $table = 'cp_itens_contrato_externo';

    protected $fillable = [
        'contrato_id',
        'numero_item',
        'hash_normalizado',
        'descricao',
        'descricao_normalizada',
        'quantidade',
        'unidade',
        'valor_unitario',
        'valor_total',
        'catmat',
        'catser',
        'dados_originais',
        'qualidade_score',
        'flags_qualidade',
    ];

    protected $casts = [
        'contrato_id' => 'integer',
        'numero_item' => 'integer',
        'quantidade' => 'decimal:4',
        'valor_unitario' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'dados_originais' => 'array',
        'qualidade_score' => 'integer',
        'flags_qualidade' => 'array',
    ];

    /**
     * Relacionamento com contrato
     */
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(ContratoExterno::class, 'contrato_id');
    }

    /**
     * Scope para itens com qualidade mínima
     */
    public function scopeQualidadeMinima($query, int $score = 70)
    {
        return $query->where('qualidade_score', '>=', $score);
    }

    /**
     * Scope para buscar por CATMAT
     */
    public function scopeCatmat($query, string $catmat)
    {
        return $query->where('catmat', $catmat);
    }

    /**
     * Scope para buscar por CATSER
     */
    public function scopeCatser($query, string $catser)
    {
        return $query->where('catser', $catser);
    }

    /**
     * Scope para buscar por descrição (fulltext)
     */
    public function scopeDescricao($query, string $termo)
    {
        return $query->whereRaw(
            "to_tsvector('portuguese', descricao) @@ plainto_tsquery('portuguese', ?)",
            [$termo]
        );
    }

    /**
     * Scope para itens recentes via contrato
     */
    public function scopeRecentes($query, int $meses = 12)
    {
        return $query->whereHas('contrato', function($q) use ($meses) {
            $q->where('data_assinatura', '>=', now()->subMonths($meses));
        });
    }

    /**
     * Scope para filtrar por fonte via contrato
     */
    public function scopeFonte($query, string $fonte)
    {
        return $query->whereHas('contrato', function($q) use ($fonte) {
            $q->where('fonte', $fonte);
        });
    }
}
