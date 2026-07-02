<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usuario') || Schema::hasColumn('usuario', 'cargo')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->string('cargo', 64)->nullable()->after('pass_iterations');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('usuario') || ! Schema::hasColumn('usuario', 'cargo')) {
            return;
        }

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropColumn('cargo');
        });
    }
};
