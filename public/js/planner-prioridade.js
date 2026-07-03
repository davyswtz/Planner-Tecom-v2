(function () {
  const NIVEIS = ['Baixa', 'Média', 'Alta'];
  const CLASSES = {
    Baixa: 'btn-prio-baixa',
    Média: 'btn-prio-media',
    Alta: 'btn-prio-alta',
  };

  function resolverWrap(container) {
    if (!container) return null;
    return container.classList?.contains('prioridade-wrap')
      ? container
      : container.querySelector?.('.prioridade-wrap');
  }

  function nivelDoBotao(btn) {
    if (btn.dataset.nivel) return btn.dataset.nivel;
    const label = btn.querySelector('.prio-label');
    return (label?.textContent || btn.textContent || '').replace(/\s*✓\s*/g, '').trim();
  }

  function normalizarBotao(btn) {
    const nivel = nivelDoBotao(btn);
    btn.dataset.nivel = nivel;
    if (!btn.querySelector('.prio-label')) {
      btn.textContent = '';
      const span = document.createElement('span');
      span.className = 'prio-label';
      span.textContent = nivel;
      btn.appendChild(span);
    } else {
      btn.querySelector('.prio-label').textContent = nivel;
    }
    btn.style.removeProperty('border-width');
    return nivel;
  }

  window.plannerAtualizarBotoesPrioridade = function (container, nivelAtivo) {
    const wrap = resolverWrap(container);
    if (!wrap) return;

    wrap.querySelectorAll('.btn-prioridade').forEach((btn) => {
      const nivel = normalizarBotao(btn);
      const ativo = nivel === nivelAtivo;
      btn.classList.toggle('btn-prio-ativo', ativo);
      btn.setAttribute('aria-pressed', ativo ? 'true' : 'false');
    });
  };

  window.plannerHtmlBotoesPrioridade = function (nivelAtivo, onclickFn) {
    return NIVEIS.map((nivel) => {
      const ativo = nivel === (nivelAtivo || 'Média');
      return `<button type="button" data-nivel="${nivel}" aria-pressed="${ativo ? 'true' : 'false'}" onclick="${onclickFn}(this,'${nivel}')" class="btn-prioridade ${CLASSES[nivel]}${ativo ? ' btn-prio-ativo' : ''}"><span class="prio-label">${nivel}</span></button>`;
    }).join('');
  };

  window.plannerResetPrioridade = function (container, nivel = 'Média') {
    window.plannerAtualizarBotoesPrioridade(container, nivel);
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.prioridade-wrap').forEach((wrap) => {
      const ativo = wrap.querySelector('.btn-prio-ativo');
      const nivel = ativo ? nivelDoBotao(ativo) : 'Média';
      window.plannerAtualizarBotoesPrioridade(wrap, nivel);
    });
  });
})();
