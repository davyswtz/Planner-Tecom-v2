<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Fila síncrona (.env QUEUE_CONNECTION=sync). Não usa tabelas jobs/job_batches/failed_jobs.
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
