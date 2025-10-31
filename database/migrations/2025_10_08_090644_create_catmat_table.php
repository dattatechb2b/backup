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
        Schema::create('cp_catmat', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique()->comment('Código CATMAT ou CATSER');
            $table->text('titulo')->comment('Descrição do item');
            $table->string('tipo', 10)->default('CATMAT')->comment('CATMAT ou CATSER');
            $table->text('caminho_hierarquia')->nullable()->comment('Hierarquia do catálogo');
            $table->string('unidade_padrao', 50)->nullable()->comment('Unidade padrão (UN, KG, etc)');
            $table->string('fonte', 50)->default('CSV_OFICIAL')->comment('CSV_OFICIAL ou PNCP_AUTO');
            $table->timestamp('primeira_ocorrencia_em')->nullable()->comment('Primeira vez que apareceu');
            $table->timestamp('ultima_ocorrencia_em')->nullable()->comment('Última vez que apareceu');
            $table->integer('contador_ocorrencias')->default(0)->comment('Quantas vezes apareceu');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            // Índices
            $table->index('codigo');
            $table->index('tipo');
            $table->index('ativo');
        });

        // Índice fulltext para busca por título (PostgreSQL)
        // Prefixo cp_ hardcoded (conexão tenant_install não tem prefixo)
        DB::statement("CREATE INDEX idx_catmat_titulo_fulltext ON cp_catmat USING gin(to_tsvector('portuguese', titulo))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_catmat');
    }
};
