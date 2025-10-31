<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\NormalizadorHelper;

class ArpCabecalho extends Model
{
    

    protected $table = 'cp_arp_cabecalhos';

    protected $fillable = [
        'numero_ata',
        'ano_ata',
        'orgao_gerenciador',
        'cnpj_orgao',
        'uasg',
        'ano_compra',
        'sequencial_compra',
        'vigencia_inicio',
        'vigencia_fim',
        'situacao',
        'fornecedor_razao',
        'fornecedor_cnpj',
        'fonte_url',
        'payload_json',
        'coletado_em',
        'coletado_por',
    ];

    protected $casts = [
        'vigencia_inicio' => 'date',
        'vigencia_fim' => 'date',
        'payload_json' => 'array',
        'coletado_em' => 'datetime',
        'ano_compra' => 'integer',
        'sequencial_compra' => 'integer',
        'ano_ata' => 'integer',
    ];

    /**
     * Relacionamento: ARP tem muitos itens
     */
    public function itens()
    {
        return $this->hasMany(ArpItem::class, 'ata_id');
    }

    /**
     * Relacionamento: ARP foi coletado por um usuário
     */
    public function coletadoPor()
    {
        return $this->belongsTo(User::class, 'coletado_por');
    }

    /**
     * Verifica se a ARP está vigente
     */
    public function isVigente()
    {
        if ($this->situacao === 'Vigente') {
            return true;
        }

        if ($this->vigencia_fim && $this->vigencia_fim >= now()->toDateString()) {
            return true;
        }

        return false;
    }

    /**
     * Scope: apenas vigentes
     */
    public function scopeVigentes($query)
    {
        return $query->where(function ($q) {
            $q->where('situacao', 'Vigente')
              ->orWhere('vigencia_fim', '>=', now()->toDateString());
        });
    }

    /**
     * Scope: filtrar por UASG
     */
    public function scopePorUasg($query, $uasg)
    {
        return $query->where('uasg', $uasg);
    }

    /**
     * Scope: filtrar por UF (extrai do CNPJ ou órgão)
     */
    public function scopePorUf($query, $uf)
    {
        return $query->where('orgao_gerenciador', 'like', "%{$uf}%");
    }

    /**
     * Scope: filtrar por período de vigência
     */
    public function scopePorPeriodo($query, $dataInicio, $dataFim)
    {
        return $query->where(function ($q) use ($dataInicio, $dataFim) {
            $q->whereBetween('vigencia_inicio', [$dataInicio, $dataFim])
              ->orWhereBetween('vigencia_fim', [$dataInicio, $dataFim]);
        });
    }

    /**
     * Mutator: normaliza CNPJ ao salvar
     */
    public function setCnpjOrgaoAttribute($value)
    {
        $this->attributes['cnpj_orgao'] = NormalizadorHelper::normalizarCNPJ($value);
    }

    /**
     * Mutator: normaliza CNPJ fornecedor ao salvar
     */
    public function setFornecedorCnpjAttribute($value)
    {
        $this->attributes['fornecedor_cnpj'] = $value ? NormalizadorHelper::normalizarCNPJ($value) : null;
    }

    /**
     * Mutator: normaliza número da ATA ao salvar
     */
    public function setNumeroAtaAttribute($value)
    {
        $this->attributes['numero_ata'] = $value;
        $this->attributes['ano_ata'] = NormalizadorHelper::extrairAnoAta($value);
    }
}
