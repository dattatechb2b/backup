<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orgao extends Model
{


    protected $table = 'cp_orgaos';

    protected $fillable = [
        'tenant_id',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cep',
        'cidade',
        'uf',
        'telefone',
        'email',
        'brasao_path',
        'responsavel_nome',
        'responsavel_matricula_siape',
        'responsavel_cargo',
        'responsavel_portaria',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com Orçamentos
     */
    public function orcamentos()
    {
        return $this->hasMany(Orcamento::class, 'orgao_id');
    }
}
