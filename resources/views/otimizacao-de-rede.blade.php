@extends('layouts.app')

@section('title', 'Otimização de Rede — Planner Telecom')
@section('page-title', 'Otimização de Rede')
@section('btn-label', 'Nova otimização')

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
  .detail-enter { animation: conteudoEntrada 0.42s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
  .btn-modal { padding: 0 16px; height: 36px; border-radius: var(--radius-sm); font-size: 13px; cursor: pointer; font-family: inherit; transition: background 0.15s, transform 0.15s, border-color 0.15s; }
  .btn-modal:active { transform: scale(0.97); }
  .btn-modal-ghost { border: 1px solid var(--gray-200); background: transparent; color: var(--gray-500); }
  .btn-modal-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }
  .btn-modal-primary { border: none; background: #166ac4; color: #fff; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
  .btn-modal-primary:hover { background: #0d5aaa; }
  .btn-modal-danger { border: 1px solid #fecaca; background: #fff; color: #dc2626; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
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
  .confirm-excluir-foot { display: flex; justify-content: flex-end; gap: 8px; }
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
  .prioridade-wrap { display: flex; gap: 8px; }
  .btn-prioridade { flex: 1; padding: 8px 0; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; cursor: pointer; font-family: inherit; transition: filter 0.15s, transform 0.1s; border-width: 1px; border-style: solid; }
  .btn-prioridade:active { transform: scale(0.97); }
  .btn-prio-baixa  { border-color: #86efac; background: #f0fdf4; color: #166534; }
  .btn-prio-media  { border-color: var(--amber); background: var(--amber-bg); color: var(--amber-text); }
  .btn-prio-alta   { border-color: #fca5a5; background: var(--red-bg); color: var(--red-text); }
  .btn-prio-ativo  { border-width: 2px; }

  /* ── SELETOR DE TÉCNICOS (igual troca de poste / rompimento) ── */
  .tecnicos-wrap {
    position: relative;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    padding: 6px 34px 6px 10px;
    min-height: 38px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    background: var(--white);
    transition: border-color 0.15s, box-shadow 0.15s;
    box-sizing: border-box;
  }
  .tecnicos-wrap:hover { border-color: var(--gray-300); }
  .tecnicos-wrap.open {
    border-color: #166ac4;
    box-shadow: 0 0 0 3px rgba(22,106,196,0.12);
    z-index: 60;
  }
  .tecnicos-wrap.disabled { cursor: not-allowed; background: var(--gray-50); }
  .tecnicos-chevron {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: var(--gray-400);
    pointer-events: none;
    transition: transform 0.2s ease, color 0.15s;
  }
  .tecnicos-wrap.open .tecnicos-chevron { transform: translateY(-50%) rotate(180deg); color: #166ac4; }
  .tecnicos-tags { display: contents; }
  .tec-tag {
    background: #e8f2fc;
    color: #0c447c;
    font-size: 11px;
    font-weight: 500;
    padding: 3px 8px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    max-width: 100%;
  }
  .tec-tag-remove {
    font-size: 10px;
    cursor: pointer;
    line-height: 1;
    opacity: 0.7;
    transition: opacity 0.15s;
  }
  .tec-tag-remove:hover { opacity: 1; }
  #input-tec {
    border: none;
    outline: none;
    font-size: 12px;
    background: transparent;
    flex: 1;
    min-width: 80px;
    box-shadow: none;
    height: 24px;
    font-family: inherit;
    cursor: pointer;
    color: var(--gray-950);
    padding: 0;
  }
  #input-tec::placeholder { color: var(--gray-400); }
  .dropdown-tecnicos {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    z-index: 300;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  }
  .tecnicos-wrap.open .dropdown-tecnicos { display: block; }
  .dropdown-tec-item {
    padding: 9px 12px;
    cursor: pointer;
    font-size: 13px;
    color: var(--gray-950);
    transition: background 0.12s;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  .dropdown-tec-item:hover { background: var(--gray-50); }
  .dropdown-tec-item.selected {
    color: var(--gray-400);
    cursor: default;
    background: transparent;
  }
  .dropdown-tec-item.selected:hover { background: transparent; }
  .dropdown-tec-empty {
    padding: 10px 12px;
    font-size: 13px;
    color: var(--gray-400);
  }
  .modal-form .os-field:has(.tecnicos-wrap) { overflow: visible; position: relative; z-index: 1; }
  .modal-form .os-field:has(.tecnicos-wrap.open) { z-index: 70; }

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
<x-modal id="modal-overlay" titulo="Nova otimização de rede" subtitulo="Preencha os dados da otimização">

  <div class="modal-form">

    <div class="os-field">
      <label class="os-label">Nome</label>
      <input type="text" id="input-nome" placeholder="Ex: Otimização rede GVA1210" class="os-input"/>
    </div>

    <div class="os-field">
      <label class="os-label">Descrição</label>
      <textarea id="input-descricao" rows="3" placeholder="Descreva a otimização..." class="os-input"
        style="resize:vertical;min-height:72px"></textarea>
    </div>

    <div class="os-field">
      <label class="os-label">Data de vencimento</label>
      <input type="date" id="input-prazo" class="os-input"/>
    </div>

    <div class="os-field">
      <label class="os-label">Região</label>
      <select id="input-regiao" onchange="carregarTecnicos(this.value)" class="os-input">
        <option value="">Selecione...</option>
        <option>Goval</option>
        <option>Vale do Aço</option>
        <option>Caratinga</option>
        <option>Teste</option>
      </select>
    </div>

    <div class="detail-grid-2">
      <div class="os-field">
        <label class="os-label">Técnico(s) responsável(is)</label>
        <div id="tecnicos-wrap" class="tecnicos-wrap disabled" onclick="toggleDropdownTecnicos(event)">
          <span id="tecnicos-tags"></span>
          <input id="input-tec" type="text" placeholder="Selecione uma região primeiro..." readonly tabindex="-1"/>
          <i class="ti ti-chevron-down tecnicos-chevron"></i>
          <div id="dropdown-tecnicos" class="dropdown-tecnicos" onclick="event.stopPropagation()"></div>
        </div>
      </div>
      <div class="os-field">
        <label class="os-label">Número da OS (Hubsoft)</label>
        <input type="text" id="input-numero-os" inputmode="numeric" placeholder="Ex: 123456" class="os-input"/>
      </div>
    </div>

    <div class="os-field">
      <label class="os-label">Prioridade</label>
      <div class="prioridade-wrap">
        <button type="button" onclick="selecionarPrioridade(this,'Baixa')" class="btn-prioridade btn-prio-baixa">Baixa</button>
        <button type="button" onclick="selecionarPrioridade(this,'Média')" class="btn-prioridade btn-prio-media btn-prio-ativo">Média ✓</button>
        <button type="button" onclick="selecionarPrioridade(this,'Alta')" class="btn-prioridade btn-prio-alta">Alta</button>
      </div>
    </div>

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

  </div>

  <x-slot name="footer">
    <button onclick="fecharModal()" class="btn-modal btn-modal-ghost">Cancelar</button>
    <button onclick="criarOtimizacao()" class="btn-modal btn-modal-primary">
      <i class="ti ti-wifi" style="font-size:14px"></i> Criar otimização
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
      <span>Nenhuma OS vinculada a esta otimização</span>
    </div>
  </div>

  <x-slot name="footer">
    <div class="modal-foot-inner">
      <button type="button" onclick="abrirConfirmacaoExclusao()" id="btn-excluir" class="btn-modal btn-modal-danger" title="Excluir esta otimização">
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

<!-- Confirmação de exclusão da tarefa -->
<div id="confirm-excluir-overlay" class="confirm-excluir-overlay" role="dialog" aria-modal="true" aria-labelledby="confirm-excluir-title">
  <div class="confirm-excluir-box">
    <div class="confirm-excluir-icon"><i class="ti ti-alert-triangle"></i></div>
    <p class="confirm-excluir-title" id="confirm-excluir-title">Excluir otimização?</p>
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
<x-modal
  id="modal-os-overlay"
  titulo="Nova Ordem de Serviço"
  titulo-id="os-modal-titulo"
  subtitulo-id="os-modal-sub"
  fechar="fecharNovaOS()">

  <div class="os-field">
    <label class="os-label">Tipo de serviço</label>
    <input type="text" id="os-input-tipo" class="os-input"
      placeholder="Ex: OTIMIZAÇÃO DE REDE"
      oninput="this.value = this.value.toUpperCase()"/>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div class="os-field">
      <label class="os-label">Técnico responsável</label>
      <select id="os-input-tecnico" class="os-input">
        <option value="">Selecione...</option>
      </select>
    </div>
    <div class="os-field">
      <label class="os-label">Status</label>
      <select id="os-input-status" class="os-input">
        <option value="Aberta">Aberta</option>
        <option value="Em andamento">Em andamento</option>
        <option value="Finalizada">Finalizada</option>
      </select>
    </div>
  </div>

  <x-slot name="footer">
    <button type="button" onclick="fecharNovaOS()" class="btn-modal btn-modal-ghost">Cancelar</button>
    <button type="button" class="btn-modal btn-modal-primary" id="os-btn-salvar" onclick="salvarOs()">
      <i class="ti ti-clipboard-check" style="font-size:14px" id="os-btn-icon"></i>
      <span id="os-btn-label">Criar OS</span>
    </button>
  </x-slot>

</x-modal>

<!-- FILTROS -->
<div class="card filtros-card">
  <div class="filtros-bar">

    <div class="filtro-search">
      <i class="ti ti-search filtro-search-icon"></i>
      <input type="text" id="filtro-taskcode" placeholder="ID da tarefa..."
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
    <span class="card-title">Kanban de Otimização de Rede</span>
    <span class="card-action">total: <span id="total-otimizacoes">0</span></span>
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
  let tecnicosSelecionados = [];
  let tecnicosSelecionadosEdicao = [];
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

  async function aplicarFiltros() {
    const filtros = {
      regiao:     document.getElementById('filtro-regiao').value,
      tecnico:    document.getElementById('filtro-tecnico').value,
      dataInicio: document.getElementById('filtro-data-inicio').value,
      dataFim:    document.getElementById('filtro-data-fim').value,
      taskCode:   document.getElementById('filtro-taskcode').value.toUpperCase().trim(),
    };
    carregarOtimizacoes(filtros);
  }

  async function limparFiltros() {
    document.getElementById('filtro-regiao').value = '';
    document.getElementById('filtro-tecnico').value = '';
    document.getElementById('filtro-data-inicio').value = '';
    document.getElementById('filtro-data-fim').value = '';
    document.getElementById('filtro-taskcode').value = '';
    carregarOtimizacoes();
  }

  // ─── MODAIS ───
  function limparFormularioOtimizacao() {
    document.getElementById('input-nome').value = '';
    document.getElementById('input-descricao').value = '';
    document.getElementById('input-prazo').value = '';
    document.getElementById('input-regiao').value = '';
    document.getElementById('input-numero-os').value = '';
    document.getElementById('input-localizacao-texto').value = '';
    document.getElementById('input-coordenadas').value = '';
    tecnicosDisponiveis = [];
    document.getElementById('dropdown-tecnicos').innerHTML = '';
    fecharDropdownTecnicos();
    document.getElementById('tecnicos-wrap')?.classList.add('disabled');
    const inputTec = document.getElementById('input-tec');
    if (inputTec) {
      inputTec.placeholder = 'Selecione uma região primeiro...';
      inputTec.style.display = 'block';
    }
    prioridadeSelecionada = 'Média';
    document.querySelectorAll('.btn-prioridade').forEach(b => {
      b.textContent = b.textContent.replace(' ✓', '');
      b.style.borderWidth = '1px';
    });
    const btnMedia = document.querySelector('.btn-prio-media');
    if (btnMedia) {
      btnMedia.style.borderWidth = '2px';
      btnMedia.textContent = 'Média ✓';
    }
  }

  window.abrirModal = function() {
    tecnicosSelecionados = [];
    limparFormularioOtimizacao();
    renderizarTags();
    document.getElementById('modal-overlay').classList.add('open');
  }

  function fecharModal() {
    document.getElementById('modal-overlay').classList.remove('open');
  }

  // ─── ORDENS DE SERVIÇO ───
  window.abrirNovaOS = function() {
    const trocaId = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (!trocaId) return;

    osEditandoId = null;
    document.getElementById('modal-os-overlay').classList.add('open');
    const regiao = document.getElementById('detalhe-conteudo').dataset.regiao || '';
    carregarTecnicos(regiao, 'os-input-tecnico');
    document.getElementById('os-modal-titulo').textContent = 'Nova Ordem de Serviço';
    document.getElementById('os-btn-icon').className = 'ti ti-clipboard-check';
    document.getElementById('os-btn-label').textContent = 'Criar OS';
    document.getElementById('os-input-tipo').value = '';
    document.getElementById('os-input-tecnico').value = '';
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
    document.getElementById('os-input-status').value = os.status || 'Aberta';

    const regiao = document.getElementById('detalhe-conteudo').dataset.regiao || '';
    carregarTecnicos(regiao, 'os-input-tecnico').then(() => {
      const tecnicoSelect = document.getElementById('os-input-tecnico');
      let encontrou = false;
      Array.from(tecnicoSelect.options).forEach(opt => {
        if (opt.value === os.responsavel || opt.text === os.responsavel) {
          opt.selected = true;
          encontrou = true;
        }
      });
      if (!encontrou && os.responsavel) {
        const opt = document.createElement('option');
        opt.value = os.responsavel;
        opt.text = os.responsavel;
        opt.selected = true;
        tecnicoSelect.appendChild(opt);
      }
    });

    document.getElementById('modal-os-overlay').classList.add('open');
  };

  window.fecharNovaOS = function() {
    osEditandoId = null;
    document.getElementById('modal-os-overlay').classList.remove('open');
  };

  document.getElementById('modal-os-overlay').addEventListener('click', function(e) {
    if (e.target === this) fecharNovaOS();
  });

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
      const trocaId = document.getElementById('detalhe-conteudo')?.dataset?.id;
      if (trocaId) carregarOS(trocaId);
    } else if (osDataMap[osId]) {
      osDataMap[osId].status = novoStatus;
    }
  };

  async function salvarOs() {
    const trocaId = document.getElementById('detalhe-conteudo')?.dataset?.id;
    const tipo = document.getElementById('os-input-tipo').value.trim();
    const tecnico = document.getElementById('os-input-tecnico').value;
    const status = document.getElementById('os-input-status').value;
    const token = localStorage.getItem('planner_token');

    if (!trocaId) return;
    if (!tipo) {
      alert('Informe o tipo de serviço.');
      return;
    }

    const btn = document.getElementById('os-btn-salvar');
    btn.disabled = true;

    try {
      if (osEditandoId) {
        const dados = {
          titulo: `OS — ${tipo}`,
          responsavel: tecnico,
          status,
        };
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
          carregarOS(trocaId);
          if (window.carregarOtimizacoes) window.carregarOtimizacoes();
        } else {
          const erro = await response.json();
          console.error('Erro ao atualizar OS:', erro.message);
          alert(erro.message || 'Erro ao atualizar OS.');
        }
      } else {
        const dados = {
          titulo: `OS — ${tipo}`,
          responsavel: tecnico,
          status,
          categoria: 'ordem-servico',
          parent_task_id: trocaId,
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
          fecharNovaOS();
          carregarOS(trocaId);
          if (window.carregarOtimizacoes) window.carregarOtimizacoes();
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

  async function carregarOS(trocaId) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/otimizacao-rede/${trocaId}/os`, {
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
  // A tarefa pai (otimização) permanece; apenas a OS vinculada é removida.

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
      if (parentId) carregarOS(parentId);
      if (window.carregarOtimizacoes) window.carregarOtimizacoes();
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
          <span>Nenhuma OS vinculada a esta otimização</span>
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
        <div class="os-card" style="border-left-color:${border}">
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
  window.resetBotoesDetalhe = resetBotoesDetalhe;

  function fecharDetalhe() {
    fecharConfirmacaoExclusao();
    fecharConfirmacaoExclusaoOs();
    document.getElementById('detalhe-overlay').classList.remove('open');
    trocarAba('detalhes');
    resetBotoesDetalhe();
    tecnicosSelecionadosEdicao = [];
  }

  function cancelarEdicao() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    tecnicosSelecionadosEdicao = [];
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
    const btn = document.getElementById('btn-confirmar-excluir');
    btn.disabled = true;

    try {
      const response = await fetch(`/api/otimizacao-rede/${id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        const erro = await response.json();
        throw new Error(erro.message || 'Erro ao excluir otimização.');
      }

      fecharConfirmacaoExclusao();
      fecharDetalhe();
      window.carregarOtimizacoes();
    } catch (err) {
      console.error('Erro ao excluir otimização:', err.message);
      alert(err.message || 'Erro ao excluir otimização.');
    } finally {
      btn.disabled = false;
    }
  }

  document.getElementById('modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
  });
  document.getElementById('detalhe-overlay').addEventListener('click', function(e) {
    if (e.target === this) fecharDetalhe();
  });
  document.getElementById('confirm-excluir-overlay')?.addEventListener('click', function(e) {
    if (e.target === this) fecharConfirmacaoExclusao();
  });
  document.getElementById('confirm-excluir-os-overlay')?.addEventListener('click', function(e) {
    if (e.target === this) fecharConfirmacaoExclusaoOs();
  });
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
    fecharDetalhe();
    fecharModal();
  });

  // ─── PRIORIDADE ───
  function selecionarPrioridade(btn, nivel) {
    document.querySelectorAll('.btn-prioridade').forEach(b => {
      b.textContent = b.textContent.replace(' ✓', '');
      b.style.borderWidth = '1px';
    });
    btn.style.borderWidth = '2px';
    btn.textContent += ' ✓';
    prioridadeSelecionada = nivel;
  }

  // ─── TÉCNICOS (MODAL CRIAR) ───
  let tecnicosDisponiveis = [];

  function escTec(valor) {
    return String(valor ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function atualizarEstadoSeletorTecnicos(regiao) {
    const wrap = document.getElementById('tecnicos-wrap');
    const inputTec = document.getElementById('input-tec');
    if (!wrap || !inputTec) return;

    if (!regiao) {
      wrap.classList.add('disabled');
      wrap.classList.remove('open');
      inputTec.placeholder = 'Selecione uma região primeiro...';
      return;
    }

    wrap.classList.remove('disabled');
    inputTec.placeholder = tecnicosDisponiveis.length ? 'Selecionar técnico...' : 'Nenhum técnico nessa região';
  }

  function renderizarDropdownTecnicos() {
    const el = document.getElementById('dropdown-tecnicos');
    if (!el) return;

    if (!tecnicosDisponiveis.length) {
      el.innerHTML = '<div class="dropdown-tec-empty">Nenhum técnico nessa região</div>';
      return;
    }

    el.innerHTML = tecnicosDisponiveis.map(t => {
      const selecionado = tecnicosSelecionados.some(s => s.id === t.id);
      const nomeJs = String(t.nome).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
      return `
        <div class="dropdown-tec-item${selecionado ? ' selected' : ''}"
          ${selecionado ? '' : `onclick="selecionarTecnico(${t.id}, '${nomeJs}')"`}>
          <span>${escTec(t.nome)}</span>
          ${selecionado ? '<i class="ti ti-check" style="font-size:13px;color:#166ac4"></i>' : ''}
        </div>`;
    }).join('');
  }

  async function carregarTecnicos(regiao, destino = 'dropdown-tecnicos') {
    if (!regiao && destino === 'dropdown-tecnicos') {
      tecnicosDisponiveis = [];
      atualizarEstadoSeletorTecnicos('');
      renderizarDropdownTecnicos();
      return;
    }

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
    if (regiao && destino === 'os-input-tecnico' && tecnicos.length === 0) {
      tecnicos = await buscarTecnicos(null);
    }

    const el = document.getElementById(destino);
    if (!el) return;

    if (el.tagName === 'SELECT') {
      const placeholder = destino === 'filtro-tecnico'
        ? '<option value="">Todos os técnicos</option>'
        : '<option value="">Selecione...</option>';
      el.innerHTML = placeholder
        + tecnicos.map(t => `<option value="${escTec(t.nome)}">${escTec(t.nome)}</option>`).join('');
      return;
    }

    tecnicosDisponiveis = tecnicos;
    atualizarEstadoSeletorTecnicos(regiao);
    renderizarDropdownTecnicos();
  }

  function fecharDropdownTecnicos() {
    document.getElementById('tecnicos-wrap')?.classList.remove('open');
  }

  function toggleDropdownTecnicos(event) {
    if (event) event.stopPropagation();
    const wrap = document.getElementById('tecnicos-wrap');
    const regiao = document.getElementById('input-regiao')?.value;
    if (!wrap || wrap.classList.contains('disabled') || !regiao) return;

    const abrir = !wrap.classList.contains('open');
    fecharDropdownTecnicos();
    if (abrir) {
      wrap.classList.add('open');
      renderizarDropdownTecnicos();
    }
  }

  function selecionarTecnico(id, nome) {
    if (tecnicosSelecionados.find(t => t.id === id)) return;
    tecnicosSelecionados.push({ id, nome });
    renderizarTags();
    renderizarDropdownTecnicos();
  }

  function removerTecnico(id, event) {
    if (event) event.stopPropagation();
    tecnicosSelecionados = tecnicosSelecionados.filter(t => t.id !== id);
    renderizarTags();
    renderizarDropdownTecnicos();
  }

  function renderizarTags() {
    const container = document.getElementById('tecnicos-tags');
    const inputTec = document.getElementById('input-tec');
    if (!container || !inputTec) return;

    container.innerHTML = tecnicosSelecionados.map(t => `
      <span class="tec-tag">
        ${escTec(t.nome)}
        <i class="ti ti-x tec-tag-remove" onclick="removerTecnico(${t.id}, event)"></i>
      </span>`).join('');

    inputTec.style.display = tecnicosSelecionados.length ? 'none' : 'block';
  }

  window.toggleDropdownTecnicos = toggleDropdownTecnicos;
  window.selecionarTecnico = selecionarTecnico;
  window.removerTecnico = removerTecnico;

  // ─── TÉCNICOS (MODAL EDIÇÃO) ───
  async function inicializarSeletorTecnicosEdicao(el, valorAtual) {
    tecnicosSelecionadosEdicao = [];
    el.innerHTML = `
      <div id="edicao-tec-wrap" style="position:relative">
        <div id="edicao-tec-tags" onclick="toggleDropdownTecnicosEdicao()"
          style="display:flex;flex-wrap:wrap;gap:4px;min-height:28px;align-items:center;cursor:pointer;
                 border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:4px 8px;background:var(--white)">
          <span id="edicao-tec-placeholder" style="color:var(--gray-400);font-size:13px">Carregando...</span>
        </div>
        <div id="dropdown-tec-edicao"
          style="display:none;position:absolute;top:100%;left:0;right:0;margin-top:4px;background:var(--white);
                 border:1px solid var(--gray-200);border-radius:var(--radius-sm);z-index:200;
                 max-height:180px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,0.1)">
        </div>
      </div>`;
    const token = localStorage.getItem('planner_token');
    const res = await fetch('/api/tecnicos', { headers: { 'Authorization': 'Bearer ' + token } });
    const tecnicos = await res.json();
    if (valorAtual) {
      valorAtual.split(',').map(n => n.trim()).filter(Boolean).forEach(nome => {
        const tec = tecnicos.find(t => t.nome === nome);
        if (tec) tecnicosSelecionadosEdicao.push({ id: tec.id, nome: tec.nome });
      });
    }
    document.getElementById('dropdown-tec-edicao').innerHTML = tecnicos.map(t => `
      <div onclick="adicionarTecnicoEdicao(${t.id}, '${t.nome.replace(/'/g, "\\'")}')"
        style="padding:8px 12px;cursor:pointer;font-size:13px;color:var(--gray-950)"
        onmouseover="this.style.background='var(--gray-50)'"
        onmouseout="this.style.background='transparent'">${t.nome}</div>`).join('');
    renderizarTagsEdicao();
  }

  function toggleDropdownTecnicosEdicao() {
    const d = document.getElementById('dropdown-tec-edicao');
    if (!d) return;
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
  }

  function adicionarTecnicoEdicao(id, nome) {
    if (tecnicosSelecionadosEdicao.find(t => t.id === id)) return;
    tecnicosSelecionadosEdicao.push({ id, nome });
    renderizarTagsEdicao();
    document.getElementById('dropdown-tec-edicao').style.display = 'none';
  }

  function removerTecnicoEdicao(id) {
    tecnicosSelecionadosEdicao = tecnicosSelecionadosEdicao.filter(t => t.id !== id);
    renderizarTagsEdicao();
  }

  function renderizarTagsEdicao() {
    const container = document.getElementById('edicao-tec-tags');
    if (!container) return;
    const vazio = tecnicosSelecionadosEdicao.length === 0;
    container.innerHTML = tecnicosSelecionadosEdicao.map(t => `
      <span style="background:#e8f2fc;color:#0c447c;font-size:11px;font-weight:500;padding:3px 8px;border-radius:20px;display:inline-flex;align-items:center;gap:4px">
        ${t.nome}
        <i class="ti ti-x" style="font-size:10px;cursor:pointer" onclick="event.stopPropagation();removerTecnicoEdicao(${t.id})"></i>
      </span>`).join('')
      + `<span id="edicao-tec-placeholder" style="color:var(--gray-400);font-size:13px;${vazio ? '' : 'display:none'}">Selecionar técnico...</span>`;
  }

  document.addEventListener('click', function(e) {
    const wrap = document.getElementById('tecnicos-wrap');
    if (wrap && !wrap.contains(e.target)) fecharDropdownTecnicos();
    const wrapEd = document.getElementById('edicao-tec-wrap');
    const dropdownEd = document.getElementById('dropdown-tec-edicao');
    if (wrapEd && dropdownEd && !wrapEd.contains(e.target)) dropdownEd.style.display = 'none';
  });

  // ─── EDIÇÃO ───
  function ativarEdicao() {
    document.getElementById('btn-excluir').style.display = 'none';
    document.getElementById('btn-editar').style.display = 'none';
    document.getElementById('btn-salvar').style.display = 'flex';
    document.getElementById('btn-cancelar').style.display = 'flex';

    const campos = [
      { id: 'campo-titulo',          tipo: 'text' },
      { id: 'campo-cto',             tipo: 'text' },
      { id: 'campo-tipo',            tipo: 'select', opcoes: ['Fusão', 'Splitter', 'Cabo', 'Conector', 'Outro'] },
      { id: 'campo-regiao',          tipo: 'select', opcoes: ['Goval', 'Vale do Aço', 'Caratinga', 'Teste'] },
      { id: 'campo-tecnicos',        tipo: 'custom' },
      { id: 'campo-numero-os',       tipo: 'text' },
      { id: 'campo-localizacao-texto', tipo: 'text' },
      { id: 'campo-coordenadas',     tipo: 'text' },
      { id: 'campo-prioridade',      tipo: 'select', opcoes: ['Baixa', 'Média', 'Alta'] },
      { id: 'campo-status',          tipo: 'select', opcoes: ['Criada', 'Em andamento', 'Impedimento', 'Finalizada'] },
    ];

    const inputStyle = 'width:100%;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:4px 8px;font-size:13px;font-family:inherit;outline:none;background:var(--white)';

    campos.forEach(({ id, tipo, opcoes }) => {
      const el = document.getElementById(id);
      if (!el) return;
      const valorAtual = el.textContent.trim();
      const valor = valorAtual === '—' ? '' : valorAtual;

      if (tipo === 'custom') {
        inicializarSeletorTecnicosEdicao(el, valor);
        return;
      }
      if (tipo === 'select') {
        const optionsHtml = opcoes.map(op => `<option value="${op}" ${op === valor ? 'selected' : ''}>${op}</option>`).join('');
        el.innerHTML = `<select style="${inputStyle}">${optionsHtml}</select>`;
        return;
      }
      el.innerHTML = `<input type="text" value="${valor}" style="${inputStyle}"/>`;
    });
  }

  async function salvarEdicao() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (!id) return;
    const getVal = (selector) => document.querySelector(selector)?.value ?? '';

    const dados = {
      titulo:            getVal('#campo-titulo input'),
      cto:               getVal('#campo-cto input'),
      descricao:         getVal('#campo-tipo select'),
      regiao:            getVal('#campo-regiao select'),
      responsavel:       tecnicosSelecionadosEdicao.map(t => t.nome).join(', '),
      numero_os:         getVal('#campo-numero-os input'),
      localizacao_texto: document.querySelector('#campo-localizacao-texto input')?.value ?? document.getElementById('campo-localizacao-texto')?.textContent ?? '',
      coordenadas:       getVal('#campo-coordenadas input'),
      prioridade:        getVal('#campo-prioridade select'),
      status:            getVal('#campo-status select'),
    };

    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/otimizacao-rede/${id}`, {
      method: 'PUT',
      headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(dados)
    });

    if (response.ok) {
      fecharDetalhe();
      window.carregarOtimizacoes();
    } else {
      const erro = await response.json();
      console.error('Erro ao salvar:', erro.message);
      alert(erro.message || 'Erro ao salvar alterações.');
    }
  }

  window.ativarEdicao = ativarEdicao;
  window.salvarEdicao = salvarEdicao;
  window.cancelarEdicao = cancelarEdicao;
  window.fecharDetalhe = fecharDetalhe;
  window.abrirConfirmacaoExclusao = abrirConfirmacaoExclusao;
  window.fecharConfirmacaoExclusao = fecharConfirmacaoExclusao;
  window.confirmarExclusaoTarefa = confirmarExclusaoTarefa;
  window.fecharConfirmacaoExclusaoOs = fecharConfirmacaoExclusaoOs;

  async function criarOtimizacao() {
    const nome = document.getElementById('input-nome').value.trim();
    const descricao = document.getElementById('input-descricao').value.trim();
    const prazo = document.getElementById('input-prazo').value;
    const regiao = document.getElementById('input-regiao').value;
    const numeroOs = document.getElementById('input-numero-os').value.trim();

    if (!nome) {
      alert('Informe o nome da otimização.');
      return;
    }
    if (!regiao) {
      alert('Selecione a região.');
      return;
    }

    const dados = {
      titulo:            nome,
      descricao:         descricao,
      prazo:             prazo || null,
      coordenadas:       document.getElementById('input-coordenadas').value.trim(),
      localizacao_texto: document.getElementById('input-localizacao-texto').value.trim(),
      regiao:            regiao,
      responsavel:       tecnicosSelecionados.map(t => t.nome).join(', '),
      prioridade:        prioridadeSelecionada,
      numero_os:         numeroOs,
      status:            'Criada',
    };

    const token = localStorage.getItem('planner_token');
    const response = await fetch('/api/otimizacao-rede', {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(dados)
    });

    const resultado = await response.json();
    if (response.ok) {
      fecharModal();
      window.carregarOtimizacoes();
    } else {
      alert(resultado.message || 'Erro ao criar otimização.');
      console.error('Erro ao criar otimização:', resultado);
    }
  }

  window.trocarAba = trocarAba;

  async function buscarEndereco(coordenada) {
    const coord = (coordenada ?? '').trim();
    if (!coord) return;

    const token = localStorage.getItem('planner_token');
    const response = await fetch(
      `/api/otimizacao-rede/coordenada?coordenada=${encodeURIComponent(coord)}`,
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
  const otimizacoesMap = {};
  const isTouchDevice = window.matchMedia('(pointer: coarse)').matches;

  const offsetMap = { 'Criada': 0, 'Em andamento': 0, 'Impedimento': 0, 'Finalizada': 0 };
  const limitMap  = { 'Criada': 10, 'Em andamento': 10, 'Impedimento': 10, 'Finalizada': 50 };
  const colIdMap  = { 'Criada': 'criada', 'Em andamento': 'andamento', 'Impedimento': 'impedimento', 'Finalizada': 'finalizada' };

  async function carregarMais(status) {
    const limit = limitMap[status];
    offsetMap[status] += limit;
    const novos = await buscarColuna(status, limit, offsetMap[status]);
    const colId = colIdMap[status];
    const col = document.getElementById(`col-${colId}`);
    novos.forEach(r => { col.insertAdjacentHTML('beforeend', renderCard(r)); otimizacoesMap[r.id] = r; });
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
    const mapa = { 'Criada':'d-blue', 'Em andamento':'d-amber', 'Impedimento':'d-red', 'Finalizada':'d-green' };
    const dot = mapa[status] || 'd-blue';
    return `<span class="badge" style="display:inline-flex;align-items:center;gap:5px;background:var(--gray-100);color:var(--gray-700)"><span class="dot ${dot}"></span>${esc(status || '—')}</span>`;
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

  // ─── CARREGAR OTIMIZAÇÕES ───
  async function buscarColuna(status, limit, offset = 0, filtros = {}) {
    const token = localStorage.getItem('planner_token');
    const params = new URLSearchParams({ status, limit, offset, ...filtros });
    const response = await fetch(`/api/otimizacao-rede?${params}`, {
      headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    });
    const data = await response.json();
    return data.otimizacaoDeRede || [];
  }

  async function carregarOtimizacoes(filtros = {}) {
    Object.keys(offsetMap).forEach(k => offsetMap[k] = 0);
    const [criadas, andamento, impedimento, finalizadas] = await Promise.all([
      buscarColuna('Criada', 10, 0, filtros),
      buscarColuna('Em andamento', 10, 0, filtros),
      buscarColuna('Impedimento', 10, 0, filtros),
      buscarColuna('Finalizada', 50, 0, filtros),
    ]);

    const todos = [...criadas, ...andamento, ...impedimento, ...finalizadas];
    Object.keys(otimizacoesMap).forEach(k => delete otimizacoesMap[k]);
    todos.forEach(r => { otimizacoesMap[r.id] = r; });

    document.getElementById('col-criada').innerHTML      = criadas.map(renderCard).join('');
    document.getElementById('col-andamento').innerHTML   = andamento.map(renderCard).join('');
    document.getElementById('col-impedimento').innerHTML = impedimento.map(renderCard).join('');
    document.getElementById('col-finalizada').innerHTML  = finalizadas.map(renderCard).join('');
    document.getElementById('count-criada').textContent      = criadas.length;
    document.getElementById('count-andamento').textContent   = andamento.length;
    document.getElementById('count-impedimento').textContent = impedimento.length;
    document.getElementById('count-finalizada').textContent  = finalizadas.length;
    document.getElementById('total-otimizacoes').textContent = todos.length;

    document.getElementById('mais-criada').style.display      = criadas.length === 10 ? 'block' : 'none';
    document.getElementById('mais-andamento').style.display   = andamento.length === 10 ? 'block' : 'none';
    document.getElementById('mais-impedimento').style.display = impedimento.length === 10 ? 'block' : 'none';
    document.getElementById('mais-finalizada').style.display  = finalizadas.length === 50 ? 'block' : 'none';
  }
  window.carregarOtimizacoes = carregarOtimizacoes;

  // ─── RENDER CARD ───
  function renderCard(r) {
    const prioridadeClass = r.prioridade?.toLowerCase() === 'alta' ? 'b-alta'
      : r.prioridade?.toLowerCase() === 'baixa' ? 'b-baixa' : 'b-media';
    const regiaoClass = r.regiao && r.regiao.toLowerCase().includes('vale') ? 'b-regiao-va' : 'b-regiao-gv';
    return `
    <div class="kcard"
      data-id="${r.id}"
      data-status="${r.status}"
      draggable="true"
      ondragstart="iniciarArrasto(event, ${r.id})">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <span class="kcard-code" style="font-size:11px">${r.taskCode || 'S/C'}</span>
        <span class="badge ${prioridadeClass}">${r.prioridade || 'Média'}</span>
      </div>
      <div class="kcard-title">${esc(r.titulo)}</div>
      <div class="kcard-foot" style="margin-top:6px">
        ${r.cto ? `<span class="badge b-cat-otm">${esc(r.cto)}</span>` : ''}
        ${r.descricao ? `<span class="badge b-cat-gen">${esc(r.descricao)}</span>` : ''}
        <span class="badge ${regiaoClass}">${r.regiao || 'Sem região'}</span>
        ${r.responsavel ? `<span style="font-size:10px;color:var(--gray-400);margin-left:auto">${esc(r.responsavel)}</span>` : ''}
      </div>
    </div>`;
  }

  // ─── RENDER DETALHE ───
  function renderDetalhe(r) {
    document.getElementById('detalhe-titulo').textContent = r.titulo || 'Otimização de Rede';
    document.getElementById('detalhe-subtitulo').textContent = r.taskCode ? `Código: ${r.taskCode}` : '';

    document.getElementById('detalhe-conteudo').innerHTML = `
      <div style="display:flex;flex-direction:column;gap:16px" class="detail-enter">
        <div class="detail-badges">
          ${badgeStatus(r.status)}
          ${badgePrioridade(r.prioridade)}
          ${badgeRegiao(r.regiao)}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Título', esc(r.titulo), 1, 'campo-titulo')}
          ${campoDetalhe('CTO', esc(r.cto), 1, 'campo-cto')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Tipo de otimização', esc(r.descricao), 1, 'campo-tipo')}
          ${campoDetalhe('Região', esc(r.regiao), 1, 'campo-regiao')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Técnico(s) responsável(is)', esc(r.responsavel), 1, 'campo-tecnicos')}
          ${campoDetalhe('Número da OS (Hubsoft)', esc(r.numero_os), 1, 'campo-numero-os')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Endereço / Localização', esc(r.localizacao_texto), 1, 'campo-localizacao-texto')}
          ${campoDetalhe('Coordenadas', esc(r.coordenadas), 1, 'campo-coordenadas')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Prioridade', esc(r.prioridade), 1, 'campo-prioridade')}
          ${campoDetalhe('Status', esc(r.status), 1, 'campo-status')}
        </div>
        <div class="detail-grid-2">
          <div class="detail-field">
            <span class="detail-label">Código da tarefa</span>
            <div style="display:flex;align-items:center;gap:6px">
              <div class="detail-value" id="campo-taskcode" style="flex:1">${esc(r.taskCode) || '—'}</div>
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
    window.trocarAba('detalhes');
    if (typeof window.resetBotoesDetalhe === 'function') window.resetBotoesDetalhe();
    renderDetalheLoading();
    const token = localStorage.getItem('planner_token');
    try {
      const response = await fetch(`/api/otimizacao-rede/${id}`, {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
      });
      const data = await response.json();
      if (!response.ok) { renderDetalheErro(data.message || 'Não foi possível carregar.'); return; }
      renderDetalhe(data.otimizacaoDeRede || data);
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

  async function moverOtimizacao(id, novoStatus, colDestino) {
    const card = document.querySelector(`.kcard[data-id="${id}"]`);
    const colOrigem = card?.closest('.kcol-body');
    const statusAnterior = card?.dataset.status;
    if (card) { card.dataset.status = novoStatus; colDestino.appendChild(card); atualizarContadores(); }

    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/otimizacao-rede/${id}`, {
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
      await moverOtimizacao(draggedId, novoStatus, col);
    });
  }

  window.iniciarArrasto = function(event, id) {
    draggedId = String(id);
    const card = event.target.closest('.kcard');
    if (card) draggedStatus = card.dataset.status;
  };

  initKanbanDragDrop();
  carregarOtimizacoes();
  carregarTecnicos(null, 'filtro-tecnico');
  carregarTecnicos(null, 'os-input-tecnico');
</script>
@endsection
