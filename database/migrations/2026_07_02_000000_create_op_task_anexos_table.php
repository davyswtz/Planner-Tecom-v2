<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('op_task_anexos')) {
            return;
        }

        Schema::create('op_task_anexos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('op_task_id');
            $table->string('nome_arquivo', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('tamanho_bytes')->default(0);
            $table->longText('conteudo_base64');
            $table->string('enviado_por', 120)->nullable();
            $table->timestamp('criado_em')->useCurrent();

            $table->index('op_task_id');
            $table->foreign('op_task_id')->references('id')->on('op_tasks')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('op_task_anexos');
    }
};
