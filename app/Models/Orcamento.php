<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orcamento extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cp_orcamentos';

    /**
     * Boot do model para gerar número automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($orcamento) {
            // Se o número não foi fornecido, gerar automaticamente
            if (empty($orcamento->numero)) {
                // Buscar o próximo ID disponível
                $ultimoId = self::withTrashed()->max('id') ?? 0;
                $proximoId = $ultimoId + 1;

                // Gerar número no formato: 00001/2025
                $ano = date('Y');
                $orcamento->numero = str_pad($proximoId, 5, '0', STR_PAD_LEFT) . '/' . $ano;
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'numero',
        'nome',
        'referencia_externa',
        'objeto',
        'orgao_interessado',
        'tipo_criacao',
        'orcamento_origem_id',
        'status',
        'data_conclusao',
        'user_id',
        'metodo_juizo_critico',
        'metodo_obtencao_preco',
        'casas_decimais',
        'observacao_justificativa',
        'anexo_pdf',
        // Dados do Orçamentista (Etapa 6)
        'orcamentista_nome',
        'orcamentista_cpf_cnpj',
        'orcamentista_matricula',
        'orcamentista_portaria',
        'orcamentista_razao_social',
        'orcamentista_endereco',
        'orcamentista_cep',
        'orcamentista_cidade',
        'orcamentista_uf',
        'orcamentista_setor',
        'brasao_path',
        // Metodologia e parâmetros (8 campos - FASE 1.3)
        'metodologia_analise_critica',
        'medida_tendencia_central',
        'prazo_validade_amostras',
        'numero_minimo_amostras',
        'aceitar_fontes_alternativas',
        'usou_similares',
        'usou_cdf',
        'usou_ecommerce',
        'orgao_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data_conclusao' => 'datetime',
        // Metodologia e parâmetros (FASE 1.3)
        'prazo_validade_amostras' => 'integer',
        'numero_minimo_amostras' => 'integer',
        'aceitar_fontes_alternativas' => 'boolean',
        'usou_similares' => 'boolean',
        'usou_cdf' => 'boolean',
        'usou_ecommerce' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento: Orçamento pertence a um usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Orçamento pertence a um órgão
     */
    public function orgao()
    {
        return $this->belongsTo(Orgao::class);
    }

    /**
     * Relacionamento: Orçamento pode ter sido criado a partir de outro orçamento
     */
    public function orcamentoOrigem()
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_origem_id');
    }

    /**
     * Relacionamento: Orçamento pode ter gerado outros orçamentos
     */
    public function orcamentosDerivados()
    {
        return $this->hasMany(Orcamento::class, 'orcamento_origem_id');
    }

    /**
     * Relacionamento: Orçamento tem muitos itens
     */
    public function itens()
    {
        return $this->hasMany(OrcamentoItem::class, 'orcamento_id');
    }

    /**
     * Relacionamento: Orçamento tem muitos lotes
     */
    public function lotes()
    {
        return $this->hasMany(Lote::class, 'orcamento_id');
    }

    /**
     * Relacionamento: Orçamento tem muitas solicitações CDF
     */
    public function solicitacoesCDF()
    {
        return $this->hasMany(SolicitacaoCDF::class, 'orcamento_id');
    }

    /**
     * Relacionamento: Orçamento tem muitas contratações similares
     */
    public function contratacoesSimilares()
    {
        return $this->hasMany(ContratacaoSimilar::class, 'orcamento_id');
    }

    /**
     * Relacionamento: Orçamento tem muitas coletas de e-commerce
     */
    public function coletasEcommerce()
    {
        return $this->hasMany(ColetaEcommerce::class, 'orcamento_id');
    }

    /**
     * Scope para filtrar apenas orçamentos pendentes
     */
    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    /**
     * Scope para filtrar apenas orçamentos realizados
     */
    public function scopeRealizados($query)
    {
        return $query->where('status', 'realizado');
    }

    /**
     * Scope para filtrar por tipo de criação
     */
    public function scopeTipoCriacao($query, $tipo)
    {
        return $query->where('tipo_criacao', $tipo);
    }

    /**
     * Marcar orçamento como realizado
     */
    public function marcarComoRealizado()
    {
        $this->update([
            'status' => 'realizado',
            'data_conclusao' => now(),
        ]);
    }

    /**
     * Marcar orçamento como pendente
     */
    public function marcarComoPendente()
    {
        $this->update([
            'status' => 'pendente',
            'data_conclusao' => null,
        ]);
    }

    /**
     * Verificar se orçamento está pendente
     */
    public function isPendente()
    {
        return $this->status === 'pendente';
    }

    /**
     * Verificar se orçamento está realizado
     */
    public function isRealizado()
    {
        return $this->status === 'realizado';
    }

    /**
     * Obter label do tipo de criação
     */
    public function getTipoCriacaoLabelAttribute()
    {
        $labels = [
            'do_zero' => 'Criado do Zero',
            'outro_orcamento' => 'Criado a partir de Outro Orçamento',
            'documento' => 'Criado a partir de Documento',
        ];

        return $labels[$this->tipo_criacao] ?? 'Desconhecido';
    }

    /**
     * Obter label do status
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pendente' => 'Pendente',
            'realizado' => 'Realizado',
        ];

        return $labels[$this->status] ?? 'Desconhecido';
    }
}
