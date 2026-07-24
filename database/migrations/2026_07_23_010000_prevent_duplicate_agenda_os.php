<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_os', function (Blueprint $table) {
            $table->unique('os_tecnico_id', 'agenda_os_os_tecnico_unique');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_os', function (Blueprint $table) {
            $table->dropUnique('agenda_os_os_tecnico_unique');
        });
    }
};
