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
        Schema::table('cp_audit_log_itens', function (Blueprint $table) {
            // Relacionamento
            $table->foreignId('item_orcamento_id')->after('id')
                ->constrained('cp_itens_orcamento')
                ->onDelete('cascade')
                ->comment('FK para o item do orçamento');

            $table->foreignId('user_id')->nullable()->after('item_orcamento_id')
                ->constrained('cp_users')
                ->onDelete('set null')
                ->comment('Usuário que fez a alteração');

            // Tipo de ação
            $table->enum('acao', [
                'SANEAMENTO_APLICADO',
                'SANEAMENTO_REMOVIDO',
                'AMOSTRA_EXPURGADA',
                'AMOSTRA_REINCLUIDA',
                'SNAPSHOT_FIXADO',
                'PRECO_ALTERADO',
                'METODO_ALTERADO',
                'ANALISE_CRITICA_ABERTA',
            ])->after('user_id')->comment('Tipo de ação realizada');

            // Dados before/after (JSON)
            $table->json('dados_antes')->nullable()->after('acao')
                ->comment('Estado dos dados ANTES da ação (JSON)');

            $table->json('dados_depois')->nullable()->after('dados_antes')
                ->comment('Estado dos dados DEPOIS da ação (JSON)');

            // Metadados
            $table->text('observacao')->nullable()->after('dados_depois')
                ->comment('Observação/descrição da ação');

            $table->string('ip_address', 45)->nullable()->after('observacao')
                ->comment('IP do usuário');

            $table->string('user_agent')->nullable()->after('ip_address')
                ->comment('User agent do navegador');

            // Índices para performance
            $table->index('item_orcamento_id');
            $table->index('user_id');
            $table->index('acao');
            $table->index('created_at');
            $table->index(['item_orcamento_id', 'acao'], 'idx_item_acao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_audit_log_itens', function (Blueprint $table) {
            $table->dropIndex('idx_item_acao');
            $table->dropIndex(['created_at']);
            $table->dropIndex(['acao']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['item_orcamento_id']);

            $table->dropForeign(['user_id']);
            $table->dropForeign(['item_orcamento_id']);

            $table->dropColumn([
                'item_orcamento_id',
                'user_id',
                'acao',
                'dados_antes',
                'dados_depois',
                'observacao',
                'ip_address',
                'user_agent',
            ]);
        });
    }
};
