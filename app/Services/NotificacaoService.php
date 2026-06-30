<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\OpTask;
use Illuminate\Support\Facades\Schema;

class NotificacaoService
{
    public function notificarTarefaAtribuida(OpTask $tarefa, string $criadoPor): void
    {
        if (! Schema::hasTable('app_notification') || ! Schema::hasColumn('app_notification', 'username')) {
            return;
        }

        $criador = $criadoPor !== '' ? $criadoPor : 'Sistema';
        $tituloTarefa = trim((string) $tarefa->titulo) ?: 'Nova tarefa';
        $prazo = $tarefa->prazo
            ? ' Prazo: ' . date('d/m/Y', strtotime((string) $tarefa->prazo)) . '.'
            : '';

        foreach (OpTask::parseResponsaveis($tarefa->responsavel) as $responsavel) {
            if ($criadoPor !== '' && $responsavel === $criadoPor) {
                continue;
            }

            AppNotification::create([
                'kind' => 'task_assigned',
                'title' => 'Nova tarefa atribuída',
                'message' => "{$criador} atribuiu a tarefa \"{$tituloTarefa}\" para você.{$prazo}",
                'ref_type' => 'op_task',
                'ref_id' => $tarefa->id,
                'op_category' => 'tarefas',
                'created_by' => $criadoPor,
                'username' => $responsavel,
            ]);
        }
    }

    public function listarPorUsuario(string $username, int $limit = 30): array
    {
        if (! $this->tabelaDisponivel()) {
            return [];
        }

        return AppNotification::query()
            ->where('username', $username)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (AppNotification $n) => $this->formatar($n))
            ->all();
    }

    public function contarNaoLidas(string $username): int
    {
        if (! $this->tabelaDisponivel()) {
            return 0;
        }

        return AppNotification::query()
            ->where('username', $username)
            ->whereNull('read_at')
            ->count();
    }

    public function marcarComoLida(int $id, string $username): bool
    {
        if (! $this->tabelaDisponivel()) {
            return false;
        }

        $notificacao = AppNotification::query()
            ->where('id', $id)
            ->where('username', $username)
            ->first();

        if (! $notificacao) {
            return false;
        }

        if ($notificacao->read_at === null) {
            $notificacao->update(['read_at' => now()]);
        }

        return true;
    }

    public function marcarTodasComoLidas(string $username): int
    {
        if (! $this->tabelaDisponivel()) {
            return 0;
        }

        return AppNotification::query()
            ->where('username', $username)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function tabelaDisponivel(): bool
    {
        return Schema::hasTable('app_notification')
            && Schema::hasColumn('app_notification', 'username');
    }

    private function formatar(AppNotification $notificacao): array
    {
        return [
            'id' => $notificacao->id,
            'kind' => $notificacao->kind,
            'title' => $notificacao->title,
            'message' => $notificacao->message,
            'ref_type' => $notificacao->ref_type,
            'ref_id' => $notificacao->ref_id,
            'op_category' => $notificacao->op_category,
            'created_by' => $notificacao->created_by,
            'read_at' => $notificacao->read_at,
            'created_at' => $notificacao->created_at,
        ];
    }
}
