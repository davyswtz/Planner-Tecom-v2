<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tecnicos')) {
            Schema::create('tecnicos', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 120);
                $table->string('username', 120)->nullable()->unique();
                $table->string('regiao', 64)->default('');
                $table->timestamps();

                $table->index('nome');
                $table->index('regiao');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tecnicos');
    }
};
