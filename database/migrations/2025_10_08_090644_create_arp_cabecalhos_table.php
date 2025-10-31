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
        Schema::create('cp_arp_cabecalhos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ata', 50)->comment('Número da ata (ex: 001/2025)');
            $table->integer('ano_ata')->nullable()->comment('Ano da ata');
            $table->string('orgao_gerenciador')->comment('Nome do órgão gerenciador');
            $table->string('cnpj_orgao', 18)->comment('CNPJ do órgão (14 dígitos)');
            $table->string('uasg', 20)->nullable()->comment('Código UASG');
            $table->integer('ano_compra')->comment('Ano da compra no PNCP');
            $table->integer('sequencial_compra')->comment('Sequencial da compra no PNCP');
            $table->date('vigencia_inicio')->nullable()->comment('Data início vigência');
            $table->date('vigencia_fim')->nullable()->comment('Data fim vigência');
            $table->string('situacao', 20)->comment('Vigente ou Expirada');
            $table->text('fornecedor_razao')->nullable()->comment('Razão social do fornecedor');
            $table->string('fornecedor_cnpj', 18)->nullable()->comment('CNPJ do fornecedor');
            $table->text('fonte_url')->nullable()->comment('Link PNCP da ata');
            $table->jsonb('payload_json')->nullable()->comment('JSON bruto da API PNCP (auditoria)');
            $table->timestamp('coletado_em')->useCurrent()->comment('Data/hora da coleta');
            $table->unsignedBigInteger('coletado_por')->nullable()->comment('ID do usuário que coletou');
            $table->timestamps();

            // Chave única: não duplicar mesma ata do mesmo órgão
            $table->unique(['cnpj_orgao', 'ano_compra', 'sequencial_compra', 'numero_ata'], 'unique_ata');

            // Índices
            $table->index('situacao');
            $table->index('vigencia_fim');
            $table->index('cnpj_orgao');
            $table->index('uasg');
            $table->index('coletado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_arp_cabecalhos');
    }
};
