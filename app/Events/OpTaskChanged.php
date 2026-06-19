<?php

namespace App\Events;

use App\Models\OpTask;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpTaskChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public int $id,
        public ?string $categoria,
        public ?string $status,
        public ?int $parent_task_id,
        public ?string $taskCode,
    ) {}

    public static function fromTask(OpTask $task, string $action): self
    {
        return new self(
            action: $action,
            id: (int) $task->id,
            categoria: $task->categoria,
            status: $task->status,
            parent_task_id: $task->parent_task_id ? (int) $task->parent_task_id : null,
            taskCode: $task->taskCode,
        );
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('planner.tasks'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OpTaskChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'id' => $this->id,
            'categoria' => $this->categoria,
            'status' => $this->status,
            'parent_task_id' => $this->parent_task_id,
            'taskCode' => $this->taskCode,
        ];
    }
}
