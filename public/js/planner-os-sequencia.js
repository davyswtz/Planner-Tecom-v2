(() => {
  const TAB_OS_ID = 'detalhe-tab-os';
  let salvando = false;
  let dragId = null;

  function tituloOsLimpo(titulo) {
    return String(titulo || '').replace(/^OS\s*[—\-]\s*/i, '').trim();
  }
  window.tituloOsLimpo = tituloOsLimpo;

  function parentIdAtual() {
    return document.getElementById('detalhe-conteudo')?.dataset?.id || null;
  }

  function cardsDaLista(container) {
    return [...container.querySelectorAll('.os-card[data-os-id]')];
  }

  function atualizarNumeros(container) {
    const cards = cardsDaLista(container);
    cards.forEach((card, index) => {
      const num = card.querySelector('.os-seq-num');
      if (num) num.textContent = String(index + 1);

      const btnUp = card.querySelector('.os-seq-btn[data-dir="up"]');
      const btnDown = card.querySelector('.os-seq-btn[data-dir="down"]');
      if (btnUp) btnUp.disabled = index === 0 || salvando;
      if (btnDown) btnDown.disabled = index === cards.length - 1 || salvando;
      card.classList.toggle('os-card--saving', salvando);
    });
  }

  function montarControles() {
    const wrap = document.createElement('div');
    wrap.className = 'os-seq-controls';
    wrap.innerHTML = `
      <button type="button" class="os-seq-handle" title="Arrastar para reordenar" aria-label="Arrastar para reordenar" draggable="true">
        <i class="ti ti-grip-vertical" style="font-size:12px"></i>
      </button>
      <span class="os-seq-num" aria-hidden="true">0</span>
      <div class="os-seq-btns">
        <button type="button" class="os-seq-btn" data-dir="up" title="Subir" aria-label="Subir na sequência">
          <i class="ti ti-chevron-up" style="font-size:11px"></i>
        </button>
        <button type="button" class="os-seq-btn" data-dir="down" title="Descer" aria-label="Descer na sequência">
          <i class="ti ti-chevron-down" style="font-size:11px"></i>
        </button>
      </div>
    `;
    return wrap;
  }

  function enhanceCard(card) {
    if (card.dataset.osSeqReady === '1') return;
    card.dataset.osSeqReady = '1';
    card.classList.add('os-card--sortable');

    const main = document.createElement('div');
    main.className = 'os-card-main';
    while (card.firstChild) {
      main.appendChild(card.firstChild);
    }

    card.appendChild(montarControles());
    card.appendChild(main);

    const tituloEl = main.querySelector('.os-card-title');
    if (tituloEl) {
      tituloEl.textContent = tituloOsLimpo(tituloEl.textContent);
    }
  }

  function enhanceLista(container) {
    if (!container) return;
    const cards = cardsDaLista(container);
    if (cards.length === 0) return;

    cards.forEach((card) => enhanceCard(card));
    atualizarNumeros(container);
  }

  function moverCard(card, direcao) {
    const container = card.closest(`#${TAB_OS_ID}`);
    if (!container || salvando) return;

    if (direcao === 'up' && card.previousElementSibling?.matches?.('.os-card[data-os-id]')) {
      container.insertBefore(card, card.previousElementSibling);
    } else if (direcao === 'down' && card.nextElementSibling?.matches?.('.os-card[data-os-id]')) {
      container.insertBefore(card.nextElementSibling, card);
    } else {
      return;
    }

    atualizarNumeros(container);
    salvarSequencia(container);
  }

  async function salvarSequencia(container) {
    const parentId = parentIdAtual();
    if (!parentId || salvando) return;

    const ids = cardsDaLista(container).map((card) => Number(card.dataset.osId)).filter(Boolean);
    if (ids.length < 2) return;

    const token = localStorage.getItem('planner_token');
    salvando = true;
    atualizarNumeros(container);

    try {
      const response = await fetch(`/api/op-tasks/${parentId}/os/sequencia`, {
        method: 'PUT',
        headers: {
          Authorization: 'Bearer ' + token,
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({ ids }),
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        throw new Error(payload.message || 'Não foi possível salvar a sequência.');
      }

      if (Array.isArray(payload.os) && typeof window.osDataMap === 'object' && window.osDataMap) {
        payload.os.forEach((os) => {
          if (os?.id != null) window.osDataMap[os.id] = os;
        });
      }
    } catch (err) {
      console.error(err);
      alert(err.message || 'Não foi possível salvar a sequência.');
    } finally {
      salvando = false;
      atualizarNumeros(container);
    }
  }

  function limparDragState(container) {
    container?.querySelectorAll('.os-card--dragging, .os-card--drag-over').forEach((el) => {
      el.classList.remove('os-card--dragging', 'os-card--drag-over');
    });
    dragId = null;
  }

  document.addEventListener('click', (event) => {
    const btn = event.target.closest('.os-seq-btn');
    if (!btn) return;
    event.preventDefault();
    event.stopPropagation();
    const card = btn.closest('.os-card[data-os-id]');
    if (!card) return;
    moverCard(card, btn.dataset.dir);
  });

  document.addEventListener('dragstart', (event) => {
    const handle = event.target.closest('.os-seq-handle');
    if (!handle) return;

    const card = handle.closest('.os-card[data-os-id]');
    if (!card || salvando) {
      event.preventDefault();
      return;
    }

    dragId = card.dataset.osId;
    card.classList.add('os-card--dragging');
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', dragId);
    try {
      event.dataTransfer.setDragImage(card, 24, 24);
    } catch (_) {
      // alguns navegadores podem recusar setDragImage
    }
  });

  document.addEventListener('dragend', (event) => {
    const card = event.target.closest('.os-card[data-os-id]');
    const container = card?.closest(`#${TAB_OS_ID}`) || document.getElementById(TAB_OS_ID);
    limparDragState(container);
  });

  document.addEventListener('dragover', (event) => {
    const card = event.target.closest(`#${TAB_OS_ID} .os-card[data-os-id]`);
    if (!card || !dragId || card.dataset.osId === dragId) return;
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';

    const container = card.closest(`#${TAB_OS_ID}`);
    container?.querySelectorAll('.os-card--drag-over').forEach((el) => {
      if (el !== card) el.classList.remove('os-card--drag-over');
    });
    card.classList.add('os-card--drag-over');
  });

  document.addEventListener('dragleave', (event) => {
    const card = event.target.closest(`#${TAB_OS_ID} .os-card[data-os-id]`);
    if (!card) return;
    if (card.contains(event.relatedTarget)) return;
    card.classList.remove('os-card--drag-over');
  });

  document.addEventListener('drop', (event) => {
    const alvo = event.target.closest(`#${TAB_OS_ID} .os-card[data-os-id]`);
    if (!alvo || !dragId) return;
    event.preventDefault();
    event.stopPropagation();

    const container = alvo.closest(`#${TAB_OS_ID}`);
    const origem = container?.querySelector(`.os-card[data-os-id="${CSS.escape(dragId)}"]`);
    if (!container || !origem || origem === alvo) {
      limparDragState(container);
      return;
    }

    const cards = cardsDaLista(container);
    const idxOrigem = cards.indexOf(origem);
    const idxAlvo = cards.indexOf(alvo);
    if (idxOrigem < idxAlvo) {
      container.insertBefore(origem, alvo.nextElementSibling);
    } else {
      container.insertBefore(origem, alvo);
    }

    limparDragState(container);
    atualizarNumeros(container);
    salvarSequencia(container);
  });

  function observarTabOs() {
    const container = document.getElementById(TAB_OS_ID);
    if (!container) return;

    enhanceLista(container);

    const observer = new MutationObserver(() => {
      enhanceLista(container);
    });

    observer.observe(container, { childList: true, subtree: false });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', observarTabOs);
  } else {
    observarTabOs();
  }
})();
