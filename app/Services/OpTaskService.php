<?php
namespace App\Services;
use App\Models\OpTask;
class OpTaskService
{
    public function getOpTasks(int $limit = 40, string $orderBy = 'updated_at', string $order = 'desc')
    {
        return OpTask::orderBy($orderBy, $order)->limit($limit)->get();
    }

    public function createOpTask(array $dados){
        $opTask = OpTask::create($dados);
        return $opTask;
    }

    public function showOpTask(OpTask $opTask){
        return $opTasks;
    }

    public function updateOpTask(OpTask $opTask, array $dados){
        return $opTask;
    }

    public function deleteOpTask(OpTask $opTask){
        $opTask->delete();
        return $opTask;
    }
}
