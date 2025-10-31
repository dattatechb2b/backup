<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitacaoCDFItem extends Model
{
    protected $table = 'cp_solicitacao_cdf_itens';

    protected $fillable = [
        'solicitacao_cdf_id',
        'orcamento_item_id',
    ];

    /**
     * Relacionamento com a solicitação CDF
     */
    public function solicitacao()
    {
        return $this->belongsTo(SolicitacaoCDF::class, 'solicitacao_cdf_id');
    }

    /**
     * Relacionamento com o item do orçamento
     */
    public function item()
    {
        return $this->belongsTo(OrcamentoItem::class, 'orcamento_item_id');
    }
}
