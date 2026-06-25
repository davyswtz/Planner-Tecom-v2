<?php

namespace App\Observers;

use App\Events\OpTaskChanged;
use App\Models\OpTask;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpTaskObserver
{
    public function created(OpTask $task): void
    {
        $this->broadcastSafely($task, 'created');
    }

    public function updated(OpTask $task): void
    {
        $this->broadcastSafely($task, 'updated');
    }

    public function deleted(OpTask $task): void
    {
        $this->broadcastSafely($task, 'deleted');
    }

    private function broadcastSafely(OpTask $task, string $action): void
    {
        $event = OpTaskChanged::fromTask($task, $action);

        dispatch(static function () use ($event): void {
            try {
                event($event);
            } catch (Throwable $e) {
                Log::warning('Falha ao transmitir atualização em tempo real.', [
                    'task_id' => $event->id,
                    'action' => $event->action,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }
}
