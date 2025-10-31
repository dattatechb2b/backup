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
        Schema::create('cp_resposta_cdf_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resposta_cdf_id')->constrained('cp_respostas_cdf')->onDelete('cascade');
            $table->foreignId('item_orcamento_id')->constrained('cp_itens_orcamento')->onDelete('cascade');
            $table->decimal('preco_unitario', 15, 2);
            $table->decimal('preco_total', 15, 2);
            $table->string('marca');
            $table->integer('prazo_entrega'); // dias
            $table->text('observacoes')->nullable();
            $table->timestamps();

            // Índices para otimização
            $table->index('resposta_cdf_id');
            $table->index('item_orcamento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_resposta_cdf_itens');
    }
};
