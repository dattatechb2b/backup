<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anexo extends Model
{
    

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cp_anexos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'amostra_id',
        'item_id',
        'orcamento_id',
        'tipo',
        'nome_arquivo',
        'caminho',
        'tamanho_bytes',
        'hash_sha256',
        'mime_type',
        'paginas',
        'uploaded_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'tamanho_bytes' => 'integer',
        'paginas' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento: Anexo pode pertencer a um item
     */
    public function item()
    {
        return $this->belongsTo(OrcamentoItem::class, 'item_id');
    }

    /**
     * Relacionamento: Anexo pertence a um orçamento
     */
    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }

    /**
     * Relacionamento: Anexo foi enviado por um usuário
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Obter label do tipo de anexo
     */
    public function getTipoLabelAttribute()
    {
        $labels = [
            'PDF_CONTRATO' => 'PDF do Contrato',
            'SCREENSHOT' => 'Captura de Tela',
            'PLANILHA_IMPORTACAO' => 'Planilha de Importação',
            'PROPOSTA_CDF' => 'Proposta CDF',
            'ESPELHO_CNPJ' => 'Espelho CNPJ',
            'OUTRO' => 'Outro',
        ];

        return $labels[$this->tipo] ?? $this->tipo;
    }

    /**
     * Obter tamanho formatado
     */
    public function getTamanhoFormatadoAttribute()
    {
        $bytes = $this->tamanho_bytes;

        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }
}
