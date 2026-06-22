/**
 * Ações imediatas no kanban após mutação local (exclusão, etc.).
 */
(function () {
  const tombstones = new Map();
  const TOMBSTONE_TTL_MS = 5 * 60 * 1000;

  window.plannerMarcarExcluida = function (id) {
    const key = String(id);
    tombstones.set(key, Date.now() + TOMBSTONE_TTL_MS);
  };

  window.plannerFiltrarExcluidas = function (items) {
    if (!Array.isArray(items) || tombstones.size === 0) return items;
    const agora = Date.now();
    for (const [key, expira] of tombstones) {
      if (expira <= agora) tombstones.delete(key);
    }
    return items.filter((item) => !tombstones.has(String(item?.id)));
  };

  window.plannerRemoverCardKanban = function (id) {
    window.plannerMarcarExcluida(id);

    const alvo = String(id);
    const seletor = `.kcard[data-id="${CSS.escape(alvo)}"]`;
    document.querySelectorAll(seletor).forEach((card) => {
      const col = card.closest('.kcol-body');
      card.remove();
      if (col?.id) {
        const countId = col.id.replace('col-', 'count-');
        const countEl = document.getElementById(countId);
        if (countEl) countEl.textContent = col.querySelectorAll('.kcard').length;
      }
    });

    const totalEl =
      document.getElementById('total-rompimentos') ||
      document.getElementById('total-otimizacoes') ||
      document.getElementById('total-trocas') ||
      document.getElementById('total-atendimentos') ||
      document.getElementById('total-tarefas');

    if (totalEl) {
      totalEl.textContent = document.querySelectorAll('.kcard').length;
    }
  };

  window.plannerAposExclusaoTarefa = async function (id) {
    window.plannerRemoverCardKanban(id);
    await window.plannerAposMutacaoLocal();
  };

  window.plannerAposMutacaoLocal = async function (reloadFn) {
    window.plannerInvalidateReloads?.();

    if (typeof window.plannerNotifyLocalMutation === 'function') {
      await window.plannerNotifyLocalMutation();
    }

    if (typeof reloadFn === 'function') {
      await reloadFn();
    }
  };
})();
