/**
 * Helpers compartilhados para editar campos do modal de detalhe.
 * Usa DOM (não innerHTML com value interpolado) para evitar quebra por aspas/HTML.
 */
(function () {
  'use strict';

  const INPUT_STYLE =
    'width:100%;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:4px 8px;font-size:13px;font-family:inherit;outline:none;background:var(--white);box-sizing:border-box;color:var(--gray-950)';

  function valorCampo(el) {
    if (!el) return '';
    const bruto = (el.textContent || '').trim();
    return bruto === '—' ? '' : bruto;
  }

  function aplicarEstilo(el, extra = '') {
    el.style.cssText = INPUT_STYLE + (extra ? ';' + extra : '');
  }

  /**
   * @param {Array<{id: string, tipo?: string, opcoes?: string[], onInput?: function}>} campos
   */
  function ativarCampos(campos) {
    (campos || []).forEach((campo) => {
      const el = document.getElementById(campo.id);
      if (!el) return;

      const valor = valorCampo(el);
      const tipo = campo.tipo || 'text';

      if (tipo === 'select') {
        const select = document.createElement('select');
        aplicarEstilo(select);
        (campo.opcoes || []).forEach((op) => {
          const option = document.createElement('option');
          option.value = op;
          option.textContent = op === '' ? 'Selecione...' : op;
          if (op === valor) option.selected = true;
          select.appendChild(option);
        });
        el.replaceChildren(select);
        return;
      }

      if (tipo === 'textarea') {
        const ta = document.createElement('textarea');
        ta.rows = campo.rows || 5;
        aplicarEstilo(ta, 'resize:vertical;min-height:' + (campo.minHeight || '120px'));
        ta.value = valor;
        el.replaceChildren(ta);
        return;
      }

      const input = document.createElement('input');
      input.type = tipo === 'number' || tipo === 'date' ? tipo : 'text';
      if (campo.inputId) input.id = campo.inputId;
      aplicarEstilo(input);
      input.value = valor;
      if (typeof campo.onInput === 'function') {
        input.addEventListener('input', () => campo.onInput(input));
      }
      el.replaceChildren(input);
    });
  }

  function ler(id, tipo = 'text') {
    const el = document.getElementById(id);
    if (!el) return '';

    if (tipo === 'select') {
      return el.querySelector('select')?.value ?? '';
    }
    if (tipo === 'textarea') {
      return el.querySelector('textarea')?.value ?? '';
    }
    return el.querySelector('input')?.value ?? '';
  }

  /** Só inclui chaves cujo controle de edição existe no DOM. */
  function montarDados(mapa) {
    const dados = {};
    Object.entries(mapa || {}).forEach(([chave, conf]) => {
      if (!conf || !conf.id) return;
      const el = document.getElementById(conf.id);
      if (!el) return;

      const tipo = conf.tipo || 'text';
      const controle =
        tipo === 'select'
          ? el.querySelector('select')
          : tipo === 'textarea'
            ? el.querySelector('textarea')
            : el.querySelector('input');

      if (!controle) return;
      let valor = controle.value ?? '';
      if (typeof conf.transform === 'function') {
        valor = conf.transform(valor);
      }
      dados[chave] = valor;
    });
    return dados;
  }

  function mostrarBotoesEdicao() {
    const excluir = document.getElementById('btn-excluir');
    const editar = document.getElementById('btn-editar');
    const salvar = document.getElementById('btn-salvar');
    const cancelar = document.getElementById('btn-cancelar');
    if (excluir) excluir.style.display = 'none';
    if (editar) editar.style.display = 'none';
    if (salvar) salvar.style.display = 'flex';
    if (cancelar) cancelar.style.display = 'flex';
  }

  async function enviarPut(url, dados) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(url, {
      method: 'PUT',
      headers: {
        Authorization: 'Bearer ' + token,
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(dados),
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const msg =
        payload.message ||
        (payload.errors ? Object.values(payload.errors).flat().join(' ') : '') ||
        'Erro ao salvar alterações.';
      throw new Error(msg);
    }
    return payload;
  }

  window.plannerDetalheEdicao = {
    ativarCampos,
    ler,
    montarDados,
    mostrarBotoesEdicao,
    enviarPut,
    valorCampo,
  };
})();
