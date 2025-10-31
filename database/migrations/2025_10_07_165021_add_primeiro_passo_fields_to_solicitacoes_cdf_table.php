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
        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            // Campos do 1º Passo
            $table->string('comprovante_path')->nullable();

            // Campos para cotação respondida (será usado posteriormente)
            $table->string('cotacao_path')->nullable();
            $table->timestamp('data_resposta')->nullable();
        });

        // Adicionar metodo_coleta como VARCHAR com CHECK constraint
        DB::statement("ALTER TABLE cp_solicitacoes_cdf ADD COLUMN metodo_coleta VARCHAR(20) NULL");
        DB::statement("ALTER TABLE cp_solicitacoes_cdf ADD CONSTRAINT solicitacoes_cdf_metodo_coleta_check CHECK (metodo_coleta IN ('email', 'presencial'))");

        // Adicionar mais um status ao enum: "Aguardando resposta"
        DB::statement("ALTER TABLE cp_solicitacoes_cdf DROP CONSTRAINT IF EXISTS solicitacoes_cdf_status_check");
        DB::statement("ALTER TABLE cp_solicitacoes_cdf ADD CONSTRAINT solicitacoes_cdf_status_check CHECK (status IN ('Pendente', 'Enviado', 'Aguardando resposta', 'Respondido', 'Validada', 'Descartada', 'Vencido', 'Cancelado'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurar enum sem "Aguardando resposta"
        DB::statement("ALTER TABLE cp_solicitacoes_cdf DROP CONSTRAINT IF EXISTS solicitacoes_cdf_status_check");
        DB::statement("ALTER TABLE cp_solicitacoes_cdf ADD CONSTRAINT solicitacoes_cdf_status_check CHECK (status IN ('Pendente', 'Enviado', 'Respondido', 'Validada', 'Descartada', 'Vencido', 'Cancelado'))");

        // Remover constraint e coluna metodo_coleta
        DB::statement("ALTER TABLE cp_solicitacoes_cdf DROP CONSTRAINT IF EXISTS solicitacoes_cdf_metodo_coleta_check");

        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->dropColumn([
                'metodo_coleta',
                'comprovante_path',
                'cotacao_path',
                'data_resposta'
            ]);
        });
    }
};
