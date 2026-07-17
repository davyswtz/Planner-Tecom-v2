<?php

use App\Models\Tecnico;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tecnicos')) {
            return;
        }

        if (! Schema::hasColumn('tecnicos', 'nicon_mention_name')) {
            Schema::table('tecnicos', function (Blueprint $table) {
                $table->string('nicon_mention_name', 180)->nullable()->after('nicon_user_id');
            });
        }

        Tecnico::query()
            ->whereRaw('LOWER(nome) = ?', ['arrhenius'])
            ->update([
                'nicon_user_id' => 1377,
                'nicon_mention_name' => 'EP ARRHENIUS LUCAS COSTA MENEZES',
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tecnicos')) {
            return;
        }

        Tecnico::query()
            ->whereRaw('LOWER(nome) = ?', ['arrhenius'])
            ->update([
                'nicon_user_id' => 1402,
                'nicon_mention_name' => null,
            ]);

        if (Schema::hasColumn('tecnicos', 'nicon_mention_name')) {
            Schema::table('tecnicos', function (Blueprint $table) {
                $table->dropColumn('nicon_mention_name');
            });
        }
    }
};
