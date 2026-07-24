<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_os', function (Blueprint $table) {
            $table->id();
            $table->foreignId('os_tecnico_id')
                ->constrained('os_tecnicos')
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
