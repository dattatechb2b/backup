<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContratoExterno extends Model
{
    // IMPORTANTE: Usar conexão 'pgsql_main' pois contratos externos são dados
    // compartilhados entre todos os tenants (assim como CATMAT e CMED)
    protected $connection = 'pgsql_main';

    protected $table = 'cp_contratos_externos';

    protected $fillable = [
        'fonte',
        'id_externo',
        'hash_normalizado',
        'numero_contrato',
        'objeto',
        'valor_total',
        'data_assinatura',
        'data_vigencia_inicio',
        'data_vigencia_fim',
        'orgao_nome',
        'orgao_cnpj',
        'orgao_uf',
        'orgao_municipio',
        'fornecedor_nome',
        'fornecedor_cnpj',
        'url_fonte',
        'dados_originais',
        'qualidade_score',
        'flags_qualidade',
    ];

    protected $casts = [
        'dados_originais' => 'array',
        'flags_qualidade' => 'array',
        'data_assinatura' => 'date',
        'data_vigencia_inicio' => 'date',
        'data_vigencia_fim' => 'date',
        'valor_total' => 'decimal:2',
        'qualidade_score' => 'integer',
    ];

    /**
     * Relacionamento com itens do contrato
     */
    public function itens(): HasMany
    {
        return $this->hasMany(ItemContratoExterno::class, 'contrato_id');
    }

    /**
     * Scope para filtrar por fonte
     */
    public function scopeFonte($query, string $fonte)
    {
        return $query->where('fonte', $fonte);
    }

    /**
     * Scope para filtrar por qualidade mínima
     */
    public function scopeQualidadeMinima($query, int $score = 70)
    {
        return $query->where('qualidade_score', '>=', $score);
    }

    /**
     * Scope para filtrar por período
     */
    public function scopePeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_assinatura', [$dataInicio, $dataFim]);
    }

    /**
     * Scope para filtrar por UF
     */
    public function scopeUf($query, string $uf)
    {
        return $query->where('orgao_uf', $uf);
    }
}
