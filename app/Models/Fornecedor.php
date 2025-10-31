<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    protected $table = 'cp_fornecedores';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'razao_social',
        'nome_fantasia',
        'inscricao_estadual',
        'inscricao_municipal',
        'telefone',
        'celular',
        'email',
        'site',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'observacoes',
        'user_id',
        // Campos PNCP
        'tags_segmento',
        'ocorrencias',
        'status',
        'fonte_url',
        'ultima_atualizacao',
        'origem',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'ultima_atualizacao' => 'datetime',
        'tags_segmento' => 'array',
    ];

    /**
     * Relacionamento: Fornecedor tem muitos itens
     */
    public function itens()
    {
        return $this->hasMany(FornecedorItem::class, 'fornecedor_id');
    }

    /**
     * Accessor: CNPJ/CPF formatado
     */
    public function getNumeroDocumentoFormatadoAttribute()
    {
        $numero = preg_replace('/\D/', '', $this->numero_documento);

        if ($this->tipo_documento === 'CNPJ' && strlen($numero) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $numero);
        }

        if ($this->tipo_documento === 'CPF' && strlen($numero) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $numero);
        }

        return $numero;
    }

    /**
     * Scope: Busca por CNPJ/CPF
     */
    public function scopeByDocumento($query, $numeroDocumento)
    {
        $numeroLimpo = preg_replace('/\D/', '', $numeroDocumento);
        return $query->where('numero_documento', $numeroLimpo);
    }

    /**
     * Scope: Busca por nome (razão social ou fantasia)
     */
    public function scopeByNome($query, $nome)
    {
        return $query->where('razao_social', 'ILIKE', "%{$nome}%")
                     ->orWhere('nome_fantasia', 'ILIKE', "%{$nome}%");
    }
}
