<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpTask extends Model
{
    const CREATED_AT = 'criadaEm';

    const UPDATED_AT = 'updated_at';

    protected $table = 'op_tasks';

    protected $primaryKey = 'id';

    protected $fillable = [
        'taskCode',
        'titulo',
        'setor',
        'regiao',
        'responsavel',
        'clientesAfetados',
        'coordenadas',
        'localizacao_texto',
        'descricao',
        'categoria',
        'prazo',
        'prioridade',
        'status',
        'is_parent_task',
        'parent_task_id',
        'criadaEm',
        'historico',
        'active_duration_minutes',
        'chat_thread_key',
        'nome_cliente',
        'protocolo',
        'ordem_servico',
        'numero_os',
        'sub_processo',
        'data_entrada',
        'data_instalacao',
        'assinada_por',
        'assinada_em',
    ];

    protected function casts(): array
    {
        return [
            'is_parent_task' => 'boolean',
            'prazo' => 'date',
            'active_duration_minutes' => 'integer',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OpTask $task) {
            if ($task->responsavel === null) {
                $task->responsavel = '';
            }
        });
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function childTasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id');
    }

    public function osTecnicos(): HasMany
    {
        return $this->hasMany(OsTecnico::class, 'parent_task_id');
    }
}
