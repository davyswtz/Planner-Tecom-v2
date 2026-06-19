/**
 * Ações imediatas no kanban após mutação local (exclusão, etc.).
 */
(function () {
  window.plannerRemoverCardKanban = function (id) {
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
      document.getElementById('total-atendimentos');

    if (totalEl) {
      totalEl.textContent = document.querySelectorAll('.kcard').length;
    }
  };

  window.plannerAposExclusaoTarefa = async function (id, reloadFn) {
    window.plannerRemoverCardKanban(id);
    await window.plannerAposMutacaoLocal(reloadFn);
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
