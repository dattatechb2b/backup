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
        Schema::create('cp_notificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('cp_users')->onDelete('cascade');
            $table->string('tipo'); // 'cdf_respondida', 'cdf_vencendo', etc.
            $table->string('titulo');
            $table->text('mensagem');
            $table->json('dados')->nullable();
            $table->boolean('lida')->default(false);
            $table->timestamp('lida_em')->nullable();
            $table->timestamps();

            // Índices para otimização de consultas
            $table->index('user_id');
            $table->index('tipo');
            $table->index('lida');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_notificacoes');
    }
};
