<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_notification')) {
            return;
        }

        Schema::table('app_notification', function (Blueprint $table) {
            if (! Schema::hasColumn('app_notification', 'username')) {
                $table->string('username', 120)->default('')->after('created_by');
                $table->index('username');
            }

            if (! Schema::hasColumn('app_notification', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('username');
                $table->index('read_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_notification')) {
            return;
        }

        Schema::table('app_notification', function (Blueprint $table) {
            if (Schema::hasColumn('app_notification', 'read_at')) {
                $table->dropIndex(['read_at']);
                $table->dropColumn('read_at');
            }

            if (Schema::hasColumn('app_notification', 'username')) {
                $table->dropIndex(['username']);
                $table->dropColumn('username');
            }
        });
    }
};
