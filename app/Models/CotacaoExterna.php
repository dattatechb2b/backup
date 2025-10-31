<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CotacaoExterna extends Model
{
    protected $table = 'cp_cotacoes_externas';

    protected $fillable = [
        'titulo',
        'numero',
        'arquivo_original_path',
        'arquivo_original_nome',
        'arquivo_original_tipo',
        'arquivo_pdf_path',
        'dados_extraidos',
        'orcamentista_nome',
        'orcamentista_cpf',
        'orcamentista_setor',
        'orcamentista_razao_social',
        'orcamentista_cnpj',
        'orcamentista_endereco',
        'orcamentista_cidade',
        'orcamentista_uf',
        'orcamentista_cep',
        'brasao_path',
        'status',
        'data_conclusao',
        'user_id',
    ];

    protected $casts = [
        'dados_extraidos' => 'array',
        'data_conclusao' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
