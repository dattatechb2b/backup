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
        Schema::create('cp_orientacoes_tecnicas', function (Blueprint $table) {
            $table->id();

            // Identificação
            $table->string('numero', 10)->unique(); // 'OT 001', 'OT 002', etc
            $table->text('titulo');

            // Conteúdo
            $table->text('conteudo'); // HTML completo

            // Organização
            $table->integer('ordem')->default(0); // Para ordenação
            $table->boolean('ativo')->default(true);

            // Controle
            $table->timestamps();

            // Índices
            $table->index('numero');
            $table->index('ordem');
            $table->index('ativo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_orientacoes_tecnicas');
    }
};
