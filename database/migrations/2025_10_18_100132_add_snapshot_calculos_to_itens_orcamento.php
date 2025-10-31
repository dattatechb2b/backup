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
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            // Snapshot de estatísticas (após saneamento) - 16 CAMPOS TOTAL

            // 1. Número de amostras válidas
            $table->integer('calc_n_validas')->nullable()->after('preco_unitario')
                ->comment('Nº de amostras válidas (após expurgo)');

            // 2. Média saneada
            $table->decimal('calc_media', 10, 2)->nullable()->after('calc_n_validas')
                ->comment('Média saneada');

            // 3. Mediana saneada
            $table->decimal('calc_mediana', 10, 2)->nullable()->after('calc_media')
                ->comment('Mediana saneada');

            // 4. Desvio-padrão saneado (populacional, ÷ n)
            $table->decimal('calc_dp', 10, 2)->nullable()->after('calc_mediana')
                ->comment('Desvio-padrão saneado (populacional, ÷ n)');

            // 5. Coeficiente de variação (%)
            $table->decimal('calc_cv', 10, 4)->nullable()->after('calc_dp')
                ->comment('Coeficiente de variação (%) = DP/Média * 100');

            // 6. Menor preço válido
            $table->decimal('calc_menor', 10, 2)->nullable()->after('calc_cv')
                ->comment('Menor preço válido');

            // 7. Maior preço válido
            $table->decimal('calc_maior', 10, 2)->nullable()->after('calc_menor')
                ->comment('Maior preço válido');

            // 8. Limite inferior (μ - σ)
            $table->decimal('calc_lim_inf', 10, 2)->nullable()->after('calc_maior')
                ->comment('Limite inferior (μ - σ)');

            // 9. Limite superior (μ + σ)
            $table->decimal('calc_lim_sup', 10, 2)->nullable()->after('calc_lim_inf')
                ->comment('Limite superior (μ + σ)');

            // 10. Método escolhido
            $table->string('calc_metodo', 20)->nullable()->after('calc_lim_sup')
                ->comment('MEDIA ou MEDIANA');

            // 11. Timestamp do snapshot
            $table->timestamp('calc_carimbado_em')->nullable()->after('calc_metodo')
                ->comment('Quando o snapshot foi fixado (botão Fixar)');

            // 12. Hash SHA-256 das amostras (NOVO - CORREÇÃO DO USUÁRIO)
            $table->string('calc_hash_amostras', 64)->nullable()->after('calc_carimbado_em')
                ->comment('SHA-256 do conjunto ordenado de IDs das amostras válidas - para detectar alterações');

            // 13. Curva ABC - Valor total do item
            $table->decimal('abc_valor_total', 10, 2)->nullable()->after('calc_hash_amostras');

            // 14. Curva ABC - Participação percentual
            $table->decimal('abc_participacao', 18, 6)->nullable()->after('abc_valor_total')
                ->comment('% do valor total do orçamento');

            // 15. Curva ABC - Percentual acumulado
            $table->decimal('abc_acumulada', 18, 6)->nullable()->after('abc_participacao')
                ->comment('% acumulada');

            // 16. Curva ABC - Classe (A, B, ou C)
            $table->char('abc_classe', 1)->nullable()->after('abc_acumulada')
                ->comment('A, B, ou C');
        });

        // Criar índice composto para performance na geração do PDF
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            $table->index(['orcamento_id', 'id'], 'idx_itens_orcamento_pdf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            $table->dropIndex('idx_itens_orcamento_pdf');

            $table->dropColumn([
                'calc_n_validas',
                'calc_media',
                'calc_mediana',
                'calc_dp',
                'calc_cv',
                'calc_menor',
                'calc_maior',
                'calc_lim_inf',
                'calc_lim_sup',
                'calc_metodo',
                'calc_carimbado_em',
                'calc_hash_amostras',  // NOVO
                'abc_valor_total',
                'abc_participacao',
                'abc_acumulada',
                'abc_classe',
            ]);
        });
    }
};
