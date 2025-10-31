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
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            // Metodologia e parâmetros aplicados (5 CAMPOS)

            // 1. Metodologia de análise crítica
            $table->string('metodologia_analise_critica', 50)->nullable()->after('observacoes')
                ->comment('DESVIO_PADRAO ou MEDIA_SANEADA_PERC');

            // 2. Medida de tendência central
            $table->string('medida_tendencia_central', 20)->nullable()->after('metodologia_analise_critica')
                ->comment('MEDIA, MEDIANA, MENOR_PRECO (pode ser null = automático por CV)');

            // 3. Prazo de validade das amostras
            $table->integer('prazo_validade_amostras')->nullable()->after('medida_tendencia_central')
                ->comment('Prazo em dias para considerar amostra válida');

            // 4. Número mínimo de amostras
            $table->integer('numero_minimo_amostras')->default(3)->after('prazo_validade_amostras')
                ->comment('Mínimo de amostras válidas exigidas');

            // 5. Aceitar fontes alternativas
            $table->boolean('aceitar_fontes_alternativas')->default(true)->after('numero_minimo_amostras')
                ->comment('Se permite e-commerce, CDF, etc.');

            // Flags de parâmetros utilizados - para exibir no PDF (3 CAMPOS)

            // 6. Flag: Utilizou Contratações Similares
            $table->boolean('usou_similares')->default(false)->after('aceitar_fontes_alternativas')
                ->comment('Utilizou Contratações Similares (PNCP, TCE, etc.)');

            // 7. Flag: Utilizou CDF
            $table->boolean('usou_cdf')->default(false)->after('usou_similares')
                ->comment('Utilizou Cotação Direta com Fornecedor');

            // 8. Flag: Utilizou E-commerce
            $table->boolean('usou_ecommerce')->default(false)->after('usou_cdf')
                ->comment('Utilizou E-commerce (Mercado Livre, Amazon, etc.)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            $table->dropColumn([
                'metodologia_analise_critica',
                'medida_tendencia_central',
                'prazo_validade_amostras',
                'numero_minimo_amostras',
                'aceitar_fontes_alternativas',
                'usou_similares',
                'usou_cdf',
                'usou_ecommerce',
            ]);
        });
    }
};
