<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usuario_permissoes')) {
            return;
        }

        Schema::create('usuario_permissoes', function (Blueprint $table) {
            $table->string('username', 120);
            $table->string('permissao', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['username', 'permissao']);
            $table->foreign('username')->references('username')->on('usuario')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_permissoes');
    }
};
