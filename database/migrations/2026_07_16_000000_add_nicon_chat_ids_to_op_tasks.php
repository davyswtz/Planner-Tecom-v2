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
            if (! Schema::hasColumn('op_tasks', 'nicon_mensagem_raiz_id')) {
                $table->unsignedBigInteger('nicon_mensagem_raiz_id')->nullable()->after('chat_thread_key');
            }
            if (! Schema::hasColumn('op_tasks', 'nicon_thread_chat_id')) {
                $table->unsignedBigInteger('nicon_thread_chat_id')->nullable()->after('nicon_mensagem_raiz_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('op_tasks')) {
            return;
        }

        Schema::table('op_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('op_tasks', 'nicon_thread_chat_id')) {
                $table->dropColumn('nicon_thread_chat_id');
            }
            if (Schema::hasColumn('op_tasks', 'nicon_mensagem_raiz_id')) {
                $table->dropColumn('nicon_mensagem_raiz_id');
            }
        });
    }
};
