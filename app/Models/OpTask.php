<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

    public const CATEGORIAS_CERTIFICACAO_CEMIG = [
        'certificacao-cemig',
        'certificação cemig',
    ];

    public const CATEGORIAS_MANUTENCAO_CORRETIVA = [
        'manutencao-corretiva',
        'manutenção corretiva',
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
        'sequencia',
        'criadaEm',
        'historico',
        'active_duration_minutes',
        'chat_thread_key',
        'nicon_mensagem_raiz_id',
        'nicon_thread_chat_id',
        'nome_cliente',
        'protocolo',
        'ordem_servico',
        'numero_os',
        'sub_processo',
        'data_entrada',
        'data_instalacao',
        'assinada_por',
        'assinada_em',
        'correcao_dados',
    ];

    protected function casts(): array
    {
        return [
            'is_parent_task' => 'boolean',
            'correcao_dados' => 'boolean',
            'prazo' => 'date',
            'active_duration_minutes' => 'integer',
            'sequencia' => 'integer',
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

    public function anexos(): HasMany
    {
        return $this->hasMany(OpTaskAnexo::class, 'op_task_id');
    }

    public function getCtoAttribute(): string
    {
        return (string) ($this->attributes['setor'] ?? '');
    }

    public function setCtoAttribute(mixed $value): void
    {
        $this->attributes['setor'] = (string) ($value ?? '');
    }

    /** @return array<int, string> */
    public static function parseResponsaveis(?string $valor): array
    {
        if ($valor === null || trim($valor) === '') {
            return [];
        }

        $partes = preg_split('/\s*(?:,|\+|·)\s*/u', $valor) ?: [];

        return array_values(array_unique(array_filter(
            array_map(static fn (string $item) => trim($item), $partes),
            static fn (string $item) => $item !== '' && $item !== '—'
        )));
    }

    /** @param array<int, string> $usernames */
    public static function serializarResponsaveis(array $usernames): string
    {
        $lista = array_values(array_unique(array_filter(
            array_map(static fn ($username) => trim((string) $username), $usernames),
            static fn (string $username) => $username !== ''
        )));

        return implode(', ', $lista);
    }

    public static function responsavelInclui(?string $responsavel, string $username): bool
    {
        $username = trim($username);

        if ($username === '') {
            return false;
        }

        return in_array($username, self::parseResponsaveis($responsavel), true);
    }

    /**
     * Campos que o cliente da API pode enviar.
     * Bloqueia mass assignment de taskCode, is_parent_task, chat_thread_key, etc.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    public static function filtrarEntradaCliente(array $dados, bool $permitirParent = false, bool $permitirCategoria = false): array
    {
        $permitidos = [
            'titulo',
            'setor',
            'cto',
            'regiao',
            'responsavel',
            'clientesAfetados',
            'coordenadas',
            'localizacao_texto',
            'descricao',
            'prazo',
            'prioridade',
            'status',
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

        if ($permitirParent) {
            $permitidos[] = 'parent_task_id';
            $permitidos[] = 'sequencia';
        }

        if ($permitirCategoria) {
            $permitidos[] = 'categoria';
        }

        $filtrados = array_intersect_key($dados, array_flip($permitidos));

        if (array_key_exists('parent_task_id', $filtrados)) {
            $parentId = (int) $filtrados['parent_task_id'];
            $filtrados['parent_task_id'] = $parentId > 0 ? $parentId : null;
        }

        if (array_key_exists('sequencia', $filtrados)) {
            $filtrados['sequencia'] = max(0, (int) $filtrados['sequencia']);
        }

        if (array_key_exists('prioridade', $filtrados)) {
            $prio = trim((string) $filtrados['prioridade']);
            $filtrados['prioridade'] = in_array($prio, ['Baixa', 'Média', 'Alta'], true) ? $prio : 'Média';
        }

        if (array_key_exists('status', $filtrados)) {
            $filtrados['status'] = mb_substr(trim((string) $filtrados['status']), 0, 64);
        }

        foreach (['titulo', 'setor', 'regiao', 'coordenadas', 'localizacao_texto', 'nome_cliente', 'numero_os', 'protocolo', 'ordem_servico', 'sub_processo'] as $campoTexto) {
            if (array_key_exists($campoTexto, $filtrados) && is_string($filtrados[$campoTexto])) {
                $filtrados[$campoTexto] = mb_substr(trim($filtrados[$campoTexto]), 0, 500);
            }
        }

        if (array_key_exists('descricao', $filtrados) && is_string($filtrados['descricao'])) {
            // Limite defensivo contra payloads enormes (ex.: base64 em descrição).
            $filtrados['descricao'] = mb_substr($filtrados['descricao'], 0, 200_000);
        }

        return $filtrados;
    }

    public function scopeWhereResponsavel(Builder $query, string $username): Builder
    {
        $username = trim($username);

        if ($username === '') {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($username) {
            $sub->where('responsavel', $username);

            foreach ([',', '+', '·'] as $separador) {
                $sub->orWhere('responsavel', 'like', $username.$separador.'%')
                    ->orWhere('responsavel', 'like', '%'.$separador.$username)
                    ->orWhere('responsavel', 'like', '%'.$separador.$username.$separador.'%');
            }
        });
    }

    /** Tarefas de topo de uma categoria (exclui subtarefas/OS filhas). */
    public function scopeTarefasPai(Builder $query, string|array $categorias): Builder
    {
        return $query
            ->whereIn('categoria', (array) $categorias)
            ->whereNull('parent_task_id');
    }

    /**
     * Busca all-in-one: código, título, setor/elemento, cliente, nº OS, descrição ou ID.
     */
    public function scopeBuscaTexto(Builder $query, ?string $termo): Builder
    {
        $termo = trim((string) $termo);
        if ($termo === '') {
            return $query;
        }

        $like = '%'.addcslashes($termo, '%_\\').'%';

        return $query->where(function (Builder $q) use ($like, $termo) {
            $q->where('taskCode', 'like', $like)
                ->orWhere('titulo', 'like', $like)
                ->orWhere('setor', 'like', $like)
                ->orWhere('nome_cliente', 'like', $like)
                ->orWhere('numero_os', 'like', $like)
                ->orWhere('ordem_servico', 'like', $like)
                ->orWhere('descricao', 'like', $like)
                ->orWhere('localizacao_texto', 'like', $like)
                ->orWhere('protocolo', 'like', $like);

            if (ctype_digit($termo)) {
                $q->orWhere('id', (int) $termo);
            }
        });
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

    public function isCertificacaoCemigPai(): bool
    {
        return $this->isTarefaPaiOf(self::CATEGORIAS_CERTIFICACAO_CEMIG);
    }

    public function isManutencaoCorretivaPai(): bool
    {
        return $this->isTarefaPaiOf(self::CATEGORIAS_MANUTENCAO_CORRETIVA);
    }

    public function isTarefaPai(): bool
    {
        return $this->parent_task_id === null;
    }
}
