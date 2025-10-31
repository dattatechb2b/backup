<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PROBLEMA: Campo orcamentista_cep estava com VARCHAR(9) mas CEP formatado
     * tem 10 caracteres (ex: 36.205-348)
     *
     * SOLUÇÃO: Aumentar para VARCHAR(15) para comportar qualquer formato
     */
    public function up(): void
    {
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            // Alterar de VARCHAR(9) para VARCHAR(15)
            $table->string('orcamentista_cep', 15)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            // Reverter para VARCHAR(9) (tamanho original)
            $table->string('orcamentista_cep', 9)->nullable()->change();
        });
    }
};
