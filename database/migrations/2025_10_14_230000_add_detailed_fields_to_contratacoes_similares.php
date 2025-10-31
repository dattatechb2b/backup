<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IMPORTANTE: Esta migration adiciona campos para compatibilidade com PDF formal
     * Adiciona 9 campos novos SEM modificar os existentes (seguro)
     */
    public function up(): void
    {
        Schema::table('cp_contratacoes_similares', function (Blueprint $table) {
            // 1. TIPO DE FONTE - Classificação da origem dos dados
            $table->enum('tipo_fonte', [
                'Portal PNCP',
                'Portal TCE',
                'Portal Transparência',
                'E-commerce',
                'Cotação Direta',
                'Outro'
            ])->nullable()->after('tipo')
                ->comment('Classificação do tipo de fonte de dados');

            // 2. ORIGEM/SISTEMA - Nome do sistema específico
            $table->string('origem_sistema', 100)->nullable()->after('tipo_fonte')
                ->comment('Sistema específico: PNCP, TCE/RS, CGU, Portal Transparência, etc.');

            // 3. CÓDIGO DE IDENTIFICAÇÃO - ID único no sistema de origem
            $table->string('codigo_identificacao', 150)->nullable()->after('numero_processo')
                ->comment('Código único no sistema de origem (ex: 00000.000000/0000-00)');

            // 4. LOTE/ITEM NA FONTE - Como está identificado na fonte original
            $table->string('lote_item_fonte', 100)->nullable()->after('codigo_identificacao')
                ->comment('Identificação do Lote/Item no sistema de origem (ex: Lote 01 - Item 003)');

            // 5. FORNECEDOR NOME - Razão social do fornecedor
            $table->string('fornecedor_nome', 255)->nullable()->after('ente_publico')
                ->comment('Razão social do fornecedor vencedor');

            // 6. FORNECEDOR CNPJ - CNPJ do fornecedor
            $table->string('fornecedor_cnpj', 20)->nullable()->after('fornecedor_nome')
                ->comment('CNPJ do fornecedor (formato: 00.000.000/0000-00)');

            // 7. MARCA - Marca do produto/serviço
            $table->string('marca', 150)->nullable()->after('fornecedor_cnpj')
                ->comment('Marca do produto ou serviço contratado');

            // 8. SITUAÇÃO - Status da amostra (validada ou expurgada)
            $table->enum('situacao', ['validada', 'expurgada'])->default('validada')->after('link_oficial')
                ->comment('Status da amostra na análise estatística');

            // 9. JUSTIFICATIVA EXPURGO - Explicação caso expurgada
            $table->text('justificativa_expurgo')->nullable()->after('situacao')
                ->comment('Justificativa técnica caso situacao = expurgada');

            // Índices para performance (opcional, mas recomendado)
            $table->index('tipo_fonte');
            $table->index('situacao');
            $table->index('fornecedor_cnpj');
        });
    }

    /**
     * Reverse the migrations.
     *
     * SEGURO: Remove apenas os campos adicionados, mantém os originais
     */
    public function down(): void
    {
        Schema::table('cp_contratacoes_similares', function (Blueprint $table) {
            // Remover índices primeiro
            $table->dropIndex(['tipo_fonte']);
            $table->dropIndex(['situacao']);
            $table->dropIndex(['fornecedor_cnpj']);

            // Remover colunas na ordem inversa
            $table->dropColumn([
                'justificativa_expurgo',
                'situacao',
                'marca',
                'fornecedor_cnpj',
                'fornecedor_nome',
                'lote_item_fonte',
                'codigo_identificacao',
                'origem_sistema',
                'tipo_fonte',
            ]);
        });
    }
};
