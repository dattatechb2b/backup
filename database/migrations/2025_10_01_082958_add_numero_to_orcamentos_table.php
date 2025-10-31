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
            $table->string('numero', 50)->nullable()->unique()->after('id')->comment('Número do orçamento no formato ID/ANO (ex: 00001/2025)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            $table->dropColumn('numero');
        });
    }
};
