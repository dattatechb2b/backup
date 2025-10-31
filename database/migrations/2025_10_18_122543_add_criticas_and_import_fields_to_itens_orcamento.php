<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adiciona campos para:
     * 1. Checkboxes da Análise Crítica (criticas_dados - JSON)
     * 2. Rastreamento de importação de planilha (3 campos)
     */
    public function up(): void
    {
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            // 1. Campo JSON para armazenar checkboxes da análise crítica
            $table->json('criticas_dados')->nullable()->after('justificativa_cotacao')
                ->comment('Checkboxes marcados na análise crítica: medidas_desiguais, valores_discrepantes, etc.');

            // 2. Campos para rastrear importação de planilha
            $table->boolean('importado_de_planilha')->default(false)->after('criticas_dados')
                ->comment('Item foi importado de planilha Excel/CSV?');
            
            $table->string('nome_arquivo_planilha', 255)->nullable()->after('importado_de_planilha')
                ->comment('Nome do arquivo de planilha importado');
            
            $table->timestamp('data_importacao')->nullable()->after('nome_arquivo_planilha')
                ->comment('Data e hora da importação da planilha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            $table->dropColumn([
                'criticas_dados',
                'importado_de_planilha',
                'nome_arquivo_planilha',
                'data_importacao'
            ]);
        });
    }
};
