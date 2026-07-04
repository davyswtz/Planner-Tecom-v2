(function () {
  function esc(str) {
    if (str == null || str === '') return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatarDataHora(valor) {
    if (!valor) return '—';
    const data = new Date(valor);
    if (Number.isNaN(data.getTime())) return esc(String(valor));
    return data.toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  function formatarRelativo(valor) {
    if (!valor) return '';
    const data = new Date(valor);
    if (Number.isNaN(data.getTime())) return '';

    const diffMs = Date.now() - data.getTime();
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 1) return 'agora';
    if (diffMin < 60) return `há ${diffMin} min`;
    const diffH = Math.floor(diffMin / 60);
    if (diffH < 24) return `há ${diffH} h`;
    const diffD = Math.floor(diffH / 24);
    if (diffD < 7) return `há ${diffD} dia${diffD > 1 ? 's' : ''}`;
    return formatarDataHora(valor);
  }

  function iconeEvento(tipo) {
    if (tipo === 'criacao') return 'ti-plus';
    if (tipo === 'status') return 'ti-arrows-right-left';
    return 'ti-pencil';
  }

  function obterTarefaIdAtual() {
    const osId = typeof window.getOsDetalheAtualId === 'function' ? window.getOsDetalheAtualId() : null;
    if (osId) return String(osId);

    const detalheId = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (detalheId) return String(detalheId);

    return null;
  }

  function renderTimeline(eventos) {
    if (!Array.isArray(eventos) || !eventos.length) {
      return '<div class="historico-vazio"><i class="ti ti-history-off"></i><span>Nenhum registro no histórico desta tarefa.</span></div>';
    }

    return `
      <div class="historico-timeline">
        ${eventos.map((evento, index) => {
          const tipo = evento.tipo || 'alteracao';
          const usuario = esc(evento.usuario || 'Sistema');
          const descricao = esc(evento.descricao || 'Alteração registrada');
          const quando = formatarDataHora(evento.data);
          const relativo = formatarRelativo(evento.data);
          const ultimo = index === 0 ? ' historico-item--ultimo' : '';

          return `
            <div class="historico-item${ultimo}">
              <div class="historico-item-rail">
                <span class="historico-item-dot"><i class="ti ${iconeEvento(tipo)}"></i></span>
                ${index < eventos.length - 1 ? '<span class="historico-item-line"></span>' : ''}
              </div>
              <div class="historico-item-body">
                <div class="historico-item-top">
                  <strong class="historico-item-user">${usuario}</strong>
                  <span class="historico-item-time" title="${quando}">${relativo || quando}</span>
                </div>
                <p class="historico-item-desc">${descricao}</p>
                ${evento.de && evento.para ? `<div class="historico-item-change"><span>${esc(evento.de)}</span><i class="ti ti-arrow-right"></i><span>${esc(evento.para)}</span></div>` : ''}
                <span class="historico-item-date">${quando}</span>
              </div>
            </div>`;
        }).join('')}
      </div>`;
  }

  function fecharHistorico() {
    document.getElementById('modal-historico-overlay')?.classList.remove('open');
  }

  async function abrirHistoricoTarefa(taskId) {
    const id = taskId || obterTarefaIdAtual();
    if (!id) return;

    const overlay = document.getElementById('modal-historico-overlay');
    const conteudo = document.getElementById('modal-historico-conteudo');
    const subtitulo = document.getElementById('modal-historico-subtitulo');
    if (!overlay || !conteudo) return;

    overlay.classList.add('open');
    conteudo.innerHTML = '<div class="historico-loading"><i class="ti ti-loader-2"></i> Carregando histórico…</div>';
    if (subtitulo) subtitulo.textContent = 'Buscando alterações…';

    const token = localStorage.getItem('planner_token');

    try {
      const response = await fetch(`/api/op-tasks/${id}/historico`, {
        headers: {
          Authorization: 'Bearer ' + token,
          Accept: 'application/json',
        },
        cache: 'no-store',
      });
      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        conteudo.innerHTML = `<div class="historico-erro">${esc(payload.message || 'Não foi possível carregar o histórico.')}</div>`;
        if (subtitulo) subtitulo.textContent = 'Erro ao carregar';
        return;
      }

      const eventos = payload.historico?.eventos || [];
      const ultima = payload.historico?.ultima;

      if (subtitulo) {
        subtitulo.textContent = ultima
          ? `Última alteração por ${ultima.usuario || 'Sistema'}${ultima.data ? ` · ${formatarRelativo(ultima.data)}` : ''}`
          : 'Linha do tempo de alterações';
      }

      conteudo.innerHTML = renderTimeline(eventos);
    } catch (_) {
      conteudo.innerHTML = '<div class="historico-erro">Erro de conexão ao carregar o histórico.</div>';
      if (subtitulo) subtitulo.textContent = 'Erro de conexão';
    }
  }

  function abrirHistoricoTarefaAtual() {
    abrirHistoricoTarefa(obterTarefaIdAtual());
  }

  function ensureHistoricoButtonInParentModal() {
    const foot = document.querySelector('#detalhe-overlay .modal-foot-inner');
    if (!foot || foot.querySelector('[data-historico-btn="parent"]')) return;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'os-btn-historico-round';
    btn.dataset.historicoBtn = 'parent';
    btn.title = 'Histórico da tarefa';
    btn.setAttribute('aria-label', 'Histórico da tarefa');
    btn.innerHTML = '<i class="ti ti-history"></i>';
    btn.addEventListener('click', abrirHistoricoTarefaAtual);

    const excluir = foot.querySelector('#btn-excluir');
    if (excluir?.parentNode) {
      excluir.insertAdjacentElement('afterend', btn);
      return;
    }

    foot.prepend(btn);
  }

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (document.getElementById('modal-historico-overlay')?.classList.contains('open')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      fecharHistorico();
    }
  }, true);

  document.addEventListener('DOMContentLoaded', () => {
    ensureHistoricoButtonInParentModal();

    document.getElementById('os-detalhe-btn-historico')?.addEventListener('click', abrirHistoricoTarefaAtual);
    document.getElementById('ordem-os-detalhe-btn-historico')?.addEventListener('click', abrirHistoricoTarefaAtual);
  });

  window.abrirHistoricoTarefa = abrirHistoricoTarefa;
  window.abrirHistoricoTarefaAtual = abrirHistoricoTarefaAtual;
  window.fecharHistorico = fecharHistorico;
})();
