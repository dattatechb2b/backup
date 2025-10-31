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
        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            // NOTA: prazo_entrega_dias e frete já existem na tabela
            // Vamos adicionar apenas os campos NOVOS necessários

            // 1. Condição de pagamento (NOVO)
            $table->text('condicao_pagamento')->nullable()->after('observacao');

            // 2. Tipo de frete expandido (NOVO - complementa o campo "frete" existente)
            $table->string('frete_tipo', 20)->nullable()->after('condicao_pagamento')
                ->comment('CIF, FOB, OUTRO - complementa campo frete existente');

            // 3. Valor do frete (NOVO)
            $table->decimal('frete_valor', 10, 2)->nullable()->after('frete_tipo');

            // 4. Garantia (NOVO)
            $table->integer('garantia_meses')->nullable()->after('frete_valor');

            // 5. Anexo da proposta (NOVO)
            $table->bigInteger('anexo_id')->nullable()->after('garantia_meses')
                ->comment('FK para tabela anexos (proposta PDF)');

            // 6. URL da resposta/proposta online (NOVO)
            $table->string('url', 500)->nullable()->after('anexo_id')
                ->comment('URL da resposta/proposta online');

            // Análise crítica (2 CAMPOS NOVOS para fechar o ciclo)

            // 7. Situação (VALIDA ou EXPURGADA) - NOVO
            $table->string('situacao_analise', 20)->default('VALIDA')->after('url')
                ->comment('VALIDA ou EXPURGADA - análise crítica da cotação');

            // 8. Motivo do expurgo - NOVO
            $table->string('motivo_expurgo', 50)->nullable()->after('situacao_analise')
                ->comment('ACIMA_MEDIA_DP, ABAIXO_MEDIA_DP, CV_ALTO, DIVERGENCIA_UNIDADE, INCONSISTENCIA_DADOS, OUTRO');
        });

        // Criar FK para tabela anexos (será criada na próxima migration)
        // Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
        //     $table->foreign('anexo_id', 'fk_cdf_anexo')
        //         ->references('id')->on('cp_anexos')
        //         ->onDelete('set null');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
        //     $table->dropForeign('fk_cdf_anexo');
        // });

        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->dropColumn([
                'condicao_pagamento',
                'frete_tipo',
                'frete_valor',
                'garantia_meses',
                'anexo_id',
                'url',
                'situacao_analise',
                'motivo_expurgo',
            ]);
        });
    }
};
