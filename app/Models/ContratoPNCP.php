<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContratoPNCP extends Model
{
    use HasFactory;

    protected $table = 'cp_contratos_pncp';

    protected $fillable = [
        'numero_controle_pncp',
        'tipo',
        'objeto_contrato',
        'valor_global',
        'numero_parcelas',
        'valor_unitario_estimado',
        'unidade_medida',
        'orgao_cnpj',
        'orgao_razao_social',
        'orgao_uf',
        'orgao_municipio',
        'data_publicacao_pncp',
        'data_vigencia_inicio',
        'data_vigencia_fim',
        'confiabilidade',
        'valor_estimado',
        'sincronizado_em',
    ];

    protected $casts = [
        'data_publicacao_pncp' => 'date',
        'data_vigencia_inicio' => 'date',
        'data_vigencia_fim' => 'date',
        'valor_global' => 'decimal:2',
        'valor_unitario_estimado' => 'decimal:2',
        'valor_estimado' => 'boolean',
        'sincronizado_em' => 'datetime',
    ];

    /**
     * Busca full-text usando PostgreSQL tsvector
     *
     * QUALQUER PALAVRA funciona!
     */
    public static function buscarPorTermo($termo, $mesesAtras = 12, $limite = 10000)
    {
        $dataLimite = now()->subMonths($mesesAtras);

        // Normalizar termo para busca
        $termoNormalizado = strtolower(trim($termo));

        return self::select([
                'id',
                'numero_controle_pncp',
                'tipo',
                'objeto_contrato',
                'valor_global',
                'numero_parcelas',
                'valor_unitario_estimado',
                'unidade_medida',
                'orgao_razao_social as orgao',
                'orgao_uf',
                'data_publicacao_pncp',
                'confiabilidade',
                'valor_estimado',
            ])
            ->where('data_publicacao_pncp', '>=', $dataLimite)
            ->where(function($query) use ($termoNormalizado) {
                // Busca ILIKE (simples e SEMPRE funciona) ao invés de full-text
                $query->where('objeto_contrato', 'ILIKE', "%{$termoNormalizado}%");
            })
            ->orderBy('data_publicacao_pncp', 'desc')
            ->limit($limite)
            ->get();
    }

    /**
     * Busca simples com ILIKE (fallback se full-text não funcionar)
     */
    public static function buscarSimples($termo, $mesesAtras = 6, $limite = 100)
    {
        $dataLimite = now()->subMonths($mesesAtras);

        return self::select([
                'id',
                'numero_controle_pncp',
                'tipo',
                'objeto_contrato',
                'valor_global',
                'numero_parcelas',
                'valor_unitario_estimado',
                'unidade_medida',
                'orgao_razao_social as orgao',
                'orgao_uf',
                'data_publicacao_pncp',
                'confiabilidade',
                'valor_estimado',
            ])
            ->where('data_publicacao_pncp', '>=', $dataLimite)
            ->where('objeto_contrato', 'ILIKE', '%' . $termo . '%')
            ->orderBy('data_publicacao_pncp', 'desc')
            ->limit($limite)
            ->get();
    }

    /**
     * Calcular valor unitário estimado
     */
    public function getValorUnitarioAttribute()
    {
        if ($this->valor_unitario_estimado) {
            return $this->valor_unitario_estimado;
        }

        if ($this->numero_parcelas && $this->numero_parcelas > 1) {
            return $this->valor_global / $this->numero_parcelas;
        }

        return $this->valor_global;
    }
}
