<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('op_tasks') && Schema::hasColumn('op_tasks', 'responsavel')) {
            Schema::table('op_tasks', function (Blueprint $table) {
                $table->string('responsavel', 500)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('op_tasks') && Schema::hasColumn('op_tasks', 'responsavel')) {
            Schema::table('op_tasks', function (Blueprint $table) {
                $table->string('responsavel', 120)->change();
            });
        }
    }
};
