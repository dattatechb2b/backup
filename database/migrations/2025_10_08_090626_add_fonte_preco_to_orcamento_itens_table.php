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
            // Rastreabilidade da origem do preço
            $table->string('fonte_preco', 50)->nullable()->after('preco_unitario')
                ->comment('ARP, CATALOGO, CONTRATO, MANUAL');

            $table->text('fonte_url')->nullable()->after('fonte_preco')
                ->comment('Link PNCP/ComprasGov da fonte');

            $table->jsonb('fonte_detalhes')->nullable()->after('fonte_url')
                ->comment('JSON com detalhes: ata, uasg, badge, preco_coletado, catmat, coletado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_itens_orcamento', function (Blueprint $table) {
            $table->dropColumn(['fonte_preco', 'fonte_url', 'fonte_detalhes']);
        });
    }
};
