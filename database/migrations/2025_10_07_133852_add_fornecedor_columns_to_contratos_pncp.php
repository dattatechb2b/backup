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
        Schema::table('cp_contratos_pncp', function (Blueprint $table) {
            // Adicionar colunas de fornecedor (todas nullable para não quebrar dados existentes)
            $table->string('fornecedor_cnpj', 14)->nullable()->after('orgao_municipio');
            $table->string('fornecedor_razao_social')->nullable()->after('fornecedor_cnpj');
            $table->unsignedBigInteger('fornecedor_id')->nullable()->after('fornecedor_razao_social');

            // Índices para performance
            $table->index('fornecedor_cnpj');
            $table->index('fornecedor_id');

            // Foreign key (com onDelete set null para não quebrar se fornecedor for deletado)
            $table->foreign('fornecedor_id')
                  ->references('id')
                  ->on('cp_fornecedores')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_contratos_pncp', function (Blueprint $table) {
            //
        });
    }
};
