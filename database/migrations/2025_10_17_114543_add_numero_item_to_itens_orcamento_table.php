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
            // Adicionar coluna numero_item (número sequencial do item no orçamento)
            $table->integer('numero_item')->nullable()->after('id');

            // Criar índice para melhorar performance de consultas
            $table->index(['orcamento_id', 'numero_item']);
        });

        // Popular coluna numero_item com valores sequenciais baseados no ID
        // Tabela com prefixo cp_ hardcoded (conexão tenant_install não tem prefixo)
        DB::statement("
            UPDATE cp_itens_orcamento
            SET numero_item = subquery.row_num
            FROM (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY orcamento_id ORDER BY id) as row_num
                FROM cp_itens_orcamento
            ) AS subquery
            WHERE cp_itens_orcamento.id = subquery.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            $table->dropIndex(['orcamento_id', 'numero_item']);
            $table->dropColumn('numero_item');
        });
    }
};
