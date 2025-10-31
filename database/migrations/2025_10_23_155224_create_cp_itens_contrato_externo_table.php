<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // IMPORTANTE: Esta tabela usa conexão 'pgsql_main' (não do tenant)
        // Só criar se NÃO existir (para evitar erro em re-execução)
        if (Schema::hasTable('cp_itens_contrato_externo')) {
            return;
        }

        Schema::create('cp_itens_contrato_externo', function (Blueprint $table) {
            $table->id();

            // Relação
            $table->foreignId('contrato_id')
                ->constrained('cp_contratos_externos')
                ->onDelete('cascade');

            // Identificação
            $table->integer('numero_item')->nullable();
            $table->string('hash_normalizado', 64)->unique();

            // Descrição
            $table->text('descricao');
            $table->text('descricao_normalizada')->nullable();

            // Valores
            $table->decimal('quantidade', 15, 4)->nullable();
            $table->string('unidade', 20)->nullable();
            $table->decimal('valor_unitario', 15, 2)->nullable();
            $table->decimal('valor_total', 15, 2)->nullable();

            // Classificação
            $table->string('catmat', 20)->nullable();
            $table->string('catser', 20)->nullable();

            // Metadata
            $table->jsonb('dados_originais')->nullable();

            // Qualidade
            $table->integer('qualidade_score')->default(0);
            $table->jsonb('flags_qualidade')->nullable();

            $table->timestamps();

            // Índices
            $table->index('contrato_id');
            $table->index('hash_normalizado');
            $table->index('catmat');
            $table->index('descricao_normalizada');
        });

        // Criar índice de texto completo
        DB::statement("CREATE INDEX itens_descricao_fulltext ON cp_itens_contrato_externo USING gin(to_tsvector('portuguese', descricao))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_itens_contrato_externo');
    }
};
