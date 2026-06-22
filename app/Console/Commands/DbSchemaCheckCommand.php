<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DbSchemaCheckCommand extends Command
{
    protected $signature = 'db:schema-check';

    protected $description = 'Compara tabelas do banco com o schema esperado (Hostinger + Laravel)';

    /** Tabelas do dump Hostinger (localhost.sql) */
    private array $hostingerTables = [
        'app_activity_event',
        'app_config',
        'app_notification',
        'bm_input',
        'bm_room',
        'calendar_notes',
        'chat_message',
        'deleted_entity_log',
        'escalas',
        'eventos',
        'op_tasks',
        'op_task_image',
        'os_tecnicos',
        'schema_migrations',
        'usuario',
    ];

    /** Tabelas extras exigidas pelo Laravel neste projeto */
    private array $laravelTables = [
        'migrations',
        'personal_access_tokens',
        'webhooks',
        'usuario_permissoes',
    ];

    public function handle(): int
    {
        $expected = array_merge($this->hostingerTables, $this->laravelTables);
        sort($expected);

        $missing = [];
        $present = [];

        foreach ($expected as $table) {
            if (Schema::hasTable($table)) {
                $present[] = $table;
            } else {
                $missing[] = $table;
            }
        }

        $this->info('Tabelas esperadas: ' . count($expected));
        $this->info('Presentes: ' . count($present));

        if ($missing === []) {
            $this->newLine();
            $this->info('OK — todas as tabelas necessárias existem.');
            $this->line('Rode `php artisan migrate` apenas se ainda não rodou (cria o que faltar).');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('Faltando (' . count($missing) . '):');
        foreach ($missing as $table) {
            $source = in_array($table, $this->laravelTables, true) ? 'Laravel' : 'Hostinger';
            $this->line("  - {$table} [{$source}]");
        }

        $this->newLine();
        $this->line('Importe o dump SQL (tabelas Hostinger) e depois:');
        $this->line('  php artisan migrate');

        return self::FAILURE;
    }
}
