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
        Schema::table('cp_catmat', function (Blueprint $table) {
            // Adicionar coluna para marcar se material tem preço no Compras.gov
            // NULL = não verificado ainda
            // TRUE = tem preços disponíveis
            // FALSE = não tem preços disponíveis
            $table->boolean('tem_preco_comprasgov')->nullable()->after('ativo');

            // Adicionar índice para otimizar consultas
            $table->index('tem_preco_comprasgov');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_catmat', function (Blueprint $table) {
            $table->dropIndex(['tem_preco_comprasgov']);
            $table->dropColumn('tem_preco_comprasgov');
        });
    }
};
