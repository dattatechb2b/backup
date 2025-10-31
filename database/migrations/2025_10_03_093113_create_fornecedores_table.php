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
        Schema::create('cp_fornecedores', function (Blueprint $table) {
            $table->id();

            // Dados de Identificação
            $table->enum('tipo_documento', ['CNPJ', 'CPF'])->default('CNPJ');
            $table->string('numero_documento', 20)->unique();
            $table->string('razao_social', 255);
            $table->string('nome_fantasia', 255)->nullable();
            $table->string('inscricao_estadual', 50)->nullable();
            $table->string('inscricao_municipal', 50)->nullable();

            // Dados de Contato
            $table->string('telefone', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('site', 255)->nullable();

            // Endereço
            $table->string('cep', 10);
            $table->string('logradouro', 255);
            $table->string('numero', 20);
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100);
            $table->string('cidade', 100);
            $table->string('uf', 2);

            // Observações
            $table->text('observacoes')->nullable();

            // Controle
            $table->unsignedBigInteger('user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Índices
            $table->index('numero_documento');
            $table->index('razao_social');
            $table->index(['cidade', 'uf']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_fornecedores');
    }
};
