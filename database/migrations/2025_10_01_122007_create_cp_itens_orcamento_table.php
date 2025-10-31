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
        Schema::create('cp_itens_orcamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('cp_orcamentos')->onDelete('cascade');
            $table->foreignId('lote_id')->nullable()->constrained('cp_lotes')->onDelete('set null');
            $table->text('descricao');
            $table->string('medida_fornecimento', 50);
            $table->decimal('quantidade', 15, 4);
            $table->string('indicacao_marca')->nullable();
            $table->enum('tipo', ['produto', 'servico'])->default('servico');
            $table->boolean('alterar_cdf')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('orcamento_id');
            $table->index('lote_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_itens_orcamento');
    }
};
