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
        // Tabela principal de coletas de e-commerce
        Schema::create('cp_coletas_ecommerce', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('cp_orcamentos')->onDelete('cascade');

            // Dados do site consultado
            $table->string('nome_site');
            $table->text('url_site');
            $table->boolean('eh_intermediacao')->default(false);

            // Dados da consulta
            $table->date('data_consulta');
            $table->time('hora_consulta');
            $table->boolean('inclui_frete')->default(false);

            // Arquivo (print da tela)
            $table->string('arquivo_print')->nullable();

            $table->timestamps();

            // Índices
            $table->index('orcamento_id');
            $table->index('data_consulta');
        });

        // Tabela de itens coletados
        Schema::create('cp_coleta_ecommerce_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coleta_ecommerce_id')->constrained('cp_coletas_ecommerce')->onDelete('cascade');
            $table->foreignId('orcamento_item_id')->constrained('cp_itens_orcamento')->onDelete('cascade');

            // Preços coletados
            $table->decimal('preco_unitario', 15, 2);
            $table->decimal('preco_total', 15, 2);

            $table->timestamps();

            // Índices
            $table->index('coleta_ecommerce_id');
            $table->index('orcamento_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_coleta_ecommerce_itens');
        Schema::dropIfExists('cp_coletas_ecommerce');
    }
};
