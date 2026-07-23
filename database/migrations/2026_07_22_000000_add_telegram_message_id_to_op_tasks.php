<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('op_tasks')) {
            return;
        }

        Schema::table('op_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('op_tasks', 'telegram_message_id')) {
                $after = Schema::hasColumn('op_tasks', 'nicon_thread_chat_id')
                    ? 'nicon_thread_chat_id'
                    : 'chat_thread_key';
                $table->unsignedBigInteger('telegram_message_id')->nullable()->after($after);
            }
            if (! Schema::hasColumn('op_tasks', 'telegram_topic_id')) {
                $after = Schema::hasColumn('op_tasks', 'telegram_message_id')
                    ? 'telegram_message_id'
                    : (Schema::hasColumn('op_tasks', 'nicon_thread_chat_id') ? 'nicon_thread_chat_id' : 'chat_thread_key');
                $table->unsignedBigInteger('telegram_topic_id')->nullable()->after($after);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('op_tasks')) {
            return;
        }

        Schema::table('op_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('op_tasks', 'telegram_topic_id')) {
                $table->dropColumn('telegram_topic_id');
            }
            if (Schema::hasColumn('op_tasks', 'telegram_message_id')) {
                $table->dropColumn('telegram_message_id');
            }
        });
    }
};
