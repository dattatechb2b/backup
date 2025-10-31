<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespostaCDF extends Model
{
    

    protected $table = 'cp_respostas_cdf';

    protected $fillable = [
        'solicitacao_cdf_id',
        'fornecedor_id',
        'validade_proposta',
        'forma_pagamento',
        'observacoes_gerais',
        'assinatura_digital',
        'data_resposta',
    ];

    protected $casts = [
        'data_resposta' => 'datetime',
        'validade_proposta' => 'integer',
    ];

    /**
     * Relacionamento com Solicitação CDF
     */
    public function solicitacao()
    {
        return $this->belongsTo(SolicitacaoCDF::class, 'solicitacao_cdf_id');
    }

    /**
     * Relacionamento com Fornecedor
     */
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    /**
     * Relacionamento com itens da resposta
     */
    public function itens()
    {
        return $this->hasMany(RespostaCDFItem::class, 'resposta_cdf_id');
    }

    /**
     * Relacionamento com anexos
     */
    public function anexos()
    {
        return $this->hasMany(RespostaCDFAnexo::class, 'resposta_cdf_id');
    }

    /**
     * Calcular valor total da resposta
     */
    public function getValorTotalAttribute()
    {
        return $this->itens->sum('preco_total');
    }
}
