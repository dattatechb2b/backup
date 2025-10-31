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
        // Criar tabela historico_buscas_similares com 8 campos
        Schema::create('cp_historico_buscas_similares', function (Blueprint $table) {
            // 1. ID primário
            $table->id();

            // 2. FK para orcamentos
            $table->bigInteger('orcamento_id');

            // 3. FK para itens_orcamento (opcional)
            $table->bigInteger('item_id')->nullable();

            // 4. Termo de busca
            $table->string('termo_busca', 255);

            // 5. Filtros aplicados (JSON)
            $table->json('filtros_aplicados')->nullable()
                ->comment('Ex: {"uf": "RN", "ano_min": 2024}');

            // 6. Resultados encontrados
            $table->integer('resultados_encontrados')
                ->comment('Performance/diagnóstico');

            // 7. Duração em milissegundos
            $table->integer('duracao_ms')->nullable()
                ->comment('Performance/diagnóstico');

            // 8. FK para users (quem fez a busca)
            $table->bigInteger('user_id')->nullable();

            // 9. Timestamp da criação (não usamos timestamps() para ter apenas created_at)
            $table->timestamp('created_at')->useCurrent();

            // Constraints (Foreign Keys)
            // $table->foreign('orcamento_id', 'fk_historico_orcamento')
            //     ->references('id')->on('cp_orcamentos')
            //     ->onDelete('cascade');

            // $table->foreign('item_id', 'fk_historico_item')
            //     ->references('id')->on('cp_itens_orcamento')
            //     ->onDelete('cascade');

            // $table->foreign('user_id', 'fk_historico_user')
            //     ->references('id')->on('cp_users')
            //     ->onDelete('set null');
        });

        // Índices para performance
        Schema::table('cp_historico_buscas_similares', function (Blueprint $table) {
            $table->index(['orcamento_id', 'item_id'], 'idx_historico_orcamento_item');
            $table->index('created_at', 'idx_historico_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_historico_buscas_similares');
    }
};
