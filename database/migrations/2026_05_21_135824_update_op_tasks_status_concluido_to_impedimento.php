<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Legado: impedimento nunca existiu no schema de produção.
 */
return new class extends Migration
{
    public function up(): void
    {
        // noop — schema alinhado em 2026_06_17_000000_create_hostinger_baseline_schema
    }

    public function down(): void
    {
        // noop
    }
};
