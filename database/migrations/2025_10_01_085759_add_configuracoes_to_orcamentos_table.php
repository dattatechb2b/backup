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
            // Método do Juízo Crítico
            $table->enum('metodo_juizo_critico', ['saneamento_desvio_padrao', 'saneamento_percentual'])
                ->default('saneamento_desvio_padrao')
                ->comment('Método para saneamento das amostras');

            // Método de Obtenção do Preço Estimado
            $table->enum('metodo_obtencao_preco', ['media_mediana', 'mediana_todas', 'media_todas', 'menor_preco'])
                ->default('media_mediana')
                ->comment('Método para calcular preço estimado');

            // Padrão de Casas Decimais
            $table->enum('casas_decimais', ['duas', 'quatro'])
                ->default('duas')
                ->comment('Número de casas decimais para valores');

            // Campo para observações/justificativa
            $table->text('observacao_justificativa')->nullable()->comment('Observação ou justificativa geral do orçamento');

            // Campo para anexo PDF
            $table->string('anexo_pdf')->nullable()->comment('Caminho do arquivo PDF anexado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            $table->dropColumn([
                'metodo_juizo_critico',
                'metodo_obtencao_preco',
                'casas_decimais',
                'observacao_justificativa',
                'anexo_pdf'
            ]);
        });
    }
};
