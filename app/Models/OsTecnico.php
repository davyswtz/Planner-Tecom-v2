<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsTecnico extends Model
{
    protected $table = 'os_tecnicos';

    protected $fillable = [
        'task_id',
        'parent_task_id',
        'tecnico_nome',
        'ordem_servico',
        'titulo',
        'task_code',
        'categoria',
        'regiao',
        'status',
        'protocolo',
        'prioridade',
        'data_criacao',
        'data_conclusao',
        'criada_em',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(OpTask::class, 'task_id');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(OpTask::class, 'parent_task_id');
    }
}
