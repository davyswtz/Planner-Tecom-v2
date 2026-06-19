<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Legado: webhooks ficam em app_config (cfg_key = webhookConfig).
 */
return new class extends Migration
{
    public function up(): void
    {
        // noop — ver 2026_06_17_000000_create_hostinger_baseline_schema
    }

    public function down(): void
    {
        // noop
    }
};
