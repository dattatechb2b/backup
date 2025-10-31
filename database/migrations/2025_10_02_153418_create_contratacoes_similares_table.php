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
        // Tabela principal de contratações similares
        Schema::create('cp_contratacoes_similares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('cp_orcamentos')->onDelete('cascade');

            // Dados da contratação (origem)
            $table->string('ente_publico', 255);
            $table->string('tipo', 100); // Pregão, Contrato, Ata, etc
            $table->string('numero_processo', 100);
            $table->boolean('eh_registro_precos')->default(false);
            $table->date('data_publicacao');
            $table->string('local_publicacao', 50)->nullable(); // "Diário Oficial" ou "Portal Transparência"
            $table->text('link_oficial');

            // Evidência/comprovação
            $table->string('arquivo_pdf')->nullable();
            $table->string('arquivo_hash')->nullable(); // MD5 ou SHA-1
            $table->integer('arquivo_tamanho')->nullable(); // em bytes
            $table->timestamp('data_coleta')->nullable();
            $table->string('usuario_coleta')->nullable();

            $table->timestamps();

            // Índices
            $table->index('orcamento_id');
            $table->index('ente_publico');
            $table->index('data_publicacao');
        });

        // Tabela de itens da contratação similar
        Schema::create('cp_contratacao_similar_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratacao_similar_id')->constrained('cp_contratacoes_similares')->onDelete('cascade');
            $table->foreignId('orcamento_item_id')->constrained('cp_itens_orcamento')->onDelete('cascade');

            // Dados do item coletado
            $table->text('descricao');
            $table->string('catmat', 20)->nullable();
            $table->string('unidade', 20);
            $table->decimal('quantidade_referencia', 15, 2)->default(1);
            $table->decimal('preco_unitario', 15, 2);
            $table->decimal('preco_total', 15, 2);

            // Nível de confiança do preço
            $table->enum('nivel_confianca', ['Unitário', 'Estimado', 'Global'])->default('Unitário');

            $table->timestamps();

            // Índices
            $table->index('contratacao_similar_id');
            $table->index('orcamento_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_contratacao_similar_itens');
        Schema::dropIfExists('cp_contratacoes_similares');
    }
};
