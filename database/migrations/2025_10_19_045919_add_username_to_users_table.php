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
     * DESABILITADO: Esta migration tentava adicionar campos que já existem
     * na migration base 0001_01_01_000000_create_users_table.php
     */
    public function up(): void
    {
        // Migration desabilitada - campos já existem na tabela cp_users desde a criação
        return;

        Schema::table('cp_users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->after('name');
            $table->string('recovery_email')->nullable()->after('email');
            $table->string('role', 20)->default('user')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        // Popular username existente extraindo do email
        // Exemplo: admin@testelassais.dattapro.online -> username = "admin"
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $username = explode('@', $user->email)[0];
            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => $username]);
        }

        // Tornar username obrigatório e único
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'recovery_email', 'role', 'last_login_at']);
        });
    }
};
