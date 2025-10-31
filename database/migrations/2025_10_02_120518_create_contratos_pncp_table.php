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
        Schema::create('cp_contratos_pncp', function (Blueprint $table) {
            $table->id();

            // Identificação PNCP
            $table->string('numero_controle_pncp')->unique()->index();
            $table->string('tipo')->index(); // 'contrato' ou 'ata'

            // Dados do contrato
            $table->text('objeto_contrato'); // Descrição completa
            $table->decimal('valor_global', 15, 2)->nullable();
            $table->integer('numero_parcelas')->nullable();
            $table->decimal('valor_unitario_estimado', 15, 2)->nullable();
            $table->string('unidade_medida')->nullable();

            // Órgão
            $table->string('orgao_cnpj')->nullable()->index();
            $table->string('orgao_razao_social')->nullable();
            $table->string('orgao_uf', 2)->nullable()->index();
            $table->string('orgao_municipio')->nullable();

            // Datas
            $table->date('data_publicacao_pncp')->index();
            $table->date('data_vigencia_inicio')->nullable();
            $table->date('data_vigencia_fim')->nullable();

            // Classificação/Confiabilidade
            $table->enum('confiabilidade', ['alta', 'media', 'baixa'])->default('baixa')->index();
            $table->boolean('valor_estimado')->default(false);

            // Sincronização
            $table->timestamp('sincronizado_em')->useCurrent();
            $table->timestamps();

            // Índices para busca rápida
            $table->index(['data_publicacao_pncp', 'tipo']);
            $table->index(['orgao_uf', 'data_publicacao_pncp']);
        });

        // Criar índice GIN para busca full-text no PostgreSQL
        DB::statement('CREATE INDEX contratos_pncp_objeto_gin ON cp_contratos_pncp USING GIN (to_tsvector(\'portuguese\', objeto_contrato))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_contratos_pncp');
    }
};
