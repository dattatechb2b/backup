<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Expande campos telefone de VARCHAR(20) para VARCHAR(50)
     * para suportar telefones longos retornados pela Receita Federal.
     *
     * Afeta 3 tabelas (4 colunas):
     * - cp_orgaos.telefone
     * - cp_fornecedores.telefone
     * - cp_fornecedores.celular
     * - cp_solicitacoes_cdf.telefone
     */
    public function up(): void
    {
        // 1. Tabela cp_orgaos - telefone
        Schema::table('cp_orgaos', function (Blueprint $table) {
            $table->string('telefone', 50)->nullable()->change();
        });

        // 2. Tabela cp_fornecedores - telefone e celular
        Schema::table('cp_fornecedores', function (Blueprint $table) {
            $table->string('telefone', 50)->nullable()->change();
            $table->string('celular', 50)->nullable()->change();
        });

        // 3. Tabela cp_solicitacoes_cdf - telefone
        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->string('telefone', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * ATENÇÃO: Rollback pode causar TRUNCAMENTO DE DADOS!
     * Se houver telefones com mais de 20 caracteres, eles serão truncados.
     */
    public function down(): void
    {
        // Reverter para VARCHAR(20)
        Schema::table('cp_orgaos', function (Blueprint $table) {
            $table->string('telefone', 20)->nullable()->change();
        });

        Schema::table('cp_fornecedores', function (Blueprint $table) {
            $table->string('telefone', 20)->nullable()->change();
            $table->string('celular', 20)->nullable()->change();
        });

        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->string('telefone', 20)->nullable()->change();
        });
    }
};
