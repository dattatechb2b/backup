<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catmat extends Model
{
    use HasFactory;

    // IMPORTANTE: Usar conexão 'pgsql_main' que SEMPRE aponta para o banco principal
    // onde estão os dados compartilhados (CATMAT, CMED) independente do tenant
    protected $connection = 'pgsql_main';

    protected $table = 'cp_catmat';

    protected $fillable = [
        'codigo',
        'titulo',
        'tipo',
        'caminho_hierarquia',
        'unidade_padrao',
        'fonte',
        'primeira_ocorrencia_em',
        'ultima_ocorrencia_em',
        'contador_ocorrencias',
        'ativo',
    ];

    protected $casts = [
        'primeira_ocorrencia_em' => 'datetime',
        'ultima_ocorrencia_em' => 'datetime',
        'ativo' => 'boolean',
        'contador_ocorrencias' => 'integer',
    ];

    /**
     * Relacionamento: CATMAT tem muitos itens ARP
     */
    public function arpItens()
    {
        return $this->hasMany(ArpItem::class, 'catmat', 'codigo');
    }

    /**
     * Relacionamento: CATMAT tem muitos produtos no catálogo
     */
    public function catalogoProdutos()
    {
        return $this->hasMany(CatalogoProduto::class, 'catmat', 'codigo');
    }

    /**
     * Relacionamento: CATMAT tem muitos históricos de preço
     */
    public function historicoPrecos()
    {
        return $this->hasMany(HistoricoPreco::class, 'catmat', 'codigo');
    }

    /**
     * Incrementa contador de ocorrências e atualiza última ocorrência
     */
    public function registrarOcorrencia()
    {
        $this->increment('contador_ocorrencias');
        $this->update(['ultima_ocorrencia_em' => now()]);

        if ($this->contador_ocorrencias === 1) {
            $this->update(['primeira_ocorrencia_em' => now()]);
        }
    }

    /**
     * Scope: apenas ativos
     */
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope: busca por código
     */
    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo', $codigo);
    }

    /**
     * Scope: busca fulltext por título (PostgreSQL)
     */
    public function scopeBuscarTitulo($query, $termo)
    {
        return $query->whereRaw("to_tsvector('portuguese', titulo) @@ plainto_tsquery('portuguese', ?)", [$termo]);
    }
}
