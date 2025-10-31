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
        Schema::create('cp_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('cp_orcamentos')->onDelete('cascade');
            $table->integer('numero');
            $table->string('nome', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->index('orcamento_id');
            $table->unique(['orcamento_id', 'numero']); // Não pode ter lotes com mesmo número no mesmo orçamento
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_lotes');
    }
};
