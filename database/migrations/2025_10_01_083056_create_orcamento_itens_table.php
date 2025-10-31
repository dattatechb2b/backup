<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * DESABILITADO: Esta é uma versão ANTIGA da tabela de itens.
     * A versão CORRETA é cp_itens_orcamento criada por create_cp_itens_orcamento_table.php
     */
    public function up(): void
    {
        // Migration desabilitada - tabela duplicada, versão correta é cp_itens_orcamento
        return;

        Schema::create('cp_orcamento_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orcamento_id')->comment('ID do orçamento');
            $table->string('descricao')->comment('Descrição do item');
            $table->decimal('quantidade', 12, 2)->default(1)->comment('Quantidade');
            $table->string('unidade', 10)->default('UN')->comment('Unidade de medida (UN, KG, M, etc)');
            $table->decimal('valor_unitario', 12, 2)->nullable()->comment('Valor unitário');
            $table->decimal('valor_total', 12, 2)->nullable()->comment('Valor total (quantidade x valor_unitario)');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key
            $table->foreign('orcamento_id')->references('id')->on('cp_orcamentos')->onDelete('cascade');

            // Indexes
            $table->index('orcamento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_orcamento_itens');
    }
};
