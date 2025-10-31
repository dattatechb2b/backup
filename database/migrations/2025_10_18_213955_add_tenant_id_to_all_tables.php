<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adicionar tenant_id em TODAS as tabelas de dados de negócio
     * para isolamento completo multi-tenant
     *
     * DESABILITADO: Esta migration é da arquitetura ANTIGA (banco compartilhado).
     * Na nova arquitetura cada tenant tem BANCO EXCLUSIVO, então tenant_id não é necessário.
     */
    public function up(): void
    {
        // Migration desabilitada - não é necessária com banco exclusivo por tenant
        return;

        // Lista de tabelas que precisam de tenant_id
        $tables = [
            'orcamentos',
            'itens_orcamento',
            'lotes',
            'historico_precos',
            'fornecedores',
            'fornecedor_itens',
            'cotacoes_externas',
            'solicitacoes_cdf',
            'solicitacao_cdf_itens',
            'respostas_cdf',
            'resposta_cdf_itens',
            'resposta_cdf_anexos',
            'contratacoes_similares',
            'contratacao_similar_itens',
            'coletas_ecommerce',
            'coleta_ecommerce_itens',
            'anexos',
            'arp_cabecalhos',
            'arp_itens',
            'notificacoes',
            'orientacoes_tecnicas',
            'catalogo_produtos',
            'audit_log_itens',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                    $table->index('tenant_id');
                });

                echo "✓ Adicionada coluna tenant_id em {$table}\n";
            }
        }

        // Popular tenant_id=1 (Catas Altas) em TODOS os registros existentes
        echo "\n🔄 Populando tenant_id=1 (Catas Altas) nos dados existentes...\n";

        foreach ($tables as $table) {
            $updated = DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => 1]);
            if ($updated > 0) {
                echo "✓ {$table}: {$updated} registros atribuídos ao tenant Catas Altas\n";
            }
        }

        // Tornar tenant_id NOT NULL após popular
        echo "\n🔒 Tornando tenant_id obrigatório...\n";

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            });
            echo "✓ {$table}: tenant_id agora é NOT NULL\n";
        }

        echo "\n✅ Migration concluída! Sistema está isolado por tenant.\n";
    }

    /**
     * Reverter a migration
     */
    public function down(): void
    {
        $tables = [
            'orcamentos',
            'itens_orcamento',
            'lotes',
            'historico_precos',
            'fornecedores',
            'fornecedor_itens',
            'cotacoes_externas',
            'solicitacoes_cdf',
            'solicitacao_cdf_itens',
            'respostas_cdf',
            'resposta_cdf_itens',
            'resposta_cdf_anexos',
            'contratacoes_similares',
            'contratacao_similar_itens',
            'coletas_ecommerce',
            'coleta_ecommerce_itens',
            'anexos',
            'arp_cabecalhos',
            'arp_itens',
            'notificacoes',
            'orientacoes_tecnicas',
            'catalogo_produtos',
            'audit_log_itens',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
