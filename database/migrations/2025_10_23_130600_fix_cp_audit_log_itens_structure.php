<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OBJETIVO: Recriar cp_audit_log_itens com estrutura correta (igual Materlândia)
     *
     * ESTRUTURA ANTIGA (Nova Roma):
     * - item_orcamento_id, user_id, acao, dados_antes, dados_depois, observacao, ip_address, user_agent
     *
     * ESTRUTURA NOVA (Materlândia):
     * - item_id, event_type, sample_number, before_value, after_value, rule_applied,
     *   justification, usuario_id, usuario_nome
     */
    public function up(): void
    {
        // DROP tabela antiga (seguro - sem dados)
        Schema::dropIfExists('cp_audit_log_itens');

        // RECREATE com estrutura correta
        Schema::create('cp_audit_log_itens', function (Blueprint $table) {
            $table->id();

            // Chave estrangeira para item do orçamento
            $table->unsignedBigInteger('item_id')->comment('FK para o item do orçamento');
            $table->foreign('item_id')
                ->references('id')
                ->on('cp_itens_orcamento')  // COM prefixo cp_ explícito
                ->onDelete('cascade');

            // Tipo de evento (APPLY_SANITIZATION_DP, PURGE_SAMPLE, etc)
            $table->string('event_type', 100)->comment('Tipo de evento/ação realizada');

            // Número da amostra (1, 2, 3...) - nullable para eventos gerais
            $table->integer('sample_number')->nullable()->comment('Número da amostra afetada');

            // Valores antes/depois (strings flexíveis)
            $table->text('before_value')->nullable()->comment('Valor ANTES da ação');
            $table->text('after_value')->nullable()->comment('Valor DEPOIS da ação');

            // Regra aplicada (ex: "DP ± MEAN", "MEDIAN ± 10%")
            $table->string('rule_applied')->nullable()->comment('Regra/método aplicado');

            // Justificativa textual
            $table->text('justification')->nullable()->comment('Justificativa da ação');

            // Usuário que fez a ação
            $table->foreignId('usuario_id')->nullable()
                ->constrained('cp_users')
                ->onDelete('set null')
                ->comment('Usuário que executou a ação');

            $table->string('usuario_nome')->nullable()->comment('Nome do usuário (snapshot)');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            // Índices para performance
            $table->index('item_id', 'idx_audit_logs_item');
            $table->index('event_type', 'idx_audit_logs_event');
            $table->index('sample_number', 'idx_audit_logs_sample');
            $table->index(['item_id', 'sample_number'], 'idx_audit_logs_item_sample');
            $table->index('usuario_id', 'idx_audit_logs_usuario');
            $table->index('created_at', 'idx_audit_logs_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para estrutura antiga (não recomendado)
        Schema::dropIfExists('cp_audit_log_itens');

        // Recriar estrutura antiga
        Schema::create('cp_audit_log_itens', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::table('cp_audit_log_itens', function (Blueprint $table) {
            $table->foreignId('item_orcamento_id')->after('id')
                ->constrained('cp_itens_orcamento')
                ->onDelete('cascade');

            $table->foreignId('user_id')->nullable()->after('item_orcamento_id')
                ->constrained('cp_users')
                ->onDelete('set null');

            $table->enum('acao', [
                'SANEAMENTO_APLICADO',
                'SANEAMENTO_REMOVIDO',
                'AMOSTRA_EXPURGADA',
                'AMOSTRA_REINCLUIDA',
                'SNAPSHOT_FIXADO',
                'PRECO_ALTERADO',
                'METODO_ALTERADO',
                'ANALISE_CRITICA_ABERTA',
            ])->after('user_id');

            $table->json('dados_antes')->nullable()->after('acao');
            $table->json('dados_depois')->nullable()->after('dados_antes');
            $table->text('observacao')->nullable()->after('dados_depois');
            $table->string('ip_address', 45)->nullable()->after('observacao');
            $table->string('user_agent')->nullable()->after('ip_address');

            $table->index('item_orcamento_id');
            $table->index('user_id');
            $table->index('acao');
            $table->index('created_at');
            $table->index(['item_orcamento_id', 'acao'], 'idx_item_acao');
        });
    }
};
