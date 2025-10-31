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
        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->string('token_resposta', 64)->unique()->nullable()->after('status');
            $table->timestamp('valido_ate')->nullable()->after('token_resposta');
            $table->boolean('respondido')->default(false)->after('valido_ate');
            $table->timestamp('data_resposta_fornecedor')->nullable()->after('respondido');

            // Índices para otimização
            $table->index('token_resposta');
            $table->index('valido_ate');
            $table->index('respondido');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_solicitacoes_cdf', function (Blueprint $table) {
            $table->dropIndex(['token_resposta']);
            $table->dropIndex(['valido_ate']);
            $table->dropIndex(['respondido']);

            $table->dropColumn([
                'token_resposta',
                'valido_ate',
                'respondido',
                'data_resposta_fornecedor'
            ]);
        });
    }
};
