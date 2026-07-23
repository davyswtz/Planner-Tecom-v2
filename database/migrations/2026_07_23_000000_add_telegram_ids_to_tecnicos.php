<?php

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

        Schema::table('tecnicos', function (Blueprint $table) {
            if (! Schema::hasColumn('tecnicos', 'telegram_user_id')) {
                $table->unsignedBigInteger('telegram_user_id')->nullable()->after('nicon_mention_name');
                $table->index('telegram_user_id');
            }
            if (! Schema::hasColumn('tecnicos', 'telegram_username')) {
                $table->string('telegram_username', 64)->nullable()->after('telegram_user_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tecnicos')) {
            return;
        }

        Schema::table('tecnicos', function (Blueprint $table) {
            if (Schema::hasColumn('tecnicos', 'telegram_username')) {
                $table->dropColumn('telegram_username');
            }
            if (Schema::hasColumn('tecnicos', 'telegram_user_id')) {
                $table->dropIndex(['telegram_user_id']);
                $table->dropColumn('telegram_user_id');
            }
        });
    }
};
