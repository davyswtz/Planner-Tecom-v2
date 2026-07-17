<?php

use App\Models\Tecnico;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, array{nome: string, nicon_user_id: int, nicon_mention_name?: string}> */
    private const MAPEAMENTO_NICON = [
        [
            'nome' => 'Arrhenius',
            'nicon_user_id' => 1377,
            'nicon_mention_name' => 'EP ARRHENIUS LUCAS COSTA MENEZES',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('tecnicos')) {
            return;
        }

        if (! Schema::hasColumn('tecnicos', 'nicon_user_id')) {
            Schema::table('tecnicos', function (Blueprint $table) {
                $table->unsignedBigInteger('nicon_user_id')->nullable()->after('google_chat_id');
                $table->index('nicon_user_id');
            });
        }

        if (! Schema::hasColumn('tecnicos', 'nicon_mention_name')) {
            Schema::table('tecnicos', function (Blueprint $table) {
                $table->string('nicon_mention_name', 180)->nullable()->after('nicon_user_id');
            });
        }

        foreach (self::MAPEAMENTO_NICON as $item) {
            Tecnico::query()
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($item['nome'])])
                ->update([
                    'nicon_user_id' => $item['nicon_user_id'],
                    'nicon_mention_name' => $item['nicon_mention_name'] ?? null,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tecnicos') || ! Schema::hasColumn('tecnicos', 'nicon_user_id')) {
            return;
        }

        Schema::table('tecnicos', function (Blueprint $table) {
            $table->dropIndex(['nicon_user_id']);
            $table->dropColumn('nicon_user_id');
        });
    }
};
