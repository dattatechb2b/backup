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
        Schema::create('cp_checkpoint_importacao', function (Blueprint $table) {
            $table->id();

            $table->string('fonte', 50); // 'TCE-RS-CONTRATOS', 'TCE-RS-LICITACOES', 'COMPRASNET'
            $table->string('arquivo', 255);
            $table->string('checksum', 64);

            $table->string('status', 20); // 'em_processamento', 'concluido', 'erro'

            $table->integer('total_registros')->default(0);
            $table->integer('registros_processados')->default(0);
            $table->integer('registros_novos')->default(0);
            $table->integer('registros_atualizados')->default(0);
            $table->integer('registros_erro')->default(0);

            $table->integer('ultima_linha_processada')->default(0);

            $table->text('erro_mensagem')->nullable();

            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();

            $table->timestamps();

            // Constraint único
            $table->unique(['fonte', 'arquivo', 'checksum']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_checkpoint_importacao');
    }
};
