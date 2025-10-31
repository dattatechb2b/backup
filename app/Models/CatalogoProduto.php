<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\NormalizadorHelper;

class CatalogoProduto extends Model
{
    protected $table = 'cp_catalogo_produtos';

    protected $fillable = [
        'descricao_padrao',
        'catmat',
        'catser',
        'unidade',
        'especificacao',
        'tags',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Relacionamento: Produto pode estar vinculado a um CATMAT
     */
    public function catmatRelacionado()
    {
        return $this->belongsTo(Catmat::class, 'catmat', 'codigo');
    }

    /**
     * Relacionamento: Produto tem histórico de preços
     */
    public function historicoPrecos()
    {
        return $this->hasMany(HistoricoPreco::class, 'catalogo_produto_id');
    }

    /**
     * Scope: apenas ativos
     */
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope: busca fulltext por descrição (PostgreSQL)
     */
    public function scopeBuscarDescricao($query, $termo)
    {
        return $query->whereRaw("to_tsvector('portuguese', descricao_padrao) @@ plainto_tsquery('portuguese', ?)", [$termo]);
    }

    /**
     * Scope: busca fulltext por tags (PostgreSQL)
     */
    public function scopeBuscarTags($query, $termo)
    {
        return $query->whereRaw("to_tsvector('portuguese', tags) @@ plainto_tsquery('portuguese', ?)", [$termo]);
    }

    /**
     * Scope: busca geral (descrição OU tags)
     */
    public function scopeBuscarGeral($query, $termo)
    {
        return $query->where(function ($q) use ($termo) {
            $q->whereRaw("to_tsvector('portuguese', descricao_padrao) @@ plainto_tsquery('portuguese', ?)", [$termo])
              ->orWhereRaw("to_tsvector('portuguese', tags) @@ plainto_tsquery('portuguese', ?)", [$termo]);
        });
    }

    /**
     * Scope: filtrar por CATMAT
     */
    public function scopePorCatmat($query, $catmat)
    {
        return $query->where('catmat', $catmat);
    }

    /**
     * Mutator: normaliza unidade ao salvar
     */
    public function setUnidadeAttribute($value)
    {
        $this->attributes['unidade'] = NormalizadorHelper::normalizarUnidade($value);
    }

    /**
     * Retorna array de tags
     */
    public function getTagsArrayAttribute()
    {
        return $this->tags ? array_map('trim', explode(',', $this->tags)) : [];
    }

    /**
     * Retorna estatísticas de preços do histórico
     */
    public function estatisticasPrecos()
    {
        $historico = $this->historicoPrecos()
            ->selectRaw('MIN(preco_unitario) as preco_min, AVG(preco_unitario) as preco_medio, MAX(preco_unitario) as preco_max, COUNT(*) as total_registros')
            ->first();

        return [
            'preco_min' => $historico->preco_min ?? null,
            'preco_medio' => $historico->preco_medio ?? null,
            'preco_max' => $historico->preco_max ?? null,
            'total_registros' => $historico->total_registros ?? 0,
        ];
    }

    /**
     * Retorna último preço registrado
     */
    public function ultimoPreco()
    {
        return $this->historicoPrecos()
            ->orderBy('data_coleta', 'desc')
            ->first();
    }
}
