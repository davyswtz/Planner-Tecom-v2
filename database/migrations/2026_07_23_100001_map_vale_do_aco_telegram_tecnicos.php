<?php

use App\Models\Tecnico;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, array{nome: string, telegram_user_id: int}> */
    private array $mapaVale = [
        ['nome' => 'Weignon', 'telegram_user_id' => 6932702102],
        ['nome' => 'Carlos', 'telegram_user_id' => 7942404772],
        ['nome' => 'Arrhenius', 'telegram_user_id' => 6138617546],
        ['nome' => 'Eduardo', 'telegram_user_id' => 8666866786],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('tecnicos') || ! Schema::hasColumn('tecnicos', 'telegram_user_id')) {
            return;
        }

        foreach ($this->mapaVale as $item) {
            Tecnico::query()
                ->where('nome', $item['nome'])
                ->where(function ($q) {
                    $q->where('regiao', 'like', '%Vale%')
                        ->orWhere('regiao', 'like', '%Aço%')
                        ->orWhere('regiao', 'like', '%Aco%');
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

        foreach ($this->mapaVale as $item) {
            Tecnico::query()
                ->where('nome', $item['nome'])
                ->where('telegram_user_id', $item['telegram_user_id'])
                ->update(['telegram_user_id' => null]);
        }
    }
};
