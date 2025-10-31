<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cp_arp_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ata_id')->constrained('cp_arp_cabecalhos')->onDelete('cascade')->comment('FK para ata');
            $table->string('catmat', 20)->nullable()->comment('Código CATMAT');
            $table->text('descricao')->comment('Descrição do item');
            $table->string('unidade', 50)->comment('Unidade de medida normalizada');
            $table->decimal('preco_unitario', 15, 4)->comment('Preço unitário oficial');
            $table->decimal('quantidade_registrada', 15, 4)->nullable()->comment('Quantidade registrada na ata');
            $table->string('lote', 50)->nullable()->comment('Número do lote (se houver)');
            $table->string('badge_confianca', 10)->default('ALTA')->comment('Sempre ALTA para ARP');
            $table->timestamp('coletado_em')->useCurrent()->comment('Data/hora da coleta');
            $table->timestamps();

            // Índices
            $table->index('ata_id');
            $table->index('catmat');
            $table->index('coletado_em');

            // Foreign key para CATMAT (opcional - pode não existir ainda)
            $table->foreign('catmat')->references('codigo')->on('cp_catmat')->onDelete('set null');
        });

        // Índice único funcional para evitar duplicatas (ata_id + catmat + lote + MD5(descricao))
        // Prefixo cp_ hardcoded (conexão tenant_install não tem prefixo)
        DB::statement("CREATE UNIQUE INDEX unique_arp_item ON cp_arp_itens (ata_id, COALESCE(catmat, ''), COALESCE(lote, ''), MD5(descricao))");

        // Índice fulltext para busca por descrição (PostgreSQL)
        DB::statement("CREATE INDEX idx_arp_itens_descricao_fulltext ON cp_arp_itens USING gin(to_tsvector('portuguese', descricao))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_arp_itens');
    }
};
