@extends('layouts.app')

@section('title', 'Mensagens — Planner Telecom')
@section('page-title', 'Mensagens')
@section('hide-topbar-btn', true)

@section('styles')
<style>
  .mensagens-page { width: 100%; max-width: 100%; display: flex; flex-direction: column; gap: 16px; }
  .mensagens-layout {
    display: grid; grid-template-columns: 240px minmax(0, 1fr); gap: 16px; align-items: start;
  }
  @media (max-width: 900px) { .mensagens-layout { grid-template-columns: 1fr; } }

  .mensagens-categorias {
    display: flex; flex-direction: column; gap: 4px; padding: 8px;
  }
  .mensagens-cat-btn {
    width: 100%; text-align: left; padding: 10px 12px; border: 1px solid transparent;
    border-radius: var(--radius-sm); background: transparent; color: var(--gray-700);
    font: inherit; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px;
  }
  .mensagens-cat-btn:hover { background: var(--gray-50); }
  .mensagens-cat-btn.active {
    background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; font-weight: 600;
  }

  .mensagens-editor { display: flex; flex-direction: column; gap: 14px; }
  .mensagens-status-card {
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden;
  }
  .mensagens-status-head {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 10px 12px; background: var(--gray-50); border-bottom: 1px solid var(--gray-100);
    cursor: pointer; user-select: none;
  }
  .mensagens-status-card.collapsed .mensagens-status-head { border-bottom-color: transparent; }
  .mensagens-status-head-left {
    display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;
  }
  .mensagens-status-head-actions {
    display: flex; align-items: center; gap: 8px; flex-shrink: 0;
  }
  .mensagens-status-collapse-btn {
    width: 28px; height: 28px; border: none; border-radius: var(--radius-sm);
    background: transparent; color: var(--gray-500); cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .mensagens-status-collapse-btn:hover { background: var(--gray-200); color: var(--gray-800); }
  .mensagens-status-collapse-btn i {
    font-size: 16px; transition: transform 0.2s ease;
  }
  .mensagens-status-card.collapsed .mensagens-status-collapse-btn i { transform: rotate(-90deg); }
  .mensagens-status-content {
    overflow: hidden; transition: max-height 0.25s ease, opacity 0.2s ease;
    max-height: 4000px; opacity: 1;
  }
  .mensagens-status-card.collapsed .mensagens-status-content {
    max-height: 0; opacity: 0; pointer-events: none;
  }
  .mensagens-status-title { font-size: 13px; font-weight: 600; color: var(--gray-800); }
  .mensagens-status-badge {
    font-size: 11px; font-weight: 500; padding: 2px 8px; border-radius: 20px;
    background: var(--gray-100); color: var(--gray-500);
  }
  .mensagens-status-badge.custom { background: #eff6ff; color: #1d4ed8; }
  .mensagens-status-body { padding: 12px; display: flex; flex-direction: column; gap: 8px; }
  .mensagens-textarea {
    width: 100%; min-height: 140px; padding: 10px 12px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); background: var(--white); color: var(--gray-950);
    font: inherit; font-size: 13px; line-height: 1.5; resize: vertical; outline: none;
  }
  .mensagens-textarea:focus { border-color: var(--blue-600); }
  .mensagens-status-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
  .mensagens-btn {
    height: 30px; padding: 0 10px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); background: var(--white); color: var(--gray-700);
    font: inherit; font-size: 12px; cursor: pointer; display: inline-flex;
    align-items: center; gap: 5px;
  }
  .mensagens-btn:hover { border-color: var(--blue-600); color: var(--blue-600); }
  .mensagens-btn.primary {
    background: var(--blue-600); border-color: var(--blue-600); color: #fff;
  }
  .mensagens-btn.primary:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
  .mensagens-btn:disabled { opacity: 0.6; cursor: not-allowed; }

  .mensagens-placeholders {
    display: flex; flex-wrap: wrap; gap: 6px; padding: 10px 12px;
    border-top: 1px solid var(--gray-100); background: var(--gray-50);
  }
  .mensagens-ph-label {
    width: 100%; font-size: 11px; font-weight: 600; color: var(--gray-500);
    text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px;
  }
  .mensagens-ph-chip {
    border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-700);
    border-radius: 20px; padding: 3px 8px; font-size: 11px; cursor: pointer;
  }
  .mensagens-ph-chip:hover { border-color: var(--blue-600); color: var(--blue-600); }

  .mensagens-preview {
    margin-top: 4px; padding: 10px 12px; border-radius: var(--radius-sm);
    background: #f8fafc; border: 1px dashed var(--gray-200);
    font-size: 12px; line-height: 1.5; color: var(--gray-700); white-space: pre-wrap;
    display: none;
  }
  .mensagens-preview.open { display: block; }

  .mensagens-topbar {
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
    flex-wrap: wrap;
  }
  .mensagens-help { font-size: 12px; color: var(--gray-500); line-height: 1.5; max-width: 640px; }
  .mensagens-feedback {
    display: none; padding: 10px 12px; border-radius: var(--radius-sm); font-size: 13px;
  }
  .mensagens-feedback.ok { display: block; background: #f0fdf4; color: #15803d; }
  .mensagens-feedback.err { display: block; background: #fef2f2; color: #b91c1c; }
  .mensagens-loading {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 48px 16px; color: var(--gray-500); font-size: 13px;
  }
  .mensagens-loading i { animation: spin 0.9s linear infinite; }

  .emoji-popover {
    position: fixed; z-index: 1200; width: 320px; max-width: calc(100vw - 24px);
    background: var(--white); border: 1px solid var(--gray-200);
    border-radius: 12px; box-shadow: 0 12px 40px rgba(15, 23, 42, 0.18);
    display: none; flex-direction: column; overflow: hidden;
  }
  .emoji-popover.open { display: flex; }
  .emoji-popover-head {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    padding: 8px 10px; border-bottom: 1px solid var(--gray-100); background: var(--gray-50);
  }
  .emoji-popover-title { font-size: 12px; font-weight: 600; color: var(--gray-700); }
  .emoji-popover-close {
    border: none; background: transparent; color: var(--gray-500); cursor: pointer;
    width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center;
  }
  .emoji-popover-close:hover { background: var(--gray-200); color: var(--gray-800); }
  .emoji-popover-tabs {
    display: flex; gap: 2px; padding: 6px 8px; border-bottom: 1px solid var(--gray-100);
    overflow-x: auto; scrollbar-width: none;
  }
  .emoji-popover-tabs::-webkit-scrollbar { display: none; }
  .emoji-popover-tab {
    flex: 0 0 auto; min-width: 36px; height: 32px; border: none; border-radius: 8px;
    background: transparent; font-size: 18px; cursor: pointer; line-height: 1;
  }
  .emoji-popover-tab:hover { background: var(--gray-50); }
  .emoji-popover-tab.active { background: #eff6ff; }
  .emoji-popover-body {
    padding: 8px; max-height: 280px; overflow-y: auto;
  }
  .emoji-popover-grid {
    display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px;
  }
  .emoji-popover-btn {
    aspect-ratio: 1; border: none; border-radius: 8px; background: transparent;
    font-size: 22px; line-height: 1; cursor: pointer; padding: 0;
    display: flex; align-items: center; justify-content: center;
  }
  .emoji-popover-btn:hover { background: var(--gray-100); transform: scale(1.12); }

  [data-theme="dark"] .emoji-popover { background: #161b22; border-color: #30363d; box-shadow: 0 12px 40px rgba(0,0,0,0.45); }
  [data-theme="dark"] .emoji-popover-head { background: #0d1117; border-color: #30363d; }
  [data-theme="dark"] .emoji-popover-title { color: #e6edf3; }
  [data-theme="dark"] .emoji-popover-tabs { border-color: #30363d; }
  [data-theme="dark"] .emoji-popover-tab:hover { background: #21262d; }
  [data-theme="dark"] .emoji-popover-tab.active { background: #0d2340; }
  [data-theme="dark"] .emoji-popover-btn:hover { background: #21262d; }
  [data-theme="dark"] .emoji-popover-close:hover { background: #30363d; color: #e6edf3; }

  [data-theme="dark"] .mensagens-status-collapse-btn:hover { background: #30363d; color: #e6edf3; }
  [data-theme="dark"] .mensagens-cat-btn { color: #e6edf3; }
  [data-theme="dark"] .mensagens-cat-btn:hover { background: #21262d; }
  [data-theme="dark"] .mensagens-cat-btn.active { background: #0d2340; border-color: #1f4b8f; color: #79c0ff; }
  [data-theme="dark"] .mensagens-status-card { border-color: #30363d; }
  [data-theme="dark"] .mensagens-status-head,
  [data-theme="dark"] .mensagens-placeholders { background: #161b22; border-color: #30363d; }
  [data-theme="dark"] .mensagens-textarea,
  [data-theme="dark"] .mensagens-btn,
  [data-theme="dark"] .mensagens-ph-chip { background: #21262d; border-color: #30363d; color: #e6edf3; }
  [data-theme="dark"] .mensagens-preview { background: #161b22; border-color: #30363d; color: #c9d1d9; }
  [data-theme="dark"] .mensagens-feedback.ok { background: #0f2419; color: #3fb950; }
  [data-theme="dark"] .mensagens-feedback.err { background: #2d1117; color: #ff7b72; }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endsection

@section('content')
<div class="mensagens-page">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Templates de mensagens</span>
    </div>
    <div style="padding: 12px 16px;">
      <div class="mensagens-topbar">
        <p class="mensagens-help">
          Configure o texto enviado ao Google Chat quando uma tarefa muda de status.
          Use variáveis entre chaves — por exemplo <code>{task_code}</code>, <code>{regiao}</code> e <code>{nome_cliente}</code> —
          elas serão substituídas pelos dados reais da tarefa. Todas as variáveis disponíveis aparecem abaixo de cada editor.
        </p>
        <button type="button" class="mensagens-btn primary" id="btn-salvar-mensagens" onclick="salvarMensagens()" disabled>
          <i class="ti ti-device-floppy"></i> Salvar alterações
        </button>
      </div>
      <div class="mensagens-feedback" id="mensagens-feedback"></div>
    </div>
  </div>

  <div class="mensagens-layout" id="mensagens-layout">
    <div class="card">
      <div class="card-header"><span class="card-title">Categorias</span></div>
      <div class="mensagens-categorias" id="mensagens-categorias">
        <div class="mensagens-loading"><i class="ti ti-loader-2"></i> Carregando...</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-title" id="mensagens-editor-titulo">Mensagens</span>
      </div>
      <div style="padding: 12px 16px 16px;" id="mensagens-editor-wrap">
        <div class="mensagens-loading"><i class="ti ti-loader-2"></i> Carregando templates...</div>
      </div>
    </div>
  </div>
</div>

<div id="emoji-popover" class="emoji-popover" role="dialog" aria-label="Seletor de emojis">
  <div class="emoji-popover-head">
    <span class="emoji-popover-title" id="emoji-popover-title">Emojis</span>
    <button type="button" class="emoji-popover-close" onclick="fecharEmojiPicker()" aria-label="Fechar">
      <i class="ti ti-x"></i>
    </button>
  </div>
  <div class="emoji-popover-tabs" id="emoji-popover-tabs"></div>
  <div class="emoji-popover-body">
    <div class="emoji-popover-grid" id="emoji-popover-grid"></div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  if (typeof window.plannerPossuiPermissao === 'function' && !window.plannerPossuiPermissao('visualizar_tela_mensagens')) {
    window.location.replace('/dashboard');
  }
</script>
<script type="module">
  let catalogo = [];
  let placeholders = [];
  let templates = {};
  let padroes = {};
  let customizados = {};
  let categoriaAtiva = null;
  let alterado = false;
  let campoAtivoId = null;
  let campoAtivoRotulo = '';
  let emojiTabAtiva = 0;
  let emojiPickerAberto = false;
  let emojiTriggerBtn = null;
  const statusExpandidos = {};

  const gruposEmojis = [
    {
      label: 'Status',
      emojis: ['✅', '❌', '⚠️', '🚨', '🔧', '🔄', '⏳', '📋', '✔️', '🆗', '🛑', '🔴', '🟡', '🟢'],
    },
    {
      label: 'Alertas',
      emojis: ['📢', '📣', '🔔', '💥', '❗', '‼️', '🔥', '⚡', '💡', '🎯'],
    },
    {
      label: 'Localização',
      emojis: ['📍', '🗺️', '🌐', '🏠', '🏢', '🛣️', '🧭'],
    },
    {
      label: 'Pessoas',
      emojis: ['👤', '👥', '🧑‍🔧', '👷', '🙋', '🤝'],
    },
    {
      label: 'Documentos',
      emojis: ['🧾', '📌', '📝', '💻', '🔑', '🆔', '📄', '📎', '📁', '🗂️'],
    },
    {
      label: 'Rede / Telecom',
      emojis: ['📡', '🛜', '🔌', '📶', '📞', '📲', '🖧', '🔗'],
    },
    {
      label: 'Ferramentas',
      emojis: ['🛠️', '⚙️', '🔩', '🧰', '🪛', '🔨'],
    },
    {
      label: 'Objetos',
      emojis: ['📦', '🏗️', '🪜', '🚧', '🚛', '🏷️'],
    },
    {
      label: 'Símbolos',
      emojis: ['⭐', '✨', '➡️', '⬇️', '▪️', '━', '•', '—'],
    },
  ];

  function token() {
    return localStorage.getItem('planner_token');
  }

  function esc(valor) {
    if (valor == null) return '';
    return String(valor)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function mostrarFeedback(mensagem, tipo = 'ok') {
    const el = document.getElementById('mensagens-feedback');
    el.textContent = mensagem;
    el.className = 'mensagens-feedback ' + tipo;
    clearTimeout(mostrarFeedback._timer);
    mostrarFeedback._timer = setTimeout(() => {
      el.className = 'mensagens-feedback';
    }, 4000);
  }

  function marcarAlterado() {
    alterado = true;
    document.getElementById('btn-salvar-mensagens').disabled = false;
  }

  async function requestJson(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        'Authorization': 'Bearer ' + token(),
        'Accept': 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(options.headers || {}),
      },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.message || 'Erro na requisição.');
    }
    return data;
  }

  function idCampo(categoria, status) {
    return `msg-${categoria}-${status}`.replace(/[^a-zA-Z0-9_-]/g, '_');
  }

  function chaveStatusColapsado(categoria, status) {
    return `${categoria}|${status}`;
  }

  function isStatusColapsado(categoria, status) {
    return !statusExpandidos[chaveStatusColapsado(categoria, status)];
  }

  window.toggleStatusColapsado = function (categoria, status, btn, event) {
    event?.stopPropagation();

    const card = btn?.closest('.mensagens-status-card')
      || document.querySelector(`.mensagens-status-card[data-categoria="${categoria}"][data-status="${status}"]`);
    if (!card) return;

    const chave = chaveStatusColapsado(categoria, status);
    const vaiColapsar = !card.classList.contains('collapsed');

    if (vaiColapsar) {
      delete statusExpandidos[chave];
    } else {
      statusExpandidos[chave] = true;
    }

    card.classList.toggle('collapsed', vaiColapsar);
    atualizarBotaoColapsar(btn, vaiColapsar);
  };

  function atualizarBotaoColapsar(btn, colapsado) {
    if (!btn) return;
    const rotulo = colapsado ? 'Expandir status' : 'Recolher status';
    btn.title = rotulo;
    btn.setAttribute('aria-label', rotulo);
    btn.setAttribute('aria-expanded', colapsado ? 'false' : 'true');
  }

  function isCustomizado(categoria, status) {
    return Boolean(customizados[categoria]?.[status]);
  }

  function renderEmojiPopover() {
    const tabs = document.getElementById('emoji-popover-tabs');
    const grid = document.getElementById('emoji-popover-grid');
    if (!tabs || !grid) return;

    tabs.innerHTML = gruposEmojis.map((grupo, index) => `
      <button type="button"
        class="emoji-popover-tab ${index === emojiTabAtiva ? 'active' : ''}"
        title="${esc(grupo.label)}"
        onclick="selecionarEmojiTab(${index})">
        ${grupo.emojis[0] || '😀'}
      </button>
    `).join('');

    const grupo = gruposEmojis[emojiTabAtiva] || gruposEmojis[0];
    grid.innerHTML = (grupo?.emojis || []).map(emoji => `
      <button type="button" class="emoji-popover-btn" title="Inserir">${emoji}</button>
    `).join('');

    grid.onclick = (event) => {
      const btn = event.target.closest('.emoji-popover-btn');
      if (!btn) return;
      window.inserirEmoji(btn.textContent || '');
    };
  }

  function posicionarEmojiPicker(anchor) {
    const popover = document.getElementById('emoji-popover');
    if (!popover || !anchor) return;

    const rect = anchor.getBoundingClientRect();
    const margem = 12;
    const largura = popover.offsetWidth || 320;
    const altura = popover.offsetHeight || 360;

    let left = rect.left;
    let top = rect.bottom + 8;

    if (left + largura > window.innerWidth - margem) {
      left = window.innerWidth - largura - margem;
    }
    if (left < margem) left = margem;

    if (top + altura > window.innerHeight - margem) {
      top = rect.top - altura - 8;
    }
    if (top < margem) top = margem;

    popover.style.left = `${left}px`;
    popover.style.top = `${top}px`;
  }

  window.selecionarEmojiTab = function (index) {
    emojiTabAtiva = index;
    renderEmojiPopover();
  };

  window.abrirEmojiPicker = function (campoId, rotulo, btn, event) {
    event?.stopPropagation();

    if (emojiPickerAberto && campoAtivoId === campoId) {
      fecharEmojiPicker();
      return;
    }

    definirCampoAtivo(campoId, rotulo);

    const popover = document.getElementById('emoji-popover');
    const titulo = document.getElementById('emoji-popover-title');
    if (!popover) return;

    if (titulo) {
      titulo.textContent = rotulo ? `Emojis · ${rotulo}` : 'Emojis';
    }

    emojiTriggerBtn = btn;
    emojiTabAtiva = 0;
    renderEmojiPopover();
    popover.classList.add('open');
    emojiPickerAberto = true;
    requestAnimationFrame(() => posicionarEmojiPicker(btn));
  };

  window.fecharEmojiPicker = function () {
    const popover = document.getElementById('emoji-popover');
    if (!popover) return;
    popover.classList.remove('open');
    emojiPickerAberto = false;
    emojiTriggerBtn = null;
  };

  function registrarCamposAtivos() {
    document.querySelectorAll('.mensagens-textarea').forEach((el) => {
      el.addEventListener('focus', () => {
        campoAtivoId = el.id;
        const card = el.closest('.mensagens-status-card');
        const categoria = card?.dataset.categoria || '';
        const status = card?.dataset.status || '';
        const catLabel = catalogo.find(item => item.key === categoria)?.label || categoria;
        campoAtivoRotulo = catLabel && status ? `${catLabel} · ${status}` : (el.id || 'Mensagem');
      });
    });
  }

  function inserirNoCampo(campoId, texto) {
    const el = document.getElementById(campoId);
    if (!el) return false;

    const inicio = el.selectionStart ?? el.value.length;
    const fim = el.selectionEnd ?? el.value.length;
    const antes = el.value.slice(0, inicio);
    const depois = el.value.slice(fim);
    el.value = antes + texto + depois;
    el.focus();
    el.selectionStart = el.selectionEnd = inicio + texto.length;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    return true;
  }

  function renderCategorias() {
    const wrap = document.getElementById('mensagens-categorias');
    wrap.innerHTML = catalogo.map(cat => `
      <button type="button"
        class="mensagens-cat-btn ${cat.key === categoriaAtiva ? 'active' : ''}"
        onclick="selecionarCategoria('${esc(cat.key)}')">
        <i class="ti ti-folder"></i> ${esc(cat.label)}
      </button>
    `).join('');
  }

  function renderEditor() {
    const cat = catalogo.find(item => item.key === categoriaAtiva);
    const wrap = document.getElementById('mensagens-editor-wrap');
    const titulo = document.getElementById('mensagens-editor-titulo');

    if (!cat) {
      titulo.textContent = 'Mensagens';
      wrap.innerHTML = '<div class="mensagens-loading">Selecione uma categoria.</div>';
      return;
    }

    titulo.textContent = cat.label;

    wrap.innerHTML = `
      <div class="mensagens-editor">
        ${cat.statuses.map(status => {
          const campoId = idCampo(cat.key, status);
          const valor = templates[cat.key]?.[status] ?? '';
          const custom = isCustomizado(cat.key, status);
          const colapsado = isStatusColapsado(cat.key, status);
          return `
            <div class="mensagens-status-card ${colapsado ? 'collapsed' : ''}" data-categoria="${esc(cat.key)}" data-status="${esc(status)}">
              <div class="mensagens-status-head"
                onclick="toggleStatusColapsado('${esc(cat.key)}', '${esc(status)}', this.querySelector('.mensagens-status-collapse-btn'), event)">
                <div class="mensagens-status-head-left">
                  <button type="button" class="mensagens-status-collapse-btn"
                    title="${colapsado ? 'Expandir status' : 'Recolher status'}"
                    aria-label="${colapsado ? 'Expandir status' : 'Recolher status'}"
                    aria-expanded="${colapsado ? 'false' : 'true'}"
                    onclick="toggleStatusColapsado('${esc(cat.key)}', '${esc(status)}', this, event)">
                    <i class="ti ti-chevron-down"></i>
                  </button>
                  <span class="mensagens-status-title">${esc(status)}</span>
                </div>
                <div class="mensagens-status-head-actions">
                  <span class="mensagens-status-badge ${custom ? 'custom' : ''}">
                    ${custom ? 'Personalizada' : 'Padrão'}
                  </span>
                </div>
              </div>
              <div class="mensagens-status-content">
              <div class="mensagens-status-body">
                <textarea id="${campoId}" class="mensagens-textarea"
                  oninput="onTemplateInput('${esc(cat.key)}', '${esc(status)}')"
                  onfocus="definirCampoAtivo('${campoId}', '${esc(cat.label)} · ${esc(status)}')"
                  placeholder="Mensagem enviada ao mudar para ${esc(status)}...">${esc(valor)}</textarea>
                <div class="mensagens-status-actions">
                  <button type="button" class="mensagens-btn" data-emoji-trigger
                    onclick="abrirEmojiPicker('${campoId}', '${esc(cat.label)} · ${esc(status)}', this, event)">
                    <i class="ti ti-mood-smile"></i> Emojis
                  </button>
                  <button type="button" class="mensagens-btn" onclick="inserirPlaceholder('${campoId}', '{task_code}')">
                    <i class="ti ti-braces"></i> Inserir variável
                  </button>
                  <button type="button" class="mensagens-btn" onclick="previewMensagem('${esc(cat.key)}', '${esc(status)}')">
                    <i class="ti ti-eye"></i> Pré-visualizar
                  </button>
                  <button type="button" class="mensagens-btn" onclick="restaurarPadrao('${esc(cat.key)}', '${esc(status)}')">
                    <i class="ti ti-refresh"></i> Restaurar padrão
                  </button>
                </div>
                <div class="mensagens-preview" id="preview-${campoId}"></div>
              </div>
              <div class="mensagens-placeholders">
                <span class="mensagens-ph-label">Variáveis disponíveis</span>
                ${placeholders.map(ph => `
                  <button type="button" class="mensagens-ph-chip"
                    title="${esc(ph.label)}"
                    onclick="inserirPlaceholder('${campoId}', '{${esc(ph.key)}}')">
                    {${esc(ph.key)}}
                  </button>
                `).join('')}
              </div>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;

    registrarCamposAtivos();
    if (campoAtivoId && !document.getElementById(campoAtivoId)) {
      campoAtivoId = null;
      campoAtivoRotulo = '';
      fecharEmojiPicker();
    }
  }

  window.definirCampoAtivo = function (campoId, rotulo) {
    campoAtivoId = campoId;
    campoAtivoRotulo = rotulo || '';
    const titulo = document.getElementById('emoji-popover-title');
    if (titulo && emojiPickerAberto) {
      titulo.textContent = rotulo ? `Emojis · ${rotulo}` : 'Emojis';
    }
  };

  window.inserirEmoji = function (emoji) {
    const valor = String(emoji || '').trim();
    if (!valor) return;

    if (!campoAtivoId) {
      mostrarFeedback('Clique em um campo de mensagem antes de inserir um emoji.', 'err');
      return;
    }

    if (!inserirNoCampo(campoAtivoId, valor)) {
      mostrarFeedback('Não foi possível inserir o emoji no campo selecionado.', 'err');
    }
  };

  window.selecionarCategoria = function (key) {
    categoriaAtiva = key;
    renderCategorias();
    renderEditor();
  };

  window.onTemplateInput = function (categoria, status) {
    const campoId = idCampo(categoria, status);
    const valor = document.getElementById(campoId)?.value ?? '';
    if (!templates[categoria]) templates[categoria] = {};
    templates[categoria][status] = valor;

    const padrao = padroes[categoria]?.[status] ?? '';
    if (valor.trim() === padrao.trim()) {
      if (customizados[categoria]) delete customizados[categoria][status];
    } else {
      if (!customizados[categoria]) customizados[categoria] = {};
      customizados[categoria][status] = valor;
    }

    const badge = document.querySelector(
      `.mensagens-status-card[data-categoria="${categoria}"][data-status="${status}"] .mensagens-status-badge`
    );
    if (badge) {
      const custom = isCustomizado(categoria, status);
      badge.textContent = custom ? 'Personalizada' : 'Padrão';
      badge.classList.toggle('custom', custom);
    }

    marcarAlterado();
  };

  window.inserirPlaceholder = function (campoId, placeholder) {
    definirCampoAtivo(campoId, campoAtivoRotulo);
    inserirNoCampo(campoId, placeholder);
  };

  window.restaurarPadrao = function (categoria, status) {
    const padrao = padroes[categoria]?.[status] ?? '';
    const campoId = idCampo(categoria, status);
    const el = document.getElementById(campoId);
    if (el) {
      el.value = padrao;
      el.dispatchEvent(new Event('input'));
    }
    if (customizados[categoria]) {
      delete customizados[categoria][status];
    }
    const preview = document.getElementById('preview-' + campoId);
    if (preview) {
      preview.classList.remove('open');
      preview.textContent = '';
    }
  };

  window.previewMensagem = async function (categoria, status) {
    const campoId = idCampo(categoria, status);
    const preview = document.getElementById('preview-' + campoId);
    const template = document.getElementById(campoId)?.value ?? '';

    if (!preview) return;

    preview.classList.add('open');
    preview.textContent = 'Gerando pré-visualização...';

    try {
      const data = await requestJson('/api/mensagens-templates/preview', {
        method: 'POST',
        body: JSON.stringify({ categoria, status, template }),
      });
      preview.textContent = data.texto || '—';
    } catch (error) {
      preview.textContent = error.message || 'Falha na pré-visualização.';
    }
  };

  window.salvarMensagens = async function () {
    const btn = document.getElementById('btn-salvar-mensagens');
    btn.disabled = true;

    try {
      const data = await requestJson('/api/mensagens-templates', {
        method: 'PUT',
        body: JSON.stringify({ templates }),
      });
      templates = data.templates || templates;
      customizados = data.customizados || {};
      alterado = false;
      renderEditor();
      mostrarFeedback(data.message || 'Mensagens salvas com sucesso.');
    } catch (error) {
      mostrarFeedback(error.message || 'Não foi possível salvar.', 'err');
      btn.disabled = false;
    }
  };

  async function carregar() {
    try {
      const data = await requestJson('/api/mensagens-templates');
      catalogo = data.catalogo || [];
      placeholders = data.placeholders || [];
      templates = data.templates || {};
      customizados = data.customizados || {};

      padroes = {};
      catalogo.forEach(cat => {
        padroes[cat.key] = cat.padroes || {};
      });

      categoriaAtiva = catalogo[0]?.key || null;
      renderCategorias();
      renderEditor();
    } catch (error) {
      document.getElementById('mensagens-editor-wrap').innerHTML =
        `<div class="mensagens-loading" style="color:#b91c1c">${esc(error.message || 'Falha ao carregar.')}</div>`;
    }
  }

  window.addEventListener('beforeunload', (event) => {
    if (alterado) {
      event.preventDefault();
      event.returnValue = '';
    }
  });

  document.addEventListener('click', (event) => {
    if (!emojiPickerAberto) return;
    const popover = document.getElementById('emoji-popover');
    if (!popover) return;
    if (popover.contains(event.target) || event.target.closest('[data-emoji-trigger]')) return;
    fecharEmojiPicker();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') fecharEmojiPicker();
  });

  window.addEventListener('resize', () => {
    if (!emojiPickerAberto || !emojiTriggerBtn) return;
    posicionarEmojiPicker(emojiTriggerBtn);
  });

  carregar();
</script>
@endsection
