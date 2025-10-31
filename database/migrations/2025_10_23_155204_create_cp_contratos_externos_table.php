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
        // IMPORTANTE: Esta tabela usa conexão 'pgsql_main' (não do tenant)
        // Só criar se NÃO existir (para evitar erro em re-execução)
        if (Schema::hasTable('cp_contratos_externos')) {
            return;
        }

        Schema::create('cp_contratos_externos', function (Blueprint $table) {
            $table->id();

            // Identificação
            $table->string('fonte', 50); // 'TCE-RS', 'COMPRASNET', 'PNCP'
            $table->string('id_externo', 255);
            $table->string('hash_normalizado', 64)->unique();

            // Dados do contrato
            $table->string('numero_contrato', 100)->nullable();
            $table->text('objeto')->nullable();
            $table->decimal('valor_total', 15, 2)->nullable();
            $table->date('data_assinatura')->nullable();
            $table->date('data_vigencia_inicio')->nullable();
            $table->date('data_vigencia_fim')->nullable();

            // Órgão
            $table->string('orgao_nome', 255)->nullable();
            $table->string('orgao_cnpj', 18)->nullable();
            $table->string('orgao_uf', 2)->nullable();
            $table->string('orgao_municipio', 100)->nullable();

            // Fornecedor
            $table->string('fornecedor_nome', 255)->nullable();
            $table->string('fornecedor_cnpj', 18)->nullable();

            // Metadata
            $table->text('url_fonte')->nullable();
            $table->jsonb('dados_originais')->nullable();

            // Qualidade
            $table->integer('qualidade_score')->default(0);
            $table->jsonb('flags_qualidade')->nullable();

            $table->timestamps();

            // Índices
            $table->index('fonte');
            $table->index('hash_normalizado');
            $table->index('orgao_cnpj');
            $table->index('fornecedor_cnpj');
            $table->index('data_assinatura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_contratos_externos');
    }
};
