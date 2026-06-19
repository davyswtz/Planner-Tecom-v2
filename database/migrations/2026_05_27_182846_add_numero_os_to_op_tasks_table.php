<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('op_tasks') || Schema::hasColumn('op_tasks', 'numero_os')) {
            return;
        }

        Schema::table('op_tasks', function (Blueprint $table) {
            $table->string('numero_os', 180)->default('')->after('ordem_servico');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('op_tasks') || ! Schema::hasColumn('op_tasks', 'numero_os')) {
            return;
        }

        Schema::table('op_tasks', function (Blueprint $table) {
            $table->dropColumn('numero_os');
        });
    }
};
