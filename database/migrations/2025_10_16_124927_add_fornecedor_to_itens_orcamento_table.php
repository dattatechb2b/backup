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
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            // Adicionar campo fornecedor_nome após descricao
            $table->string('fornecedor_nome', 255)->nullable()->after('descricao');
            $table->string('fornecedor_cnpj', 18)->nullable()->after('fornecedor_nome');

            // Adicionar índice para melhorar consultas
            $table->index('fornecedor_cnpj');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            // Remover índice e colunas
            $table->dropIndex(['fornecedor_cnpj']);
            $table->dropColumn(['fornecedor_nome', 'fornecedor_cnpj']);
        });
    }
};
