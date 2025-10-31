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
        Schema::create('cp_respostas_cdf', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_cdf_id')->constrained('cp_solicitacoes_cdf')->onDelete('cascade');
            $table->foreignId('fornecedor_id')->constrained('cp_fornecedores')->onDelete('cascade');
            $table->integer('validade_proposta'); // dias
            $table->string('forma_pagamento');
            $table->text('observacoes_gerais')->nullable();
            $table->string('assinatura_digital')->nullable(); // caminho do arquivo PNG
            $table->timestamp('data_resposta');
            $table->timestamps();

            // Índices para otimização de busca
            $table->index('solicitacao_cdf_id');
            $table->index('fornecedor_id');
            $table->index('data_resposta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_respostas_cdf');
    }
};
