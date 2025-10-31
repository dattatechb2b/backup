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
        Schema::create('cp_medicamentos_cmed', function (Blueprint $table) {
            $table->id();

            // Informações Básicas do Medicamento
            $table->text('substancia')->nullable(); // Princípio Ativo
            $table->string('cnpj_laboratorio', 18)->nullable();
            $table->string('laboratorio')->nullable();
            $table->string('codigo_ggrem')->nullable(); // Código GG
            $table->string('registro')->nullable(); // Registro MS

            // Códigos EAN (Código de Barras)
            $table->string('ean1', 13)->nullable()->index();
            $table->string('ean2', 13)->nullable();
            $table->string('ean3', 13)->nullable();

            // Detalhes do Produto
            $table->text('produto')->nullable()->index(); // Nome Comercial
            $table->text('apresentacao')->nullable(); // Ex: "COMPRIMIDO 25MG"
            $table->string('classe_terapeutica')->nullable();
            $table->string('tipo_produto')->nullable(); // Genérico/Similar/Referência
            $table->string('regime_preco')->nullable();

            // Preços PF (Preço Fábrica) - Diferentes alíquotas de ICMS
            $table->decimal('pf_sem_impostos', 10, 2)->nullable();
            $table->decimal('pf_0', 10, 2)->nullable(); // ICMS 0%
            $table->decimal('pf_12', 10, 2)->nullable(); // ICMS 12%
            $table->decimal('pf_12_sem_icms', 10, 2)->nullable();
            $table->decimal('pf_13', 10, 2)->nullable();
            $table->decimal('pf_13_com_icms', 10, 2)->nullable();
            $table->decimal('pf_14', 10, 2)->nullable();
            $table->decimal('pf_15', 10, 2)->nullable();
            $table->decimal('pf_15_com_icms', 10, 2)->nullable();
            $table->decimal('pf_16', 10, 2)->nullable();
            $table->decimal('pf_17', 10, 2)->nullable();
            $table->decimal('pf_17_alagas', 10, 2)->nullable();
            $table->decimal('pf_17_com_icms', 10, 2)->nullable();
            $table->decimal('pf_18', 10, 2)->nullable();
            $table->decimal('pf_18_com_icms', 10, 2)->nullable();
            $table->decimal('pf_19', 10, 2)->nullable();
            $table->decimal('pf_19_com_icms', 10, 2)->nullable();
            $table->decimal('pf_20', 10, 2)->nullable();
            $table->decimal('pf_20_com_icms', 10, 2)->nullable();
            $table->decimal('pf_21', 10, 2)->nullable();
            $table->decimal('pf_22', 10, 2)->nullable();
            $table->decimal('pf_23', 10, 2)->nullable();

            // Preços PMC (Preço Máximo ao Consumidor) - Diferentes alíquotas
            $table->decimal('pmc_sem_impostos', 10, 2)->nullable();
            $table->decimal('pmc_0', 10, 2)->nullable()->index(); // Mais usado
            $table->decimal('pmc_12', 10, 2)->nullable();
            $table->decimal('pmc_12_sem_icms', 10, 2)->nullable();
            $table->decimal('pmc_13', 10, 2)->nullable();
            $table->decimal('pmc_13_com_icms', 10, 2)->nullable();
            $table->decimal('pmc_14', 10, 2)->nullable();
            $table->decimal('pmc_15', 10, 2)->nullable();
            $table->decimal('pmc_15_com_icms', 10, 2)->nullable();
            $table->decimal('pmc_16', 10, 2)->nullable();
            $table->decimal('pmc_17', 10, 2)->nullable();
            $table->decimal('pmc_17_alagas', 10, 2)->nullable();
            $table->decimal('pmc_17_com_icms', 10, 2)->nullable();
            $table->decimal('pmc_18', 10, 2)->nullable();
            $table->decimal('pmc_18_com_icms', 10, 2)->nullable();
            $table->decimal('pmc_19', 10, 2)->nullable();
            $table->decimal('pmc_19_com_icms', 10, 2)->nullable();
            $table->decimal('pmc_20', 10, 2)->nullable();
            $table->decimal('pmc_20_com_icms', 10, 2)->nullable();
            $table->decimal('pmc_21', 10, 2)->nullable();
            $table->decimal('pmc_22', 10, 2)->nullable();
            $table->decimal('pmc_23', 10, 2)->nullable();

            // Dados Tributários e Regulatórios
            $table->boolean('restricao_hospitalar')->default(false);
            $table->boolean('cap')->default(false); // Coeficiente de Adequação de Preços
            $table->boolean('confaz')->default(false);
            $table->boolean('icms_0')->default(false);
            $table->text('analise_recursal')->nullable();
            $table->text('lista_concessao_credito')->nullable();
            $table->string('comercializacao_2024')->nullable();
            $table->decimal('taxa_anvisa', 10, 2)->nullable();

            // Controle de Importação
            $table->string('mes_referencia', 20)->nullable(); // Ex: "Outubro 2025"
            $table->date('data_importacao')->nullable();

            $table->timestamps();

            // Índices para busca rápida
            $table->index('substancia');
            $table->index('laboratorio');
            $table->index('tipo_produto');
            $table->index('mes_referencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_medicamentos_cmed');
    }
};
