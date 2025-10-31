<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrientacaoTecnica extends Model
{
    use HasFactory;

    // IMPORTANTE: Orientações Técnicas são isoladas por tenant
    // Cada prefeitura pode ter suas próprias orientações customizadas
    // Usa a conexão dinâmica configurada pelo ProxyAuth (pgsql)

    protected $table = 'cp_orientacoes_tecnicas';

    protected $fillable = [
        'numero',
        'titulo',
        'conteudo',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    /**
     * Scope para buscar apenas OTs ativas
     */
    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    /**
     * Scope para ordenar por número de ordem
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('ordem', 'asc');
    }

    /**
     * Buscar por termo (título ou conteúdo)
     */
    public static function buscarPorTermo($termo)
    {
        return self::ativas()
            ->where(function($query) use ($termo) {
                $query->where('titulo', 'ILIKE', "%{$termo}%")
                      ->orWhere('conteudo', 'ILIKE', "%{$termo}%")
                      ->orWhere('numero', 'ILIKE', "%{$termo}%");
            })
            ->ordenadas()
            ->get();
    }

    /**
     * Obter todas as OTs ativas e ordenadas
     */
    public static function obterTodas()
    {
        return self::ativas()->ordenadas()->get();
    }
}
