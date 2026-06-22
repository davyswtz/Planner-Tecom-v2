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

  function aplicarTarefa(tarefa) {
    if (!tarefaPertenceAoUsuario(tarefa)) return;

    if (typeof window.adicionarCardTarefa === 'function') {
      window.adicionarCardTarefa(tarefa);
    }

    if (typeof window.atualizarCardSuasTarefas === 'function') {
      window.atualizarCardSuasTarefas(tarefa);
    }
  }

  window.plannerSyncTarefa = function (tarefa) {
    if (!tarefa?.id) return;

    try {
      channel?.postMessage({ type: 'tarefa-updated', tarefa });
    } catch {
      /* ignore */
    }

    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ t: Date.now(), tarefa }));
    } catch {
      /* ignore */
    }
  };

  channel?.addEventListener('message', (event) => {
    if (event.data?.type === 'tarefa-updated') {
      aplicarTarefa(event.data.tarefa);
    }
  });

  window.addEventListener('storage', (event) => {
    if (event.key !== STORAGE_KEY || !event.newValue) return;

    try {
      const payload = JSON.parse(event.newValue);
      aplicarTarefa(payload?.tarefa);
    } catch {
      /* ignore */
    }
  });
})();
