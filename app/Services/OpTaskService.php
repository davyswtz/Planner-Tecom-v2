<?php
namespace App\Services;
use App\Models\OpTask;
class OpTaskService
{
    public function getOpTasks()
    {
        $opTasks = OpTask::all();
        return $opTasks;
    }

    public function createOpTask(array $dados){
        $opTask = OpTask::create($dados);
        return $opTasks;
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
