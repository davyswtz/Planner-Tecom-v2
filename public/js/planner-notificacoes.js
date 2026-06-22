/**
 * Notificações in-app (sino no topbar).
 */
(function () {
  const POLL_MS = 45000;
  let painelAberto = false;
  let timer = null;

  function token() {
    return localStorage.getItem('planner_token');
  }

  function els() {
    return {
      btn: document.getElementById('btn-notificacoes'),
      dot: document.getElementById('notif-dot'),
      painel: document.getElementById('notificacoes-painel'),
      lista: document.getElementById('notificacoes-lista'),
      vazio: document.getElementById('notificacoes-vazio'),
      badge: document.getElementById('notificacoes-badge'),
    };
  }

  function esc(valor) {
    if (valor == null || valor === '') return '';
    return String(valor)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatarQuando(valor) {
    if (!valor) return '';
    const data = new Date(valor);
    if (isNaN(data.getTime())) return '';
    const agora = new Date();
    const diffMs = agora - data;
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 1) return 'agora';
    if (diffMin < 60) return `há ${diffMin} min`;
    const diffH = Math.floor(diffMin / 60);
    if (diffH < 24) return `há ${diffH}h`;
    return data.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
  }

  function atualizarBadge(qtd) {
    const { dot, badge } = els();
    if (dot) dot.style.display = qtd > 0 ? 'block' : 'none';
    if (badge) {
      badge.textContent = qtd > 99 ? '99+' : String(qtd);
      badge.style.display = qtd > 0 ? 'inline-flex' : 'none';
    }
  }

  function renderLista(notificacoes) {
    const { lista, vazio } = els();
    if (!lista || !vazio) return;

    if (!notificacoes.length) {
      lista.innerHTML = '';
      vazio.style.display = 'block';
      return;
    }

    vazio.style.display = 'none';
    lista.innerHTML = notificacoes.map((n) => {
      const lida = Boolean(n.read_at);
      return `
        <button type="button" class="notif-item${lida ? ' notif-item--lida' : ''}" data-id="${n.id}" data-ref-id="${n.ref_id || ''}">
          <span class="notif-item-title">${esc(n.title)}</span>
          <span class="notif-item-msg">${esc(n.message)}</span>
          <span class="notif-item-time">${formatarQuando(n.created_at)}</span>
        </button>`;
    }).join('');
  }

  async function carregarNotificacoes() {
    if (!token()) return;

    try {
      const response = await fetch('/api/notificacoes', {
        headers: {
          Authorization: 'Bearer ' + token(),
          Accept: 'application/json',
        },
        cache: 'no-store',
      });

      if (response.status === 401) return;

      const data = await response.json();
      if (!response.ok) return;

      renderLista(data.notificacoes || []);
      atualizarBadge(data.nao_lidas || 0);
    } catch (_) {}
  }

  async function marcarLida(id) {
    if (!token() || !id) return;

    try {
      const response = await fetch(`/api/notificacoes/${id}/ler`, {
        method: 'POST',
        headers: {
          Authorization: 'Bearer ' + token(),
          Accept: 'application/json',
        },
      });

      if (!response.ok) return;

      const data = await response.json();
      atualizarBadge(data.nao_lidas || 0);

      const item = document.querySelector(`.notif-item[data-id="${CSS.escape(String(id))}"]`);
      item?.classList.add('notif-item--lida');
    } catch (_) {}
  }

  function fecharPainel() {
    const { painel } = els();
    painelAberto = false;
    painel?.classList.remove('open');
  }

  function togglePainel() {
    const { painel } = els();
    if (!painel) return;

    painelAberto = !painelAberto;
    painel.classList.toggle('open', painelAberto);

    if (painelAberto) {
      carregarNotificacoes();
    }
  }

  async function aoClicarNotificacao(btn) {
    const id = btn.dataset.id;
    await marcarLida(id);
    fecharPainel();

    if (btn.dataset.refId) {
      window.location.href = '/dashboard';
    }
  }

  function iniciar() {
    const { btn, painel, lista } = els();
    if (!btn || !painel) return;

    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      togglePainel();
    });

    document.getElementById('btn-notificacoes-marcar-todas')?.addEventListener('click', async (e) => {
      e.stopPropagation();
      if (!token()) return;

      try {
        await fetch('/api/notificacoes/ler-todas', {
          method: 'POST',
          headers: {
            Authorization: 'Bearer ' + token(),
            Accept: 'application/json',
          },
        });
        await carregarNotificacoes();
      } catch (_) {}
    });

    lista?.addEventListener('click', (e) => {
      const item = e.target.closest('.notif-item');
      if (item) aoClicarNotificacao(item);
    });

    document.addEventListener('click', (e) => {
      if (!painelAberto) return;
      if (painel.contains(e.target) || btn.contains(e.target)) return;
      fecharPainel();
    });

    carregarNotificacoes();
    timer = setInterval(carregarNotificacoes, POLL_MS);
  }

  window.plannerRecarregarNotificacoes = carregarNotificacoes;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
  } else {
    iniciar();
  }
})();
