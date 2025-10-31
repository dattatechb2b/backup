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
        // Criar tabela orgaos com 11 campos
        Schema::create('cp_orgaos', function (Blueprint $table) {
            // 1. ID primário
            $table->id();

            // 2. Tenant ID (único por órgão)
            $table->string('tenant_id', 50)->unique();

            // 3. Razão social
            $table->string('razao_social', 255);

            // 4. Nome fantasia
            $table->string('nome_fantasia', 255)->nullable();

            // 5. CNPJ
            $table->string('cnpj', 20)->nullable();

            // 6. Endereço
            $table->string('endereco', 255)->nullable();

            // 7. CEP
            $table->string('cep', 10)->nullable();

            // 8. Cidade
            $table->string('cidade', 100)->nullable();

            // 9. UF
            $table->char('uf', 2)->nullable();

            // 10. Caminho do brasão/logo
            $table->string('brasao_path', 500)->nullable();

            // 11-12. Timestamps
            $table->timestamps();
        });

        // Adicionar FK orgao_id em orcamentos
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            $table->bigInteger('orgao_id')->nullable()->after('id');

            // $table->foreign('orgao_id', 'fk_orcamentos_orgao')
            //     ->references('id')->on('cp_orgaos')
            //     ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover FK de orcamentos primeiro
        Schema::table('cp_orcamentos', function (Blueprint $table) {
            // $table->dropForeign('fk_orcamentos_orgao');
            $table->dropColumn('orgao_id');
        });

        // Depois dropar a tabela orgaos
        Schema::dropIfExists('cp_orgaos');
    }
};
