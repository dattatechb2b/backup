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
        Schema::create('cp_anexos', function (Blueprint $table) {
            // 1. ID primário
            $table->id();

            // 2-4. Vinculação (um dos três)
            $table->bigInteger('amostra_id')->nullable()
                ->comment('FK para amostras_selecionadas');
            $table->bigInteger('item_id')->nullable()
                ->comment('FK para itens_orcamento');
            $table->bigInteger('orcamento_id')
                ->comment('FK para orcamentos');

            // 5. Tipo do anexo
            $table->string('tipo', 50)
                ->comment('PDF_CONTRATO, SCREENSHOT, PLANILHA_IMPORTACAO, PROPOSTA_CDF');

            // 6. Nome do arquivo
            $table->string('nome_arquivo', 255);

            // 7. Caminho do arquivo
            $table->string('caminho', 500);

            // 8. Tamanho em bytes
            $table->integer('tamanho_bytes');

            // 9. Hash SHA-256
            $table->string('hash_sha256', 64);

            // 10. Mime type
            $table->string('mime_type', 100);

            // 11. Páginas (se for PDF)
            $table->integer('paginas')->nullable()
                ->comment('Número de páginas se for PDF');

            // 12. Uploaded by (quem fez upload)
            $table->bigInteger('uploaded_by')->nullable()
                ->comment('FK para users');

            // 13-14. Timestamps
            $table->timestamps();

            // Constraints (Foreign Keys)
            // Nota: Assumindo que as tabelas já existem
            // Se houver problemas, as FKs podem ser adicionadas em migration separada

            // $table->foreign('amostra_id', 'fk_anexos_amostra')
            //     ->references('id')->on('cp_amostras_selecionadas')
            //     ->onDelete('cascade');

            // $table->foreign('item_id', 'fk_anexos_item')
            //     ->references('id')->on('cp_itens_orcamento')
            //     ->onDelete('cascade');

            // $table->foreign('orcamento_id', 'fk_anexos_orcamento')
            //     ->references('id')->on('cp_orcamentos')
            //     ->onDelete('cascade');

            // $table->foreign('uploaded_by', 'fk_anexos_user')
            //     ->references('id')->on('cp_users')
            //     ->onDelete('set null');
        });

        // Índices para performance
        Schema::table('cp_anexos', function (Blueprint $table) {
            $table->index('amostra_id', 'idx_anexos_amostra');
            $table->index('item_id', 'idx_anexos_item');
            $table->index('orcamento_id', 'idx_anexos_orcamento');
            $table->index('hash_sha256', 'idx_anexos_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_anexos');
    }
};
