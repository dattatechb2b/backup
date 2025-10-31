<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrosswalkFonte extends Model
{
    protected $table = 'cp_crosswalk_fontes';

    protected $fillable = [
        'master_id',
        'id_tce_rs',
        'id_comprasnet',
        'id_pncp',
        'id_outros',
        'tipo',
        'matching_method',
        'matching_confidence',
        'matching_fields',
        'fonte_prioritaria',
    ];

    protected $casts = [
        'id_outros' => 'json',
        'matching_confidence' => 'decimal:2',
        'matching_fields' => 'json',
    ];
}
