/**
 * Sincroniza o kanban de Tarefas entre abas (dashboard ↔ /tarefas).
 */
(function () {
  const STORAGE_KEY = 'planner_tarefa_sync';
  let channel = null;

  try {
    channel = new BroadcastChannel('planner-tarefas-sync');
  } catch {
    channel = null;
  }

  function tarefaPertenceAoUsuario(tarefa) {
    if (!tarefa?.id || tarefa.categoria !== 'tarefas') return false;

    try {
      const user = JSON.parse(localStorage.getItem('planner_user') || 'null');
      return !!(user?.username && tarefa.responsavel === user.username);
    } catch {
      return false;
    }
  }

  function podeVerTodasTarefasNoKanban() {
    return typeof window.plannerPossuiPermissao === 'function'
      && window.plannerPossuiPermissao('visualizar_aba_tarefas');
  }

  function tarefaFoiExcluida(id) {
    return typeof window.plannerEstaExcluida === 'function' && window.plannerEstaExcluida(id);
  }

  function aplicarTarefa(tarefa) {
    if (!tarefa?.id || tarefa.categoria !== 'tarefas') return;
    if (tarefaFoiExcluida(tarefa.id)) return;

    const pertenceAoUsuario = tarefaPertenceAoUsuario(tarefa);

    if (typeof window.adicionarCardTarefa === 'function' && (podeVerTodasTarefasNoKanban() || pertenceAoUsuario)) {
      window.adicionarCardTarefa(tarefa);
    }

    if (typeof window.atualizarCardSuasTarefas === 'function' && pertenceAoUsuario) {
      window.atualizarCardSuasTarefas(tarefa);
    }
  }

  function publicarSync(payload) {
    if (payload?.type === 'tarefa-updated' && tarefaFoiExcluida(payload.tarefa?.id)) {
      return;
    }

    try {
      channel?.postMessage(payload);
    } catch {
      /* ignore */
    }

    try {
      if (payload?.type === 'tarefa-deleted') {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({ t: Date.now(), ...payload }));
        return;
      }

      const atual = localStorage.getItem(STORAGE_KEY);
      if (atual) {
        try {
          const parsed = JSON.parse(atual);
          if (
            parsed?.type === 'tarefa-deleted'
            && String(parsed.id) === String(payload?.tarefa?.id)
          ) {
            return;
          }
        } catch {
          /* ignore */
        }
      }

      localStorage.setItem(STORAGE_KEY, JSON.stringify({ t: Date.now(), ...payload }));
    } catch {
      /* ignore */
    }
  }

  window.plannerSyncTarefa = function (tarefa) {
    if (!tarefa?.id) return;
    if (tarefaFoiExcluida(tarefa.id)) return;
    publicarSync({ type: 'tarefa-updated', tarefa });
  };

  window.plannerSyncExclusaoTarefa = function (id) {
    if (id == null || id === '') return;
    publicarSync({ type: 'tarefa-deleted', id: String(id) });
  };

  function aplicarExclusao(id) {
    if (id == null || id === '') return;
    window.plannerMarcarExcluida?.(id);
    window.plannerRemoverCardKanban?.(id);
  }

  channel?.addEventListener('message', (event) => {
    if (event.data?.type === 'tarefa-updated') {
      aplicarTarefa(event.data.tarefa);
      return;
    }
    if (event.data?.type === 'tarefa-deleted') {
      aplicarExclusao(event.data.id);
    }
  });

  window.addEventListener('storage', (event) => {
    if (event.key !== STORAGE_KEY || !event.newValue) return;

    try {
      const payload = JSON.parse(event.newValue);
      if (payload?.type === 'tarefa-deleted') {
        aplicarExclusao(payload.id);
        return;
      }
      aplicarTarefa(payload?.tarefa);
    } catch {
      /* ignore */
    }
  });
})();
