<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpTask extends Model
{
    const CREATED_AT = 'criadaEm';

    const UPDATED_AT = 'updated_at';

    public const CATEGORIAS_OTIMIZACAO_REDE = [
        'otimizacao-rede',
        'otimizacao de rede',
        'otimização de rede',
        'OTIMIZACAO DE REDE',
        'OTIMIZAÇÃO DE REDE',
    ];

    public const CATEGORIAS_ATENDIMENTO = [
        'atendimento-cliente',
        'atendimento ao cliente',
    ];

    public const CATEGORIAS_CORRECAO_SINAL = [
        'correcao-atenuacao',
        'correção de atenuação',
    ];

    protected $table = 'op_tasks';

    protected $primaryKey = 'id';

    protected $appends = [
        'cto',
    ];

    protected $fillable = [
        'taskCode',
        'titulo',
        'setor',
        'cto',
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

        static::deleting(function (OpTask $task) {
            OsTecnico::where('parent_task_id', $task->id)->delete();
            OpTask::where('parent_task_id', $task->id)->delete();
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

    public function getCtoAttribute(): string
    {
        return (string) ($this->attributes['setor'] ?? '');
    }

    public function setCtoAttribute(mixed $value): void
    {
        $this->attributes['setor'] = (string) ($value ?? '');
    }

    /** Tarefas de topo de uma categoria (exclui subtarefas/OS filhas). */
    public function scopeTarefasPai(Builder $query, string|array $categorias): Builder
    {
        return $query
            ->whereIn('categoria', (array) $categorias)
            ->whereNull('parent_task_id');
    }

    public function scopeRompimentosPai(Builder $query): Builder
    {
        return $query->tarefasPai(['rompimento', 'rompimentos']);
    }

    public function scopeOtimizacoesRedePai(Builder $query): Builder
    {
        return $query
            ->whereNull('parent_task_id')
            ->where(function (Builder $query) {
                $query
                    ->whereIn('categoria', self::CATEGORIAS_OTIMIZACAO_REDE)
                    ->orWhere('taskCode', 'like', '%-OTM-%');
            });
    }

    public function isTarefaPaiOf(string|array $categorias): bool
    {
        return $this->parent_task_id === null
            && in_array($this->categoria, (array) $categorias, true);
    }

    public function isRompimentoPai(): bool
    {
        return $this->isTarefaPaiOf(['rompimento', 'rompimentos']);
    }

    public function isOtimizacaoRedePai(): bool
    {
        return $this->isTarefaPai()
            && (
                in_array($this->categoria, self::CATEGORIAS_OTIMIZACAO_REDE, true)
                || str_contains(strtoupper((string) $this->taskCode), '-OTM-')
            );
    }

    public function isAtendimentoPai(): bool
    {
        return $this->isTarefaPaiOf(self::CATEGORIAS_ATENDIMENTO);
    }

    public function isCorrecaoSinalPai(): bool
    {
        return $this->isTarefaPaiOf(self::CATEGORIAS_CORRECAO_SINAL);
    }

    public function isTarefaPai(): bool
    {
        return $this->parent_task_id === null;
    }
}
