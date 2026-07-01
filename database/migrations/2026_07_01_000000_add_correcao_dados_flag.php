<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('op_tasks') && ! Schema::hasColumn('op_tasks', 'correcao_dados')) {
            Schema::table('op_tasks', function (Blueprint $table) {
                $table->boolean('correcao_dados')->default(false)->after('assinada_em');
                $table->index('correcao_dados');
            });
        }

        if (Schema::hasTable('os_tecnicos') && ! Schema::hasColumn('os_tecnicos', 'correcao_dados')) {
            Schema::table('os_tecnicos', function (Blueprint $table) {
                $table->boolean('correcao_dados')->default(false)->after('criada_em');
                $table->index('correcao_dados');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('op_tasks') && Schema::hasColumn('op_tasks', 'correcao_dados')) {
            Schema::table('op_tasks', function (Blueprint $table) {
                $table->dropIndex(['correcao_dados']);
                $table->dropColumn('correcao_dados');
            });
        }

        if (Schema::hasTable('os_tecnicos') && Schema::hasColumn('os_tecnicos', 'correcao_dados')) {
            Schema::table('os_tecnicos', function (Blueprint $table) {
                $table->dropIndex(['correcao_dados']);
                $table->dropColumn('correcao_dados');
            });
        }
    }
};
