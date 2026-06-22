<?php

use App\Models\Webhook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array{nome: string, url: string}> */
    private array $webhooks = [
        'PADRAO' => [
            'nome' => 'Padrão',
            'url' => 'https://chat.googleapis.com/v1/spaces/AAQAALsNEnY/messages?key=AIzaSyDdI0hCZtE6vySjMm-WEfRq3CPzqKqqsHI&token=zOzkVRXR_IpFq180r_0RGs38Nijutd44TrH9Vpj1Qgo',
        ],
        'GOVAL' => [
            'nome' => 'Goval',
            'url' => 'https://chat.googleapis.com/v1/spaces/AAQAALsNEnY/messages?key=AIzaSyDdI0hCZtE6vySjMm-WEfRq3CPzqKqqsHI&token=zOzkVRXR_IpFq180r_0RGs38Nijutd44TrH9Vpj1Qgo',
        ],
        'VALE_DO_ACO' => [
            'nome' => 'Vale do Aço',
            'url' => 'https://chat.googleapis.com/v1/spaces/AAQAXZP72GA/messages?key=AIzaSyDdI0hCZtE6vySjMm-WEfRq3CPzqKqqsHI&token=H0xwX2gBWHgfN2YmxaNGFTtLt_MG1-HEdlVPpmFHlUc',
        ],
        'CARATINGA' => [
            'nome' => 'Caratinga',
            'url' => 'https://chat.googleapis.com/v1/spaces/AAQArIWN3jM/messages?key=AIzaSyDdI0hCZtE6vySjMm-WEfRq3CPzqKqqsHI&token=eFDHTyVmz4f1AsxpJzxSPVC8TI0nOP0VSGOaK_bpowI',
        ],
        'TESTE' => [
            'nome' => 'Teste',
            'url' => 'https://chat.googleapis.com/v1/spaces/AAQAgqsNKYg/messages?key=AIzaSyDdI0hCZtE6vySjMm-WEfRq3CPzqKqqsHI&token=03UNaWYGsXuzDGcs-ascMurXLVsbxThfdDjda7taoDk',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('webhooks')) {
            return;
        }

        Webhook::query()->whereIn('regiao', ['BACKUP'])->delete();

        foreach ($this->webhooks as $regiao => $dados) {
            Webhook::updateOrCreate(
                ['regiao' => $regiao],
                ['nome' => $dados['nome'], 'url' => $dados['url'], 'ativo' => true]
            );
        }
    }

    public function down(): void
    {
        // Mantém os dados — sem rollback destrutivo de URLs de produção.
    }
};
