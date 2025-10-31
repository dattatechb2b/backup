<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitacaoCDF extends Model
{
    protected $table = 'cp_solicitacoes_cdf';

    protected $fillable = [
        'orcamento_id',
        'cnpj',
        'razao_social',
        'email',
        'telefone',
        'justificativa_fornecedor_unico',
        'justificativa_produto_exclusivo',
        'justificativa_urgencia',
        'justificativa_melhor_preco',
        'justificativa_outro',
        'prazo_resposta_dias',
        'prazo_entrega_dias',
        'frete',
        'observacao',
        'fornecedor_valido',
        'arquivo_cnpj',
        'status',
        'metodo_coleta',
        'comprovante_path',
        'cotacao_path',
        'data_resposta',
        'validacao_respostas',
        'descarte_motivo',
        'cancelamento_motivos',
        'cancelamento_obs',
        'descarte_motivos',
        'descarte_obs',
        // Novos campos para sistema de resposta por link
        'token_resposta',
        'valido_ate',
        'respondido',
        'data_resposta_fornecedor',
    ];

    protected $casts = [
        'justificativa_fornecedor_unico' => 'boolean',
        'justificativa_produto_exclusivo' => 'boolean',
        'justificativa_urgencia' => 'boolean',
        'justificativa_melhor_preco' => 'boolean',
        'fornecedor_valido' => 'boolean',
        'prazo_resposta_dias' => 'integer',
        'prazo_entrega_dias' => 'integer',
        'data_resposta' => 'datetime',
        'validacao_respostas' => 'array',
        'cancelamento_motivos' => 'array',
        'descarte_motivos' => 'array',
        // Novos campos para sistema de resposta por link
        'respondido' => 'boolean',
        'valido_ate' => 'datetime',
        'data_resposta_fornecedor' => 'datetime',
    ];

    /**
     * Relacionamento com Orçamento
     */
    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class);
    }

    /**
     * Relacionamento com itens da solicitação
     */
    public function itens()
    {
        return $this->hasMany(SolicitacaoCDFItem::class, 'solicitacao_cdf_id');
    }

    /**
     * Relacionamento com a resposta do fornecedor (novo)
     */
    public function resposta()
    {
        return $this->hasOne(RespostaCDF::class, 'solicitacao_cdf_id');
    }

    /**
     * Verificar se o link ainda é válido
     */
    public function linkValido()
    {
        return $this->valido_ate && $this->valido_ate->isFuture() && !$this->respondido;
    }

    /**
     * Verificar se já foi respondido
     */
    public function foiRespondido()
    {
        return $this->respondido;
    }

    /**
     * Obter URL de resposta
     */
    public function getUrlRespostaAttribute()
    {
        if (!$this->token_resposta) {
            return null;
        }

        // Obter dados do tenant da request para construir URL pública
        $tenant = request()->attributes->get('tenant');

        if ($tenant && isset($tenant['subdomain'])) {
            // URL pública COM prefixo do proxy: https://{subdomain}.dattapro.online/module-proxy/price_basket/responder-cdf/{token}
            // IMPORTANTE: O module-proxy é necessário para roteamento correto pelo ModuleProxyController
            $subdomain = $tenant['subdomain'];
            return "https://{$subdomain}.dattapro.online/module-proxy/price_basket/responder-cdf/{$this->token_resposta}";
        }

        // Fallback para localhost (desenvolvimento)
        return url('/responder-cdf/' . $this->token_resposta);
    }
}
