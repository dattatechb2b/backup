<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primeiro, adicionar as colunas JSON e TEXT
        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->json('validacao_respostas')->nullable();
            $table->text('descarte_motivo')->nullable();
            $table->json('cancelamento_motivos')->nullable();
            $table->text('cancelamento_obs')->nullable();
            $table->json('descarte_motivos')->nullable();
            $table->text('descarte_obs')->nullable();
        });

        // Depois, modificar o enum do status usando SQL puro
        DB::statement("ALTER TABLE cp_solicitacoes_cdf DROP CONSTRAINT IF EXISTS solicitacoes_cdf_status_check");
        DB::statement("ALTER TABLE cp_solicitacoes_cdf ALTER COLUMN status TYPE VARCHAR(50)");
        DB::statement("ALTER TABLE cp_solicitacoes_cdf ADD CONSTRAINT solicitacoes_cdf_status_check CHECK (status IN ('Pendente', 'Enviado', 'Respondido', 'Validada', 'Descartada', 'Vencido', 'Cancelado'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar enum original
        DB::statement("ALTER TABLE cp_solicitacoes_cdf DROP CONSTRAINT IF EXISTS solicitacoes_cdf_status_check");
        DB::statement("ALTER TABLE cp_solicitacoes_cdf ADD CONSTRAINT solicitacoes_cdf_status_check CHECK (status IN ('Pendente', 'Enviado', 'Respondido', 'Vencido', 'Cancelado'))");

        // Remover colunas adicionadas
        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->dropColumn([
                'validacao_respostas',
                'descarte_motivo',
                'cancelamento_motivos',
                'cancelamento_obs',
                'descarte_motivos',
                'descarte_obs'
            ]);
        });
    }
};
