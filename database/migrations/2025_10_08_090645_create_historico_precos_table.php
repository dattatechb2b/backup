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
        Schema::create('cp_historico_precos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalogo_produto_id')->nullable()->constrained('cp_catalogo_produtos')->onDelete('cascade')->comment('FK para catálogo');
            $table->string('catmat', 20)->nullable()->comment('Código CATMAT (se não vinculado a catálogo)');
            $table->string('fonte', 20)->comment('ARP, CONTRATO, MANUAL');
            $table->text('fonte_url')->nullable()->comment('Link PNCP da fonte');
            $table->decimal('preco_unitario', 15, 4)->comment('Preço unitário coletado');
            $table->string('badge', 10)->nullable()->comment('🟢, 🟡, 🔴');
            $table->timestamp('data_coleta')->useCurrent()->comment('Data/hora da coleta');
            $table->timestamps();

            // Índices
            $table->index('catalogo_produto_id');
            $table->index('catmat');
            $table->index('fonte');
            $table->index('data_coleta');

            // Foreign key
            $table->foreign('catmat')->references('codigo')->on('cp_catmat')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_historico_precos');
    }
};
