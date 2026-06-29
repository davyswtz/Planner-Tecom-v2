/**
 * Ações imediatas no kanban após mutação local (exclusão, etc.).
 * Tombstones em sessionStorage + localStorage para sobreviver entre abas.
 */
(function () {
  const TOMBSTONE_SESSION_KEY = 'planner_tarefas_excluidas';
  const TOMBSTONE_LOCAL_KEY = 'planner_tarefas_excluidas_ls';
  const tombstones = new Set();

  function persistirTombstones() {
    const payload = JSON.stringify([...tombstones]);
    try {
      sessionStorage.setItem(TOMBSTONE_SESSION_KEY, payload);
    } catch {
      /* ignore */
    }
    try {
      localStorage.setItem(TOMBSTONE_LOCAL_KEY, payload);
    } catch {
      /* ignore */
    }
  }

  function carregarTombstones() {
    for (const storage of [sessionStorage, localStorage]) {
      const key = storage === sessionStorage ? TOMBSTONE_SESSION_KEY : TOMBSTONE_LOCAL_KEY;
      try {
        const raw = storage.getItem(key);
        if (!raw) continue;
        JSON.parse(raw).forEach((id) => tombstones.add(String(id)));
      } catch {
        /* ignore */
      }
    }
  }

  carregarTombstones();

  window.plannerMarcarExcluida = function (id) {
    if (id == null || id === '') return;
    tombstones.add(String(id));
    persistirTombstones();
  };

  window.plannerDesmarcarExcluida = function (id) {
    if (id == null || id === '') return;
    tombstones.delete(String(id));
    persistirTombstones();
  };

  window.plannerEstaExcluida = function (id) {
    return tombstones.has(String(id));
  };

  window.plannerFiltrarExcluidas = function (items) {
    if (!Array.isArray(items) || tombstones.size === 0) return items;
    return items.filter((item) => !tombstones.has(String(item?.id)));
  };

  window.plannerLimparTombstonesConfirmadas = function (idsPresentes) {
    if (!Array.isArray(idsPresentes)) return;
    const presentes = new Set(idsPresentes.map((id) => String(id)));
    let mudou = false;
    for (const id of [...tombstones]) {
      if (!presentes.has(id)) {
        tombstones.delete(id);
        mudou = true;
      }
    }
    if (mudou) persistirTombstones();
  };

  window.plannerRemoverCardKanban = function (id) {
    window.plannerMarcarExcluida(id);

    const alvo = String(id);
    const seletorKanban = `.kcard[data-id="${CSS.escape(alvo)}"]`;
    document.querySelectorAll(seletorKanban).forEach((card) => {
      const col = card.closest('.kcol-body');
      card.remove();
      if (col?.id) {
        const countId = col.id.replace('col-', 'count-');
        const countEl = document.getElementById(countId);
        if (countEl) countEl.textContent = col.querySelectorAll('.kcard').length;
      }
    });

    const seletorSuasTarefas = `#suas-tarefas-body .starefa-item[data-id="${CSS.escape(alvo)}"]`;
    document.querySelectorAll(seletorSuasTarefas).forEach((card) => card.remove());

    const suasTarefasBody = document.getElementById('suas-tarefas-body');
    if (suasTarefasBody && !suasTarefasBody.querySelector('.starefa-item') && !suasTarefasBody.querySelector('.suas-tarefas-empty')) {
      suasTarefasBody.innerHTML = `
        <div class="suas-tarefas-empty">
          <i class="ti ti-checklist"></i>
          <span>Nenhuma tarefa atribuída a você.</span>
        </div>`;
    }

    const contadorSuasTarefas = document.getElementById('suas-tarefas-count');
    if (contadorSuasTarefas) {
      contadorSuasTarefas.textContent = suasTarefasBody?.querySelectorAll('.starefa-item').length || 0;
    }

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

  window.plannerAposExclusaoTarefa = async function (id, reloadFn) {
    window.plannerMarcarExcluida(id);
    window.plannerRemoverCardKanban(id);
    window.plannerPausarPolling?.(60000);
    await window.plannerNotifyLocalMutation?.();
    if (typeof reloadFn === 'function') {
      await reloadFn();
    }
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

  window.addEventListener('storage', (event) => {
    if (event.key !== TOMBSTONE_LOCAL_KEY || !event.newValue) return;
    try {
      JSON.parse(event.newValue).forEach((id) => tombstones.add(String(id)));
    } catch {
      /* ignore */
    }
  });
})();
