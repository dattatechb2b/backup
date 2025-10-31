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
        Schema::create('cp_resposta_cdf_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resposta_cdf_id')->constrained('cp_respostas_cdf')->onDelete('cascade');
            $table->string('nome_arquivo');
            $table->string('caminho');
            $table->integer('tamanho'); // bytes
            $table->timestamps();

            // Índice para otimização
            $table->index('resposta_cdf_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_resposta_cdf_anexos');
    }
};
