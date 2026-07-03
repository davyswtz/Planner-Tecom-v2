@extends('layouts.app')

@section('title', 'Mensagens — Planner Telecom')
@section('page-title', 'Mensagens')
@section('hide-topbar-btn', true)

@section('styles')
<style>
  .mensagens-page {
    width: 100%; max-width: 100%; display: flex; flex-direction: column; gap: 0;
    min-height: calc(100vh - 120px);
  }

  .msg-workspace {
    display: grid;
    grid-template-columns: 240px minmax(0, 1fr);
    gap: 0;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    background: var(--white);
    overflow: hidden;
    min-height: 520px;
  }

  .msg-sidebar {
    border-right: 1px solid var(--gray-200);
    background: var(--gray-50);
    display: flex;
    flex-direction: column;
    min-height: 0;
    width: 240px;
  }
  .msg-sidebar-head {
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--gray-200);
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--gray-500);
  }
  .mensagens-categorias {
    display: flex; flex-direction: column; gap: 0;
    padding: 8px 8px 12px; overflow-y: auto; flex: 1;
  }
  .msg-sidebar-grupo { margin-bottom: 10px; }
  .msg-sidebar-grupo:last-child { margin-bottom: 0; }
  .msg-sidebar-grupo-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--gray-400);
    padding: 6px 8px 4px; margin: 0;
  }
  .msg-sidebar-grupo-lista { display: flex; flex-direction: column; gap: 2px; }
  .mensagens-cat-btn {
    width: 100%; text-align: left; padding: 9px 12px;
    border: 1px solid transparent; border-radius: var(--radius-sm);
    background: transparent; color: var(--gray-700);
    font: inherit; font-size: 13px; cursor: pointer;
    display: flex; align-items: center; gap: 8px;
    transition: background .15s, border-color .15s;
  }
  .mensagens-cat-btn:hover { background: var(--white); }
  .mensagens-cat-btn.active {
    background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; font-weight: 600;
  }
  .mensagens-cat-btn.os-cat.active {
    background: #ecfdf5; border-color: #6ee7b7; color: #047857;
  }
  .mensagens-cat-btn i { font-size: 15px; opacity: .85; flex-shrink: 0; }

  .msg-context-banner {
    display: none; margin: 0 20px 12px; padding: 12px 14px;
    border-radius: var(--radius-sm); border: 1px solid #bfdbfe;
    background: #eff6ff; color: #1e40af; font-size: 12px; line-height: 1.5;
  }
  .msg-context-banner.os { display: flex; gap: 10px; align-items: flex-start;
    border-color: #6ee7b7; background: #ecfdf5; color: #065f46; }
  .msg-context-banner i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
  .msg-context-banner strong { display: block; font-size: 13px; margin-bottom: 2px; }

  .msg-status-meta { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
  .msg-webhook-tag {
    font-size: 10px; padding: 2px 7px; border-radius: 999px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .03em;
  }
  .msg-webhook-tag.on { background: #dcfce7; color: #166534; }
  .msg-webhook-tag.off { background: var(--gray-100); color: var(--gray-500); }

  .msg-vars-destaque {
    display: none; margin: 0 20px 12px; padding: 10px 12px;
    border: 1px dashed var(--gray-200); border-radius: var(--radius-sm);
    background: var(--gray-50);
  }
  .msg-vars-destaque.visible { display: block; }
  .msg-vars-destaque-titulo {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .04em; color: var(--gray-500); margin-bottom: 8px;
  }
  .msg-vars-chips { display: flex; flex-wrap: wrap; gap: 6px; }
  .msg-var-chip {
    font-size: 11px; padding: 4px 8px; border-radius: 999px;
    border: 1px solid var(--gray-200); background: var(--white);
    color: #1d4ed8; font-family: ui-monospace, monospace; cursor: pointer;
    transition: background .15s, border-color .15s;
  }
  .msg-var-chip:hover { background: #eff6ff; border-color: #bfdbfe; }

  .msg-panel { display: flex; flex-direction: column; min-width: 0; min-height: 0; }
  .msg-panel-head {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; padding: 16px 20px 12px;
    border-bottom: 1px solid var(--gray-100);
    flex-wrap: wrap;
  }
  .msg-panel-head-left { min-width: 0; flex: 1; }
  .msg-panel-title { font-size: 16px; font-weight: 700; color: var(--gray-900); margin: 0 0 2px; }
  .msg-panel-sub { font-size: 12px; color: var(--gray-500); margin: 0; }
  .msg-panel-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

  .msg-format-hints {
    display: flex; flex-wrap: wrap; gap: 6px;
    padding: 0 20px 12px; border-bottom: 1px solid var(--gray-100);
  }
  .msg-format-chip {
    font-size: 11px; padding: 3px 8px; border-radius: 999px;
    background: var(--gray-50); border: 1px solid var(--gray-200);
    color: var(--gray-600);
  }
  .msg-format-chip code { font-size: 10px; }

  .mensagens-categorias-mobile {
    display: none; overflow-x: auto; padding: 10px 12px;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .mensagens-categorias-mobile::-webkit-scrollbar { display: none; }
  #mensagens-categorias-mobile-inner {
    display: flex; gap: 6px; min-width: min-content;
  }
  .msg-cat-pill {
    flex-shrink: 0; padding: 6px 14px; border-radius: 999px;
    border: 1px solid var(--gray-200); background: var(--white);
    font: inherit; font-size: 12px; color: var(--gray-700); cursor: pointer;
    white-space: nowrap; transition: all .15s;
  }
  .msg-cat-pill.active {
    background: #1d4ed8; border-color: #1d4ed8; color: #fff; font-weight: 600;
  }
  .msg-cat-pill.os-pill.active {
    background: #047857; border-color: #047857;
  }
  .msg-cat-pill.msg-cat-sep {
    background: transparent; border: none; color: var(--gray-400);
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; padding: 6px 8px 2px; cursor: default;
    pointer-events: none;
  }

  #mensagens-editor-wrap {
    padding: 14px 20px 20px; overflow-y: auto; flex: 1;
  }

  .mensagens-editor { display: flex; flex-direction: column; gap: 12px; }
  .mensagens-status-card {
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm); overflow: hidden;
  }
  .mensagens-status-head {
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 10px 14px; background: var(--gray-50);
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer; user-select: none;
  }
  .mensagens-status-card.collapsed .mensagens-status-head { border-bottom-color: transparent; }
  .mensagens-status-card.collapsed .mensagens-status-content { display: none; }
  .mensagens-status-head-left {
    display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;
  }
  .mensagens-status-collapse-btn {
    width: 28px; height: 28px; border: none; border-radius: var(--radius-sm);
    background: transparent; color: var(--gray-500); cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .mensagens-status-collapse-btn:hover { background: var(--gray-200); color: var(--gray-800); }
  .mensagens-status-card.collapsed .mensagens-status-collapse-btn i { transform: rotate(-90deg); }
  .mensagens-status-title { font-size: 13px; font-weight: 600; color: var(--gray-800); }
  .mensagens-status-badge {
    font-size: 11px; padding: 2px 8px; border-radius: 999px;
    background: var(--gray-100); color: var(--gray-600); flex-shrink: 0;
  }
  .mensagens-status-badge.custom { background: #fef3c7; color: #92400e; }

  .mensagens-status-body { padding: 0; }
  .msg-toolbar {
    display: flex; align-items: center; gap: 2px; flex-wrap: wrap;
    padding: 8px 10px; background: var(--gray-50);
    border-bottom: 1px solid var(--gray-100);
  }
  .msg-tool-btn {
    width: 32px; height: 32px; border: none; border-radius: var(--radius-sm);
    background: transparent; color: var(--gray-600); cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 16px;
  }
  .msg-tool-btn:hover { background: var(--gray-200); color: var(--gray-900); }
  .msg-tool-sep { width: 1px; height: 20px; background: var(--gray-200); margin: 0 4px; }
  .msg-tool-spacer { flex: 1; min-width: 8px; }
  .msg-tool-hint { font-size: 10px; color: var(--gray-400); white-space: nowrap; }

  .mensagens-textarea {
    width: 100%; min-height: 140px; padding: 12px 14px;
    border: none; resize: vertical; font: inherit; font-size: 13px;
    line-height: 1.55; color: var(--gray-800); background: var(--white);
    box-sizing: border-box;
  }
  .mensagens-textarea:focus { outline: none; }

  .mensagens-status-foot {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    padding: 8px 12px; border-top: 1px solid var(--gray-100);
    flex-wrap: wrap;
  }
  .mensagens-char-count { font-size: 11px; color: var(--gray-400); }
  .mensagens-char-count.warn { color: #b45309; }
  .mensagens-status-actions { display: flex; gap: 6px; flex-wrap: wrap; }

  .mensagens-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 12px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); background: var(--white);
    font: inherit; font-size: 12px; color: var(--gray-700); cursor: pointer;
    white-space: nowrap;
  }
  .mensagens-btn:hover { background: var(--gray-50); }
  .mensagens-btn.primary {
    background: #1d4ed8; border-color: #1d4ed8; color: #fff; font-weight: 600;
  }
  .mensagens-btn.primary:hover { background: #1e40af; }
  .mensagens-btn.primary:disabled { opacity: .5; cursor: not-allowed; }

  .mensagens-preview-wrap { display: none; padding: 0 12px 12px; }
  .mensagens-preview-wrap.open { display: block; }
  .mensagens-preview-label { font-size: 11px; color: var(--gray-500); margin-bottom: 6px; }
  .mensagens-preview {
    padding: 12px 14px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); background: var(--gray-50);
    font-size: 13px; line-height: 1.55; color: var(--gray-800);
    white-space: pre-wrap; word-break: break-word;
  }
  .mensagens-preview strong { font-weight: 700; }
  .mensagens-preview em { font-style: italic; }
  .mensagens-preview s { text-decoration: line-through; }
  .mensagens-preview code {
    font-family: ui-monospace, monospace; font-size: 12px;
    background: var(--gray-200); padding: 1px 4px; border-radius: 3px;
  }
  .mensagens-preview-loading { color: var(--gray-500); font-style: italic; }

  .mensagens-feedback {
    display: none; padding: 8px 20px; font-size: 13px;
    border-bottom: 1px solid transparent;
  }
  .mensagens-feedback.ok { display: block; background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
  .mensagens-feedback.err { display: block; background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

  .mensagens-loading {
    display: flex; align-items: center; gap: 8px; padding: 24px;
    color: var(--gray-500); font-size: 13px;
  }
  .mensagens-loading i { animation: spin 1s linear infinite; }

  .msg-save-mobile {
    display: none; position: fixed; bottom: 0; left: 0; right: 0;
    padding: 10px 16px; background: var(--white);
    border-top: 1px solid var(--gray-200);
    box-shadow: 0 -4px 12px rgba(0,0,0,.08);
    z-index: 100; transform: translateY(100%);
    transition: transform .2s;
  }
  .msg-save-mobile.is-visible { transform: translateY(0); }
  .msg-save-mobile .mensagens-btn { width: 100%; justify-content: center; padding: 12px; }

  /* Popovers */
  .emoji-popover, .variaveis-popover {
    display: none; position: fixed; z-index: 2000;
    width: min(320px, calc(100vw - 24px));
    background: var(--white); border: 1px solid var(--gray-200);
    border-radius: var(--radius); box-shadow: 0 8px 24px rgba(0,0,0,.12);
  }
  .emoji-popover.open, .variaveis-popover.open { display: flex; flex-direction: column; }
  .emoji-popover-head, .variaveis-popover-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 12px; border-bottom: 1px solid var(--gray-100);
  }
  .emoji-popover-title, .variaveis-popover-title { font-size: 13px; font-weight: 600; }
  .emoji-popover-close {
    width: 28px; height: 28px; border: none; border-radius: var(--radius-sm);
    background: transparent; cursor: pointer; color: var(--gray-500);
    display: inline-flex; align-items: center; justify-content: center;
  }
  .emoji-popover-tabs {
    display: flex; gap: 2px; padding: 6px 8px; border-bottom: 1px solid var(--gray-100);
    overflow-x: auto;
  }
  .emoji-popover-tab {
    flex-shrink: 0; width: 36px; height: 32px; border: none;
    border-radius: var(--radius-sm); background: transparent;
    cursor: pointer; font-size: 18px;
  }
  .emoji-popover-tab.active { background: var(--gray-100); }
  .emoji-popover-body { padding: 8px; max-height: 220px; overflow-y: auto; }
  .emoji-popover-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; }
  .emoji-popover-btn {
    width: 100%; aspect-ratio: 1; border: none; border-radius: var(--radius-sm);
    background: transparent; cursor: pointer; font-size: 20px;
  }
  .emoji-popover-btn:hover { background: var(--gray-100); }

  .variaveis-popover-search-wrap { padding: 8px 10px; border-bottom: 1px solid var(--gray-100); }
  .variaveis-popover-search {
    width: 100%; padding: 8px 10px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); font: inherit; font-size: 13px;
    box-sizing: border-box;
  }
  #variaveis-popover-body { padding: 8px; max-height: 280px; overflow-y: auto; }
  .msg-var-grupo-titulo {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: var(--gray-400); padding: 6px 4px 4px;
  }
  .msg-var-grupo-lista { display: flex; flex-direction: column; gap: 2px; margin-bottom: 6px; }
  .msg-var-item {
    display: flex; flex-direction: column; align-items: flex-start; gap: 1px;
    width: 100%; padding: 7px 10px; border: none; border-radius: var(--radius-sm);
    background: transparent; cursor: pointer; text-align: left;
  }
  .msg-var-item:hover { background: var(--gray-50); }
  .msg-var-item-key { font-size: 12px; font-family: ui-monospace, monospace; color: #1d4ed8; font-weight: 600; }
  .msg-var-item-label { font-size: 11px; color: var(--gray-500); }
  .msg-var-vazio { padding: 16px; text-align: center; color: var(--gray-400); font-size: 13px; }

  @media (max-width: 1024px) {
    .msg-workspace { grid-template-columns: 200px minmax(0, 1fr); }
    .msg-sidebar { width: auto; }
    .msg-tool-hint { display: none; }
  }

  @media (max-width: 860px) {
    .msg-workspace { grid-template-columns: 1fr; }
    .msg-sidebar { display: none; }
    .mensagens-categorias-mobile { display: block; }
    .msg-panel-head { padding: 12px 14px 10px; }
    .msg-format-hints { padding: 0 14px 10px; }
    #mensagens-editor-wrap { padding: 12px 14px 80px; }
    .msg-save-mobile { display: block; }
    .msg-panel-actions .mensagens-btn.primary { display: none; }
  }

  @media (max-width: 480px) {
    .mensagens-status-foot { flex-direction: column; align-items: stretch; }
    .mensagens-status-actions { justify-content: stretch; }
    .mensagens-status-actions .mensagens-btn { flex: 1; justify-content: center; }
    .msg-toolbar { gap: 0; }
  }

  /* Dark mode */
  [data-theme="dark"] .msg-workspace { background: #050608; border-color: #1e2228; }
  [data-theme="dark"] .msg-sidebar,
  [data-theme="dark"] .mensagens-categorias-mobile { background: #0a0c10; border-color: #1e2228; }
  [data-theme="dark"] .msg-sidebar-head,
  [data-theme="dark"] .msg-panel-head,
  [data-theme="dark"] .msg-format-hints { border-color: #1e2228; }
  [data-theme="dark"] .mensagens-cat-btn { color: #c9d1d9; }
  [data-theme="dark"] .mensagens-cat-btn:hover { background: #12151a; }
  [data-theme="dark"] .mensagens-cat-btn.active { background: #1c2d4a; border-color: #1f6feb; color: #58a6ff; }
  [data-theme="dark"] .mensagens-cat-btn.os-cat.active { background: #0f2419; border-color: #238636; color: #3fb950; }
  [data-theme="dark"] .msg-context-banner.os { background: #0f2419; border-color: #238636; color: #3fb950; }
  [data-theme="dark"] .msg-vars-destaque { background: #0a0c10; border-color: #1e2228; }
  [data-theme="dark"] .msg-var-chip { background: #12151a; border-color: #1e2228; color: #58a6ff; }
  [data-theme="dark"] .msg-webhook-tag.on { background: #0f2419; color: #3fb950; }
  [data-theme="dark"] .msg-cat-pill { background: #12151a; border-color: #1e2228; color: #c9d1d9; }
  [data-theme="dark"] .msg-cat-pill.active { background: #1f6feb; border-color: #1f6feb; }
  [data-theme="dark"] .msg-panel-title { color: #e6edf3; }
  [data-theme="dark"] .msg-format-chip { background: #12151a; border-color: #1e2228; color: #8b949e; }
  [data-theme="dark"] .mensagens-status-card { border-color: #1e2228; }
  [data-theme="dark"] .mensagens-status-head,
  [data-theme="dark"] .msg-toolbar { background: #0a0c10; border-color: #1e2228; }
  [data-theme="dark"] .mensagens-textarea { background: #050608; color: #e6edf3; }
  [data-theme="dark"] .mensagens-btn,
  [data-theme="dark"] .variaveis-popover-search { background: #12151a; border-color: #1e2228; color: #c9d1d9; }
  [data-theme="dark"] .mensagens-preview { background: #0a0c10; border-color: #1e2228; color: #c9d1d9; }
  [data-theme="dark"] .emoji-popover,
  [data-theme="dark"] .variaveis-popover { background: #0a0c10; border-color: #1e2228; }
  [data-theme="dark"] .msg-save-mobile { background: #050608; border-color: #1e2228; }
  [data-theme="dark"] .mensagens-feedback.ok { background: #0f2419; color: #3fb950; }
  [data-theme="dark"] .mensagens-feedback.err { background: #2d1117; color: #ff7b72; }

  @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endsection

@section('content')
<div class="mensagens-page">
  <div class="msg-workspace">
    <aside class="msg-sidebar">
      <div class="msg-sidebar-head">Templates</div>
      <div class="mensagens-categorias" id="mensagens-categorias">
        <div class="mensagens-loading"><i class="ti ti-loader-2"></i> Carregando...</div>
      </div>
    </aside>

    <div class="msg-panel">
      <div class="mensagens-categorias-mobile">
        <div id="mensagens-categorias-mobile-inner"></div>
      </div>

      <div class="mensagens-feedback" id="mensagens-feedback"></div>

      <div class="msg-panel-head">
        <div class="msg-panel-head-left">
          <h2 class="msg-panel-title" id="mensagens-editor-titulo">Mensagens</h2>
          <p class="msg-panel-sub" id="mensagens-editor-sub">Templates enviados ao Google Chat via webhook</p>
        </div>
        <div class="msg-panel-actions">
          <button type="button" class="mensagens-btn primary" id="btn-salvar-mensagens" onclick="salvarMensagens()" disabled>
            <i class="ti ti-device-floppy"></i> Salvar alterações
          </button>
        </div>
      </div>

      <div class="msg-format-hints">
        <span class="msg-format-chip"><code>*texto*</code> negrito</span>
        <span class="msg-format-chip"><code>_texto_</code> itálico</span>
        <span class="msg-format-chip"><code>~texto~</code> tachado</span>
        <span class="msg-format-chip"><code>`código`</code> mono</span>
        <span class="msg-format-chip"><code>{task_code}</code> variáveis</span>
      </div>

      <div class="msg-context-banner" id="msg-context-banner"></div>
      <div class="msg-vars-destaque" id="msg-vars-destaque"></div>

      <div id="mensagens-editor-wrap">
        <div class="mensagens-loading"><i class="ti ti-loader-2"></i> Carregando templates...</div>
      </div>
    </div>
  </div>
</div>

<div class="msg-save-mobile" id="msg-save-mobile">
  <button type="button" class="mensagens-btn primary" onclick="salvarMensagens()">
    <i class="ti ti-device-floppy"></i> Salvar alterações
  </button>
</div>

<div id="emoji-popover" class="emoji-popover" role="dialog" aria-label="Seletor de emojis">
  <div class="emoji-popover-head">
    <span class="emoji-popover-title">Emojis</span>
    <button type="button" class="emoji-popover-close" onclick="document.getElementById('emoji-popover').classList.remove('open')" aria-label="Fechar">
      <i class="ti ti-x"></i>
    </button>
  </div>
  <div class="emoji-popover-tabs" id="emoji-popover-tabs"></div>
  <div class="emoji-popover-body">
    <div class="emoji-popover-grid" id="emoji-popover-grid"></div>
  </div>
</div>

<div id="variaveis-popover" class="variaveis-popover" role="dialog" aria-label="Inserir variável">
  <div class="variaveis-popover-head">
    <span class="variaveis-popover-title">Variáveis</span>
    <button type="button" class="emoji-popover-close" onclick="document.getElementById('variaveis-popover').classList.remove('open')" aria-label="Fechar">
      <i class="ti ti-x"></i>
    </button>
  </div>
  <div class="variaveis-popover-search-wrap">
    <input type="search" id="variaveis-popover-search" class="variaveis-popover-search" placeholder="Buscar variável..." autocomplete="off">
  </div>
  <div id="variaveis-popover-body"></div>
</div>
@endsection

@section('scripts')
<script type="module">
  import { initMensagensPage } from '/js/planner-mensagens-editor.js?v={{ filemtime(public_path('js/planner-mensagens-editor.js')) }}';
  initMensagensPage();
</script>
@endsection
