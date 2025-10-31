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
        Schema::create('cp_licitacon_cache', function (Blueprint $table) {
            $table->id();
            $table->string('numero_licitacao', 100)->nullable()->comment('Número da licitação');
            $table->text('objeto')->nullable()->comment('Objeto da licitação');
            $table->text('descricao')->nullable()->comment('Descrição do item');
            $table->decimal('valor_unitario', 15, 2)->nullable()->comment('Valor unitário do item');
            $table->string('unidade_medida', 20)->nullable()->comment('Unidade de medida (KG, UN, etc)');
            $table->string('orgao', 255)->nullable()->comment('Órgão responsável');
            $table->string('municipio', 100)->nullable()->comment('Município');
            $table->date('data_homologacao')->nullable()->comment('Data de homologação');
            $table->string('fornecedor', 255)->nullable()->comment('Fornecedor vencedor');
            $table->timestamps();
            $table->softDeletes();

            // Índices para busca rápida
            $table->index(['descricao', 'objeto'], 'idx_licitacon_busca');
            $table->index('municipio', 'idx_licitacon_municipio');
            $table->index('data_homologacao', 'idx_licitacon_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_licitacon_cache');
    }
};
