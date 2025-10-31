<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultaPncpCache extends Model
{
    use HasFactory;

    protected $table = 'cp_consultas_pncp_cache';

    protected $fillable = [
        'hash_consulta',
        'tipo',
        'parametros',
        'resposta_json',
        'coletado_em',
        'ttl_expira_em',
    ];

    protected $casts = [
        'parametros' => 'array',
        'resposta_json' => 'array',
        'coletado_em' => 'datetime',
        'ttl_expira_em' => 'datetime',
    ];

    /**
     * Scope: apenas consultas válidas (não expiradas)
     */
    public function scopeValidas($query)
    {
        return $query->where('ttl_expira_em', '>', now());
    }

    /**
     * Scope: apenas consultas expiradas
     */
    public function scopeExpiradas($query)
    {
        return $query->where('ttl_expira_em', '<=', now());
    }

    /**
     * Scope: filtrar por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Verifica se o cache está válido
     */
    public function isValido()
    {
        return $this->ttl_expira_em > now();
    }

    /**
     * Verifica se o cache está expirado
     */
    public function isExpirado()
    {
        return !$this->isValido();
    }

    /**
     * Gera hash MD5 dos parâmetros para cache
     */
    public static function gerarHash(array $parametros)
    {
        ksort($parametros); // Ordenar para garantir consistência
        return md5(json_encode($parametros));
    }

    /**
     * Busca cache válido por hash
     */
    public static function buscarCache($hash)
    {
        return self::where('hash_consulta', $hash)
            ->validas()
            ->first();
    }

    /**
     * Cria ou atualiza cache
     */
    public static function salvarCache($tipo, array $parametros, $respostaJson, $ttlHoras = 24)
    {
        $hash = self::gerarHash($parametros);

        return self::updateOrCreate(
            ['hash_consulta' => $hash],
            [
                'tipo' => $tipo,
                'parametros' => $parametros,
                'resposta_json' => $respostaJson,
                'coletado_em' => now(),
                'ttl_expira_em' => now()->addHours($ttlHoras),
            ]
        );
    }

    /**
     * Limpa cache expirado
     */
    public static function limparExpirados()
    {
        return self::expiradas()->delete();
    }
}
