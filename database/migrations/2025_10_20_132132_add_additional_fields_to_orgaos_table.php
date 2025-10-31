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
        Schema::table('cp_orgaos', function (Blueprint $table) {
            // Adicionar campos para configurações completas do órgão
            $table->string('numero', 20)->nullable()->after('endereco');
            $table->string('complemento', 100)->nullable()->after('numero');
            $table->string('bairro', 100)->nullable()->after('complemento');
            $table->string('telefone', 20)->nullable()->after('uf');
            $table->string('email', 150)->nullable()->after('telefone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_orgaos', function (Blueprint $table) {
            $table->dropColumn(['numero', 'complemento', 'bairro', 'telefone', 'email']);
        });
    }
};
