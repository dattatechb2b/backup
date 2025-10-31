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
        Schema::create('cp_orcamentos', function (Blueprint $table) {
            $table->id();

            // Campos do formulário
            $table->string('nome')->comment('Nome do Orçamento (obrigatório)');
            $table->string('referencia_externa')->nullable()->comment('Referência Externa (opcional)');
            $table->text('objeto')->comment('Objeto do orçamento (obrigatório)');
            $table->string('orgao_interessado')->nullable()->comment('Órgão Interessado (opcional)');

            // Tipo de criação (qual aba foi usada)
            $table->enum('tipo_criacao', ['do_zero', 'outro_orcamento', 'documento'])
                  ->default('do_zero')
                  ->comment('Como foi criado: do zero, de outro orçamento ou de documento');

            // Relacionamento com outro orçamento (se criado a partir de outro)
            $table->unsignedBigInteger('orcamento_origem_id')->nullable()
                  ->comment('ID do orçamento de origem (quando criado a partir de outro)');

            // Status e controle
            $table->enum('status', ['pendente', 'realizado'])->default('pendente');
            $table->timestamp('data_conclusao')->nullable()->comment('Data em que foi marcado como realizado');

            // Usuário que criou
            $table->unsignedBigInteger('user_id')->comment('Usuário que criou o orçamento');

            // Timestamps
            $table->timestamps();
            $table->softDeletes()->comment('Exclusão suave');

            // Índices
            $table->index('status');
            $table->index('user_id');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('cp_users')->onDelete('cascade');
            $table->foreign('orcamento_origem_id')->references('id')->on('cp_orcamentos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_orcamentos');
    }
};
