<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrigir constraint duplicada de status em cp_solicitacoes_cdf
     *
     * CONTEXTO:
     * - A migration 2025_10_07_164801 tentou dropar a constraint antiga
     * - Mas usou o nome errado: 'solicitacoes_cdf_status_check'
     * - O nome correto é: 'cp_solicitacoes_cdf_status_check' (com prefixo)
     * - Isso resultou em 2 constraints coexistindo, causando erro ao salvar status 'Descartada'
     *
     * PROBLEMA:
     * - Constraint ANTIGA: permite apenas ['Pendente', 'Enviado', 'Respondido', 'Vencido', 'Cancelado']
     * - Constraint NOVA: permite também ['Validada', 'Descartada', 'Aguardando resposta']
     * - PostgreSQL valida AMBAS, então 'Descartada' é rejeitada pela antiga
     *
     * SOLUÇÃO:
     * - Dropar constraint ANTIGA usando o nome correto
     * - Manter apenas a constraint NOVA que permite todos os status
     */
    public function up(): void
    {
        // Dropar a constraint antiga que estava bloqueando 'Descartada' e 'Validada'
        // Usar IF EXISTS para evitar erro se já foi corrigida manualmente
        DB::statement("
            ALTER TABLE cp_solicitacoes_cdf
            DROP CONSTRAINT IF EXISTS cp_solicitacoes_cdf_status_check
        ");

        // Verificar que a constraint correta ainda existe
        // (A constraint 'solicitacoes_cdf_status_check' criada pela migration 2025_10_07_164801)
        $constraintExists = DB::selectOne("
            SELECT COUNT(*) as total
            FROM pg_constraint con
            JOIN pg_class rel ON rel.oid = con.conrelid
            WHERE rel.relname = 'cp_solicitacoes_cdf'
              AND con.conname = 'solicitacoes_cdf_status_check'
        ");

        if ($constraintExists->total == 0) {
            // Se por algum motivo a constraint correta não existe, criar ela
            DB::statement("
                ALTER TABLE cp_solicitacoes_cdf
                ADD CONSTRAINT solicitacoes_cdf_status_check
                CHECK (status IN (
                    'Pendente',
                    'Enviado',
                    'Aguardando resposta',
                    'Respondido',
                    'Validada',
                    'Descartada',
                    'Vencido',
                    'Cancelado'
                ))
            ");
        }
    }

    /**
     * Reverter a correção (não recomendado, mas necessário para rollback)
     */
    public function down(): void
    {
        // Recriar a constraint antiga (apenas para rollback, não usar em produção)
        DB::statement("
            ALTER TABLE cp_solicitacoes_cdf
            DROP CONSTRAINT IF EXISTS cp_solicitacoes_cdf_status_check
        ");

        DB::statement("
            ALTER TABLE cp_solicitacoes_cdf
            ADD CONSTRAINT cp_solicitacoes_cdf_status_check
            CHECK (status IN (
                'Pendente',
                'Enviado',
                'Respondido',
                'Vencido',
                'Cancelado'
            ))
        ");
    }
};
