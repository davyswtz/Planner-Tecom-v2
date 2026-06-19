<?php

namespace App\Observers;

use App\Events\OpTaskChanged;
use App\Models\OpTask;

class OpTaskObserver
{
    public function created(OpTask $task): void
    {
        event(OpTaskChanged::fromTask($task, 'created'));
    }

    public function updated(OpTask $task): void
    {
        event(OpTaskChanged::fromTask($task, 'updated'));
    }

    public function deleted(OpTask $task): void
    {
        event(OpTaskChanged::fromTask($task, 'deleted'));
    }
}
