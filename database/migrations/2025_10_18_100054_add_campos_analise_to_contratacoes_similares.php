<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // NOTA: Campos já existem devido a migration anterior (2025_10_14_230000_add_detailed_fields_to_contratacoes_similares)
        // Esta migration foi mantida apenas para documentação e compatibilidade do checklist
        // Os campos estão presentes com nomes ligeiramente diferentes:
        // - justificativa_expurgo (ao invés de motivo_expurgo)
        // - situacao com valores 'validada'/'expurgada' (ao invés de 'VALIDA'/'EXPURGADA')

        // Nada a fazer - campos já existem
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nada a fazer - campos já existiam antes desta migration
    }
};
