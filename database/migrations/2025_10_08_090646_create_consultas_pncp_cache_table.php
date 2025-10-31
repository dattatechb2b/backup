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
        Schema::create('cp_consultas_pncp_cache', function (Blueprint $table) {
            $table->id();
            $table->string('hash_consulta', 64)->unique()->comment('MD5 dos parâmetros da consulta');
            $table->string('tipo', 20)->comment('ARP, CONTRATO, CATMAT');
            $table->jsonb('parametros')->comment('Parâmetros da consulta (termo, período, etc)');
            $table->jsonb('resposta_json')->nullable()->comment('JSON completo da resposta PNCP');
            $table->timestamp('coletado_em')->useCurrent()->comment('Data/hora da consulta');
            $table->timestamp('ttl_expira_em')->comment('Data/hora de expiração do cache');
            $table->timestamps();

            // Índices
            $table->index('hash_consulta');
            $table->index('tipo');
            $table->index('ttl_expira_em');
            $table->index('coletado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_consultas_pncp_cache');
    }
};
