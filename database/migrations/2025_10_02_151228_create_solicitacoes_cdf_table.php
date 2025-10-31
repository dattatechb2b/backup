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
        // Tabela principal de solicitações CDF
        Schema::create('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('cp_orcamentos')->onDelete('cascade');

            // Dados do fornecedor
            $table->string('cnpj', 18);
            $table->string('razao_social', 255);
            $table->string('email', 255);
            $table->string('telefone', 20)->nullable();

            // Justificativas (checkboxes)
            $table->boolean('justificativa_fornecedor_unico')->default(false);
            $table->boolean('justificativa_produto_exclusivo')->default(false);
            $table->boolean('justificativa_urgencia')->default(false);
            $table->boolean('justificativa_melhor_preco')->default(false);
            $table->text('justificativa_outro')->nullable();

            // Condições comerciais
            $table->integer('prazo_resposta_dias');
            $table->integer('prazo_entrega_dias');
            $table->enum('frete', ['CIF', 'FOB']);
            $table->text('observacao')->nullable();

            // Validação
            $table->boolean('fornecedor_valido')->default(true);
            $table->string('arquivo_cnpj')->nullable();

            // Status da solicitação
            $table->enum('status', ['Pendente', 'Enviado', 'Respondido', 'Vencido', 'Cancelado'])->default('Pendente');

            $table->timestamps();

            // Índices
            $table->index('orcamento_id');
            $table->index('cnpj');
            $table->index('status');
        });

        // Tabela de itens da solicitação CDF
        Schema::create('cp_solicitacao_cdf_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_cdf_id')->constrained('cp_solicitacoes_cdf')->onDelete('cascade');
            $table->foreignId('orcamento_item_id')->constrained('cp_itens_orcamento')->onDelete('cascade');
            $table->timestamps();

            // Índices
            $table->index('solicitacao_cdf_id');
            $table->index('orcamento_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_solicitacao_cdf_itens');
        Schema::dropIfExists('cp_solicitacoes_cdf');
    }
};
