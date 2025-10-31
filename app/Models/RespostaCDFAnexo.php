<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespostaCDFAnexo extends Model
{
    

    protected $table = 'cp_resposta_cdf_anexos';

    protected $fillable = [
        'resposta_cdf_id',
        'nome_arquivo',
        'caminho',
        'tamanho',
    ];

    protected $casts = [
        'tamanho' => 'integer',
    ];

    /**
     * Relacionamento com a resposta CDF
     */
    public function resposta()
    {
        return $this->belongsTo(RespostaCDF::class, 'resposta_cdf_id');
    }

    /**
     * Obter tamanho formatado (ex: 1.5 MB)
     */
    public function getTamanhoFormatadoAttribute()
    {
        $bytes = $this->tamanho;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Obter URL pública do arquivo
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->caminho);
    }
}
