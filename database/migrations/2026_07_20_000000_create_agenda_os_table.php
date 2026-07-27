<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agenda_os')) {
            return;
        }

        Schema::create('agenda_os', function (Blueprint $table) {
            $table->id();
            // os_tecnicos.id no banco de produção é bigint assinado (legado Hostinger).
            $table->bigInteger('os_tecnico_id');
            $table->foreign('os_tecnico_id')
                ->references('id')
                ->on('os_tecnicos')
                ->cascadeOnDelete();
            $table->foreignId('tecnico_id')
                ->constrained('tecnicos')
                ->restrictOnDelete();
            $table->date('data');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->integer('ordem')->default(0);
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['tecnico_id', 'data']);
            $table->index(['data', 'hora_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_os');
    }
};
