<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Legado: produção usa setor (não coluna cto).
 */
return new class extends Migration
{
    public function up(): void
    {
        // noop — coluna cto não existe no banco Hostinger
    }

    public function down(): void
    {
        // noop
    }
};
