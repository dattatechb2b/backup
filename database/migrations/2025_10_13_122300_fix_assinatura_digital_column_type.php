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
        Schema::table('cp_respostas_cdf', function (Blueprint $table) {
            // Alterar assinatura_digital de string (255) para longText
            // para suportar assinaturas em base64 que podem ter 20k+ caracteres
            $table->longText('assinatura_digital')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_respostas_cdf', function (Blueprint $table) {
            // Reverter para string (255)
            $table->string('assinatura_digital')->nullable()->change();
        });
    }
};
