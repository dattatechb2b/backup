<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabela de preços praticados do Compras.gov (API de Preços)
     * Armazena dados dos últimos 12 meses para consulta rápida
     * Sincronização híbrida: Base local (rápida) + API fallback (cobertura completa)
     */
    public function up(): void
    {
        // IMPORTANTE: Esta tabela é criada no banco PRINCIPAL (pgsql_main)
        // para ser compartilhada entre todos os tenants
        Schema::connection('pgsql_main')->create('cp_precos_comprasgov', function (Blueprint $table) {
            $table->id();

            // Informações do produto
            $table->string('catmat_codigo', 20)->index();
            $table->text('descricao_item');

            // Informações de preço
            $table->decimal('preco_unitario', 15, 2);
            $table->decimal('quantidade', 15, 3)->default(1);
            $table->string('unidade_fornecimento', 50)->nullable();

            // Informações do fornecedor
            $table->string('fornecedor_nome', 255)->nullable();
            $table->string('fornecedor_cnpj', 14)->nullable()->index();

            // Informações do órgão comprador
            $table->string('orgao_nome', 255)->nullable();
            $table->string('orgao_codigo', 50)->nullable();
            $table->string('orgao_uf', 2)->nullable()->index();

            // Localização
            $table->string('municipio', 100)->nullable();
            $table->string('uf', 2)->nullable()->index();

            // Datas
            $table->date('data_compra')->nullable()->index();
            $table->timestamp('sincronizado_em');
            $table->timestamp('created_at')->nullable();
        });

        // Índices para performance
        Schema::connection('pgsql_main')->table('cp_precos_comprasgov', function($table) {
            // Índice de full-text search para descrição (PostgreSQL)
            \DB::connection('pgsql_main')->statement(
                "CREATE INDEX idx_precos_comprasgov_desc ON cp_precos_comprasgov USING gin(to_tsvector('portuguese', descricao_item))"
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql_main')->dropIfExists('cp_precos_comprasgov');
    }
};
