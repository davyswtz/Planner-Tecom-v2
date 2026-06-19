<?php

use Illuminate\Database\Migrations\Migration;

/**
 * O projeto usa a tabela `usuario` (schema Hostinger), não `users` do Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        // noop
    }

    public function down(): void
    {
        // noop
    }
};
