@extends('layouts.app')

@section('title', 'Tarefas — Planner Telecom')
@section('page-title', 'Tarefas')
@section('btn-label', 'Criar tarefa')

@section('styles')
<style>
  .kcard { cursor: pointer; }
  .modal-foot-inner { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .modal-foot-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }
  .btn-modal-danger { border: 1px solid #fecaca; background: #fff; color: #dc2626; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
  .btn-modal-danger:hover { background: #fef2f2; border-color: #f87171; }
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
  [data-theme="dark"] .btn-modal-danger { background: #21262d; border-color: #7f1d1d; color: #ff7b72; }
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
  .modal-form { display: flex; flex-direction: column; gap: 14px; }
  .tarefa-field { display: flex; flex-direction: column; gap: 5px; }
  .tarefa-label { font-size: 12px; font-weight: 500; color: var(--gray-500); }
  .tarefa-input, .tarefa-textarea {
    width: 100%;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    padding: 8px 10px;
    font-size: 13px;
    font-family: inherit;
    outline: none;
    background: var(--white);
    color: var(--gray-950);
    box-sizing: border-box;
  }
  .tarefa-input { height: 38px; }
  .tarefa-textarea { min-height: 80px; resize: vertical; }
  .tarefa-field--descricao { min-width: 0; }
  .tarefa-descricao-mount { width: 100%; min-width: 0; }
  .tarefa-input:focus, .tarefa-textarea:focus {
    border-color: #166ac4;
    box-shadow: 0 0 0 3px rgba(22,106,196,0.12);
  }
  [data-theme="dark"] .tarefa-input,
  [data-theme="dark"] .tarefa-textarea {
    background: #21262d;
    border-color: #30363d;
    color: #e6edf3;
  }
  .tarefas-page-content {
    display: flex;
    flex-direction: column;
    min-height: 0;
    flex: 1;
  }
  .tarefas-kanban-card {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
  }
  .tarefas-kanban-card .kanban-cols {
    min-height: 0;
  }
  .tarefas-kanban-card .kcol {
    min-height: 0;
  }
  .prioridade-wrap { display: flex; gap: 8px; }
  .btn-prioridade {
    flex: 1; padding: 8px 0; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500;
    cursor: pointer; font-family: inherit; transition: filter 0.15s, transform 0.1s;
    border-width: 1px; border-style: solid;
  }
  .btn-prioridade:active { transform: scale(0.97); }
  .btn-prio-baixa  { border-color: #86efac; background: #f0fdf4; color: #166534; }
  .btn-prio-media  { border-color: var(--amber); background: var(--amber-bg); color: var(--amber-text); }
  .btn-prio-alta   { border-color: #fca5a5; background: var(--red-bg); color: var(--red-text); }
  .btn-prio-ativo  { border-width: 2px; }
</style>
@endsection

@section('content')
<div class="tarefas-page-content">

<!-- MODAL CRIAR -->
<x-modal id="modal-overlay" titulo="Nova tarefa" subtitulo="Preencha os dados da tarefa">
  <div class="modal-form">
    <div class="tarefa-field">
      <label class="tarefa-label" for="input-titulo">Título</label>
      <input type="text" id="input-titulo" class="tarefa-input" placeholder="Ex: Verificar equipamento na CTO"/>
    </div>

    <div class="tarefa-field tarefa-field--descricao">
      <label class="tarefa-label" for="input-descricao-editor">Descrição</label>
      <div id="input-descricao-wrap" class="tarefa-descricao-mount"></div>
    </div>

    <div class="detail-grid-2">
      <div class="tarefa-field">
        <label class="tarefa-label" for="input-responsavel">Responsável <span style="font-weight:400;color:var(--gray-400)">(opcional)</span></label>
        <select id="input-responsavel" class="tarefa-input">
          <option value="">Nenhum (opcional)</option>
        </select>
      </div>
      <div class="tarefa-field">
        <label class="tarefa-label" for="input-prazo">Prazo</label>
        <input type="date" id="input-prazo" class="tarefa-input"/>
      </div>
    </div>

    <div class="tarefa-field">
      <label class="tarefa-label">Prioridade</label>
      <div class="prioridade-wrap" id="prioridade-criar-wrap">
        <button type="button" onclick="selecionarPrioridadeCriar(this,'Baixa')" class="btn-prioridade btn-prio-baixa">Baixa</button>
        <button type="button" onclick="selecionarPrioridadeCriar(this,'Média')" class="btn-prioridade btn-prio-media btn-prio-ativo">Média ✓</button>
        <button type="button" onclick="selecionarPrioridadeCriar(this,'Alta')" class="btn-prioridade btn-prio-alta">Alta</button>
      </div>
    </div>
  </div>

  <x-slot name="footer">
    <button type="button" onclick="fecharModal()" class="btn-modal btn-modal-ghost">Cancelar</button>
    <button type="button" onclick="criarTarefa()" class="btn-modal btn-modal-primary" id="btn-criar-tarefa">
      <i class="ti ti-checklist" style="font-size:14px"></i> Criar tarefa
    </button>
  </x-slot>
</x-modal>

<!-- MODAL DETALHE -->
<x-modal
  id="detalhe-overlay"
  titulo-id="detalhe-titulo"
  subtitulo-id="detalhe-subtitulo"
  fechar="fecharDetalhe()">
  <div id="detalhe-conteudo"></div>
  <x-slot name="footer">
    <div class="modal-foot-inner">
      <button type="button" onclick="abrirConfirmacaoExclusao()" id="btn-excluir" class="btn-modal btn-modal-danger" title="Excluir esta tarefa">
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

<!-- Confirmação de exclusão -->
<div id="confirm-excluir-overlay" class="confirm-excluir-overlay" role="dialog" aria-modal="true" aria-labelledby="confirm-excluir-title">
  <div class="confirm-excluir-box">
    <div class="confirm-excluir-icon"><i class="ti ti-alert-triangle"></i></div>
    <p class="confirm-excluir-title" id="confirm-excluir-title">Excluir tarefa?</p>
    <p class="confirm-excluir-text" id="confirm-excluir-text">
      Esta ação não pode ser desfeita. A tarefa será removida permanentemente.
    </p>
    <div class="confirm-excluir-foot">
      <button type="button" onclick="fecharConfirmacaoExclusao()" class="btn-modal btn-modal-ghost">Cancelar</button>
      <button type="button" onclick="confirmarExclusaoTarefa()" id="btn-confirmar-excluir" class="btn-modal btn-modal-danger">
        <i class="ti ti-trash" style="font-size:14px"></i> Excluir
      </button>
    </div>
  </div>
</div>

<!-- KANBAN -->
<div class="card tarefas-kanban-card">
  <div class="card-header">
    <span class="card-title">Kanban de Tarefas</span>
    <span class="card-action">total: <span id="total-tarefas">0</span></span>
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
        <div class="kcol-name"><div class="dot d-green"></div> Concluída</div>
        <div style="display:flex;align-items:center;gap:5px">
          <span class="kcol-count" id="count-concluida">0</span>
          <button class="btn-kcol-toggle" onclick="toggleColuna(this)" title="Minimizar coluna" aria-label="Minimizar coluna">
            <i class="ti ti-chevron-down"></i>
          </button>
        </div>
      </div>
      <div class="kcol-content-wrap">
        <div class="kcol-body" id="col-concluida" data-status="Concluída"></div>
        <div id="mais-concluida" style="display:none;padding:8px">
          <button onclick="carregarMais('Concluída')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Carregar mais</button>
        </div>
        <div id="menos-concluida" style="display:none;padding:8px">
          <button onclick="verMenos('Concluída')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Ver menos</button>
        </div>
      </div>
    </div>
    <div class="kcol">
      <div class="kcol-head">
        <div class="kcol-name"><div class="dot d-green"></div> Finalizar</div>
        <div style="display:flex;align-items:center;gap:5px">
          <span class="kcol-count" id="count-finalizar">0</span>
          <button class="btn-kcol-toggle" onclick="toggleColuna(this)" title="Minimizar coluna" aria-label="Minimizar coluna">
            <i class="ti ti-chevron-down"></i>
          </button>
        </div>
      </div>
      <div class="kcol-content-wrap">
        <div class="kcol-body" id="col-finalizar" data-status="Finalizar"></div>
        <div id="mais-finalizar" style="display:none;padding:8px">
          <button onclick="carregarMais('Finalizar')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Carregar mais</button>
        </div>
        <div id="menos-finalizar" style="display:none;padding:8px">
          <button onclick="verMenos('Finalizar')" style="width:100%;padding:6px;border:1px dashed var(--gray-200);border-radius:var(--radius-sm);background:transparent;color:var(--gray-400);font-size:12px;cursor:pointer">Ver menos</button>
        </div>
      </div>
    </div>
  </div>
</div>

</div>
@endsection

@section('scripts')
<script>
  if (typeof window.plannerPossuiPermissao === 'function' && !window.plannerPossuiPermissao('visualizar_aba_tarefas')) {
    window.location.replace('/dashboard');
  }
</script>
<script type="module">
  import { listarTarefas, criarTarefa as criarTarefaApi, atualizarTarefa as atualizarTarefaApi, deletarTarefa as deletarTarefaApi } from '{{ asset("js/modules/opTask.js") }}';
  import {
    renderDescricaoView,
    mountDescricaoEditor,
    getDescricaoEditorValue,
    resetDescricaoEditor,
  } from '{{ asset("js/planner-descricao-editor.js") }}';

  let tarefasMap = {};
  let usuariosSistema = [];
  let prioridadeSelecionada = 'Média';
  let prioridadeEdicao = 'Média';

  const NIVEIS_PRIORIDADE = ['Baixa', 'Média', 'Alta'];

  const colunasPorStatus = {
    'Criada': 'col-criada',
    'Em andamento': 'col-andamento',
    'Concluída': 'col-concluida',
    'Finalizar': 'col-finalizar',
    'Finalizada': 'col-finalizar',
  };

  const colunasKanban = ['col-criada', 'col-andamento', 'col-concluida', 'col-finalizar'];

  let draggedId = null;
  let draggedStatus = null;
  let wasDragged = false;
  const isTouchDevice = window.matchMedia('(pointer: coarse)').matches;

  function normalizarStatus(status) {
    if (status === 'Finalizada') return 'Finalizar';
    if (status === 'Impedimento') return 'Em andamento';
    return status || 'Criada';
  }

  function esc(valor) {
    if (valor == null || valor === '') return '';
    return String(valor).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function formatarPrazo(valor) {
    if (!valor) return '';
    const data = new Date(valor);
    if (isNaN(data.getTime())) return valor;
    return data.toLocaleDateString('pt-BR');
  }

  function formatarData(valor) {
    if (!valor) return '—';
    const data = new Date(valor);
    if (isNaN(data.getTime())) return valor;
    return data.toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
  }

  function prazoParaInput(valor) {
    if (!valor) return '';
    const data = new Date(valor);
    if (isNaN(data.getTime())) return '';
    return data.toISOString().slice(0, 10);
  }

  function badgePrioridade(prioridade) {
    const nivel = prioridade?.toLowerCase();
    const cls = nivel === 'alta' ? 'b-alta' : nivel === 'baixa' ? 'b-baixa' : 'b-media';
    return `<span class="badge ${cls}">${esc(prioridade || 'Média')}</span>`;
  }

  function atualizarBotoesPrioridade(container, nivelAtivo) {
    if (!container) return;
    container.querySelectorAll('.btn-prioridade').forEach(btn => {
      const nivel = btn.dataset.nivel || btn.textContent.replace(' ✓', '').trim();
      const ativo = nivel === nivelAtivo;
      btn.textContent = ativo ? `${nivel} ✓` : nivel;
      btn.style.borderWidth = ativo ? '2px' : '1px';
      btn.classList.toggle('btn-prio-ativo', ativo);
    });
  }

  function htmlBotoesPrioridade(nivelAtivo, onclickFn) {
    const classes = { Baixa: 'btn-prio-baixa', Média: 'btn-prio-media', Alta: 'btn-prio-alta' };
    return NIVEIS_PRIORIDADE.map(nivel => {
      const ativo = nivel === (nivelAtivo || 'Média');
      return `<button type="button" data-nivel="${nivel}" onclick="${onclickFn}(this,'${nivel}')" class="btn-prioridade ${classes[nivel]}${ativo ? ' btn-prio-ativo' : ''}" style="border-width:${ativo ? '2px' : '1px'}">${nivel}${ativo ? ' ✓' : ''}</button>`;
    }).join('');
  }

  window.selecionarPrioridadeCriar = function (btn, nivel) {
    prioridadeSelecionada = nivel;
    atualizarBotoesPrioridade(document.getElementById('prioridade-criar-wrap'), nivel);
  };

  window.selecionarPrioridadeEdicao = function (btn, nivel) {
    prioridadeEdicao = nivel;
    atualizarBotoesPrioridade(document.getElementById('campo-prioridade'), nivel);
  };

  function badgeStatus(status) {
    const normalizado = normalizarStatus(status);
    const mapa = {
      'Criada': 'b-aberta',
      'Em andamento': 'b-media',
      'Concluída': 'b-media',
      'Finalizar': 'b-baixa',
    };
    return `<span class="badge ${mapa[normalizado] || 'b-cat-gen'}">${esc(normalizado) || '—'}</span>`;
  }

  function campoDescricaoDetalhe(label, valor, id = 'campo-descricao') {
    return `
      <div class="detail-field span-3">
        <span class="detail-label">${label}</span>
        <div class="detail-value descricao-field" id="${id}">${renderDescricaoView(valor)}</div>
      </div>`;
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

  function textoResponsavel(responsavel) {
    const valor = (responsavel || '').trim();
    return valor ? esc(valor) : 'Não tem responsável pela tarefa';
  }

  function renderCard(t) {
    return `
      <div class="kcard" data-id="${t.id}" data-status="${esc(normalizarStatus(t.status))}" draggable="true">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
          <span class="kcard-code">${esc(t.taskCode || 'S/C')}</span>
          ${t.prazo ? `<span style="font-size:10px;color:var(--gray-500)">${formatarPrazo(t.prazo)}</span>` : ''}
        </div>
        <div class="kcard-title">${esc(t.titulo)}</div>
        <div class="kcard-foot" style="margin-top:6px">
          <span class="badge b-cat-gen">Tarefa</span>
          ${badgePrioridade(t.prioridade)}
          ${t.responsavel ? `<span class="badge b-regiao-gv">${esc(t.responsavel)}</span>` : `<span style="font-size:10px;color:var(--gray-400)">Sem responsável</span>`}
        </div>
      </div>`;
  }

  function limparDragOver() {
    document.querySelectorAll('.kcol-body').forEach(el => {
      el.classList.remove('drag-over', 'drag-bloqueado');
    });
  }

  function podeMoverParaStatus() {
    return true;
  }

  async function moverTarefa(id, novoStatus, colDestino) {
    const alvo = String(id);
    const card = document.querySelector(`.kcard[data-id="${CSS.escape(alvo)}"]`);
    const colOrigem = card?.closest('.kcol-body');
    const statusAnterior = card?.dataset.status;

    if (card) {
      card.dataset.status = novoStatus;
      colDestino.appendChild(card);
      atualizarContadores();
    }

    try {
      const resultado = await atualizarTarefaApi(alvo, { status: novoStatus, categoria: 'tarefas' });
      const tarefa = resultado.opTask || resultado.tarefa || resultado;
      if (window.plannerEstaExcluida?.(alvo)) return;

      tarefasMap[alvo] = tarefa;

      if (card) {
        card.dataset.status = normalizarStatus(tarefa.status);
      }

      window.plannerSyncTarefa?.(tarefa);
      await window.plannerAposMutacaoLocal?.();
    } catch (err) {
      if (card && colOrigem && statusAnterior) {
        card.dataset.status = statusAnterior;
        colOrigem.appendChild(card);
        atualizarContadores();
      }
      alert(err.message || 'Erro ao mover tarefa.');
    }
  }

  function initKanbanDragDrop() {
    const kanban = document.querySelector('.kanban-cols');
    if (!kanban || kanban.dataset.dragInit) return;
    kanban.dataset.dragInit = '1';

    kanban.addEventListener('click', (e) => {
      if (wasDragged) {
        wasDragged = false;
        return;
      }
      const card = e.target.closest('.kcard');
      if (card?.dataset.id) abrirDetalhe(card.dataset.id);
    });

    if (isTouchDevice) return;

    kanban.addEventListener('dragstart', (e) => {
      const card = e.target.closest('.kcard');
      if (!card) return;
      draggedId = card.dataset.id;
      draggedStatus = normalizarStatus(card.dataset.status);
      wasDragged = false;
      card.classList.add('kcard-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', draggedId);
    });

    kanban.addEventListener('drag', () => {
      wasDragged = true;
    });

    kanban.addEventListener('dragend', (e) => {
      const card = e.target.closest('.kcard');
      if (card) card.classList.remove('kcard-dragging');
      limparDragOver();
      setTimeout(() => {
        draggedId = null;
        draggedStatus = null;
      }, 0);
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
      if (normalizarStatus(novoStatus) === normalizarStatus(draggedStatus)) return;
      if (!podeMoverParaStatus(draggedId, novoStatus)) return;
      await moverTarefa(draggedId, novoStatus, col);
    });
  }

  function atualizarContadores() {
    const statuses = ['Criada', 'Em andamento', 'Concluída', 'Finalizar'];
    let total = 0;

    statuses.forEach(status => {
      const colId = colunasPorStatus[status];
      const qtd = document.getElementById(colId).querySelectorAll('.kcard').length;
      document.getElementById(colId.replace('col-', 'count-')).textContent = qtd;
      total += qtd;
    });

    document.getElementById('total-tarefas').textContent = total;
  }

  function renderKanban(tarefas) {
    Object.keys(tarefasMap).forEach(k => delete tarefasMap[k]);
    colunasKanban.forEach(id => {
      document.getElementById(id).innerHTML = '';
    });

    tarefas.forEach(adicionarCardTarefa);
  }

  function adicionarCardTarefa(tarefa) {
    if (!tarefa?.id) return;
    if (tarefa.categoria && tarefa.categoria !== 'tarefas') return;
    if (window.plannerEstaExcluida?.(tarefa.id)) return;

    tarefasMap[tarefa.id] = tarefa;
    document.querySelectorAll(`.kcard[data-id="${CSS.escape(String(tarefa.id))}"]`).forEach(card => card.remove());

    const status = normalizarStatus(tarefa.status);
    const colId = colunasPorStatus[status] || colunasPorStatus['Criada'];
    const col = document.getElementById(colId);
    if (!col) return;

    col.insertAdjacentHTML('afterbegin', renderCard(tarefa));
    atualizarContadores();
  }

  async function carregarTarefas() {
    const gen = window.plannerBeginReload?.() ?? 0;
    try {
      const tarefas = await listarTarefas({ categoria: 'tarefas', limit: 500 });
      if (window.plannerIsReloadCurrent && !window.plannerIsReloadCurrent(gen)) return;

      const lista = Array.isArray(tarefas) ? tarefas : [];
      const filtradas = window.plannerFiltrarExcluidas ? window.plannerFiltrarExcluidas(lista) : lista;
      renderKanban(filtradas);
      window.plannerPurgarCardsExcluidos?.();
    } catch (err) {
      console.error(err);
    }
  }

  window.carregarTarefas = carregarTarefas;
  window.adicionarCardTarefa = adicionarCardTarefa;

  async function carregarUsuariosSistema() {
    const token = localStorage.getItem('planner_token');
    const response = await fetch('/api/usuarios/opcoes', {
      headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
      cache: 'no-store',
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Erro ao carregar usuários.');

    usuariosSistema = (data.usuarios || []).filter(u => u.funcao !== 'tecnico');
    atualizarSelectResponsavelCriar();
  }

  function atualizarSelectResponsavel(selectId = 'input-responsavel') {
    const select = document.getElementById(selectId);
    if (!select) return;
    const valorAtual = select.value;
    select.innerHTML = '<option value="">Nenhum (opcional)</option>'
      + usuariosSistema.map(u => `<option value="${esc(u.username)}">${esc(u.username)}</option>`).join('');
    if (usuariosSistema.some(u => u.username === valorAtual)) {
      select.value = valorAtual;
    }
  }

  function resetBotoesDetalhe() {
    document.getElementById('btn-excluir').style.display = '';
    document.getElementById('btn-editar').style.display = '';
    document.getElementById('btn-salvar').style.display = 'none';
    document.getElementById('btn-cancelar').style.display = 'none';
  }

  function renderDetalhe(t) {
    document.getElementById('detalhe-titulo').textContent = t.titulo || 'Tarefa';
    document.getElementById('detalhe-subtitulo').textContent = t.taskCode ? `Código: ${t.taskCode}` : '';

    document.getElementById('detalhe-conteudo').innerHTML = `
      <div style="display:flex;flex-direction:column;gap:16px" class="detail-enter">
        <div class="detail-badges">
          ${badgeStatus(t.status)}
          <span class="badge b-cat-gen">Tarefa</span>
          ${badgePrioridade(t.prioridade)}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Título', esc(t.titulo), 1, 'campo-titulo')}
          ${campoDetalhe('Responsável', textoResponsavel(t.responsavel), 1, 'campo-responsavel')}
        </div>
        <div class="detail-grid">
          ${campoDescricaoDetalhe('Descrição', t.descricao)}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Prazo', t.prazo ? formatarPrazo(t.prazo) : '—', 1, 'campo-prazo')}
          ${campoDetalhe('Criado em', formatarData(t.criadaEm))}
        </div>
        <div class="detail-grid-2">
          ${campoDetalhe('Prioridade', esc(t.prioridade || 'Média'), 1, 'campo-prioridade')}
          <div class="detail-field">
            <span class="detail-label">Atualizado em</span>
            <div class="detail-value">${formatarData(t.updated_at)}</div>
          </div>
        </div>
        <div class="detail-grid-2">
          <div class="detail-field">
            <span class="detail-label">Código da tarefa</span>
            <div class="detail-value">${esc(t.taskCode) || '—'}</div>
          </div>
        </div>
      </div>`;

    document.getElementById('detalhe-conteudo').dataset.id = t.id;
  }

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
    resetBotoesDetalhe();
    renderDetalheLoading();

    const token = localStorage.getItem('planner_token');
    try {
      const response = await fetch(`/api/op-tasks/${id}`, {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
        cache: 'no-store',
      });
      const data = await response.json();
      if (!response.ok) {
        renderDetalheErro(data.message || 'Não foi possível carregar.');
        return;
      }
      const tarefa = data.opTask || data.tarefa || data;
      if (tarefa.categoria && tarefa.categoria !== 'tarefas') {
        renderDetalheErro('Tarefa não encontrada.');
        return;
      }
      tarefasMap[tarefa.id] = tarefa;
      renderDetalhe(tarefa);
    } catch {
      renderDetalheErro('Erro de conexão.');
    }
  }

  function fecharDetalhe() {
    fecharConfirmacaoExclusao();
    document.getElementById('detalhe-overlay').classList.remove('open');
    resetBotoesDetalhe();
  }

  async function ativarEdicao() {
    if (!usuariosSistema.length) {
      try {
        await carregarUsuariosSistema();
      } catch (err) {
        alert(err.message || 'Não foi possível carregar os usuários.');
        return;
      }
    }

    document.getElementById('btn-excluir').style.display = 'none';
    document.getElementById('btn-editar').style.display = 'none';
    document.getElementById('btn-salvar').style.display = 'flex';
    document.getElementById('btn-cancelar').style.display = 'flex';

    const inputStyle = 'width:100%;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:8px 10px;font-size:13px;font-family:inherit;outline:none;background:var(--white);box-sizing:border-box';

    const tituloEl = document.getElementById('campo-titulo');
    if (tituloEl) {
      const valor = tituloEl.textContent.trim() === '—' ? '' : tituloEl.textContent.trim();
      tituloEl.innerHTML = `<input type="text" value="${esc(valor)}" style="${inputStyle}"/>`;
    }

    const descEl = document.getElementById('campo-descricao');
    if (descEl) {
      const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
      const tarefa = id ? tarefasMap[id] : null;
      const html = tarefa?.descricao || '';
      descEl.className = 'detail-value descricao-field';
      mountDescricaoEditor(descEl, { html, placeholder: 'Detalhes da tarefa (opcional)' });
    }

    const respEl = document.getElementById('campo-responsavel');
    if (respEl) {
      const bruto = respEl.textContent.trim();
      const valor = (bruto === '—' || bruto === 'Não tem responsável pela tarefa') ? '' : bruto;
      const opcoes = usuariosSistema.map(u =>
        `<option value="${esc(u.username)}" ${u.username === valor ? 'selected' : ''}>${esc(u.username)}</option>`
      ).join('');
      respEl.innerHTML = `<select style="${inputStyle}"><option value="">Nenhum (opcional)</option>${opcoes}</select>`;
    }

    const prazoEl = document.getElementById('campo-prazo');
    if (prazoEl) {
      const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
      const tarefa = id ? tarefasMap[id] : null;
      const valor = prazoParaInput(tarefa?.prazo) || (prazoEl.textContent.trim() !== '—' ? '' : '');
      prazoEl.innerHTML = `<input type="date" value="${valor}" style="${inputStyle}"/>`;
    }

    const prioEl = document.getElementById('campo-prioridade');
    if (prioEl) {
      const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
      const tarefa = id ? tarefasMap[id] : null;
      prioridadeEdicao = tarefa?.prioridade || 'Média';
      prioEl.innerHTML = `<div class="prioridade-wrap">${htmlBotoesPrioridade(prioridadeEdicao, 'selecionarPrioridadeEdicao')}</div>`;
    }
  }

  async function salvarEdicao() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (!id) return;

    const titulo = document.querySelector('#campo-titulo input')?.value?.trim() || '';
    const responsavel = document.querySelector('#campo-responsavel select')?.value || '';
    const descricao = getDescricaoEditorValue(document.getElementById('campo-descricao')) || '';
    const prazo = document.querySelector('#campo-prazo input')?.value || '';

    if (!titulo) {
      alert('Informe o título da tarefa.');
      return;
    }

    const btn = document.getElementById('btn-salvar');
    btn.disabled = true;

    const dados = { titulo, descricao, responsavel, prioridade: prioridadeEdicao, categoria: 'tarefas' };
    if (prazo) dados.prazo = prazo;

    try {
      const resultado = await atualizarTarefaApi(id, dados);
      const tarefa = resultado.opTask || resultado.tarefa || resultado;
      fecharDetalhe();
      if (!window.plannerEstaExcluida?.(id)) {
        adicionarCardTarefa(tarefa);
        window.plannerSyncTarefa?.(tarefa);
      }
      await window.plannerAposMutacaoLocal?.();
    } catch (err) {
      alert(err.message || 'Erro ao salvar alterações.');
    } finally {
      btn.disabled = false;
    }
  }

  function cancelarEdicao() {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    resetBotoesDetalhe();
    if (id) abrirDetalhe(id);
  }

  function abrirConfirmacaoExclusao() {
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

    const btn = document.getElementById('btn-confirmar-excluir');
    btn.disabled = true;

    window.plannerPausarPolling?.(30000);
    window.plannerMarcarExcluida?.(id);
    window.plannerRemoverCardKanban?.(id);
    window.plannerInvalidateReloads?.();

    try {
      await deletarTarefaApi(id);
      window.plannerConfirmarExclusaoServidor?.(id);
      fecharConfirmacaoExclusao();
      fecharDetalhe();
      delete tarefasMap[id];
      window.plannerSyncExclusaoTarefa?.(id);
      window.plannerPausarPolling?.(120000);
      await window.plannerNotifyLocalMutation?.();
    } catch (err) {
      window.plannerDesmarcarExcluida?.(id);
      await carregarTarefas();
      alert(err.message || 'Erro ao excluir tarefa.');
    } finally {
      btn.disabled = false;
    }
  }

  window.abrirDetalhe = abrirDetalhe;
  window.fecharDetalhe = fecharDetalhe;
  window.ativarEdicao = ativarEdicao;
  window.salvarEdicao = salvarEdicao;
  window.cancelarEdicao = cancelarEdicao;
  window.abrirConfirmacaoExclusao = abrirConfirmacaoExclusao;
  window.fecharConfirmacaoExclusao = fecharConfirmacaoExclusao;
  window.confirmarExclusaoTarefa = confirmarExclusaoTarefa;

  function atualizarSelectResponsavelCriar() {
    atualizarSelectResponsavel('input-responsavel');
  }

  function limparFormulario() {
    document.getElementById('input-titulo').value = '';
    resetDescricaoEditor(document.getElementById('input-descricao-wrap'));
    document.getElementById('input-responsavel').value = '';
    document.getElementById('input-prazo').value = '';
    prioridadeSelecionada = 'Média';
    atualizarBotoesPrioridade(document.getElementById('prioridade-criar-wrap'), 'Média');
  }

  window.abrirModal = async function () {
    limparFormulario();
    try {
      await carregarUsuariosSistema();
    } catch (err) {
      alert(err.message || 'Não foi possível carregar os usuários.');
      return;
    }
    document.getElementById('modal-overlay').classList.add('open');
    setTimeout(() => document.getElementById('input-titulo').focus(), 0);
  };

  window.fecharModal = function () {
    document.getElementById('modal-overlay').classList.remove('open');
  };

  window.criarTarefa = async function () {
    const titulo = document.getElementById('input-titulo').value.trim();
    const responsavel = document.getElementById('input-responsavel').value;
    const prazo = document.getElementById('input-prazo').value;

    if (!titulo) {
      alert('Informe o título da tarefa.');
      return;
    }

    const btn = document.getElementById('btn-criar-tarefa');
    btn.disabled = true;

    const dados = {
      titulo,
      descricao: getDescricaoEditorValue(document.getElementById('input-descricao-wrap')),
      responsavel,
      prioridade: prioridadeSelecionada,
      categoria: 'tarefas',
      status: 'Criada',
    };

    if (prazo) {
      dados.prazo = prazo;
    }

    try {
      const tarefa = await criarTarefaApi(dados);
      fecharModal();
      adicionarCardTarefa(tarefa);
      window.plannerSyncTarefa?.(tarefa);
    } catch (err) {
      alert(err.message || 'Erro ao criar tarefa.');
    } finally {
      btn.disabled = false;
    }
  };

  function toggleColuna(btn) {
    const col = btn.closest('.kcol');
    const collapsed = col.classList.toggle('collapsed');
    btn.title = collapsed ? 'Expandir coluna' : 'Minimizar coluna';
    btn.setAttribute('aria-label', collapsed ? 'Expandir coluna' : 'Minimizar coluna');
  }

  window.toggleColuna = toggleColuna;
  window.carregarMais = function () {};
  window.verMenos = function () {};

  document.getElementById('modal-overlay').addEventListener('click', function (e) {
    if (e.target === this) fecharModal();
  });

  document.getElementById('detalhe-overlay').addEventListener('click', function (e) {
    if (e.target === this) fecharDetalhe();
  });

  document.getElementById('confirm-excluir-overlay')?.addEventListener('click', function (e) {
    if (e.target === this) fecharConfirmacaoExclusao();
  });

  initKanbanDragDrop();
  mountDescricaoEditor(document.getElementById('input-descricao-wrap'), {
    placeholder: 'Detalhes da tarefa (opcional)',
  });
  carregarTarefas();
</script>
@endsection
