<?php

use App\Models\Tecnico;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Tecnico::query()
            ->whereRaw('LOWER(nome) = ?', ['lucas'])
            ->update([
                'nicon_user_id' => 1479,
                'nicon_mention_name' => 'LUCAS SILVA',
            ]);
    }

    public function down(): void
    {
        Tecnico::query()
            ->whereRaw('LOWER(nome) = ?', ['lucas'])
            ->update([
                'nicon_user_id' => null,
                'nicon_mention_name' => null,
            ]);
    }
};
