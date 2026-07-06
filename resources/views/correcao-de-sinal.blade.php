@extends('layouts.app')

@section('title', 'Correção de sinal — Planner Telecom')
@section('page-title', 'Correção de sinal')
@section('btn-label', 'Nova correção de sinal')

@section('styles')
<style>
  .kcard[draggable="true"] {
    cursor: grab;
    user-select: none;
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                box-shadow 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                border-color 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: transform;
  }
  .kcard[draggable="true"]:active { cursor: grabbing; }
  @media (pointer: coarse) { .kcard { cursor: pointer; } }
  .kcard-dragging {
    opacity: 0.55;
    cursor: grabbing;
    transform: scale(1.02) rotate(1deg);
    box-shadow: 0 10px 28px rgba(22,106,196,0.16);
    transition: none;
  }
  .kcol-body {
    transition: background 0.28s cubic-bezier(0.16, 1, 0.3, 1),
                outline-color 0.28s cubic-bezier(0.16, 1, 0.3, 1),
                outline-offset 0.28s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .kcol-body.drag-over {
    background: var(--blue-50);
    outline: 2px dashed var(--blue-600);
    outline-offset: -4px;
    border-radius: var(--radius-sm);
  }
  .kcol-body.drag-bloqueado {
    outline: 2px dashed #ef4444;
    outline-offset: -4px;
    background: #fef2f2;
  }
  .modal-overlay {
    position: fixed; inset: 0; z-index: 100;
    display: flex; align-items: center; justify-content: center; padding: 16px;
    background: rgba(0,0,0,0); visibility: hidden; pointer-events: none;
    transition: background 0.32s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.32s;
  }
  .modal-overlay.open { visibility: visible; pointer-events: auto; background: rgba(0,0,0,0.45); }
  .modal-box {
    background: var(--white); border-radius: var(--radius); border: 1px solid var(--gray-200);
    width: 100%; max-width: 680px; overflow: hidden; max-height: calc(100vh - 32px);
    display: flex; flex-direction: column;
    opacity: 0; transform: scale(0.96) translateY(14px);
    transition: transform 0.38s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.38s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: transform, opacity;
  }
  .modal-overlay.open .modal-box { opacity: 1; transform: scale(1) translateY(0); }
  .modal-head { padding: 16px 24px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
  .modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
  .modal-foot { padding: 14px 24px; border-top: 1px solid var(--gray-200); display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0; }
  .modal-title { font-size: 15px; font-weight: 600; color: var(--gray-950); margin: 0; }
  .modal-sub { font-size: 12px; color: var(--gray-500); margin: 0; }
  .modal-close { background: transparent; border: none; cursor: pointer; color: var(--gray-500); font-size: 18px; display: flex; align-items: center; padding: 4px; transition: color 0.15s, transform 0.15s; }
  .modal-close:hover { color: var(--gray-950); transform: scale(1.08); }
  .detail-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
  .detail-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .detail-field { display: flex; flex-direction: column; gap: 5px; }
  .detail-field.span-2 { grid-column: span 2; }
  .detail-field.span-3 { grid-column: span 3; }
  .detail-label { font-size: 12px; font-weight: 500; color: var(--gray-500); }
  .detail-value { border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 8px 10px; min-height: 38px; font-size: 13px; color: var(--gray-950); background: var(--gray-50); line-height: 1.4; word-break: break-word; }
  .detail-badges { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; min-height: 38px; }
  .detail-loading { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: var(--gray-500); font-size: 13px; }
  .detail-loading i { animation: spin 0.9s cubic-bezier(0.4, 0, 0.2, 1) infinite; }
  .detail-error { padding: 16px; border-radius: var(--radius-sm); background: var(--red-bg); color: var(--red-text); font-size: 13px; }
  .kcard-signals { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
  .sinal-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 8px; border-radius: 999px; border: 1px solid transparent;
    font-size: 11px; font-weight: 600; line-height: 1;
  }
  .sinal-chip-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.03em; opacity: 0.8; }
  .sinal-chip--ideal { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
  .sinal-chip--preventiva { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
  .sinal-chip--alerta { background: #fefce8; color: #a16207; border-color: #fef08a; }
  .sinal-chip--critico { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
  .sinal-chip--sem-dados { background: var(--gray-100); color: var(--gray-500); border-color: var(--gray-200); }
  .detail-value--sinal { font-weight: 600; }
  .detail-value--sinal.sinal-texto--ideal { color: #15803d; }
  .detail-value--sinal.sinal-texto--preventiva { color: #16a34a; }
  .detail-value--sinal.sinal-texto--alerta { color: #a16207; }
  .detail-value--sinal.sinal-texto--critico { color: #dc2626; }
  .detail-enter { animation: conteudoEntrada 0.42s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
  .btn-modal { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 0 16px; height: 36px; border-radius: var(--radius-sm); font-size: 13px; line-height: 1; white-space: nowrap; cursor: pointer; font-family: inherit; box-sizing: border-box; transition: background 0.15s, transform 0.15s, border-color 0.15s; }
  .btn-modal:active { transform: scale(0.97); }
  .btn-modal-ghost { border: 1px solid var(--gray-200); background: transparent; color: var(--gray-500); }
  .btn-modal-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }
  .btn-modal-primary { border: none; background: #166ac4; color: #fff; font-weight: 500; }
  .btn-modal-primary:hover { background: #0d5aaa; }
  .btn-modal-danger { border: 1px solid #fecaca; background: #fff; color: #dc2626; font-weight: 500; }
  .btn-modal-danger:hover { background: #fef2f2; border-color: #f87171; }
  .modal-foot-inner { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .modal-foot-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }
  .confirm-excluir-overlay {
    position: fixed; inset: 0; z-index: 110;
    display: flex; align-items: center; justify-content: center; padding: 16px;
    background: rgba(0,0,0,0); visibility: hidden; pointer-events: none;
    transition: background 0.25s ease, visibility 0.25s;
  }
  .confirm-excluir-overlay.open { visibility: visible; pointer-events: auto; background: rgba(0,0,0,0.5); }
  .confirm-excluir-box {
    background: var(--white); border-radius: var(--radius); border: 1px solid var(--gray-200);
    width: 100%; max-width: 400px; padding: 20px 24px;
    opacity: 0; transform: scale(0.96) translateY(8px);
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.28s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .confirm-excluir-overlay.open .confirm-excluir-box { opacity: 1; transform: scale(1) translateY(0); }
  .confirm-excluir-icon {
    width: 40px; height: 40px; border-radius: 50%; background: #fef2f2; color: #dc2626;
    display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px;
  }
  .confirm-excluir-title { font-size: 15px; font-weight: 600; color: var(--gray-950); margin: 0 0 6px; }
  .confirm-excluir-text { font-size: 13px; color: var(--gray-500); margin: 0 0 18px; line-height: 1.5; }
  .confirm-excluir-foot { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
  .modal-tabs { display: flex; border-bottom: 1px solid var(--gray-200); padding: 0 24px; flex-shrink: 0; gap: 0; }
  .modal-tab { padding: 10px 16px; font-size: 13px; font-weight: 500; color: var(--gray-500); border: none; background: transparent; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; transition: color 0.15s, border-color 0.15s; }
  .modal-tab i { font-size: 14px; }
  .modal-tab:hover { color: var(--gray-700); }
  .modal-tab.active { color: #166ac4; border-bottom-color: #166ac4; }
  .os-tab-head { display: flex; align-items: center; justify-content: space-between; padding-bottom: 12px; margin-bottom: 14px; border-bottom: 1px solid var(--gray-200); }
  .os-tab-title { font-size: 13px; font-weight: 600; color: var(--gray-700); display: inline-flex; align-items: center; gap: 7px; }
  .os-count-pill { font-size: 11px; font-weight: 600; background: var(--gray-100); color: var(--gray-500); border-radius: 20px; padding: 1px 8px; }
  .os-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: var(--gray-400); }
  .os-empty i { font-size: 32px; color: var(--gray-300); }
  .os-empty span { font-size: 12px; }
  .btn-nova-os { display: inline-flex; align-items: center; gap: 5px; height: 30px; padding: 0 12px; border-radius: var(--radius-sm); border: none; background: #166ac4; color: #fff; font-size: 12px; font-weight: 500; cursor: pointer; font-family: inherit; transition: background 0.15s, transform 0.15s; }
  .btn-nova-os:hover { background: #0d5aaa; }
  .btn-nova-os:active { transform: scale(0.97); }
  .os-card { border: 1px solid var(--gray-200); border-left: 3px solid var(--gray-300); border-radius: var(--radius-sm); padding: 10px 12px 10px 13px; margin-bottom: 7px; background: var(--white); transition: box-shadow 0.15s; }
  .os-card:last-child { margin-bottom: 0; }
  .os-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
  .os-card-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 7px; }
  .os-card-title { font-size: 13px; font-weight: 600; color: var(--gray-950); line-height: 1.45; }
  .os-card-meta { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
  .os-card-av { width: 20px; height: 20px; border-radius: 50%; background: var(--blue-50); color: var(--blue-800); font-size: 9px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .os-card-tecnico { font-size: 12px; color: var(--gray-500); }
  .os-card-code { font-size: 11px; color: var(--gray-400); font-family: 'Courier New', monospace; margin-left: auto; }
  .btn-edit-os { background: transparent; border: none; cursor: pointer; color: var(--gray-300); padding: 3px 4px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; flex-shrink: 0; line-height: 1; transition: color 0.15s, background 0.15s; }
  .btn-edit-os:hover { color: #166ac4; background: #eff6ff; }
  /* Ações do card de OS: editar e excluir ficam agrupadas à direita do título */
  .os-card-actions { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
  .btn-delete-os { background: transparent; border: none; cursor: pointer; color: var(--gray-300); padding: 3px 4px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; flex-shrink: 0; line-height: 1; transition: color 0.15s, background 0.15s; }
  .btn-delete-os:hover { color: #dc2626; background: #fef2f2; }
  .os-status-wrap { display: inline-flex; align-items: center; }
  .os-status-badge { cursor: pointer; border: none; background: transparent; padding: 0; display: inline-flex; align-items: center; gap: 3px; }
  .os-status-badge .badge { pointer-events: none; transition: filter 0.15s; }
  .os-status-badge:hover .badge { filter: brightness(0.88); }
  .os-status-badge .drop-arrow { pointer-events: none; font-size: 8px; opacity: 0.55; }
  .os-status-pills { display: none; align-items: center; gap: 4px; }
  .os-status-pills.open { display: inline-flex; }
  .os-status-pill { border: none; cursor: pointer; font-size: 10px; font-weight: 600; padding: 2px 9px; border-radius: 20px; white-space: nowrap; opacity: 0.38; transition: opacity 0.15s, transform 0.12s; }
  .os-status-pill:hover { opacity: 0.72; }
  .os-status-pill.active { opacity: 1; box-shadow: 0 0 0 2px currentColor; }
  .os-status-pill-aberta     { background: #dbeafe; color: #1d4ed8; }
  .os-status-pill-andamento  { background: #fef3c7; color: #92400e; }
  .os-status-pill-finalizada { background: #dcfce7; color: #166534; }
  .os-status-close { border: none; background: transparent; cursor: pointer; color: var(--gray-400); font-size: 13px; padding: 2px 3px; line-height: 1; margin-left: 2px; border-radius: 4px; }
  .os-status-close:hover { color: var(--gray-700); background: var(--gray-100, #f3f4f6); }
  .os-field { display: flex; flex-direction: column; gap: 5px; }
  .os-label { font-size: 12px; font-weight: 500; color: var(--gray-500); }
  .os-input { width: 100%; height: 38px; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 0 10px; font-size: 13px; font-family: inherit; outline: none; background: var(--white); color: var(--gray-950); transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
  .os-input:focus { border-color: #166ac4; box-shadow: 0 0 0 3px rgba(22,106,196,0.12); }
  @keyframes conteudoEntrada { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

  /* ── FILTROS ── */
  .filtros-card { margin-bottom: 12px; }
  .filtros-bar { padding: 10px 16px; display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; }
  .filtro-search { display: flex; align-items: center; gap: 6px; flex: 1; min-width: 130px; }
  .filtro-search-icon { color: var(--gray-400); font-size: 14px; flex-shrink: 0; }
  .filtro-search-input { text-transform: uppercase; border: none; outline: none; font-size: 13px; font-family: inherit; background: transparent; width: 100%; color: var(--gray-950); }
  .filtro-divider { width: 1px; height: 20px; background: var(--gray-200); flex-shrink: 0; }
  .filtro-select { border: none; outline: none; font-size: 13px; font-family: inherit; background: transparent; color: var(--gray-700); cursor: pointer; flex-shrink: 0; max-width: 160px; }
  .filtro-datas { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
  .filtro-data-label { font-size: 12px; color: var(--gray-500); white-space: nowrap; }
  .filtro-date { border: none; outline: none; font-size: 13px; font-family: inherit; background: transparent; color: var(--gray-700); cursor: pointer; max-width: 140px; }
  .filtro-limpar-btn { border: none; background: transparent; color: var(--gray-400); font-size: 12px; cursor: pointer; font-family: inherit; display: flex; align-items: center; gap: 4px; flex-shrink: 0; white-space: nowrap; }
  .filtro-limpar-btn:hover { color: var(--gray-700); }

  @media (max-width: 900px) { .filtros-bar { flex-wrap: wrap; overflow-x: visible; } .filtro-select { max-width: none; } .filtro-date { max-width: none; } }
  @media (max-width: 600px) {
    .filtros-bar { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; padding: 12px; }
    .filtro-search { grid-column: 1 / -1; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 8px 10px; min-width: unset; }
    .filtro-divider { display: none; }
    .filtro-select { border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 8px 10px; width: 100%; max-width: none; height: 38px; background: var(--white); box-sizing: border-box; }
    .filtro-datas { grid-column: 1 / -1; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 8px 10px; width: 100%; box-sizing: border-box; }
    .filtro-date { flex: 1; min-width: 0; }
    .filtro-limpar-btn { grid-column: 1 / -1; justify-content: flex-end; padding-top: 2px; }
  }
  @media (max-width: 768px) { .detail-grid, .detail-grid-2 { grid-template-columns: 1fr; } .detail-field.span-2, .detail-field.span-3 { grid-column: span 1; } }

  .btn-kcol-toggle { background: transparent; border: none; cursor: pointer; color: var(--gray-400); font-size: 12px; padding: 2px 4px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; line-height: 1; transition: color 0.15s, background 0.15s; flex-shrink: 0; }
  .btn-kcol-toggle:hover { color: var(--gray-700); background: var(--gray-100); }
  .btn-kcol-toggle i { transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); display: block; }
  .kcol.collapsed .btn-kcol-toggle i { transform: rotate(-90deg); }
  .kcol-content-wrap { flex: 1; overflow: hidden; display: flex; flex-direction: column; max-height: 3000px; opacity: 1; transition: max-height 0.36s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.26s cubic-bezier(0.16, 1, 0.3, 1); }
  .kcol.collapsed .kcol-content-wrap { max-height: 0; opacity: 0; pointer-events: none; }

  /* ── MODAL FORM ── */
  .modal-form { display: flex; flex-direction: column; gap: 14px; }
  .input-hint { font-size: 11px; color: var(--gray-400); margin: 2px 0 0; }
  @media (max-width: 600px) { .modal-body { padding: 14px 14px !important; } .modal-head { padding: 14px 16px !important; } .modal-foot { padding: 12px 14px !important; } }
  @media (prefers-reduced-motion: reduce) {
    .kcard[draggable="true"], .kcol-body, .modal-overlay, .modal-box, .modal-close, .btn-modal { transition: none; }
    .detail-enter, .detail-loading i { animation: none; }
    .modal-overlay.open .modal-box { opacity: 1; transform: none; }
    .btn-kcol-toggle i, .kcol-content-wrap { transition: none; }
  }
</style>
@endsection

@section('content')

<!-- MODAL CRIAR -->
<x-modal id="modal-overlay" titulo="Nova correção de sinal" subtitulo="Preencha os dados da correção de sinal">

  <div class="modal-form">

    <div class="detail-grid-2">
      <div class="os-field">
        <label class="os-label">Coordenadas (opcional)</label>
        <input type="text" id="input-coordenadas" placeholder="Ex: -18.8517, -41.9494" class="os-input"/>
      </div>
      <div class="os-field">
        <label class="os-label">Endereço / Localização</label>
        <input type="text" id="input-localizacao-texto" placeholder="Ex: Rua das Flores, 123 — Goval" class="os-input"/>
      </div>
    </div>

    <div class="os-field">
      <label class="os-label">Região</label>
      <select id="input-regiao" class="os-input">
        <option value="">Selecione...</option>
        <option>Goval</option>
        <option>Vale do Aço</option>
        <option>Caratinga</option>
        <option>Teste</option>
      </select>
    </div>

    <div class="os-field">
      <label class="os-label">Número da OS (Hubsoft)</label>
      <input type="text" id="input-numero-os" inputmode="numeric" placeholder="Ex: 123456" class="os-input"/>
    </div>

    <div class="os-field">
      <label class="os-label">Prioridade</label>
      <div class="prioridade-wrap" role="group" aria-label="Prioridade">
        <button onclick="selecionarPrioridade(this,'Baixa')" class="btn-prioridade btn-prio-baixa" data-nivel="Baixa" aria-pressed="false"><span class="prio-label">Baixa</span></button>
        <button onclick="selecionarPrioridade(this,'Média')" class="btn-prioridade btn-prio-media btn-prio-ativo" data-nivel="Média" aria-pressed="true"><span class="prio-label">Média</span></button>
        <button onclick="selecionarPrioridade(this,'Alta')" class="btn-prioridade btn-prio-alta" data-nivel="Alta" aria-pressed="false"><span class="prio-label">Alta</span></button>
      </div>
    </div>

  </div>

  <x-slot name="footer">
    <button onclick="fecharModal()" class="btn-modal btn-modal-ghost">Cancelar</button>
    <button onclick="criarCorrecaoPoste()" class="btn-modal btn-modal-primary">
      <i class="ti ti-tools" style="font-size:14px"></i> Criar correção de sinal
    </button>
  </x-slot>

</x-modal>

<!-- MODAL DETALHE -->
<x-modal
  id="detalhe-overlay"
  titulo-id="detalhe-titulo"
  subtitulo-id="detalhe-subtitulo"
  fechar="fecharDetalhe()">

  <x-slot name="tabs">
    <div class="modal-tabs">
      <button class="modal-tab active" id="tab-btn-detalhes" onclick="trocarAba('detalhes')">
        <i class="ti ti-info-circle"></i> Detalhes
      </button>
      <button class="modal-tab" id="tab-btn-os" onclick="trocarAba('os')">
        <i class="ti ti-clipboard-list"></i> Ordens de Serviço
      </button>
    </div>
  </x-slot>

  <div id="detalhe-tab-detalhes">
    <div id="detalhe-conteudo"></div>
  </div>

  <div id="detalhe-tab-os" style="display:none">
    <div class="os-tab-head">
      <span class="os-tab-title">Ordens de Serviço vinculadas</span>
      <button type="button" class="btn-nova-os" onclick="abrirNovaOS()">
        <i class="ti ti-plus" style="font-size:13px"></i> Nova OS
      </button>
    </div>
    <div class="os-empty">
      <i class="ti ti-clipboard-off"></i>
      <span>Nenhuma OS vinculada a esta correção de sinal</span>
    </div>
  </div>

  <x-slot name="footer">
    <div class="modal-foot-inner">
      <button type="button" onclick="abrirConfirmacaoExclusao()" id="btn-excluir" class="btn-modal btn-modal-danger" title="Excluir esta correção de sinal">
        <i class="ti ti-trash" style="font-size:14px"></i> Excluir
      </button>
      <div class="modal-foot-actions">
        <button type="button" onclick="fecharDetalhe()" class="btn-modal btn-modal-ghost">Fechar</button>
        <button type="button" onclick="ativarEdicao()" id="btn-editar" class="btn-modal btn-modal-primary">
          <i class="ti ti-pencil"></i> Editar
        </button>
        <button type="button" onclick="salvarEdicao()" id="btn-salvar" class="btn-modal btn-modal-primary" style="display:none">
          <i class="ti ti-check" style="font-size:14px"></i> Salvar
        </button>
        <button type="button" onclick="cancelarEdicao()" id="btn-cancelar" class="btn-modal btn-modal-ghost" style="display:none">
          <i class="ti ti-x" style="font-size:14px"></i> Cancelar
        </button>
      </div>
    </div>
  </x-slot>

</x-modal>

<!-- Confirmação de exclusão (somente UI — integrar API no backend) -->
<div id="confirm-excluir-overlay" class="confirm-excluir-overlay" role="dialog" aria-modal="true" aria-labelledby="confirm-excluir-title">
  <div class="confirm-excluir-box">
    <div class="confirm-excluir-icon"><i class="ti ti-alert-triangle"></i></div>
    <p class="confirm-excluir-title" id="confirm-excluir-title">Excluir correção de sinal?</p>
    <p class="confirm-excluir-text" id="confirm-excluir-text">
      Esta ação não pode ser desfeita. A tarefa e os dados vinculados serão removidos permanentemente.
    </p>
    <div class="confirm-excluir-foot">
      <button type="button" onclick="fecharConfirmacaoExclusao()" class="btn-modal btn-modal-ghost">Cancelar</button>
      <button type="button" onclick="confirmarExclusaoTarefa()" id="btn-confirmar-excluir" class="btn-modal btn-modal-danger">
        <i class="ti ti-trash" style="font-size:14px"></i> Excluir
      </button>
    </div>
  </div>
</div>

<!-- Confirmação de exclusão de OS (modal separado da tarefa pai — z-index reutiliza .confirm-excluir-overlay) -->
<div id="confirm-excluir-os-overlay" class="confirm-excluir-overlay" style="z-index:120" role="dialog" aria-modal="true" aria-labelledby="confirm-excluir-os-title">
  <div class="confirm-excluir-box">
    <div class="confirm-excluir-icon"><i class="ti ti-alert-triangle"></i></div>
    <p class="confirm-excluir-title" id="confirm-excluir-os-title">Excluir ordem de serviço?</p>
    <p class="confirm-excluir-text" id="confirm-excluir-os-text">
      Esta ação não pode ser desfeita. A OS será removida permanentemente.
    </p>
    <div class="confirm-excluir-foot">
      <button type="button" onclick="fecharConfirmacaoExclusaoOs()" class="btn-modal btn-modal-ghost">Cancelar</button>
      <button type="button" onclick="confirmarExclusaoOs()" id="btn-confirmar-excluir-os" class="btn-modal btn-modal-danger">
        <i class="ti ti-trash" style="font-size:14px"></i> Excluir OS
      </button>
    </div>
  </div>
</div>

<!-- MODAL NOVA OS (vinculada à tarefa) -->
<x-modal-os tipo-placeholder="Ex: Correção sinal — cliente" status-variant="titulo" />

<!-- FILTROS -->
<div class="card filtros-card">
  <div class="filtros-bar">

    <div class="filtro-search">
      <i class="ti ti-search filtro-search-icon"></i>
      <input type="text" id="filtro-busca" placeholder="Buscar por nome, código ou ID..."
        oninput="aplicarFiltrosDebounce()"
        class="filtro-search-input"/>
    </div>

    <div class="filtro-divider"></div>

    <select id="filtro-regiao" onchange="aplicarFiltros()" class="filtro-select">
      <option value="">Todas as regiões</option>
      <option>Goval</option>
      <option>Vale do Aço</option>
      <option>Caratinga</option>
      <option>Teste</option>
    </select>

    <div class="filtro-divider"></div>

    <select id="filtro-tecnico" onchange="aplicarFiltros()" class="filtro-select">
      <option value="">Todos os técnicos</option>
    </select>

    <div class="filtro-divider"></div>

    <div class="filtro-datas">
      <label class="filtro-data-label">De</label>
      <input type="date" id="filtro-data-inicio" onchange="aplicarFiltros()" class="filtro-date"/>
      <label class="filtro-data-label">Até</label>
      <input type="date" id="filtro-data-fim" onchange="aplicarFiltros()" class="filtro-date"/>
    </div>

    <div class="filtro-divider"></div>

    <button onclick="limparFiltros()" class="filtro-limpar-btn">
      <i class="ti ti-x" style="font-size:12px"></i> Limpar
    </button>

  </div>
</div>

<!-- KANBAN -->
<div class="card" style="flex:1">
  <div class="card-header">
    <span class="card-title">Kanban de Correção de sinal</span>
    <span class="card-action">total: <span id="total-correcoes">0</span></span>
  </div>
  <div class="kanban-cols">
    <div class="kcol">
      <div class="kcol-head">
        <div class="kcol-name"><div class="dot d-blue"></div> Criada</div>
        <div style="display:flex;align-items:center;gap:5px">
          <span class="kcol-count" id="count-criada">0</span>
          <button class="btn-kcol-toggle" onclick="toggleColuna(this)" title="Minimizar coluna" aria-label="Minimizar coluna">
            <i class="ti ti-chevron-down"></i>
          </button>
        </div>
      </div>
      <div class="kcol-content-wrap">
        <div class="kcol-body" id="col-criada" data-status="Criada"></div>
        <div id="mais-criada" style="display:none;padding:8px">
          <button onclick="carregarMais('Criada')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Carregar mais</button>
        </div>
        <div id="menos-criada" style="display:none;padding:8px">
          <button onclick="verMenos('Criada')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Ver menos</button>
        </div>
      </div>
    </div>
    <div class="kcol">
      <div class="kcol-head">
        <div class="kcol-name"><div class="dot d-amber"></div> Em andamento</div>
        <div style="display:flex;align-items:center;gap:5px">
          <span class="kcol-count" id="count-andamento">0</span>
          <button class="btn-kcol-toggle" onclick="toggleColuna(this)" title="Minimizar coluna" aria-label="Minimizar coluna">
            <i class="ti ti-chevron-down"></i>
          </button>
        </div>
      </div>
      <div class="kcol-content-wrap">
        <div class="kcol-body" id="col-andamento" data-status="Em andamento"></div>
        <div id="mais-andamento" style="display:none;padding:8px">
          <button onclick="carregarMais('Em andamento')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Carregar mais</button>
        </div>
        <div id="menos-andamento" style="display:none;padding:8px">
          <button onclick="verMenos('Em andamento')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Ver menos</button>
        </div>
      </div>
    </div>
    <div class="kcol">
      <div class="kcol-head">
        <div class="kcol-name"><div class="dot d-red"></div> Impedimento</div>
        <div style="display:flex;align-items:center;gap:5px">
          <span class="kcol-count" id="count-impedimento">0</span>
          <button class="btn-kcol-toggle" onclick="toggleColuna(this)" title="Minimizar coluna" aria-label="Minimizar coluna">
            <i class="ti ti-chevron-down"></i>
          </button>
        </div>
      </div>
      <div class="kcol-content-wrap">
        <div class="kcol-body" id="col-impedimento" data-status="Impedimento"></div>
        <div id="mais-impedimento" style="display:none;padding:8px">
          <button onclick="carregarMais('Impedimento')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Carregar mais</button>
        </div>
        <div id="menos-impedimento" style="display:none;padding:8px">
          <button onclick="verMenos('Impedimento')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Ver menos</button>
        </div>
      </div>
    </div>
    <div class="kcol">
      <div class="kcol-head">
        <div class="kcol-name"><div class="dot d-green"></div> Finalizada</div>
        <div style="display:flex;align-items:center;gap:5px">
          <span class="kcol-count" id="count-finalizada">0</span>
          <button class="btn-kcol-toggle" onclick="toggleColuna(this)" title="Minimizar coluna" aria-label="Minimizar coluna">
            <i class="ti ti-chevron-down"></i>
          </button>
        </div>
      </div>
      <div class="kcol-content-wrap">
        <div class="kcol-body" id="col-finalizada" data-status="Finalizada"></div>
        <div id="mais-finalizada" style="display:none;padding:8px">
          <button onclick="carregarMais('Finalizada')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Carregar mais</button>
        </div>
        <div id="menos-finalizada" style="display:none;padding:8px">
          <button onclick="verMenos('Finalizada')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Ver menos</button>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
  let prioridadeSelecionada = 'Média';
  let osEditandoId = null;
  /** ID da OS aguardando confirmação no modal de exclusão (null = nenhuma pendente) */
  let osExclusaoPendenteId = null;
  const osDataMap = {};

  function debounce(func, delay = 500) {
    let timeout;
    return function (...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), delay);
    }
  }

  const aplicarFiltrosDebounce = debounce(aplicarFiltros, 500);

  function obterFiltrosFormulario() {
    return {
      regiao:     document.getElementById('filtro-regiao').value,
      tecnico:    document.getElementById('filtro-tecnico').value,
      dataInicio: document.getElementById('filtro-data-inicio').value,
      dataFim:    document.getElementById('filtro-data-fim').value,
      busca: document.getElementById('filtro-busca').value.trim(),
    };
  }

  function filtrosParaApi(filtros) {
    return Object.fromEntries(
      Object.entries(filtros).filter(([, valor]) => valor != null && String(valor).trim() !== '')
    );
  }

  window.obterFiltrosFormulario = obterFiltrosFormulario;
  window.filtrosParaApi = filtrosParaApi;

  async function aplicarFiltros() {
    if (window.carregarCorrecoes) {
      window.carregarCorrecoes(obterFiltrosFormulario());
    }
  }

  async function limparFiltros() {
    document.getElementById('filtro-regiao').value = '';
    document.getElementById('filtro-tecnico').value = '';
    document.getElementById('filtro-data-inicio').value = '';
    document.getElementById('filtro-data-fim').value = '';
    document.getElementById('filtro-busca').value = '';
    if (window.carregarCorrecoes) window.carregarCorrecoes({});
  }

  // ─── MODAIS ───
  function limparFormularioCorrecao() {
    document.getElementById('input-regiao').value = '';
    document.getElementById('input-numero-os').value = '';
    document.getElementById('input-localizacao-texto').value = '';
    document.getElementById('input-coordenadas').value = '';
    prioridadeSelecionada = 'Média';
    window.plannerResetPrioridade?.(document.querySelector('.prioridade-wrap'), 'Média');
  }

  window.abrirModal = function() {
    limparFormularioCorrecao();
    document.getElementById('modal-overlay').classList.add('open');
  }

  function fecharModal() {
    document.getElementById('modal-overlay').classList.remove('open');
  }

  // ─── ORDENS DE SERVIÇO ───
  window.abrirNovaOS = function() {
    const correcaoId = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (!correcaoId) return;

    osEditandoId = null;
    document.getElementById('modal-os-overlay').classList.add('open');
    const regiao = document.getElementById('detalhe-conteudo').dataset.regiao || '';
    window.carregarTecnicosOsModal?.(regiao);
    document.getElementById('os-modal-titulo').textContent = 'Nova Ordem de Serviço';
    document.getElementById('os-btn-icon').className = 'ti ti-clipboard-check';
    document.getElementById('os-btn-label').textContent = 'Criar OS';
    document.getElementById('os-input-tipo').value = '';
    window.resetOsAnexosModal?.();
    window.resetOsTecnicosModal?.();
    document.getElementById('os-input-status').value = 'Aberta';
  };

  window.editarOs = function(id) {
    const os = osDataMap[id];
    if (!os) return;

    osEditandoId = id;
    document.getElementById('os-modal-titulo').textContent = 'Editar Ordem de Serviço';
    document.getElementById('os-btn-icon').className = 'ti ti-check';
    document.getElementById('os-btn-label').textContent = 'Salvar alterações';

    const tipoValue = (os.titulo || '').replace(/^OS\s*[—\-]\s*/i, '');
    document.getElementById('os-input-tipo').value = tipoValue;
    window.setOsDescricaoValor?.(os.descricao || '');
    window.carregarAnexosOsModal?.(id);
    document.getElementById('os-input-status').value = os.status || 'Aberta';

    const regiao = document.getElementById('detalhe-conteudo').dataset.regiao || '';
    window.carregarTecnicosOsModal?.(regiao).then(() => {
      window.setOsTecnicosValor?.(os.responsavel || '');
    });

    document.getElementById('modal-os-overlay').classList.add('open');
  };

  window.fecharNovaOS = function() {
    osEditandoId = null;
    window.resetOsAnexosModal?.();
    document.getElementById('modal-os-overlay').classList.remove('open');
  };
window.alterarStatusOS = async function(osId, novoStatus) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/op-tasks/${osId}`, {
      method: 'PUT',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ status: novoStatus }),
    });

    if (!response.ok) {
      const erro = await response.json();
      console.error('Erro ao atualizar o status da OS:', erro.message);
      const correcaoId = document.getElementById('detalhe-conteudo')?.dataset?.id;
      if (correcaoId) carregarOS(correcaoId);
    } else if (osDataMap[osId]) {
      osDataMap[osId].status = novoStatus;
    }
  };

  async function salvarOs() {
    const correcaoId = document.getElementById('detalhe-conteudo')?.dataset?.id;
    const tipo = document.getElementById('os-input-tipo').value.trim();
    const descricao = window.getOsDescricaoValor?.() || '';
    const tecnico = window.getOsTecnicosValor?.() || '';
    const status = document.getElementById('os-input-status').value;
    const token = localStorage.getItem('planner_token');

    if (!correcaoId) return;
    if (!tipo) {
      alert('Informe o tipo de serviço.');
      return;
    }

    const btn = document.getElementById('os-btn-salvar');
    btn.disabled = true;

    try {
      if (osEditandoId) {
        const dados = {
          titulo: tipo,
          descricao,
          responsavel: tecnico,
          status,
        };
        await window.enviarAnexosPendentesOs?.(osEditandoId);
        const response = await fetch(`/api/op-tasks/${osEditandoId}`, {
          method: 'PUT',
          headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(dados),
        });
        if (response.ok) {
          fecharNovaOS();
          carregarOS(correcaoId);
          if (window.carregarCorrecoes) window.carregarCorrecoes();
        } else {
          const erro = await response.json();
          console.error('Erro ao atualizar OS:', erro.message);
          alert(erro.message || 'Erro ao atualizar OS.');
        }
      } else {
        const dados = {
          titulo: tipo,
          descricao,
          responsavel: tecnico,
          status,
          categoria: 'ordem-servico',
          parent_task_id: correcaoId,
        };
        const response = await fetch('/api/op-tasks', {
          method: 'POST',
          headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify(dados),
        });
        if (response.ok) {
          const criada = await response.json();
          await window.enviarAnexosPendentesOs?.(criada.id);
          fecharNovaOS();
          carregarOS(correcaoId);
          if (window.carregarCorrecoes) window.carregarCorrecoes();
        } else {
          const erro = await response.json();
          console.error('Erro ao criar OS:', erro.message);
          alert(erro.message || 'Erro ao criar OS.');
        }
      }
    } finally {
      btn.disabled = false;
    }
  }
  window.salvarOs = salvarOs;

  function escOs(valor) {
    if (valor == null || valor === '') return '—';
    return String(valor).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  async function carregarOS(correcaoId) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/correcao-sinal/${correcaoId}/os`, {
      headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json',
      },
    });

    const data = await response.json();
    const lista = data.os || [];

    lista.forEach(os => { osDataMap[os.id] = os; });
    renderListaOS(lista);
  }
  window.carregarOS = carregarOS;

  // ─── EXCLUSÃO DE OS ───────────────────────────────────────────────────────
  // Fluxo: botão lixeira no card → modal de confirmação → DELETE /api/op-tasks/{id}
  // A tarefa pai (correção de sinal) permanece; apenas a OS vinculada é removida.

  /** Abre o modal pedindo confirmação antes de excluir uma OS */
  window.abrirConfirmacaoExclusaoOs = function(osId, event) {
    if (event) event.stopPropagation();
    const os = osDataMap[osId];
    if (!os) return;

    osExclusaoPendenteId = osId;
    const titulo = os.titulo || os.taskCode || 'esta OS';
    document.getElementById('confirm-excluir-os-text').textContent =
      `Tem certeza que deseja excluir "${titulo}"? Esta ação não pode ser desfeita.`;
    document.getElementById('confirm-excluir-os-overlay').classList.add('open');
  };

  /** Cancela a exclusão pendente e fecha o modal */
  function fecharConfirmacaoExclusaoOs() {
    osExclusaoPendenteId = null;
    document.getElementById('confirm-excluir-os-overlay')?.classList.remove('open');
  }

  /** Confirma e executa DELETE na API; recarrega a lista de OS da tarefa pai */
  async function confirmarExclusaoOs() {
    const osId = osExclusaoPendenteId;
    if (!osId) return;

    const parentId = document.getElementById('detalhe-conteudo')?.dataset?.id;
    const token = localStorage.getItem('planner_token');
    const btn = document.getElementById('btn-confirmar-excluir-os');

    btn.disabled = true;
    try {
      const response = await fetch(`/api/op-tasks/${osId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        const erro = await response.json();
        throw new Error(erro.message || 'Erro ao excluir ordem de serviço.');
      }

      delete osDataMap[osId];
      fecharConfirmacaoExclusaoOs();
      await window.plannerAposMutacaoLocal(async () => {
        if (parentId) await carregarOS(parentId);
        if (window.carregarCorrecoes) await window.carregarCorrecoes();
      });
    } catch (err) {
      console.error('Erro ao excluir OS:', err.message);
      alert(err.message || 'Erro ao excluir ordem de serviço.');
    } finally {
      btn.disabled = false;
    }
  }
  window.confirmarExclusaoOs = confirmarExclusaoOs;

  function renderListaOS(lista) {
    const container = document.getElementById('detalhe-tab-os');
    const statusBorder = { 'Finalizada': '#22c55e', 'Impedimento': '#ef4444', 'Em andamento': '#f59e0b', 'Aberta': '#166ac4' };
    const statusBadge  = { 'Finalizada': 'b-baixa', 'Impedimento': 'b-alta', 'Aberta': 'b-aberta' };
    const statusOpts   = [
      { value: 'Aberta',       label: 'Aberta',       cls: 'aberta',     border: '#166ac4' },
      { value: 'Em andamento', label: 'Em andamento', cls: 'andamento',  border: '#f59e0b' },
      { value: 'Finalizada',   label: 'Finalizada',   cls: 'finalizada', border: '#22c55e' },
    ];

    if (lista.length === 0) {
      container.innerHTML = `
        <div class="os-tab-head">
          <span class="os-tab-title">Ordens de Serviço <span class="os-count-pill">0</span></span>
          <button type="button" class="btn-nova-os" onclick="abrirNovaOS()">
            <i class="ti ti-plus" style="font-size:12px"></i> Nova OS
          </button>
        </div>
        <div class="os-empty">
          <i class="ti ti-clipboard-off"></i>
          <span>Nenhuma OS vinculada a esta correção de sinal</span>
        </div>`;
      return;
    }

    container.innerHTML = `
      <div class="os-tab-head">
        <span class="os-tab-title">Ordens de Serviço <span class="os-count-pill">${lista.length}</span></span>
        <button type="button" class="btn-nova-os" onclick="abrirNovaOS()">
          <i class="ti ti-plus" style="font-size:12px"></i> Nova OS
        </button>
      </div>
      ${lista.map(os => {
        const border = statusBorder[os.status] || '#166ac4';
        const badge  = statusBadge[os.status]  || 'b-media';
        const av     = (os.responsavel || '?').trim().split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
        return `
        <div class="os-card" data-os-id="${os.id}" style="border-left-color:${border}">
          <div class="os-card-row">
            <span class="os-card-title">${escOs(os.titulo)}</span>
            <div class="os-card-actions">
              <button type="button" class="btn-edit-os" title="Editar OS" onclick="editarOs(${os.id})">
                <i class="ti ti-pencil" style="font-size:12px"></i>
              </button>
              <button type="button" class="btn-delete-os" title="Excluir OS" onclick="abrirConfirmacaoExclusaoOs(${os.id}, event)">
                <i class="ti ti-trash" style="font-size:12px"></i>
              </button>
            </div>
          </div>
          <div class="os-card-meta">
            <span class="os-card-av">${av}</span>
            <span class="os-card-tecnico">${escOs(os.responsavel || '—')}</span>
            <div class="os-status-wrap" id="sw-${os.id}">
              <button type="button" class="os-status-badge" onclick="abrirStatusPills(${os.id}, event)" title="Alterar status">
                <span class="badge ${badge}" id="sb-${os.id}">${escOs(os.status)}</span>
                <span class="drop-arrow">✎</span>
              </button>
              <div class="os-status-pills" id="sp-${os.id}">
                ${statusOpts.map(opt => `<button type="button" class="os-status-pill os-status-pill-${opt.cls}${os.status === opt.value ? ' active' : ''}" onclick="selecionarStatusOS(${os.id}, '${opt.value}', event)">${opt.label}</button>`).join('')}
                <button type="button" class="os-status-close" onclick="fecharStatusPills(${os.id}, event)" title="Cancelar">✕</button>
              </div>
            </div>
            <span class="os-card-code">${os.taskCode ? escOs(os.taskCode) : ''}</span>
          </div>
        </div>`;
      }).join('')}`;
  }

  const statusBadgeMap  = { 'Aberta': 'b-aberta', 'Finalizada': 'b-baixa', 'Impedimento': 'b-alta' };
  const statusBorderMap = { 'Aberta': '#166ac4', 'Finalizada': '#22c55e', 'Impedimento': '#ef4444', 'Em andamento': '#f59e0b' };

  window.abrirStatusPills = function(osId, e) {
    e.stopPropagation();
    fecharTodasPills();
    const wrap  = document.getElementById('sw-' + osId);
    const pills = document.getElementById('sp-' + osId);
    if (!wrap || !pills) return;
    wrap.querySelector('.os-status-badge').style.display = 'none';
    pills.classList.add('open');
  };

  window.fecharStatusPills = function(osId, e) {
    if (e) e.stopPropagation();
    const wrap  = document.getElementById('sw-' + osId);
    const pills = document.getElementById('sp-' + osId);
    if (!wrap || !pills) return;
    pills.classList.remove('open');
    wrap.querySelector('.os-status-badge').style.display = '';
  };

  function fecharTodasPills() {
    document.querySelectorAll('.os-status-pills.open').forEach(p => {
      fecharStatusPills(p.id.replace('sp-', ''), null);
    });
  }

  window.selecionarStatusOS = function(osId, novoStatus, e) {
    e.stopPropagation();

    const wrap  = document.getElementById('sw-' + osId);
    const pills = document.getElementById('sp-' + osId);
    if (!wrap || !pills) return;

    const badgeEl = document.getElementById('sb-' + osId);
    if (badgeEl) {
      badgeEl.className = 'badge ' + (statusBadgeMap[novoStatus] || 'b-media');
      badgeEl.textContent = novoStatus;
    }

    const card = wrap.closest('.os-card');
    if (card) card.style.borderLeftColor = statusBorderMap[novoStatus] || '#166ac4';

    pills.querySelectorAll('.os-status-pill').forEach(pill => {
      pill.classList.toggle('active', pill.textContent.trim() === novoStatus);
    });

    fecharStatusPills(osId, null);

    if (typeof window.alterarStatusOS === 'function') {
      window.alterarStatusOS(osId, novoStatus);
    }
  };

  document.addEventListener('click', fecharTodasPills);

  function trocarAba(aba) {
    document.getElementById('detalhe-tab-detalhes').style.display = aba === 'detalhes' ? 'block' : 'none';
    document.getElementById('detalhe-tab-os').style.display = aba === 'os' ? 'block' : 'none';
    document.getElementById('tab-btn-detalhes').classList.toggle('active', aba === 'detalhes');
    document.getElementById('tab-btn-os').classList.toggle('active', aba === 'os');
    const naAbaOs = aba === 'os';
    const btnExcluir = document.getElementById('btn-excluir');
    const btnEditar = document.getElementById('btn-editar');
    if (btnExcluir) btnExcluir.style.display = naAbaOs ? 'none' : '';
    if (btnEditar) btnEditar.style.display = naAbaOs ? 'none' : '';
    if (aba === 'os') {
      const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
      if (id) carregarOS(id);
    }
  }

  function resetBotoesDetalhe() {
    document.getElementById('btn-excluir').style.display = '';
    document.getElementById('btn-editar').style.display = '';
    document.getElementById('btn-salvar').style.display = 'none';
    document.getElementById('btn-cancelar').style.display = 'none';
  }

  function fecharDetalhe() {
    fecharConfirmacaoExclusao();
    fecharConfirmacaoExclusaoOs();
    document.getElementById('detalhe-overlay').classList.remove('open');
    trocarAba('detalhes');
    resetBotoesDetalhe();
  }

  function cancelarEdicao() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    resetBotoesDetalhe();
    if (id) window.abrirDetalhe(id);
  }

  function abrirConfirmacaoExclusao() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (!id) return;
    const titulo = document.getElementById('detalhe-titulo')?.textContent?.trim() || 'esta tarefa';
    document.getElementById('confirm-excluir-text').textContent =
      `Tem certeza que deseja excluir "${titulo}"? Esta ação não pode ser desfeita.`;
    document.getElementById('confirm-excluir-overlay').classList.add('open');
  }

  function fecharConfirmacaoExclusao() {
    document.getElementById('confirm-excluir-overlay')?.classList.remove('open');
  }

  async function confirmarExclusaoTarefa() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (!id) return;

    const token = localStorage.getItem('planner_token');
    try {
      const response = await fetch(`/api/correcao-sinal/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        const erro = await response.json();
        throw new Error(erro.message || 'Erro ao excluir correção de sinal.');
      }

      fecharConfirmacaoExclusao();
      fecharDetalhe();
      await window.plannerAposExclusaoTarefa(id, () => window.carregarCorrecoes());
    } catch (err) {
      console.error('Erro ao excluir correção de sinal:', err.message);
      alert(err.message || 'Erro ao excluir correção de sinal.');
    }
  }
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    // Fecha modais de confirmação na ordem: OS → tarefa pai → detalhe/criar
    if (document.getElementById('confirm-excluir-os-overlay')?.classList.contains('open')) {
      fecharConfirmacaoExclusaoOs();
      return;
    }
    if (document.getElementById('confirm-excluir-overlay')?.classList.contains('open')) {
      fecharConfirmacaoExclusao();
      return;
    }
    if (document.getElementById('modal-os-overlay')?.classList.contains('open')) {
      fecharNovaOS();
      return;
    }
    if (document.getElementById('detalhe-overlay')?.classList.contains('open')) {
      fecharDetalhe();
      return;
    }
    if (document.getElementById('modal-overlay')?.classList.contains('open')) {
      fecharModal();
    }
  });

  // ─── PRIORIDADE ───
  function selecionarPrioridade(btn, nivel) {
    prioridadeSelecionada = nivel;
    window.plannerAtualizarBotoesPrioridade?.(btn.closest('.prioridade-wrap'), nivel);
  }

  async function carregarTecnicos(regiao, destino = 'os-input-tecnico') {
    const el = document.getElementById(destino);
    if (!el || el.tagName !== 'SELECT') return;

    const token = localStorage.getItem('planner_token');
    const headers = { 'Authorization': 'Bearer ' + token };

    async function buscarTecnicos(regiaoFiltro) {
      const url = regiaoFiltro
        ? `/api/tecnicos?regiao=${encodeURIComponent(regiaoFiltro)}`
        : '/api/tecnicos';
      const res = await fetch(url, { headers });
      const data = await res.json();
      return Array.isArray(data) ? data : [];
    }

    let tecnicos = await buscarTecnicos(regiao);
    if (regiao && tecnicos.length === 0 && destino === 'os-input-tecnico') {
      tecnicos = await buscarTecnicos(null);
    }

    const placeholder = destino === 'filtro-tecnico'
      ? '<option value="">Todos os técnicos</option>'
      : '<option value="">Selecione...</option>';
    el.innerHTML = placeholder
      + tecnicos.map(t => `<option value="${t.nome}">${t.nome}</option>`).join('');
  }

  // ─── EDIÇÃO ───
  function ativarEdicao() {
    window.plannerDetalheEdicao.mostrarBotoesEdicao();
    window.plannerDetalheEdicao.ativarCampos([
      { id: 'campo-titulo', tipo: 'text' },
      { id: 'campo-regiao', tipo: 'select', opcoes: ['Goval', 'Vale do Aço', 'Caratinga', 'Teste'] },
      { id: 'campo-numero-os', tipo: 'text' },
      { id: 'campo-localizacao-texto', tipo: 'text' },
      { id: 'campo-coordenadas', tipo: 'text' },
      { id: 'campo-descricao', tipo: 'textarea' },
      { id: 'campo-nome-cliente', tipo: 'text' },
      { id: 'campo-setor', tipo: 'text' },
      { id: 'campo-prioridade', tipo: 'select', opcoes: ['Baixa', 'Média', 'Alta'] },
      { id: 'campo-status', tipo: 'select', opcoes: ['Criada', 'Em andamento', 'Impedimento', 'Finalizada'] },
    ]);
  }

  async function salvarEdicao() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (!id) return;

    const dados = window.plannerDetalheEdicao.montarDados({
      titulo: { id: 'campo-titulo', tipo: 'text' },
      regiao: { id: 'campo-regiao', tipo: 'select' },
      numero_os: { id: 'campo-numero-os', tipo: 'text' },
      localizacao_texto: { id: 'campo-localizacao-texto', tipo: 'text' },
      coordenadas: { id: 'campo-coordenadas', tipo: 'text' },
      descricao: { id: 'campo-descricao', tipo: 'textarea' },
      nome_cliente: { id: 'campo-nome-cliente', tipo: 'text' },
      setor: { id: 'campo-setor', tipo: 'text' },
      prioridade: { id: 'campo-prioridade', tipo: 'select' },
      status: { id: 'campo-status', tipo: 'select' },
    });

    const btn = document.getElementById('btn-salvar');
    if (btn) btn.disabled = true;
    try {
      await window.plannerDetalheEdicao.enviarPut(`/api/correcao-sinal/${id}`, dados);
      fecharDetalhe();
      window.carregarCorrecoes();
    } catch (err) {
      console.error('Erro ao salvar:', err);
      alert(err.message || 'Erro ao salvar alterações.');
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  window.ativarEdicao = ativarEdicao;
  window.salvarEdicao = salvarEdicao;
  window.cancelarEdicao = cancelarEdicao;

  async function criarCorrecaoPoste() {
    const regiao = document.getElementById('input-regiao').value;
    const numeroOs = document.getElementById('input-numero-os').value.trim();

    if (!regiao) {
      alert('Selecione a região.');
      return;
    }

    const dados = {
      titulo:            numeroOs ? `Correção de sinal — OS ${numeroOs}` : 'Correção de sinal',
      coordenadas:       document.getElementById('input-coordenadas').value.trim(),
      localizacao_texto: document.getElementById('input-localizacao-texto').value.trim(),
      regiao:            regiao,
      responsavel:       '',
      prioridade:        prioridadeSelecionada,
      numero_os:         numeroOs,
      status:            'Criada',
    };

    const token = localStorage.getItem('planner_token');
    const response = await fetch('/api/correcao-sinal', {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(dados)
    });

    const resultado = await response.json();
    if (response.ok) {
      fecharModal();
      window.carregarCorrecoes();
    } else {
      alert(resultado.message || 'Erro ao criar correção de sinal.');
      console.error('Erro ao criar correção de sinal:', resultado);
    }
  }

  window.trocarAba = trocarAba;

  async function buscarEndereco(coordenada) {
    const coord = (coordenada ?? '').trim();
    if (!coord) return;

    const token = localStorage.getItem('planner_token');
    const response = await fetch(
      `/api/correcao-sinal/coordenada?coordenada=${encodeURIComponent(coord)}`,
      { headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' } }
    );
    const data = await response.json();
    if (response.ok) {
      document.getElementById('input-localizacao-texto').value = data.endereco ?? '';
    }
  }

  document.getElementById('input-coordenadas').addEventListener('blur', function () {
    buscarEndereco(this.value);
  });
</script>

<script type="module">
  let draggedId = null;
  let draggedStatus = null;
  let wasDragged = false;
  const correcoesMap = {};
  let filtrosAtivos = {};
  const isTouchDevice = window.matchMedia('(pointer: coarse)').matches;

  const offsetMap = { 'Criada': 0, 'Em andamento': 0, 'Impedimento': 0, 'Finalizada': 0 };
  const limitMap  = { 'Criada': 10, 'Em andamento': 10, 'Impedimento': 10, 'Finalizada': 50 };
  const colIdMap  = { 'Criada': 'criada', 'Em andamento': 'andamento', 'Impedimento': 'impedimento', 'Finalizada': 'finalizada' };

  async function carregarMais(status) {
    const limit = limitMap[status];
    offsetMap[status] += limit;
    const novos = await buscarColuna(status, limit, offsetMap[status], filtrosAtivos);
    const colId = colIdMap[status];
    const col = document.getElementById(`col-${colId}`);
    novos.forEach(r => { col.insertAdjacentHTML('beforeend', renderCard(r)); correcoesMap[r.id] = r; });
    document.getElementById(`count-${colId}`).textContent = col.querySelectorAll('.kcard').length;
    if (novos.length < limit) document.getElementById(`mais-${colId}`).style.display = 'none';
    document.getElementById(`menos-${colId}`).style.display = 'block';
  }
  window.carregarMais = carregarMais;

  async function verMenos(status) {
    const colId = colIdMap[status];
    const col = document.getElementById(`col-${colId}`);
    col.querySelectorAll('.kcard').forEach((card, index) => { if (index >= 10) card.remove(); });
    offsetMap[status] = 0;
    document.getElementById(`count-${colId}`).textContent = col.querySelectorAll('.kcard').length;
    document.getElementById(`menos-${colId}`).style.display = 'none';
    document.getElementById(`mais-${colId}`).style.display = 'block';
  }
  window.verMenos = verMenos;

  function toggleColuna(btn) {
    const col = btn.closest('.kcol');
    const collapsed = col.classList.toggle('collapsed');
    btn.title = collapsed ? 'Expandir coluna' : 'Minimizar coluna';
    btn.setAttribute('aria-label', collapsed ? 'Expandir coluna' : 'Minimizar coluna');
  }
  window.toggleColuna = toggleColuna;

  // ─── HELPERS ───
  function esc(valor) {
    if (valor == null || valor === '') return '—';
    return String(valor).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function formatarData(valor) {
    if (!valor) return '—';
    const data = new Date(valor);
    if (isNaN(data.getTime())) return esc(valor);
    return data.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
  }

  function badgePrioridade(prioridade) {
    const nivel = prioridade?.toLowerCase();
    const cls = nivel === 'alta' ? 'b-alta' : nivel === 'baixa' ? 'b-baixa' : 'b-media';
    return `<span class="badge ${cls}">${esc(prioridade || 'Média')}</span>`;
  }

  function badgeStatus(status) {
    const exibicao = status === 'Backlog' ? 'Criada'
      : status === 'Concluída' ? 'Finalizada'
      : (status || '—');
    const mapa = { 'Criada':'d-blue', 'Em andamento':'d-amber', 'Impedimento':'d-red', 'Finalizada':'d-green' };
    const dot = mapa[exibicao] || 'd-blue';
    return `<span class="badge" style="display:inline-flex;align-items:center;gap:5px;background:var(--gray-100);color:var(--gray-700)"><span class="dot ${dot}"></span>${esc(exibicao)}</span>`;
  }

  function badgeRegiao(regiao) {
    const cls = regiao && regiao.toLowerCase().includes('vale') ? 'b-regiao-va' : 'b-regiao-gv';
    return `<span class="badge ${cls}">${esc(regiao || 'Sem região')}</span>`;
  }

  function campoDetalhe(label, valor, span = 1, id = '') {
    const spanClass = span === 3 ? ' span-3' : span === 2 ? ' span-2' : '';
    const idAttr = id ? `id="${id}"` : '';
    return `
    <div class="detail-field${spanClass}">
      <span class="detail-label">${label}</span>
      <div class="detail-value" ${idAttr}>${valor || '—'}</div>
    </div>`;
  }

  function escapeRegex(texto) {
    return String(texto).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function normalizarSinalNumero(valor) {
    if (valor == null || valor === '') return null;
    if (typeof valor === 'number') return Number.isNaN(valor) ? null : valor;
    const texto = String(valor).replace(',', '.');
    const match = texto.match(/-?\d+(?:\.\d+)?/);
    if (!match) return null;
    const numero = Number(match[0]);
    return Number.isNaN(numero) ? null : numero;
  }

  function extrairSinalDescricao(descricao, rotulos) {
    const texto = String(descricao || '');
    if (!texto.trim()) return null;

    for (const rotulo of rotulos) {
      const regex = new RegExp(`^${escapeRegex(rotulo)}\\s*:\\s*(-?\\d+(?:[\\.,]\\d+)?)`, 'mi');
      const match = texto.match(regex);
      if (match) {
        return normalizarSinalNumero(match[1]);
      }
    }

    return null;
  }

  function obterSinaisCorrecao(registro) {
    return {
      chegada: normalizarSinalNumero(registro?.sinal_rx)
        ?? extrairSinalDescricao(registro?.descricao, ['Sinal Chegada', 'Sinal RX']),
      retorno: normalizarSinalNumero(registro?.sinal_rx_olt)
        ?? extrairSinalDescricao(registro?.descricao, ['Sinal Retorno', 'Sinal RX OLT', 'Sinal de Retorno']),
    };
  }

  function nivelSinalChegada(valor) {
    if (valor == null) return null;
    if (valor >= -23) return 'ideal';
    if (valor >= -25) return 'preventiva';
    if (valor >= -27) return 'alerta';
    return 'critico';
  }

  function nivelSinalRetorno(valor) {
    if (valor == null) return null;
    if (valor >= -25) return 'ideal';
    if (valor >= -26) return 'preventiva';
    if (valor >= -28) return 'alerta';
    return 'critico';
  }

  function formatarSinalDbm(valor) {
    if (valor == null) return '—';
    return `${Number(valor).toFixed(2)} dBm`;
  }

  function classeChipSinal(tipo, valor) {
    const nivel = tipo === 'retorno' ? nivelSinalRetorno(valor) : nivelSinalChegada(valor);
    return nivel ? `sinal-chip sinal-chip--${nivel}` : 'sinal-chip sinal-chip--sem-dados';
  }

  function classeTextoSinal(tipo, valor) {
    const nivel = tipo === 'retorno' ? nivelSinalRetorno(valor) : nivelSinalChegada(valor);
    return nivel ? `detail-value detail-value--sinal sinal-texto--${nivel}` : 'detail-value';
  }

  function renderChipSinal(label, tipo, valor) {
    return `
      <span class="${classeChipSinal(tipo, valor)}">
        <span class="sinal-chip-label">${esc(label)}</span>
        <span>${esc(formatarSinalDbm(valor))}</span>
      </span>
    `;
  }

  function renderCampoSinalDetalhe(label, tipo, valor) {
    return `
      <div class="detail-field">
        <span class="detail-label">${label}</span>
        <div class="${classeTextoSinal(tipo, valor)}">${esc(formatarSinalDbm(valor))}</div>
      </div>
    `;
  }

  // ─── CARREGAR CORREÇÕES ───
  async function buscarColuna(status, limit, offset = 0, filtros = {}) {
    const token = localStorage.getItem('planner_token');
    const params = new URLSearchParams({ status, limit, offset });
    Object.entries(filtros).forEach(([chave, valor]) => {
      if (valor != null && String(valor).trim() !== '') params.set(chave, valor);
    });
    const response = await fetch(`/api/correcao-sinal?${params}`, {
      headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
      cache: 'no-store',
    });
    const data = await response.json();
    const lista = data.correcaoDeSinal || [];
    return window.plannerFiltrarExcluidas ? window.plannerFiltrarExcluidas(lista) : lista;
  }

  async function carregarCorrecoes(filtros) {
    const gen = window.plannerBeginReload?.() ?? 0;
    const filtrosEfetivos = filtros !== undefined
      ? filtros
      : (window.obterFiltrosFormulario ? window.obterFiltrosFormulario() : {});
    filtrosAtivos = window.filtrosParaApi
      ? window.filtrosParaApi(filtrosEfetivos)
      : filtrosEfetivos;
    Object.keys(offsetMap).forEach(k => offsetMap[k] = 0);
    const [criadas, andamento, impedimento, finalizadas] = await Promise.all([
      buscarColuna('Criada', 10, 0, filtrosAtivos),
      buscarColuna('Em andamento', 10, 0, filtrosAtivos),
      buscarColuna('Impedimento', 10, 0, filtrosAtivos),
      buscarColuna('Finalizada', 50, 0, filtrosAtivos),
    ]);

    const todos = [...criadas, ...andamento, ...impedimento, ...finalizadas];
    if (window.plannerIsReloadCurrent && !window.plannerIsReloadCurrent(gen)) return;

    Object.keys(correcoesMap).forEach(k => delete correcoesMap[k]);
    todos.forEach(r => { correcoesMap[r.id] = r; });

    document.getElementById('col-criada').innerHTML      = criadas.map(renderCard).join('');
    document.getElementById('col-andamento').innerHTML   = andamento.map(renderCard).join('');
    document.getElementById('col-impedimento').innerHTML = impedimento.map(renderCard).join('');
    document.getElementById('col-finalizada').innerHTML  = finalizadas.map(renderCard).join('');
    document.getElementById('count-criada').textContent      = criadas.length;
    document.getElementById('count-andamento').textContent   = andamento.length;
    document.getElementById('count-impedimento').textContent = impedimento.length;
    document.getElementById('count-finalizada').textContent  = finalizadas.length;
    document.getElementById('total-correcoes').textContent = todos.length;

    document.getElementById('mais-criada').style.display      = criadas.length === 10 ? 'block' : 'none';
    document.getElementById('mais-andamento').style.display   = andamento.length === 10 ? 'block' : 'none';
    document.getElementById('mais-impedimento').style.display = impedimento.length === 10 ? 'block' : 'none';
    document.getElementById('mais-finalizada').style.display  = finalizadas.length === 50 ? 'block' : 'none';
  }
  window.carregarCorrecoes = carregarCorrecoes;

  // ─── RENDER CARD ───
  function renderCard(r) {
    const prioridadeClass = r.prioridade?.toLowerCase() === 'alta' ? 'b-alta'
      : r.prioridade?.toLowerCase() === 'baixa' ? 'b-baixa' : 'b-media';
    const regiaoClass = r.regiao && r.regiao.toLowerCase().includes('vale') ? 'b-regiao-va' : 'b-regiao-gv';
    const titulo = r.nome || r.nome_cliente || r.titulo || 'Correção de sinal';
    const codigo = r.taskCode || r.codigo_exibicao || 'S/C';
    const sinais = obterSinaisCorrecao(r);
    const sinaisHtml = [
      sinais.chegada != null ? renderChipSinal('Chegada', 'chegada', sinais.chegada) : '',
      sinais.retorno != null ? renderChipSinal('Retorno', 'retorno', sinais.retorno) : '',
    ].filter(Boolean).join('');

    return `
    <div class="kcard"
      data-id="${r.id}"
      data-status="${esc(r.status_kanban || r.status)}"
      draggable="true"
      ondragstart="iniciarArrasto(event, ${r.id})">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <span class="kcard-code" style="font-size:11px">${esc(codigo)}</span>
        <span class="badge ${prioridadeClass}">${r.prioridade || 'Média'}</span>
      </div>
      <div class="kcard-title">${esc(titulo)}</div>
      ${sinaisHtml ? `<div class="kcard-signals">${sinaisHtml}</div>` : ''}
      <div class="kcard-foot" style="margin-top:6px">
        ${r.setor ? `<span class="badge b-cat-gen">${esc(r.setor)}</span>` : ''}
        <span class="badge ${regiaoClass}">${r.regiao || 'Sem região'}</span>
        ${r.responsavel ? `<span style="font-size:10px;color:var(--gray-400);margin-left:auto">${esc(r.responsavel)}</span>` : ''}
      </div>
    </div>`;
  }

  // ─── RENDER DETALHE ───
  function renderDetalhe(r) {
    const titulo = r.nome || r.nome_cliente || r.titulo || 'Correção de sinal';
    const codigo = r.taskCode || r.codigo_exibicao || '';
    const sinais = obterSinaisCorrecao(r);
    document.getElementById('detalhe-titulo').textContent = titulo;
    document.getElementById('detalhe-subtitulo').textContent = codigo ? `Código: ${codigo}` : '';

    document.getElementById('detalhe-conteudo').innerHTML = `
      <div style="display:flex;flex-direction:column;gap:16px" class="detail-enter">
        <div class="detail-badges">
          ${badgeStatus(r.status_exibicao || r.status)}
          ${badgePrioridade(r.prioridade)}
          ${badgeRegiao(r.regiao)}
          ${sinais.chegada != null ? renderChipSinal('Chegada', 'chegada', sinais.chegada) : ''}
          ${sinais.retorno != null ? renderChipSinal('Retorno', 'retorno', sinais.retorno) : ''}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Título', esc(titulo), 1, 'campo-titulo')}
          ${campoDetalhe('Região', esc(r.regiao), 1, 'campo-regiao')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Número da OS (Hubsoft)', esc(r.numero_os), 2, 'campo-numero-os')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Cliente', esc(r.nome_cliente), 1, 'campo-nome-cliente')}
          ${campoDetalhe('Caixa / Setor', esc(r.setor || r.localizacao_texto), 1, 'campo-setor')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Endereço / Localização', esc(r.localizacao_texto), 1, 'campo-localizacao-texto')}
          ${campoDetalhe('Coordenadas', esc(r.coordenadas), 1, 'campo-coordenadas')}
        </div>
        <div class="detail-grid-2">
          ${renderCampoSinalDetalhe('Sinal de Chegada', 'chegada', sinais.chegada)}
          ${renderCampoSinalDetalhe('Sinal de Retorno', 'retorno', sinais.retorno)}
        </div>
        <div class="detail-field span-2">
          <span class="detail-label">Descrição</span>
          <div class="detail-value" id="campo-descricao" style="white-space:pre-wrap;min-height:72px">${esc(r.descricao || '—')}</div>
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Prioridade', esc(r.prioridade), 1, 'campo-prioridade')}
          ${campoDetalhe('Status', esc(r.status_exibicao || r.status), 1, 'campo-status')}
        </div>
        <div class="detail-grid-2">
          <div class="detail-field">
            <span class="detail-label">Código da tarefa</span>
            <div style="display:flex;align-items:center;gap:6px">
              <div class="detail-value" id="campo-taskcode" style="flex:1">${esc(codigo) || '—'}</div>
              <button onclick="puxarId()" title="Copiar código"
                style="flex-shrink:0;height:38px;padding:0 10px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);background:var(--white);color:var(--gray-500);cursor:pointer;font-size:14px;display:flex;align-items:center;transition:background 0.15s,color 0.15s"
                onmouseover="this.style.background='var(--gray-50)';this.style.color='var(--gray-700)'"
                onmouseout="this.style.background='var(--white)';this.style.color='var(--gray-500)'">
                <i class="ti ti-copy"></i>
              </button>
            </div>
          </div>
          ${campoDetalhe('Criado em', formatarData(r.criadaEm))}
        </div>
      </div>`;

    document.getElementById('detalhe-conteudo').dataset.id = r.id;
    document.getElementById('detalhe-conteudo').dataset.regiao = r.regiao || '';
    carregarOS(r.id);
  }

  function puxarId() {
    const codigo = document.getElementById('campo-taskcode')?.textContent?.trim();
    if (!codigo || codigo === '—') return;
    navigator.clipboard.writeText(codigo);
    const btn = event.currentTarget;
    btn.innerHTML = '<i class="ti ti-check"></i>';
    btn.style.color = '#16a34a';
    btn.style.borderColor = '#16a34a';
    setTimeout(() => {
      btn.innerHTML = '<i class="ti ti-copy"></i>';
      btn.style.color = 'var(--gray-500)';
      btn.style.borderColor = 'var(--gray-200)';
    }, 1500);
  }
  window.puxarId = puxarId;

  function renderDetalheLoading() {
    document.getElementById('detalhe-titulo').textContent = 'Carregando...';
    document.getElementById('detalhe-subtitulo').textContent = '';
    document.getElementById('detalhe-conteudo').innerHTML = `
      <div class="detail-loading detail-enter">
        <i class="ti ti-loader"></i> Buscando informações...
      </div>`;
  }

  function renderDetalheErro(mensagem) {
    document.getElementById('detalhe-titulo').textContent = 'Erro ao carregar';
    document.getElementById('detalhe-subtitulo').textContent = '';
    document.getElementById('detalhe-conteudo').innerHTML = `<div class="detail-error detail-enter">${esc(mensagem)}</div>`;
  }

  async function abrirDetalhe(id) {
    document.getElementById('detalhe-overlay').classList.add('open');
    trocarAba('detalhes');
    renderDetalheLoading();
    const token = localStorage.getItem('planner_token');
    try {
      const response = await fetch(`/api/correcao-sinal/${id}`, {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
      });
      const data = await response.json();
      if (!response.ok) { renderDetalheErro(data.message || 'Não foi possível carregar.'); return; }
      renderDetalhe(data.correcaoDeSinal || data);
    } catch {
      renderDetalheErro('Erro de conexão.');
    }
  }
  window.abrirDetalhe = abrirDetalhe;

  // ─── DRAG AND DROP ───
  function atualizarContadores() {
    ['criada', 'andamento', 'impedimento', 'finalizada'].forEach(col => {
      const body = document.getElementById(`col-${col}`);
      document.getElementById(`count-${col}`).textContent = body.querySelectorAll('.kcard').length;
    });
  }

  function limparDragOver() {
    document.querySelectorAll('.kcol-body').forEach(el => {
      el.classList.remove('drag-over', 'drag-bloqueado');
    });
  }

  async function moverCorrecao(id, novoStatus, colDestino) {
    const card = document.querySelector(`.kcard[data-id="${id}"]`);
    const colOrigem = card?.closest('.kcol-body');
    const statusAnterior = card?.dataset.status;
    if (card) { card.dataset.status = novoStatus; colDestino.appendChild(card); atualizarContadores(); }

    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/correcao-sinal/${id}`, {
      method: 'PUT',
      headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ status: novoStatus })
    });

    if (!response.ok) {
      if (card && colOrigem && statusAnterior) { card.dataset.status = statusAnterior; colOrigem.appendChild(card); atualizarContadores(); }
      const erro = await response.json();
      if (response.status === 422) alert(erro.message || 'Não foi possível mover o card.');
    }
  }

  function initKanbanDragDrop() {
    const kanban = document.querySelector('.kanban-cols');
    if (!kanban || kanban.dataset.dragInit) return;
    kanban.dataset.dragInit = '1';

    kanban.addEventListener('click', (e) => {
      if (wasDragged) { wasDragged = false; return; }
      const card = e.target.closest('.kcard');
      if (card) abrirDetalhe(card.dataset.id);
    });

    if (isTouchDevice) return;

    kanban.addEventListener('dragstart', (e) => {
      const card = e.target.closest('.kcard');
      if (!card) return;
      draggedId = card.dataset.id;
      draggedStatus = card.dataset.status;
      wasDragged = false;
      card.classList.add('kcard-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', draggedId);
    });

    kanban.addEventListener('drag', () => { wasDragged = true; });

    kanban.addEventListener('dragend', (e) => {
      const card = e.target.closest('.kcard');
      if (card) card.classList.remove('kcard-dragging');
      limparDragOver();
      setTimeout(() => { draggedId = null; draggedStatus = null; }, 0);
    });

    kanban.addEventListener('dragover', (e) => {
      const col = e.target.closest('.kcol-body');
      if (!col || !draggedId) return;
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      document.querySelectorAll('.kcol-body').forEach(el => {
        el.classList.toggle('drag-over', el === col);
        el.classList.remove('drag-bloqueado');
      });
    });

    kanban.addEventListener('drop', async (e) => {
      e.preventDefault();
      const col = e.target.closest('.kcol-body');
      if (!col || !draggedId) return;
      limparDragOver();
      const novoStatus = col.dataset.status;
      if (novoStatus === draggedStatus) return;
      await moverCorrecao(draggedId, novoStatus, col);
    });
  }

  window.iniciarArrasto = function(event, id) {
    draggedId = String(id);
    const card = event.target.closest('.kcard');
    if (card) draggedStatus = card.dataset.status;
  };

  initKanbanDragDrop();
  carregarCorrecoes();
  carregarTecnicos(null, 'filtro-tecnico');
  window.carregarTecnicosOsModal?.();
</script>
@endsection
