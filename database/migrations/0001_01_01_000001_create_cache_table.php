<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Cache em arquivo (.env CACHE_STORE=file). Não usa tabelas cache/cache_locks.
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
