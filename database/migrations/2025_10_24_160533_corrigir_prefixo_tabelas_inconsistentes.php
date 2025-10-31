<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrigir tabelas que foram criadas sem o prefixo cp_
     *
     * CONTEXTO:
     * - Todas as tabelas do módulo devem ter prefixo cp_ para isolamento multitenancy
     * - 2 migrations criaram tabelas sem o prefixo, mas os Models esperam com prefixo
     * - Esta migration corrige essa inconsistência renomeando as tabelas
     *
     * IMPACTO:
     * - checkpoint_importacao → cp_checkpoint_importacao
     * - consultas_pncp_cache → cp_consultas_pncp_cache
     *
     * SEGURANÇA:
     * - Todas as tabelas estão vazias (0 registros)
     * - Sem foreign keys afetadas
     * - Reversível via down()
     */
    public function up(): void
    {
        // Renomear checkpoint_importacao para cp_checkpoint_importacao
        if (Schema::hasTable('checkpoint_importacao') && !Schema::hasTable('cp_checkpoint_importacao')) {
            Schema::rename('checkpoint_importacao', 'cp_checkpoint_importacao');

            DB::statement('COMMENT ON TABLE cp_checkpoint_importacao IS \'Rastreamento de progresso de importações de dados externos (TCE-RS, etc)\'');
        }

        // Renomear consultas_pncp_cache para cp_consultas_pncp_cache
        if (Schema::hasTable('consultas_pncp_cache') && !Schema::hasTable('cp_consultas_pncp_cache')) {
            Schema::rename('consultas_pncp_cache', 'cp_consultas_pncp_cache');

            DB::statement('COMMENT ON TABLE cp_consultas_pncp_cache IS \'Cache de consultas à API PNCP (Portal Nacional de Contratações Públicas)\'');
        }
    }

    /**
     * Reverter renomeação das tabelas
     */
    public function down(): void
    {
        // Reverter cp_checkpoint_importacao para checkpoint_importacao
        if (Schema::hasTable('cp_checkpoint_importacao') && !Schema::hasTable('checkpoint_importacao')) {
            Schema::rename('cp_checkpoint_importacao', 'checkpoint_importacao');
        }

        // Reverter cp_consultas_pncp_cache para consultas_pncp_cache
        if (Schema::hasTable('cp_consultas_pncp_cache') && !Schema::hasTable('consultas_pncp_cache')) {
            Schema::rename('cp_consultas_pncp_cache', 'consultas_pncp_cache');
        }
    }
};
