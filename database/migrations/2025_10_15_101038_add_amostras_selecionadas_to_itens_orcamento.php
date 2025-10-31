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
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            // Campo JSON para armazenar amostras selecionadas
            $table->json('amostras_selecionadas')->nullable()->after('alterar_cdf');

            // Campo para armazenar justificativa/observação da cotação
            $table->text('justificativa_cotacao')->nullable()->after('amostras_selecionadas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            $table->dropColumn(['amostras_selecionadas', 'justificativa_cotacao']);
        });
    }
};
