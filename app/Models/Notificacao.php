<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacao extends Model
{
    use HasFactory;

    protected $table = 'cp_notificacoes';

    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'mensagem',
        'dados',
        'lida',
        'lida_em',
    ];

    protected $casts = [
        'dados' => 'array',
        'lida' => 'boolean',
        'lida_em' => 'datetime',
    ];

    /**
     * Relacionamento com o usuário
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para notificações não lidas
     */
    public function scopeNaoLidas($query)
    {
        return $query->where('lida', false);
    }

    /**
     * Scope para notificações lidas
     */
    public function scopeLidas($query)
    {
        return $query->where('lida', true);
    }

    /**
     * Marcar como lida
     */
    public function marcarComoLida()
    {
        $this->update([
            'lida' => true,
            'lida_em' => now(),
        ]);
    }

    /**
     * Marcar como não lida
     */
    public function marcarComoNaoLida()
    {
        $this->update([
            'lida' => false,
            'lida_em' => null,
        ]);
    }
}
