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
        Schema::create('cp_cotacoes_externas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->string('numero', 50)->nullable(); // Número da cotação

            // Arquivo original enviado
            $table->string('arquivo_original_path', 500)->nullable();
            $table->string('arquivo_original_nome', 255)->nullable();
            $table->string('arquivo_original_tipo', 50)->nullable(); // pdf, xlsx, docx

            // Arquivo PDF gerado com nosso layout
            $table->string('arquivo_pdf_path', 500)->nullable();

            // Dados extraídos do documento (JSON)
            $table->json('dados_extraidos')->nullable();

            // Dados do orçamentista
            $table->string('orcamentista_nome', 255)->nullable();
            $table->string('orcamentista_cpf', 14)->nullable();
            $table->string('orcamentista_setor', 255)->nullable();
            $table->string('orcamentista_razao_social', 255)->nullable();
            $table->string('orcamentista_cnpj', 18)->nullable();
            $table->string('orcamentista_endereco', 500)->nullable();
            $table->string('orcamentista_cidade', 100)->nullable();
            $table->string('orcamentista_uf', 2)->nullable();
            $table->string('orcamentista_cep', 10)->nullable();
            $table->string('brasao_path', 500)->nullable();

            // Controle
            $table->enum('status', ['em_andamento', 'concluido'])->default('em_andamento');
            $table->timestamp('data_conclusao')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('cp_users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('status');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_cotacoes_externas');
    }
};
