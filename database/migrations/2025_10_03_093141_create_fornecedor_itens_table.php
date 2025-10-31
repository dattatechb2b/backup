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
        Schema::create('cp_fornecedor_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fornecedor_id');
            $table->string('descricao', 255);
            $table->string('codigo_catmat', 50)->nullable();
            $table->string('unidade', 20)->nullable();
            $table->decimal('preco_referencia', 15, 2)->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('fornecedor_id')
                  ->references('id')
                  ->on('cp_fornecedores')
                  ->onDelete('cascade');

            // Índices
            $table->index('fornecedor_id');
            $table->index('descricao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_fornecedor_itens');
    }
};
