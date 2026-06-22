<?php

use App\Models\AppConfig;
use App\Models\Webhook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $regioes = [
        'PADRAO' => 'Padrão',
        'GOVAL' => 'Governador Valadares',
        'VALE_DO_ACO' => 'Vale do Aço',
        'CARATINGA' => 'Caratinga',
        'TESTE' => 'Teste',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('webhooks')) {
            Schema::create('webhooks', function (Blueprint $table) {
                $table->id();
                $table->string('regiao', 32)->unique();
                $table->string('nome', 120);
                $table->text('url');
                $table->boolean('ativo')->default(true);
                $table->timestamps();

                $table->index('ativo');
            });
        }

        $this->importarDeAppConfig();
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }

    private function importarDeAppConfig(): void
    {
        if (! Schema::hasTable('app_config')) {
            return;
        }

        $config = AppConfig::getJson('webhookConfig');
        if ($config === []) {
            return;
        }

        if (! empty($config['url'])) {
            Webhook::updateOrCreate(
                ['regiao' => 'PADRAO'],
                ['nome' => $this->regioes['PADRAO'], 'url' => $config['url'], 'ativo' => true]
            );
        }

        $urlsByRegion = $config['urlsByRegion'] ?? [];
        foreach ($this->regioes as $codigo => $nome) {
            if ($codigo === 'PADRAO') {
                continue;
            }

            $url = $urlsByRegion[$codigo] ?? null;
            if (! is_string($url) || $url === '') {
                continue;
            }

            Webhook::updateOrCreate(
                ['regiao' => $codigo],
                ['nome' => $nome, 'url' => $url, 'ativo' => true]
            );
        }

        if (! empty($config['events']) && is_array($config['events'])) {
            AppConfig::setJson('webhookEvents', $config['events']);
        }
    }
};
