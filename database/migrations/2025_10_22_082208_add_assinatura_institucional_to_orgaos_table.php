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
            $table->string('responsavel_nome')->nullable()->after('brasao_path');
            $table->string('responsavel_matricula_siape')->nullable()->after('responsavel_nome');
            $table->string('responsavel_cargo')->nullable()->after('responsavel_matricula_siape');
            $table->string('responsavel_portaria')->nullable()->after('responsavel_cargo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_orgaos', function (Blueprint $table) {
            $table->dropColumn([
                'responsavel_nome',
                'responsavel_matricula_siape',
                'responsavel_cargo',
                'responsavel_portaria'
            ]);
        });
    }
};
