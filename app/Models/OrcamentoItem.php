<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrcamentoItem extends Model
{
    

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cp_itens_orcamento';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'orcamento_id',
        'lote_id',
        'numero_item',
        'descricao',
        'fornecedor_nome',
        'fornecedor_cnpj',
        'medida_fornecimento',
        'quantidade',
        'preco_unitario',
        'indicacao_marca',
        'tipo',
        'alterar_cdf',
        'amostras_selecionadas',
        'justificativa_cotacao',
        'criticas_dados',
        'importado_de_planilha',
        'nome_arquivo_planilha',
        'data_importacao',
        // Snapshot de estatísticas (16 campos - FASE 1.2)
        'calc_n_validas',
        'calc_media',
        'calc_mediana',
        'calc_dp',
        'calc_cv',
        'calc_menor',
        'calc_maior',
        'calc_lim_inf',
        'calc_lim_sup',
        'calc_metodo',
        'calc_carimbado_em',
        'calc_hash_amostras',
        'abc_valor_total',
        'abc_participacao',
        'abc_acumulada',
        'abc_classe',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantidade' => 'decimal:4',
        'preco_unitario' => 'decimal:2',
        'alterar_cdf' => 'boolean',
        'importado_de_planilha' => 'boolean',
        'data_importacao' => 'datetime',
        // Snapshot de estatísticas (FASE 1.2)
        'calc_n_validas' => 'integer',
        'calc_media' => 'decimal:2',
        'calc_mediana' => 'decimal:2',
        'calc_dp' => 'decimal:2',
        'calc_cv' => 'decimal:4',
        'calc_menor' => 'decimal:2',
        'calc_maior' => 'decimal:2',
        'calc_lim_inf' => 'decimal:2',
        'calc_lim_sup' => 'decimal:2',
        'calc_carimbado_em' => 'datetime',
        'abc_valor_total' => 'decimal:2',
        'abc_participacao' => 'decimal:6',
        'abc_acumulada' => 'decimal:6',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento: Item pertence a um orçamento
     */
    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }

    /**
     * Relacionamento: Item pode pertencer a um lote
     */
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lote_id');
    }
}
