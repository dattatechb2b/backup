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
        Schema::table('cp_fornecedores', function (Blueprint $table) {
            // Tags de segmento (material_escritorio, informatica, etc)
            $table->jsonb('tags_segmento')->nullable()->after('observacoes')
                ->comment('Tags de categorização automática (JSON array)');

            // Número de ocorrências em contratos PNCP
            $table->integer('ocorrencias')->default(1)->after('tags_segmento')
                ->comment('Quantidade de vezes que apareceu em contratos PNCP');

            // Status do fornecedor
            $table->string('status', 50)->default('publico_nao_verificado')->after('ocorrencias')
                ->comment('publico_nao_verificado, verificado, favorito, oculto');

            // URL da fonte PNCP
            $table->text('fonte_url')->nullable()->after('status')
                ->comment('Link do contrato PNCP de onde foi extraído');

            // Data da última atualização dos dados PNCP
            $table->timestamp('ultima_atualizacao')->nullable()->after('fonte_url')
                ->comment('Data da última coleta/atualização via PNCP');

            // Origem do cadastro
            $table->string('origem', 20)->default('manual')->after('ultima_atualizacao')
                ->comment('manual, pncp, receita_federal');

            // Índices para performance
            $table->index('status');
            $table->index('origem');
            $table->index('ocorrencias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_fornecedores', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['origem']);
            $table->dropIndex(['ocorrencias']);

            $table->dropColumn([
                'tags_segmento',
                'ocorrencias',
                'status',
                'fonte_url',
                'ultima_atualizacao',
                'origem'
            ]);
        });
    }
};
