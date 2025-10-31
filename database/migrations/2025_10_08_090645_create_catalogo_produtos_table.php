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
        Schema::create('cp_catalogo_produtos', function (Blueprint $table) {
            $table->id();
            $table->text('descricao_padrao')->comment('Descrição padronizada do órgão');
            $table->string('catmat', 20)->nullable()->comment('Código CATMAT');
            $table->string('catser', 20)->nullable()->comment('Código CATSER');
            $table->string('unidade', 50)->comment('Unidade de medida normalizada');
            $table->text('especificacao')->nullable()->comment('Especificação técnica detalhada');
            $table->text('tags')->nullable()->comment('Tags separadas por vírgula (material_escritorio, informatica)');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            // Índices
            $table->index('ativo');
            $table->index('catmat');
            $table->index('catser');

            // Foreign keys
            $table->foreign('catmat')->references('codigo')->on('cp_catmat')->onDelete('set null');
        });

        // Índices fulltext para busca (PostgreSQL)
        // Prefixo cp_ hardcoded (conexão tenant_install não tem prefixo)
        DB::statement("CREATE INDEX idx_catalogo_descricao_fulltext ON cp_catalogo_produtos USING gin(to_tsvector('portuguese', descricao_padrao))");
        DB::statement("CREATE INDEX idx_catalogo_tags_fulltext ON cp_catalogo_produtos USING gin(to_tsvector('portuguese', tags))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_catalogo_produtos');
    }
};
