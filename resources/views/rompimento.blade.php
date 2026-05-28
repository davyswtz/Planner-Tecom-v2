@extends('layouts.app')

@section('title', 'Rompimentos — Planner Telecom')
@section('page-title', 'Rompimentos')
@section('btn-label', 'Novo rompimento')

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
    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(0,0,0,0);
    visibility: hidden;
    pointer-events: none;
    transition: background 0.32s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.32s;
  }
  .modal-overlay.open {
    visibility: visible;
    pointer-events: auto;
    background: rgba(0,0,0,0.45);
  }
  .modal-box {
    background: var(--white);
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    width: 100%;
    max-width: 680px;
    overflow: hidden;
    max-height: calc(100vh - 32px);
    display: flex;
    flex-direction: column;
    opacity: 0;
    transform: scale(0.96) translateY(14px);
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
  .modal-os-overlay { position: fixed; inset: 0; z-index: 150; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(0,0,0,0); visibility: hidden; pointer-events: none; transition: background 0.28s cubic-bezier(0.16,1,0.3,1), visibility 0.28s; }
  .modal-os-overlay.open { visibility: visible; pointer-events: auto; background: rgba(0,0,0,0.55); }
  .modal-os-box { background: var(--white); border-radius: var(--radius); border: 1px solid var(--gray-200); width: 100%; max-width: 560px; overflow: hidden; max-height: calc(100vh - 32px); display: flex; flex-direction: column; opacity: 0; transform: scale(0.96) translateY(14px); transition: transform 0.34s cubic-bezier(0.16,1,0.3,1), opacity 0.34s cubic-bezier(0.16,1,0.3,1); will-change: transform, opacity; }
  .modal-os-overlay.open .modal-os-box { opacity: 1; transform: scale(1) translateY(0); }
  .os-field { display: flex; flex-direction: column; gap: 5px; }
  .os-label { font-size: 12px; font-weight: 500; color: var(--gray-500); }
  .os-input { width: 100%; height: 38px; border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 0 10px; font-size: 13px; font-family: inherit; outline: none; background: var(--white); color: var(--gray-950); transition: border-color 0.15s, box-shadow 0.15s; box-sizing: border-box; }
  .os-input:focus { border-color: #166ac4; box-shadow: 0 0 0 3px rgba(22,106,196,0.12); }
  @keyframes conteudoEntrada { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
  @media (max-width: 768px) {
    .detail-grid, .detail-grid-2 { grid-template-columns: 1fr; }
    .detail-field.span-2, .detail-field.span-3 { grid-column: span 1; }
  }
  .btn-kcol-toggle {
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--gray-400);
    font-size: 12px;
    padding: 2px 4px;
    border-radius: var(--radius-sm);
    display: inline-flex;
    align-items: center;
    line-height: 1;
    transition: color 0.15s, background 0.15s;
    flex-shrink: 0;
  }
  .btn-kcol-toggle:hover { color: var(--gray-700); background: var(--gray-100); }
  .btn-kcol-toggle i {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    display: block;
  }
  .kcol.collapsed .btn-kcol-toggle i { transform: rotate(-90deg); }
  .kcol-content-wrap {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 3000px;
    opacity: 1;
    transition: max-height 0.36s cubic-bezier(0.16, 1, 0.3, 1),
                opacity 0.26s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .kcol.collapsed .kcol-content-wrap {
    max-height: 0;
    opacity: 0;
    pointer-events: none;
  }
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
<x-modal id="modal-overlay" titulo="Novo rompimento" subtitulo="Preencha os dados do rompimento">

  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
    <div>
      <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Elemento(s)</label>
      <input type="text" id="input-cto" placeholder="Ex: GVA1210"
        oninput="this.value = this.value.toUpperCase(); buscarCTO(this.value)"
        style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none"/>
      <p style="font-size:11px;color:var(--gray-400);margin-top:4px">Coordenadas preenchidas automaticamente</p>
    </div>
    <div>
      <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Tipo de rompimento</label>
      <select id="input-tipo" style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none;background:var(--white)">
        <option value="">Selecione...</option>
        <option>Fibra cortada</option>
        <option>CTO offline</option>
        <option>Queda de sinal</option>
        <option>OLT offline</option>
        <option>Cabo subterrâneo</option>
      </select>
    </div>
    <div>
      <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Região</label>
      <select id="input-regiao" onchange="carregarTecnicos(this.value)"
        style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none;background:var(--white)">
        <option value="">Selecione...</option>
        <option>Goval</option>
        <option>Vale do Aço</option>
        <option>Caratinga</option>
        <option>Teste</option>
      </select>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div>
      <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Técnico(s) responsável(is)</label>
      <div id="tecnicos-wrap" style="position:relative;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:6px 10px;min-height:38px;display:flex;flex-wrap:wrap;gap:5px;align-items:center;cursor:text">
        <span id="tecnicos-tags"></span>
        <input id="input-tec" type="text" placeholder="Selecione uma região primeiro..." readonly
          style="border:none;outline:none;font-size:12px;background:transparent;flex:1;min-width:80px;box-shadow:none;height:24px;font-family:inherit;cursor:pointer"
          onclick="toggleDropdownTecnicos()"/>
        <div id="dropdown-tecnicos" style="display:none;position:absolute;top:100%;left:0;right:0;margin-top:4px;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-sm);z-index:200;max-height:180px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,0.1)"></div>
      </div>
    </div>
    <div>
      <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Cliente(s) afetado(s)</label>
      <input type="number" id="input-clientes" placeholder="0" min="0"
        style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none"/>
    </div>
  </div>

  <div>
    <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:8px">Prioridade</label>
    <div style="display:flex;gap:8px">
      <button onclick="selecionarPrioridade(this,'Baixa')" class="btn-prioridade" style="flex:1;padding:8px 0;border-radius:var(--radius-sm);border:1px solid #86efac;background:#f0fdf4;color:#166534;font-size:13px;font-weight:500;cursor:pointer">Baixa</button>
      <button onclick="selecionarPrioridade(this,'Média')" class="btn-prioridade" style="flex:1;padding:8px 0;border-radius:var(--radius-sm);border:2px solid var(--amber);background:var(--amber-bg);color:var(--amber-text);font-size:13px;font-weight:500;cursor:pointer">Média ✓</button>
      <button onclick="selecionarPrioridade(this,'Alta')" class="btn-prioridade" style="flex:1;padding:8px 0;border-radius:var(--radius-sm);border:1px solid #fca5a5;background:var(--red-bg);color:var(--red-text);font-size:13px;font-weight:500;cursor:pointer">Alta</button>
    </div>
  </div>

  <div>
    <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Número da OS (Hubsoft)</label>
    <input
      type="text"
      id="input-numero-hubsoft"
      inputmode="numeric"
      placeholder="Ex: 123456"
      style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none"
    />
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
    <div>
      <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Coordenadas</label>
      <input id="input-coords" type="text" placeholder="Preenchido pela CTO automaticamente" readonly
        style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none;background:var(--gray-50);color:var(--gray-500)"/>
    </div>
    <div>
      <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Endereço</label>
      <div id="endereco-box" style="border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:8px 10px;min-height:38px;font-size:13px;color:var(--gray-400);background:var(--gray-50)">
        Gerado pelas coordenadas...
      </div>
    </div>
  </div>

  <x-slot name="footer">
    <button onclick="fecharModal()" class="btn-modal btn-modal-ghost">Cancelar</button>
    <button onclick="criarRompimento()" class="btn-modal btn-modal-primary">
      <i class="ti ti-bolt" style="font-size:14px"></i> Criar rompimento
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
      <button class="btn-nova-os" onclick="abrirNovaOS()">
        <i class="ti ti-plus" style="font-size:13px"></i> Nova OS
      </button>
    </div>
    <div class="os-empty">
      <i class="ti ti-clipboard-off"></i>
      <span>Nenhuma OS vinculada a este rompimento</span>
    </div>
  </div>

  <x-slot name="footer">
    <button onclick="fecharDetalhe()" class="btn-modal btn-modal-ghost">Fechar</button>
    <button onclick="ativarEdicao()" id="btn-editar" class="btn-modal btn-modal-primary">
      <i class="ti ti-pencil"></i> Editar
    </button>
    <button onclick="salvarEdicao()" id="btn-salvar" class="btn-modal btn-modal-primary" style="display:none">
      <i class="ti ti-check" style="font-size:14px"></i> Salvar
    </button>
    <button onclick="cancelarEdicao()" id="btn-cancelar" class="btn-modal btn-modal-ghost" style="display:none">
      <i class="ti ti-x" style="font-size:14px"></i> Cancelar
    </button>
  </x-slot>

</x-modal>

<!-- MODAL NOVA OS -->
<x-modal
  id="modal-os-overlay"
  titulo="Nova Ordem de Serviço"
  titulo-id="os-modal-titulo"
  subtitulo-id="os-modal-sub"
  fechar="fecharNovaOS()">

  <div class="os-field">
    <label class="os-label">Tipo de serviço</label>
    <input type="text" id="os-input-tipo" class="os-input"
      placeholder="Ex: REPARO DE CABO"
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
        <option value="aberta">Aberta</option>
        <option value="em_andamento">Em andamento</option>
        <option value="finalizada">Finalizada</option>
      </select>
    </div>
  </div>

  <x-slot name="footer">
    <button onclick="fecharNovaOS()" class="btn-modal btn-modal-ghost">Cancelar</button>
    <button class="btn-modal btn-modal-primary" id="os-btn-salvar" onclick="salvarOs()">
      <i class="ti ti-clipboard-check" style="font-size:14px" id="os-btn-icon"></i>
      <span id="os-btn-label">Criar OS</span>
    </button>
  </x-slot>

</x-modal>

<!-- FILTROS -->
<div class="card" style="margin-bottom:12px">
  <div style="padding:12px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    
    <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:140px">
      <i class="ti ti-search" style="color:var(--gray-400);font-size:14px"></i>
      <input type="text" id="filtro-taskcode" placeholder="ID da tarefa..."
        oninput="aplicarFiltrosDebounce()"
        style=" text-transform:uppercase;border:none;outline:none;font-size:13px;font-family:inherit;background:transparent;width:100%;color:var(--gray-950)"/>
    </div>

    <div style="width:1px;height:20px;background:var(--gray-200)"></div>

    <select id="filtro-regiao" onchange="aplicarFiltros()"
      style="border:none;outline:none;font-size:13px;font-family:inherit;background:transparent;color:var(--gray-700);cursor:pointer">
      <option value="">Todas as regiões</option>
      <option>Goval</option>
      <option>Vale do Aço</option>
      <option>Caratinga</option>
      <option>Teste</option>
    </select>

    <div style="width:1px;height:20px;background:var(--gray-200)"></div>

    <select id="filtro-tecnico" onchange="aplicarFiltros()"
      style="border:none;outline:none;font-size:13px;font-family:inherit;background:transparent;color:var(--gray-700);cursor:pointer">
      <option value="">Todos os técnicos</option>
    </select>

    <div style="width:1px;height:20px;background:var(--gray-200)"></div>

    <div style="display:flex;align-items:center;gap:6px">
      <label style="font-size:12px;color:var(--gray-500)">De</label>
      <input type="date" id="filtro-data-inicio" onchange="aplicarFiltros()"
        style="border:none;outline:none;font-size:13px;font-family:inherit;background:transparent;color:var(--gray-700);cursor:pointer"/>
      <label style="font-size:12px;color:var(--gray-500)">Até</label>
      <input type="date" id="filtro-data-fim" onchange="aplicarFiltros()"
        style="border:none;outline:none;font-size:13px;font-family:inherit;background:transparent;color:var(--gray-700);cursor:pointer"/>
    </div>

    <div style="width:1px;height:20px;background:var(--gray-200)"></div>

    <button onclick="limparFiltros()"
      style="border:none;background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:4px">
      <i class="ti ti-x" style="font-size:12px"></i> Limpar
    </button>

  </div>
</div>


<!-- KANBAN -->
<div class="card" style="flex:1">
  <div class="card-header">
    <span class="card-title">Kanban de Rompimentos</span>
    <span class="card-action">total: <span id="total-rompimentos">0</span></span>
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
  let osDataMap = {};
  let osEditandoId = null;

  function debounce(func, delay = 500){
    let timeout;

    return function (...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), delay);
  }
}

const aplicarFiltrosDebounce = debounce(aplicarFiltros, 500);

  async function aplicarFiltros(){
    const filtros = {
      regiao: document.getElementById('filtro-regiao').value,
      tecnico: document.getElementById('filtro-tecnico').value,
      dataInicio: document.getElementById('filtro-data-inicio').value,
      dataFim: document.getElementById('filtro-data-fim').value,
      taskCode: document.getElementById('filtro-taskcode').value.toUpperCase().trim(),
    };
    carregarRompimentos(filtros);
  }

  async function limparFiltros(){
    document.getElementById('filtro-regiao').value = '';
    document.getElementById('filtro-tecnico').value = '';
    document.getElementById('filtro-data-inicio').value = '';
    document.getElementById('filtro-data-fim').value = '';
    document.getElementById('filtro-taskcode').value = '';
    carregarRompimentos();
  }


  // ─── MODAIS ───
  window.abrirModal = function() {
    tecnicosSelecionados = [];
    renderizarTags();
    document.getElementById('modal-overlay').classList.add('open');
  }

  function fecharModal() {
    document.getElementById('modal-overlay').classList.remove('open');
  }

  // ─── MODAL NOVA OS ───
  function abrirNovaOS() {
    osEditandoId = null;
    document.getElementById('modal-os-overlay').classList.add('open');
    const regiaoRompimento = document.getElementById('detalhe-conteudo').dataset.regiao || '';
    carregarTecnicos(regiaoRompimento, 'os-input-tecnico');
    document.getElementById('os-modal-titulo').textContent = 'Nova Ordem de Serviço';
    document.getElementById('os-btn-icon').className = 'ti ti-clipboard-check';
    document.getElementById('os-btn-label').textContent = 'Criar OS';
    document.getElementById('os-input-tipo').value = '';
    document.getElementById('os-input-tecnico').value = '';
    document.getElementById('os-input-status').value = 'aberta';
    
  }

  function editarOs(id) {
    const os = osDataMap[id];
    if (!os) return;

    osEditandoId = id;
    document.getElementById('os-modal-titulo').textContent = 'Editar Ordem de Serviço';
    document.getElementById('os-btn-icon').className = 'ti ti-check';
    document.getElementById('os-btn-label').textContent = 'Salvar alterações';

    const tipoValue = (os.titulo || '').replace(/^OS\s*[—\-]\s*/i, '');
    document.getElementById('os-input-tipo').value = tipoValue;
    document.getElementById('os-input-status').value = os.status || 'aberta';

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

    document.getElementById('modal-os-overlay').classList.add('open');
  }

  window.alterarStatusOS = async function(osId, novoStatus) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/op-tasks/${osId}`, {
      method: 'PUT',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status: novoStatus })
    });

    if(!response.ok) {
      const erro = await response.json();
      console.error("Erro ao atualizar o status da OS:", erro.message);
    }
  }

  function fecharNovaOS() {
    osEditandoId = null;
    document.getElementById('modal-os-overlay').classList.remove('open');
  }

  document.getElementById('modal-os-overlay').addEventListener('click', function(e) {
    if (e.target === this) fecharNovaOS();
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharNovaOS();
  }, { capture: false });

  function trocarAba(aba) {
    document.getElementById('detalhe-tab-detalhes').style.display = aba === 'detalhes' ? 'block' : 'none';
    document.getElementById('detalhe-tab-os').style.display = aba === 'os' ? 'block' : 'none';
    document.getElementById('tab-btn-detalhes').classList.toggle('active', aba === 'detalhes');
    document.getElementById('tab-btn-os').classList.toggle('active', aba === 'os');

    if (aba === 'os') {
        const id = document.getElementById('detalhe-conteudo').dataset.id;
        carregarOS(id);
    }
}

  function fecharDetalhe() {
    document.getElementById('detalhe-overlay').classList.remove('open');
    trocarAba('detalhes');
    document.getElementById('btn-editar').style.display = '';
    document.getElementById('btn-salvar').style.display = 'none';
    document.getElementById('btn-cancelar').style.display = 'none';
    tecnicosSelecionadosEdicao = [];
  }

  function cancelarEdicao() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    tecnicosSelecionadosEdicao = [];
    document.getElementById('btn-editar').style.display = '';
    document.getElementById('btn-salvar').style.display = 'none';
    document.getElementById('btn-cancelar').style.display = 'none';
    if (id) window.abrirDetalhe(id);
  }

  document.getElementById('modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
  });

  document.getElementById('detalhe-overlay').addEventListener('click', function(e) {
    if (e.target === this) fecharDetalhe();
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { fecharDetalhe(); fecharModal(); }
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

  // ─── CTOs ───
  const CTO_SOURCES = [
    '/json/cto-gv-viabilidade.json',
    '/json/cto-ipatinga-viabilidade.json',
  ];
  let CTOs = [];

  async function carregarCTOs() {
    for (const url of CTO_SOURCES) {
      try {
        const res = await fetch(url);
        const data = await res.json();
        CTOs = CTOs.concat(data);
      } catch (e) {
        console.warn('Erro ao carregar CTOs de:', url);
      }
    }
    console.log(`Total de CTOs carregadas: ${CTOs.length}`);
  }

  function setField(id, texto) {
    const el = document.getElementById(id);
    if (!el) return;
    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.value = texto;
    else el.textContent = texto;
  }

  function buscarCTO(valor, campoCoords = 'input-coords', campoEndereco = 'endereco-box') {
    const termo = valor.trim().toUpperCase();
    if (termo.length < 3) {
      const elCoords = document.getElementById(campoCoords);
      if (elCoords) elCoords.value = '';
      setField(campoEndereco, 'Gerado pelas coordenadas...');
      return;
    }
    const encontrada = CTOs.find(cto => cto.nome && cto.nome.toUpperCase() === termo);
    if (encontrada) {
      const elCoords = document.getElementById(campoCoords);
      if (elCoords) elCoords.value = `${encontrada.lat}, ${encontrada.lng}`;
      setField(campoEndereco, 'Buscando endereço...');
      buscarEndereco(encontrada.lat, encontrada.lng, campoEndereco);
    } else {
      const elCoords = document.getElementById(campoCoords);
      if (elCoords) elCoords.value = '';
      setField(campoEndereco, 'CTO não encontrada — preencha manualmente');
    }
  }

  async function buscarEndereco(lat, lng, campoEndereco = 'endereco-box') {
    try {
      const res = await fetch(
        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`,
        { headers: { 'Accept-Language': 'pt-BR' } }
      );
      const data = await res.json();
      setField(campoEndereco, data.display_name || 'Endereço não encontrado');
    } catch (e) {
      setField(campoEndereco, 'Erro ao buscar endereço');
    }
  }

  // ─── TÉCNICOS (MODAL CRIAR) ───
  async function carregarTecnicos(regiao, destino = 'dropdown-tecnicos') {
    if (!regiao && destino === 'dropdown-tecnicos') return;

    const token = localStorage.getItem('planner_token');
    const url = regiao ? `/api/tecnicos?regiao=${encodeURIComponent(regiao)}` : '/api/tecnicos';
    const res = await fetch(url, {
        headers: { 'Authorization': 'Bearer ' + token }
    });
    const tecnicos = await res.json();

    const el = document.getElementById(destino);
    if (!el) return;

    if (el.tagName === 'SELECT') {
        el.innerHTML = '<option value="">Selecione...</option>'
            + tecnicos.map(t => `<option value="${t.nome}">${t.nome}</option>`).join('');
        return;
    }

    el.innerHTML = tecnicos.length
        ? tecnicos.map(t => `<div onclick="selecionarTecnico(${t.id}, '${t.nome.replace(/'/g, "\\'")}')"
                style="padding:8px 12px;cursor:pointer;font-size:13px;color:var(--gray-950)"
                onmouseover="this.style.background='var(--gray-50)'"
                onmouseout="this.style.background='transparent'">${t.nome}</div>`).join('')
        : '<div style="padding:8px 12px;font-size:13px;color:var(--gray-400)">Nenhum técnico nessa região</div>';

    document.getElementById('input-tec').placeholder = tecnicos.length ? 'Selecionar técnico...' : 'Nenhum técnico';
}
  function toggleDropdownTecnicos() {
    const d = document.getElementById('dropdown-tecnicos');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
  }

  function selecionarTecnico(id, nome) {
    if (tecnicosSelecionados.find(t => t.id === id)) return;
    tecnicosSelecionados.push({ id, nome });
    renderizarTags();
    document.getElementById('dropdown-tecnicos').style.display = 'none';
  }

  function removerTecnico(id) {
    tecnicosSelecionados = tecnicosSelecionados.filter(t => t.id !== id);
    renderizarTags();
  }

  function renderizarTags() {
    document.getElementById('tecnicos-tags').innerHTML = tecnicosSelecionados.map(t => `
      <span style="background:#e8f2fc;color:#0c447c;font-size:11px;font-weight:500;padding:3px 8px;border-radius:20px;display:inline-flex;align-items:center;gap:4px">
        ${t.nome}
        <i class="ti ti-x" style="font-size:10px;cursor:pointer" onclick="removerTecnico(${t.id})"></i>
      </span>`).join('');
  }

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
    const dropdown = document.getElementById('dropdown-tecnicos');
    if (wrap && dropdown && !wrap.contains(e.target)) dropdown.style.display = 'none';

    const wrapEd = document.getElementById('edicao-tec-wrap');
    const dropdownEd = document.getElementById('dropdown-tec-edicao');
    if (wrapEd && dropdownEd && !wrapEd.contains(e.target)) dropdownEd.style.display = 'none';
  });

  // ─── EDIÇÃO ───
  function ativarEdicao() {
    document.getElementById('btn-editar').style.display = 'none';
    document.getElementById('btn-salvar').style.display = 'flex';
    document.getElementById('btn-cancelar').style.display = 'flex';

    const campos = [
      { id: 'campo-cto', tipo: 'cto' },
      { id: 'campo-tipo', tipo: 'select', opcoes: ['Fibra cortada', 'CTO offline', 'Queda de sinal', 'OLT offline', 'Cabo subterrâneo'] },
      { id: 'campo-regiao', tipo: 'select', opcoes: ['Goval', 'Vale do Aço', 'Caratinga'] },
      { id: 'campo-tecnicos', tipo: 'custom' },
      { id: 'campo-clientes', tipo: 'number' },
      { id: 'campo-coordenadas', tipo: 'coords' },
      { id: 'campo-endereco', tipo: 'text' },
      { id: 'campo-prioridade', tipo: 'select', opcoes: ['Baixa', 'Média', 'Alta'] },
      { id: 'campo-status', tipo: 'select', opcoes: ['Criada', 'Em andamento', 'Impedimento', 'Finalizada'] },
      { id: 'campo-numero-os', tipo: 'text' },
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

      if (tipo === 'cto') {
        el.innerHTML = `<input type="text" value="${valor}"
          oninput="this.value = this.value.toUpperCase(); buscarCTO(this.value, 'campo-coordenadas', 'campo-endereco')"
          style="${inputStyle}"/>`;
        return;
      }

      if (tipo === 'coords') {
        el.innerHTML = `<input type="text" id="campo-coordenadas-input" value="${valor}" style="${inputStyle}"/>`;
        return;
      }

      if (tipo === 'select') {
        const optionsHtml = opcoes.map(op => `<option value="${op}" ${op === valor ? 'selected' : ''}>${op}</option>`).join('');
        el.innerHTML = `<select style="${inputStyle}">${optionsHtml}</select>`;
        return;
      }

      el.innerHTML = `<input type="${tipo}" value="${valor}" style="${inputStyle}"/>`;
    });
  }

  async function salvarEdicao() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (!id) return;

    const getVal = (selector) => document.querySelector(selector)?.value ?? '';

    const dados = {
      cto:               getVal('#campo-cto input'),
      titulo:            `Rompimento — ${getVal('#campo-cto input')}`,
      descricao:         getVal('#campo-tipo select'),
      regiao:            getVal('#campo-regiao select'),
      responsavel:       tecnicosSelecionadosEdicao.map(t => t.nome).join(', '),
      clientesAfetados:  getVal('#campo-clientes input'),
      coordenadas:       getVal('#campo-coordenadas input') || getVal('#campo-coordenadas-input'),
      localizacao_texto: document.querySelector('#campo-endereco input')?.value ?? document.getElementById('campo-endereco')?.textContent ?? '',
      prioridade:        getVal('#campo-prioridade select'),
      status:            getVal('#campo-status select'),
      numero_os:         getVal('#campo-numero-os input'),
    };

    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/rompimentos/${id}`, {
      method: 'PUT',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(dados)
    });

    if (response.ok) {
      fecharDetalhe();
      window.carregarRompimentos();
    } else {
      const erro = await response.json();
      console.error('Erro ao salvar:', erro.message);
    }
  }

  async function salvarOs() {
    const rompimentoId = document.getElementById('detalhe-conteudo').dataset.id;
    const tipo      = document.getElementById('os-input-tipo').value;
    const tecnico   = document.getElementById('os-input-tecnico').value;
    const status    = document.getElementById('os-input-status').value;
    const token     = localStorage.getItem('planner_token');

    const btn = document.getElementById('os-btn-salvar');
    btn.disabled = true;

    try {
      if (osEditandoId) {
        const dados = {
          titulo:      `OS — ${tipo}`,
          responsavel: tecnico,
          status,
        };
        const response = await fetch(`/api/op-tasks/${osEditandoId}`, {
          method: 'PUT',
          headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(dados)
        });
        if (response.ok) {
          fecharNovaOS();
          carregarOS(rompimentoId);
          if (rompimentoId && window.carregarRompimentos) window.carregarRompimentos();
        } else {
          const erro = await response.json();
          console.error('Erro ao atualizar OS:', erro.message);
        }
      } else {
        const dados = {
          titulo:         `OS — ${tipo}`,
          responsavel:    tecnico,
          status,
          categoria:      'ordem-servico',
          parent_task_id: rompimentoId,
        };
        const response = await fetch('/api/op-tasks', {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(dados)
        });
        if (response.ok) {
          fecharNovaOS();
          carregarOS(rompimentoId);
          if (rompimentoId && window.carregarRompimentos) window.carregarRompimentos();
        } else {
          const erro = await response.json();
          console.error('Erro ao criar OS:', erro.message);
        }
      }
    } finally {
      btn.disabled = false;
    }
  }

async function carregarOS(rompimentoId) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/rompimentos/${rompimentoId}/os`, {
        headers: {
            'Authorization': 'Bearer ' + token,
            'Accept': 'application/json'
        }
    });

    const data = await response.json();
    const lista = data.os || [];

    lista.forEach(os => { osDataMap[os.id] = os; });

    const container = document.getElementById('detalhe-tab-os');

    const statusBorder = { 'Finalizada': '#22c55e', 'Impedimento': '#ef4444', 'Em andamento': '#f59e0b', 'em_andamento': '#f59e0b', 'Aberta': '#166ac4' };
    const statusBadge  = { 'Finalizada': 'b-baixa', 'Impedimento': 'b-alta', 'Aberta': 'b-aberta', 'aberta': 'b-aberta' };
    const statusDot    = { 'Aberta': '#166ac4', 'aberta': '#166ac4', 'Em andamento': '#f59e0b', 'em_andamento': '#f59e0b', 'Finalizada': '#22c55e', 'Impedimento': '#ef4444' };
    const statusOpts   = [
        { value: 'Aberta',       label: 'Aberta',       cls: 'aberta',     border: '#166ac4' },
        { value: 'Em andamento', label: 'Em andamento', cls: 'andamento',  border: '#f59e0b' },
        { value: 'Finalizada',   label: 'Finalizada',   cls: 'finalizada', border: '#22c55e' },
    ];

    if (lista.length === 0) {
        container.innerHTML = `
            <div class="os-tab-head">
                <span class="os-tab-title">Ordens de Serviço <span class="os-count-pill">0</span></span>
                <button class="btn-nova-os" onclick="abrirNovaOS()">
                    <i class="ti ti-plus" style="font-size:12px"></i> Nova OS
                </button>
            </div>
            <div class="os-empty">
                <i class="ti ti-clipboard-off"></i>
                <span>Nenhuma OS vinculada a este rompimento</span>
            </div>`;
        return;
    }

    container.innerHTML = `
        <div class="os-tab-head">
            <span class="os-tab-title">Ordens de Serviço <span class="os-count-pill">${lista.length}</span></span>
            <button class="btn-nova-os" onclick="abrirNovaOS()">
                <i class="ti ti-plus" style="font-size:12px"></i> Nova OS
            </button>
        </div>
        ${lista.map(os => {
            const border = statusBorder[os.status] || '#166ac4';
            const badge  = statusBadge[os.status]  || 'b-media';
            const av     = (os.responsavel || '?').trim().split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
            return `
            <div class="os-card" style="border-left-color:${border}">
                <div class="os-card-row">
                    <span class="os-card-title">${os.titulo}</span>
                    <button class="btn-edit-os" title="Editar OS" onclick="editarOs(${os.id})">
                        <i class="ti ti-pencil" style="font-size:12px"></i>
                    </button>
                </div>
                <div class="os-card-meta">
                    <span class="os-card-av">${av}</span>
                    <span class="os-card-tecnico">${os.responsavel || '—'}</span>
                    <div class="os-status-wrap" id="sw-${os.id}">
                        <button class="os-status-badge" onclick="abrirStatusPills(${os.id}, event)" title="Alterar status">
                            <span class="badge ${badge}" id="sb-${os.id}">${os.status}</span>
                            <span class="drop-arrow">✎</span>
                        </button>
                        <div class="os-status-pills" id="sp-${os.id}">
                            ${statusOpts.map(opt => `<button class="os-status-pill os-status-pill-${opt.cls}${os.status === opt.value ? ' active' : ''}" onclick="selecionarStatusOS(${os.id}, '${opt.value}', event)">${opt.label}</button>`).join('')}
                            <button class="os-status-close" onclick="fecharStatusPills(${os.id}, event)" title="Cancelar">✕</button>
                        </div>
                    </div>
                    <span class="os-card-code">${os.taskCode || ''}</span>
                </div>
            </div>`;
        }).join('')}`;
}


  async function criarRompimento() {
    const dados = {
      titulo:            `Rompimento — ${document.getElementById('input-cto').value}`,
      cto:               document.getElementById('input-cto').value,
      descricao:         document.getElementById('input-tipo').value,
      regiao:            document.getElementById('input-regiao').value,
      responsavel:       tecnicosSelecionados.map(t => t.nome).join(', '),
      clientesAfetados:  document.getElementById('input-clientes').value,
      prioridade:        prioridadeSelecionada,
      coordenadas:       document.getElementById('input-coords').value,
      localizacao_texto: document.getElementById('endereco-box').textContent,
      status:            'Criada',
      categoria:         'rompimentos',
      numero_os:         document.getElementById('input-numero-hubsoft').value
    };

    const token = localStorage.getItem('planner_token');
    const response = await fetch('/api/rompimentos', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(dados)
    });

    const resultado = await response.json();
    if (response.ok) {
      fecharModal();
      window.carregarRompimentos();
    } else {
      console.error('Erro ao criar rompimento:', resultado.message);
    }
  }
  window.trocarAba = trocarAba;
  carregarCTOs();

  // ── Status pills inline das OS ────────────────────────────────────────
  const statusBadgeMap  = { 'Aberta': 'b-aberta', 'aberta': 'b-aberta', 'Finalizada': 'b-baixa', 'Impedimento': 'b-alta' };
  const statusBorderMap = { 'Aberta': '#166ac4', 'aberta': '#166ac4', 'Finalizada': '#22c55e', 'Impedimento': '#ef4444', 'Em andamento': '#f59e0b' };

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
      const id = p.id.replace('sp-', '');
      fecharStatusPills(id, null);
    });
  }

  window.selecionarStatusOS = function(osId, novoStatus, e) {
    e.stopPropagation();
    const wrap  = document.getElementById('sw-' + osId);
    const pills = document.getElementById('sp-' + osId);
    if (!wrap || !pills) return;

    // Atualiza badge
    const badgeEl = document.getElementById('sb-' + osId);
    if (badgeEl) {
      badgeEl.className = 'badge ' + (statusBadgeMap[novoStatus] || 'b-media');
      badgeEl.textContent = novoStatus;
    }

    // Atualiza borda esquerda do card
    const card = wrap.closest('.os-card');
    if (card) card.style.borderLeftColor = statusBorderMap[novoStatus] || '#166ac4';

    // Marca pill ativa
    pills.querySelectorAll('.os-status-pill').forEach(pill => {
      pill.classList.toggle('active', pill.textContent.trim() === novoStatus);
    });

    fecharStatusPills(osId, null);

    // Chama o handler que você irá implementar
    if (typeof window.alterarStatusOS === 'function') {
      window.alterarStatusOS(osId, novoStatus);
    }
  };

  // Fecha pills ao clicar fora
  document.addEventListener('click', fecharTodasPills);
</script>

<script type="module">
  let draggedId = null;
  let draggedStatus = null;
  let wasDragged = false;
  const rompimentosMap = {};
  const isTouchDevice = window.matchMedia('(pointer: coarse)').matches;


const offsetMap = {
  'Criada': 0,
  'Em andamento': 0,
  'Impedimento':0,
  'Finalizada': 0
}

const limitMap = {
    'Criada': 10,
    'Em andamento': 10,
    'Impedimento': 10,
    'Finalizada': 50,
};

const colIdMap = {
    'Criada': 'criada',
    'Em andamento': 'andamento',
    'Impedimento': 'impedimento',
    'Finalizada': 'finalizada',
};

async function carregarMais(status) {
  const limit = limitMap[status];
  offsetMap[status] += limit;

  const novos = await buscarColuna(status, limit, offsetMap[status]);

  const colId = colIdMap[status];
  const col = document.getElementById(`col-${colId}`);

  novos.forEach(r => {
        col.insertAdjacentHTML('beforeend', renderCard(r));
        rompimentosMap[r.id] = r;
    });

    // atualiza contador
    const count = col.querySelectorAll('.kcard').length;
    document.getElementById(`count-${colId}`).textContent = count;

    // esconde o botão se não vieram mais registros
    if (novos.length < limit) {
        document.getElementById(`mais-${colId}`).style.display = 'none';
    }

    document.getElementById(`menos-${colId}`).style.display = 'block';
}

window.carregarMais = carregarMais;

async function verMenos(status){
  const colId = colIdMap[status];
  const col = document.getElementById(`col-${colId}`);
  const cards = col.querySelectorAll('.kcard');

  cards.forEach((card,index)=>{
    if(index>= 10) {
      card.remove();
    }
  })

  offsetMap[status] = 0

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

  function rompimentoTemOsVinculada(r) {
    if (!r) return false;
    return r.is_parent_task === true || r.is_parent_task === 1 || r.is_parent_task === '1';
  }

  function rompimentoTemNumeroOs(r) {
    return String(r?.numero_os ?? '').trim() !== '';
  }

  /** Só exige OS vinculada + número ao entrar em "Em andamento". */
  function podeMoverParaStatus(id, novoStatus) {
    if (novoStatus !== 'Em andamento') return true;
    const r = rompimentosMap[id];
    return rompimentoTemOsVinculada(r) && rompimentoTemNumeroOs(r);
  }

  function mensagemBloqueioEmAndamento(id) {
    const r = rompimentosMap[id];
    const faltas = [];
    if (!rompimentoTemOsVinculada(r)) faltas.push('pelo menos uma OS vinculada');
    if (!rompimentoTemNumeroOs(r)) faltas.push('número da OS (Hubsoft) no rompimento');
    return 'Para mover para Em andamento: ' + faltas.join(' e ') + '.';
  }

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

  // ─── CARREGAR ROMPIMENTOS ───
  async function buscarColuna(status, limit, offset = 0, filtros = {}) {
    const token = localStorage.getItem('planner_token');
    const params = new URLSearchParams({ status, limit, offset, ...filtros });
    const response = await fetch(`/api/rompimentos?${params}`, {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    });
    const data = await response.json();
    return data.rompimentos || [];
}

async function carregarRompimentos(filtros = {}) {
  Object.keys(offsetMap).forEach(k => offsetMap[k] = 0);
    const [criadas, andamento, impedimento, finalizadas] = await Promise.all([
        buscarColuna('Criada', 10, 0, filtros),
        buscarColuna('Em andamento', 10, 0, filtros),
        buscarColuna('Impedimento', 10, 0, filtros),
        buscarColuna('Finalizada', 50, 0, filtros),
    ]);

    const todos = [...criadas, ...andamento, ...impedimento, ...finalizadas];
    Object.keys(rompimentosMap).forEach(k => delete rompimentosMap[k]);
    todos.forEach(r => { rompimentosMap[r.id] = r; });

    document.getElementById('col-criada').innerHTML      = criadas.map(renderCard).join('');
    document.getElementById('col-andamento').innerHTML   = andamento.map(renderCard).join('');
    document.getElementById('col-impedimento').innerHTML = impedimento.map(renderCard).join('');
    document.getElementById('col-finalizada').innerHTML  = finalizadas.map(renderCard).join('');
    document.getElementById('count-criada').textContent      = criadas.length;
    document.getElementById('count-andamento').textContent   = andamento.length;
    document.getElementById('count-impedimento').textContent = impedimento.length;
    document.getElementById('count-finalizada').textContent  = finalizadas.length;
    document.getElementById('total-rompimentos').textContent = todos.length;

    document.getElementById('mais-criada').style.display      = criadas.length === 10 ? 'block' : 'none';
    document.getElementById('mais-andamento').style.display   = andamento.length === 10 ? 'block' : 'none';
    document.getElementById('mais-impedimento').style.display = impedimento.length === 10 ? 'block' : 'none';
    document.getElementById('mais-finalizada').style.display  = finalizadas.length === 50 ? 'block' : 'none';

}
window.carregarRompimentos = carregarRompimentos;
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
      <div class="kcard-title">${esc(r.cto || r.titulo)}</div>
      <div class="kcard-foot" style="margin-top:6px">
        <span class="badge ${regiaoClass}">${r.regiao || 'Sem região'}</span>
        ${r.clientesAfetados ? `<span style="font-size:10px;color:var(--gray-500);margin-left:auto">👥 ${r.clientesAfetados}</span>` : ''}
      </div>
    </div>`;
  }

  // ─── RENDER DETALHE ───
  function renderDetalhe(r) {
    document.getElementById('detalhe-conteudo').dataset.regiao = r.regiao || '';
    document.getElementById('detalhe-titulo').textContent = r.titulo || 'Rompimento';
    document.getElementById('detalhe-subtitulo').textContent = r.taskCode ? `Código: ${r.taskCode}` : '';

    const tecnicos = r.responsavel || '—';

    document.getElementById('detalhe-conteudo').innerHTML = `
      <div style="display:flex;flex-direction:column;gap:16px" class="detail-enter">
        <div class="detail-badges">
          ${badgeStatus(r.status)}
          ${badgePrioridade(r.prioridade)}
          ${badgeRegiao(r.regiao)}
        </div>
        <div class="detail-grid">
          ${campoDetalhe('CTO / Elemento', esc(r.cto), 1, 'campo-cto')}
          ${campoDetalhe('Tipo de rompimento', esc(r.descricao), 1, 'campo-tipo')}
          ${campoDetalhe('Região', esc(r.regiao), 1, 'campo-regiao')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Técnico(s) responsável(is)', esc(tecnicos), 1, 'campo-tecnicos')}
          ${campoDetalhe('Número da OS (Hubsoft)', esc(r.numero_os), 1, 'campo-numero-os')}
          ${campoDetalhe('Clientes afetados', esc(r.clientesAfetados ?? '0'), 1, 'campo-clientes')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Coordenadas', esc(r.coordenadas), 1, 'campo-coordenadas')}
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
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Prioridade', esc(r.prioridade), 1, 'campo-prioridade')}
          ${campoDetalhe('Status', esc(r.status), 1, 'campo-status')}
        </div>
        <div class="detail-grid">
          ${campoDetalhe('Endereço', esc(r.localizacao_texto), 3, 'campo-endereco')}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Criado em', formatarData(r.criadaEm))}
          ${campoDetalhe('Atualizado em', formatarData(r.updated_at))}
        </div>
      </div>`;

    document.getElementById('detalhe-conteudo').dataset.id = r.id;
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
      const response = await fetch(`/api/rompimentos/${id}`, {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
      });
      const data = await response.json();
      if (!response.ok) { renderDetalheErro(data.message || 'Não foi possível carregar.'); return; }
      renderDetalhe(data.rompimento || data);
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

  async function moverRompimento(id, novoStatus, colDestino) {
    const card = document.querySelector(`.kcard[data-id="${id}"]`);
    const colOrigem = card?.closest('.kcol-body');
    const statusAnterior = card?.dataset.status;

    if (card) {
        card.dataset.status = novoStatus;
        colDestino.appendChild(card);
        atualizarContadores();
    }

    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/rompimentos/${id}`, {
        method: 'PUT',
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ status: novoStatus })
    });

    if (!response.ok) {
        const erro = await response.json();

        if (card && colOrigem && statusAnterior) {
            card.dataset.status = statusAnterior;
            colOrigem.appendChild(card);
            atualizarContadores();
        }

        if (response.status === 422) {
            alert(erro.message || 'Finalize todas as OS antes de finalizar o rompimento.');
        }
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
      const novoStatus = col.dataset.status;
      const permitido = podeMoverParaStatus(draggedId, novoStatus);
      e.preventDefault();
      e.dataTransfer.dropEffect = permitido ? 'move' : 'none';
      document.querySelectorAll('.kcol-body').forEach(el => {
        const alvo = el === col;
        el.classList.toggle('drag-over', alvo && permitido);
        el.classList.toggle('drag-bloqueado', alvo && !permitido);
      });
    });

    kanban.addEventListener('drop', async (e) => {
      e.preventDefault();
      const col = e.target.closest('.kcol-body');
      if (!col || !draggedId) return;
      limparDragOver();
      const novoStatus = col.dataset.status;
      if (novoStatus === draggedStatus) return;
      if (!podeMoverParaStatus(draggedId, novoStatus)) {
        alert(mensagemBloqueioEmAndamento(draggedId));
        return;
      }
      await moverRompimento(draggedId, novoStatus, col);
    });
  }

  // função global para o iniciarArrasto chamado pelo ondragstart do HTML
  window.iniciarArrasto = function(event, id) {
    draggedId = String(id);
    const card = event.target.closest('.kcard');
    if (card) draggedStatus = card.dataset.status;
  };

  initKanbanDragDrop();
  carregarRompimentos();
  carregarTecnicos(null, 'filtro-tecnico');
</script>
@endsection
