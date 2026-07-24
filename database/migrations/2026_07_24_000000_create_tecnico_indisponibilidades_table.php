<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tecnico_indisponibilidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tecnico_id')->constrained('tecnicos')->cascadeOnDelete();
            $table->string('motivo', 32);
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->string('observacao', 255)->nullable();
            $table->timestamps();

            $table->index(['tecnico_id', 'data_inicio', 'data_fim'], 'tecnico_indisponibilidade_periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tecnico_indisponibilidades');
    }
};
