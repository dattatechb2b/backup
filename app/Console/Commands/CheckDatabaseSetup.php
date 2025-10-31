<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabaseSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:check-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica a configuração do banco de dados e prefixo das tabelas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Verificando Configuração do Banco de Dados ===');

        try {
            // Verificar conexão
            DB::connection()->getPdo();
            $this->info('✓ Conexão com o banco de dados estabelecida');

            // Mostrar configurações
            $config = config('database.connections.pgsql');
            $this->table(
                ['Configuração', 'Valor'],
                [
                    ['Database', $config['database']],
                    ['Host', $config['host']],
                    ['Port', $config['port']],
                    ['Prefixo', $config['prefix']],
                    ['Schema', $config['search_path']],
                ]
            );

            // Verificar tabelas com prefixo
            $prefix = $config['prefix'];
            $this->info("\nTabelas com prefixo '{$prefix}':");

            $tables = DB::select("
                SELECT tablename
                FROM pg_tables
                WHERE schemaname = 'public'
                AND tablename LIKE ?
            ", [$prefix . '%']);

            if (count($tables) > 0) {
                foreach ($tables as $table) {
                    $this->line("  - {$table->tablename}");
                }
            } else {
                $this->warn("  Nenhuma tabela encontrada com o prefixo '{$prefix}'");
                $this->info("  (Normal para nova instalação - execute 'php artisan migrate' para criar as tabelas)");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('✗ Erro ao conectar com o banco de dados:');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}