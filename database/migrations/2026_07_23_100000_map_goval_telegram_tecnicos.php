<?php

use App\Models\Tecnico;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, array{nome: string, telegram_user_id: int}> */
    private array $mapaGoval = [
        ['nome' => 'Leyzon', 'telegram_user_id' => 8218803319],
        ['nome' => 'Lucas', 'telegram_user_id' => 6919500936],
        ['nome' => 'Guilherme', 'telegram_user_id' => 5723995964],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('tecnicos') || ! Schema::hasColumn('tecnicos', 'telegram_user_id')) {
            return;
        }

        foreach ($this->mapaGoval as $item) {
            Tecnico::query()
                ->where('nome', $item['nome'])
                ->where(function ($q) {
                    $q->where('regiao', 'like', '%Goval%')
                        ->orWhere('regiao', 'like', '%Governador%');
                })
                ->update([
                    'telegram_user_id' => $item['telegram_user_id'],
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tecnicos') || ! Schema::hasColumn('tecnicos', 'telegram_user_id')) {
            return;
        }

        foreach ($this->mapaGoval as $item) {
            Tecnico::query()
                ->where('nome', $item['nome'])
                ->where('telegram_user_id', $item['telegram_user_id'])
                ->update(['telegram_user_id' => null]);
        }
    }
};
