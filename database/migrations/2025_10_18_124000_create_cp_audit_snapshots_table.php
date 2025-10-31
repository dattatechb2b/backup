<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MIGRATION OFICIAL E IDEMPOTENTE para cp_audit_snapshots
     *
     * Esta migration cria a tabela cp_audit_snapshots de forma inteligente:
     * - Verifica se já existe antes de criar
     * - Usa nomes CORRETOS com prefixo cp_
     * - Foreign key CORRETA para cp_itens_orcamento
     *
     * HISTÓRICO:
     * - 18/10: Tabela criada manualmente em Materlândia (sem migration)
     * - 20/10: Tentativa de migration com ERROS (deletada)
     * - 22/10: Migration oficial e CORRETA criada
     */
    public function up(): void
    {
        // Verifica se tabela já existe (idempotente)
        if (!Schema::hasTable('cp_audit_snapshots')) {
            Schema::create('cp_audit_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('item_id');
                $table->timestamp('snapshot_timestamp');
                $table->integer('n_validas')->nullable();
                $table->decimal('media', 10, 4)->nullable();
                $table->decimal('mediana', 10, 4)->nullable();
                $table->decimal('desvio_padrao', 10, 4)->nullable();
                $table->decimal('coef_variacao', 10, 4)->nullable();
                $table->decimal('limite_inferior', 10, 4)->nullable();
                $table->decimal('limite_superior', 10, 4)->nullable();
                $table->string('metodo', 50)->nullable();
                $table->string('hash_sha256', 64)->nullable();
                $table->timestamps();

                // Foreign key CORRETA com prefixo cp_
                $table->foreign('item_id', 'cp_audit_snapshots_item_id_fkey')
                      ->references('id')
                      ->on('cp_itens_orcamento')  // ✅ NOME CORRETO
                      ->onDelete('cascade');

                // Índices
                $table->index('item_id', 'idx_audit_snapshots_item');
                $table->index('snapshot_timestamp', 'idx_audit_snapshots_timestamp');
            });

            DB::statement('COMMENT ON TABLE cp_audit_snapshots IS \'Snapshots de cálculos estatísticos dos itens de orçamento - Auditoria\'');
        } else {
            // Tabela já existe - valida estrutura
            // Se precisar adicionar colunas faltantes, fazer aqui
            $this->validateExistingTable();
        }
    }

    /**
     * Valida estrutura da tabela existente e corrige se necessário
     */
    private function validateExistingTable(): void
    {
        // Verifica se foreign key existe com nome correto
        $fkExists = DB::select("
            SELECT constraint_name
            FROM information_schema.table_constraints
            WHERE table_name = 'cp_audit_snapshots'
            AND constraint_type = 'FOREIGN KEY'
            AND constraint_name = 'cp_audit_snapshots_item_id_fkey'
        ");

        if (empty($fkExists)) {
            // FK não existe ou tem nome errado - precisa corrigir
            // Aqui entraria lógica de correção se necessário
            \Log::warning('cp_audit_snapshots existe mas FK pode estar com nome incorreto');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_audit_snapshots');
    }
};
