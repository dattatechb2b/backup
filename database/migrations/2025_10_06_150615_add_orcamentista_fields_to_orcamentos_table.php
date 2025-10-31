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
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            // Dados do Orçamentista (Etapa 6)
            $table->string('orcamentista_nome')->nullable()->after('observacao_justificativa');
            $table->string('orcamentista_cpf_cnpj', 18)->nullable()->after('orcamentista_nome');
            $table->string('orcamentista_matricula')->nullable()->after('orcamentista_cpf_cnpj');
            $table->string('orcamentista_portaria')->nullable()->after('orcamentista_matricula');

            // Dados do CNPJ (quando aplicável - vem da ReceitaWS)
            $table->string('orcamentista_razao_social')->nullable()->after('orcamentista_portaria');
            $table->text('orcamentista_endereco')->nullable()->after('orcamentista_razao_social');
            $table->string('orcamentista_cep', 9)->nullable()->after('orcamentista_endereco');
            $table->string('orcamentista_cidade')->nullable()->after('orcamentista_cep');
            $table->string('orcamentista_uf', 2)->nullable()->after('orcamentista_cidade');
            $table->string('orcamentista_setor')->nullable()->after('orcamentista_uf');

            // Caminho do brasão (upload)
            $table->string('brasao_path')->nullable()->after('orcamentista_setor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            $table->dropColumn([
                'orcamentista_nome',
                'orcamentista_cpf_cnpj',
                'orcamentista_matricula',
                'orcamentista_portaria',
                'orcamentista_razao_social',
                'orcamentista_endereco',
                'orcamentista_cep',
                'orcamentista_cidade',
                'orcamentista_uf',
                'orcamentista_setor',
                'brasao_path'
            ]);
        });
    }
};
