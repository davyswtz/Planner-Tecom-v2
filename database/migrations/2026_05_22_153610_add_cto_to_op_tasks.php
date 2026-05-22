<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('op_tasks', function (Blueprint $table) {
            $table->string('cto')->nullable()->after('coordenadas');
        });
    }

    public function down(): void
    {
        Schema::table('op_tasks', function (Blueprint $table) {
            $table->dropColumn('cto');
        });
    }
};