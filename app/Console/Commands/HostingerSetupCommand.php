<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class HostingerSetupCommand extends Command
{
    protected $signature = 'hostinger:setup {--force : Executa migrate mesmo em production}';

    protected $description = 'Configuração inicial na Hostinger (após upload + .env)';

    public function handle(): int
    {
        if (! file_exists(base_path('.env'))) {
            $this->error('Arquivo .env não encontrado. Copie deploy/env/hostinger.env.example para .env');

            return self::FAILURE;
        }

        if (empty(config('app.key'))) {
            $this->warn('APP_KEY vazio — gerando...');
            Artisan::call('key:generate', ['--force' => true]);
            $this->line(Artisan::output());
        }

        $required = [
            'APP_URL' => config('app.url'),
            'DB_DATABASE' => config('database.connections.mysql.database'),
            'DB_USERNAME' => config('database.connections.mysql.username'),
            'DB_PASSWORD' => config('database.connections.mysql.password'),
        ];

        foreach ($required as $name => $value) {
            if ($value === null || $value === '') {
                $this->error("Preencha {$name} no .env antes de continuar.");

                return self::FAILURE;
            }
        }

        if (str_contains(config('app.url'), 'seudominio')) {
            $this->error('Ajuste APP_URL no .env para a URL real do subdomínio (ex.: https://planner.tecom.com.br).');

            return self::FAILURE;
        }

        $this->info('Testando conexão MySQL...');
        try {
            \DB::connection()->getPdo();
            $this->info('OK — banco conectado.');
        } catch (\Throwable $e) {
            $this->error('Falha na conexão: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Rodando migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(Artisan::output());

        $this->info('Otimizando caches...');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->newLine();
        $this->info('Setup concluído.');
        $this->line('Acesse: '.config('app.url'));
        $this->line('Health check: '.config('app.url').'/up');

        if (config('broadcasting.default') === 'log') {
            $this->warn('BROADCAST_CONNECTION=log — tempo real entre abas desativado até configurar Pusher.');
        }

        if (config('broadcasting.default') === 'pusher' && ! filled(config('broadcasting.connections.pusher.key'))) {
            $this->error('BROADCAST_CONNECTION=pusher sem PUSHER_APP_KEY. Use BROADCAST_CONNECTION=log ou preencha as chaves Pusher.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
