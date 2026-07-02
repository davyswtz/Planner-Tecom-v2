@extends('layouts.app')

@section('title', 'Ordem de Serviço — Planner Telecom')
@section('page-title', 'Ordem de Serviço')
@section('btn-label', 'Atualizar')
@section('btn-icon', 'ti-refresh')

@section('styles')
<style>
  .os-page {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  /* ── Faixa de números ── */
  .os-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    background: var(--white);
    overflow: hidden;
  }
  .os-stat {
    flex: 1 1 120px;
    padding: 14px 18px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    border-right: 1px solid var(--gray-100);
  }
  .os-stat:last-child { border-right: none; }
  .os-stat-n {
    font-size: 22px;
    font-weight: 600;
    color: var(--gray-950);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
  }
  .os-stat-l {
    font-size: 12px;
    color: var(--gray-500);
  }
  .os-stat--aberta .os-stat-n { color: #2563eb; }
  .os-stat--andamento .os-stat-n { color: #d97706; }
  .os-stat--finalizada .os-stat-n { color: #16a34a; }

  /* ── Filtros compactos ── */
  .os-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
  }
  .os-toolbar-search {
    flex: 1 1 200px;
    min-width: 160px;
    height: 36px;
    padding: 0 12px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    background: var(--white);
    font-family: inherit;
    font-size: 13px;
    color: var(--gray-950);
    outline: none;
  }
  .os-toolbar-search:focus { border-color: var(--blue-600); }
  .os-toolbar-search::placeholder { color: var(--gray-400); }
  .os-toolbar select,
  .os-toolbar input[type="date"] {
    height: 36px;
    padding: 0 10px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    background: var(--white);
    font-family: inherit;
    font-size: 13px;
    color: var(--gray-800);
    outline: none;
    min-width: 0;
  }
  .os-toolbar select:focus,
  .os-toolbar input[type="date"]:focus { border-color: var(--blue-600); }
  .os-toolbar-btn {
    height: 36px;
    padding: 0 12px;
    border: none;
    background: transparent;
    color: var(--gray-500);
    font-family: inherit;
    font-size: 12px;
    cursor: pointer;
    border-radius: var(--radius-sm);
    white-space: nowrap;
  }
  .os-toolbar-btn:hover { color: var(--gray-950); background: var(--gray-50); }
  .os-toolbar-btn--export {
    border: 1px solid var(--gray-200);
    background: var(--white);
    color: var(--gray-700);
    display: inline-flex;
    align-items: center;
    gap: 5px;
  }
  .os-toolbar-btn--export:hover {
    border-color: var(--blue-600);
    color: var(--blue-600);
    background: var(--blue-50);
  }
  .os-toolbar-btn--export:disabled { opacity: 0.5; cursor: not-allowed; }
  [data-theme="dark"] .os-toolbar-btn--export {
    background: #161b22;
    border-color: #30363d;
    color: #e6edf3;
  }
  [data-theme="dark"] .os-toolbar-btn--export:hover {
    border-color: #58a6ff;
    color: #58a6ff;
    background: #0d2340;
  }
  .os-filtros-extra {
    display: none;
    flex-wrap: wrap;
    gap: 8px;
    width: 100%;
    padding-top: 4px;
  }
  .os-filtros-extra.open { display: flex; }
  .os-periodo-hint {
    font-size: 12px;
    color: var(--gray-500);
    width: 100%;
    display: none;
  }
  .os-periodo-hint.visible { display: block; }

  [data-theme="dark"] .os-stats,
  [data-theme="dark"] .os-toolbar-search,
  [data-theme="dark"] .os-toolbar select,
  [data-theme="dark"] .os-toolbar input[type="date"] {
    background: #161b22;
    border-color: #30363d;
    color: #e6edf3;
  }
  [data-theme="dark"] .os-stat { border-right-color: #21262d; }
  [data-theme="dark"] .os-toolbar-btn:hover { background: #21262d; color: #e6edf3; }

  /* ── Painéis ── */
  .os-layout {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 16px;
    align-items: start;
  }
  @media (max-width: 960px) { .os-layout { grid-template-columns: 1fr; } }

  .os-panel {
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    background: var(--white);
    overflow: hidden;
  }
  .os-panel-head {
    padding: 12px 16px;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-950);
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .os-panel-meta {
    font-size: 12px;
    font-weight: 400;
    color: var(--gray-500);
  }
  [data-theme="dark"] .os-panel {
    background: #161b22;
    border-color: #30363d;
  }
  [data-theme="dark"] .os-panel-head { border-bottom-color: #21262d; }

  /* ── Lista de técnicos (minimal) ── */
  .os-tech-list { padding: 4px 0; }
  .os-tech-row {
    display: grid;
    grid-template-columns: 1fr auto auto auto auto;
    gap: 12px;
    align-items: center;
    padding: 10px 16px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.1s;
    border-bottom: 1px solid var(--gray-50);
  }
  .os-tech-row:last-child { border-bottom: none; }
  .os-tech-row:hover { background: var(--gray-50); }
  .os-tech-name { font-weight: 500; color: var(--gray-950); }
  .os-tech-reg { font-size: 11px; color: var(--gray-400); margin-top: 1px; }
  .os-tech-num {
    font-variant-numeric: tabular-nums;
    font-size: 12px;
    color: var(--gray-600);
    min-width: 28px;
    text-align: center;
  }
  .os-tech-num.is-zero { color: var(--gray-300); }
  .os-tech-total {
    font-weight: 600;
    color: var(--gray-950);
    min-width: 32px;
    text-align: right;
    font-variant-numeric: tabular-nums;
  }
  .os-tech-head {
    display: grid;
    grid-template-columns: 1fr auto auto auto auto;
    gap: 12px;
    padding: 6px 16px 8px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--gray-400);
  }
  .os-tech-head span:not(:first-child) { text-align: center; min-width: 28px; }
  .os-tech-head span:last-child { text-align: right; min-width: 32px; }

  [data-theme="dark"] .os-tech-row { border-bottom-color: #21262d; }
  [data-theme="dark"] .os-tech-row:hover { background: #21262d; }

  /* ── Aside: região + origem ── */
  .os-aside { display: flex; flex-direction: column; gap: 16px; }
  .os-mini-list { padding: 8px 0; }
  .os-mini-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.1s;
  }
  .os-mini-item:hover { background: var(--gray-50); }
  .os-mini-label { color: var(--gray-700); }
  .os-mini-count {
    font-weight: 600;
    color: var(--gray-950);
    font-variant-numeric: tabular-nums;
  }
  [data-theme="dark"] .os-mini-item:hover { background: #21262d; }

  /* ── Tabela OS ── */
  .os-table-wrap { overflow-x: auto; }
  .os-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }
  .os-table th {
    text-align: left;
    padding: 10px 16px;
    font-size: 11px;
    font-weight: 500;
    color: var(--gray-400);
    border-bottom: 1px solid var(--gray-100);
    white-space: nowrap;
  }
  .os-table td {
    padding: 11px 16px;
    border-bottom: 1px solid var(--gray-50);
    color: var(--gray-800);
    vertical-align: middle;
  }
  .os-table tbody tr {
    cursor: pointer;
    transition: background 0.1s;
  }
  .os-table tbody tr:hover { background: var(--gray-50); }
  .os-table tbody tr:last-child td { border-bottom: none; }
  .os-cell-main { font-weight: 500; color: var(--gray-950); }
  .os-cell-sub { font-size: 11px; color: var(--gray-400); margin-top: 2px; }
  .os-cell-muted { color: var(--gray-500); font-size: 12px; }

  .status-dot {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--gray-700);
  }
  .status-dot::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .status-dot--aberta::before { background: #3b82f6; }
  .status-dot--andamento::before { background: #f59e0b; }
  .status-dot--finalizada::before { background: #22c55e; }

  .os-list-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    border-top: 1px solid var(--gray-100);
    gap: 12px;
    flex-wrap: wrap;
  }
  .os-list-foot-info { font-size: 12px; color: var(--gray-500); }
  .os-pag { display: flex; gap: 4px; }
  .os-pag-btn {
    border: 1px solid var(--gray-200);
    background: var(--white);
    color: var(--gray-600);
    border-radius: var(--radius-sm);
    padding: 5px 12px;
    font-size: 12px;
    cursor: pointer;
    font-family: inherit;
  }
  .os-pag-btn:disabled { opacity: 0.35; cursor: not-allowed; }
  .os-pag-btn:not(:disabled):hover { border-color: var(--gray-400); color: var(--gray-950); }

  .os-empty {
    padding: 32px 16px;
    text-align: center;
    font-size: 13px;
    color: var(--gray-400);
  }
  .os-loading {
    padding: 32px 16px;
    text-align: center;
    font-size: 13px;
    color: var(--gray-400);
  }
  .os-loading i { animation: os-spin 0.8s linear infinite; display: inline-block; }
  @keyframes os-spin { to { transform: rotate(360deg); } }

  [data-theme="dark"] .os-table th { border-bottom-color: #21262d; }
  [data-theme="dark"] .os-table td { border-bottom-color: #161b22; }
  [data-theme="dark"] .os-table tbody tr:hover { background: #21262d; }
  [data-theme="dark"] .os-list-foot { border-top-color: #21262d; }
  [data-theme="dark"] .os-pag-btn { background: #21262d; border-color: #30363d; color: #8b949e; }

  /* ── Modal detalhe ── */
  .detail-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
  .detail-field { display: flex; flex-direction: column; gap: 4px; }
  .detail-field.span-2 { grid-column: span 2; }
  .detail-field.span-3 { grid-column: span 3; }
  .detail-label { font-size: 11px; color: var(--gray-400); }
  .detail-value {
    font-size: 13px;
    color: var(--gray-950);
    line-height: 1.45;
    word-break: break-word;
  }
  @media (max-width: 700px) {
    .detail-grid { grid-template-columns: 1fr 1fr; }
    .detail-field.span-2, .detail-field.span-3 { grid-column: span 2; }
  }
  @media (max-width: 480px) {
    .detail-grid { grid-template-columns: 1fr; }
    .detail-field.span-2, .detail-field.span-3 { grid-column: span 1; }
  }
  .detail-descricao {
    font-size: 13px;
    color: var(--gray-800);
    line-height: 1.5;
    word-break: break-word;
  }
  .detail-descricao img { max-width: 100%; height: auto; border-radius: var(--radius-sm); }
  .detail-error { color: #dc2626; font-size: 13px; padding: 8px 0; }
  .os-table tbody tr.is-active { background: var(--blue-50); }
  [data-theme="dark"] .os-table tbody tr.is-active { background: #0d2340; }
</style>
@endsection

@section('content')

<div class="os-page">

  <div class="os-stats" id="metrics-row">
    <div class="os-stat">
      <span class="os-stat-n" id="metric-total">—</span>
      <span class="os-stat-l">ordens de serviço</span>
    </div>
    <div class="os-stat os-stat--aberta">
      <span class="os-stat-n" id="metric-aberta">—</span>
      <span class="os-stat-l">abertas</span>
    </div>
    <div class="os-stat os-stat--andamento">
      <span class="os-stat-n" id="metric-andamento">—</span>
      <span class="os-stat-l">em andamento</span>
    </div>
    <div class="os-stat os-stat--finalizada">
      <span class="os-stat-n" id="metric-finalizada">—</span>
      <span class="os-stat-l">finalizadas</span>
    </div>
    <div class="os-stat">
      <span class="os-stat-n" id="metric-tecnicos">—</span>
      <span class="os-stat-l">técnicos</span>
    </div>
  </div>

  <div class="os-toolbar">
    <input type="text" id="filtro-busca" placeholder="Buscar OS, código, técnico…"
      oninput="aplicarFiltrosDebounce()" class="os-toolbar-search"/>
    <select id="filtro-regiao" onchange="aplicarFiltros()">
      <option value="">Região</option>
      <option>Goval</option>
      <option>Vale do Aço</option>
      <option>Caratinga</option>
      <option>Teste</option>
    </select>
    <select id="filtro-tecnico" onchange="aplicarFiltros()">
      <option value="">Técnico</option>
    </select>
    <select id="filtro-status" onchange="aplicarFiltros()">
      <option value="">Status</option>
      <option value="Aberta">Aberta</option>
      <option value="Em andamento">Em andamento</option>
      <option value="Finalizada">Finalizada</option>
    </select>
    <button type="button" class="os-toolbar-btn" onclick="toggleFiltrosExtra()">
      <span id="btn-filtros-extra-label">Mais filtros</span>
    </button>
    <button type="button" class="os-toolbar-btn" onclick="limparFiltros()">Limpar</button>
    <button type="button" class="os-toolbar-btn os-toolbar-btn--export" id="btn-exportar-planilha" onclick="exportarPlanilha()" title="Exportar planilha com os filtros atuais">
      <i class="ti ti-download" style="font-size:14px;"></i> Exportar
    </button>

    <div class="os-filtros-extra" id="filtros-extra">
      <select id="filtro-categoria-pai" onchange="aplicarFiltros()">
        <option value="">Origem</option>
        <option value="rompimentos">Rompimentos</option>
        <option value="troca-poste">Troca de poste</option>
        <option value="troca-etiqueta">Troca de etiqueta</option>
        <option value="otimizacao-rede">Otimização de rede</option>
        <option value="atendimento-cliente">Atendimento</option>
      </select>
      <select id="filtro-prioridade" onchange="aplicarFiltros()">
        <option value="">Prioridade</option>
        <option>Alta</option>
        <option>Média</option>
        <option>Baixa</option>
      </select>
      <select id="filtro-tipo-data" onchange="aplicarFiltros()">
        <option value="criacao">Criação</option>
        <option value="conclusao">Conclusão</option>
      </select>
      <input type="date" id="filtro-data-inicio" onchange="aplicarFiltros()" title="Data início"/>
      <input type="date" id="filtro-data-fim" onchange="aplicarFiltros()" title="Data fim"/>
      <span id="filtro-periodo-ativo" class="os-periodo-hint"></span>
    </div>
  </div>

  <div class="os-layout">
    <div class="os-panel">
      <div class="os-panel-head">
        Por técnico
        <span class="os-panel-meta" id="total-tecnicos-label">—</span>
      </div>
      <div id="tabela-tecnicos-wrap">
        <div class="os-loading">Carregando…</div>
      </div>
    </div>

    <div class="os-aside">
      <div class="os-panel">
        <div class="os-panel-head">Região</div>
        <div class="os-mini-list" id="lista-regioes">
          <div class="os-loading">…</div>
        </div>
      </div>
      <div class="os-panel">
        <div class="os-panel-head">Origem</div>
        <div class="os-mini-list" id="lista-categorias">
          <div class="os-loading">…</div>
        </div>
      </div>
    </div>
  </div>

  <div class="os-panel">
    <div class="os-panel-head">
      Ordens de serviço
      <span class="os-panel-meta"><span id="lista-total">0</span> registros</span>
    </div>
    <div class="os-table-wrap" id="tabela-os-wrap">
      <div class="os-loading">Carregando…</div>
    </div>
    <div class="os-list-foot">
      <span class="os-list-foot-info" id="lista-paginacao-info">—</span>
      <div class="os-pag">
        <button class="os-pag-btn" id="btn-pag-anterior" onclick="paginaAnterior()" disabled>Anterior</button>
        <button class="os-pag-btn" id="btn-pag-proxima" onclick="paginaProxima()" disabled>Próxima</button>
      </div>
    </div>
  </div>

</div>

<x-modal id="detalhe-overlay" titulo-id="detalhe-titulo" subtitulo-id="detalhe-subtitulo" fechar="fecharDetalhe()">
  <div id="detalhe-conteudo"></div>
  <x-slot name="footer">
    <div class="modal-foot-os">
      <div class="modal-foot-os-left">
        <button type="button" class="os-btn-anexo-round" id="ordem-os-detalhe-btn-anexo" title="Anexar imagem">
          <i class="ti ti-paperclip"></i>
        </button>
        <input type="file" id="ordem-os-detalhe-input-anexo" accept="image/*" multiple hidden />
      </div>
      <div class="modal-foot-os-actions">
        <button onclick="fecharDetalhe()" class="btn-modal btn-modal-ghost">Fechar</button>
      </div>
    </div>
  </x-slot>
</x-modal>

@endsection

@section('scripts')
<script>
  window.abrirNovoItem = function () {
    if (typeof window.carregarOrdemServicoDashboard === 'function') {
      window.carregarOrdemServicoDashboard(true);
    }
  };
  document.getElementById('btn-topbar-atualizar')?.addEventListener('click', () => {
    window.abrirNovoItem();
  });
</script>
<script type="module">
  import { getUrl } from '{{ asset("js/api/client.js") }}';

  const PAGE_SIZE = 50;
  let offsetAtual = 0;
  let totalLista = 0;
  let debounceTimer = null;
  let filtrosExtraAbertos = false;
  let osDetalheAtivaId = null;

  window.fecharDetalhe = function() {
    document.getElementById('detalhe-overlay')?.classList.remove('open');
    osDetalheAtivaId = null;
    document.querySelectorAll('.os-table tbody tr.is-active').forEach((row) => {
      row.classList.remove('is-active');
    });
  };

  function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  window.toggleFiltrosExtra = function() {
    filtrosExtraAbertos = !filtrosExtraAbertos;
    document.getElementById('filtros-extra').classList.toggle('open', filtrosExtraAbertos);
    document.getElementById('btn-filtros-extra-label').textContent =
      filtrosExtraAbertos ? 'Menos filtros' : 'Mais filtros';
  };

  function obterFiltros() {
    return {
      busca: document.getElementById('filtro-busca').value.trim(),
      regiao: document.getElementById('filtro-regiao').value,
      tecnico: document.getElementById('filtro-tecnico').value,
      status: document.getElementById('filtro-status').value,
      categoriaPai: document.getElementById('filtro-categoria-pai').value,
      prioridade: document.getElementById('filtro-prioridade').value,
      tipoData: document.getElementById('filtro-tipo-data').value,
      dataInicio: document.getElementById('filtro-data-inicio').value,
      dataFim: document.getElementById('filtro-data-fim').value,
    };
  }

  function atualizarIndicadorPeriodo(filtros) {
    const el = document.getElementById('filtro-periodo-ativo');
    if (!el) return;
    if (!filtros.dataInicio && !filtros.dataFim) {
      el.classList.remove('visible');
      el.textContent = '';
      return;
    }
    const tipo = filtros.tipoData === 'conclusao' ? 'conclusão' : 'criação';
    const de = filtros.dataInicio ? formatarData(filtros.dataInicio) : '…';
    const ate = filtros.dataFim ? formatarData(filtros.dataFim) : '…';
    el.textContent = `Período (${tipo}): ${de} – ${ate}`;
    el.classList.add('visible');
    if (!filtrosExtraAbertos) toggleFiltrosExtra();
  }

  function filtrosParaQuery(filtros) {
    const params = new URLSearchParams();
    const ativos = { ...filtros };

    if (!ativos.dataInicio && !ativos.dataFim) {
      delete ativos.tipoData;
    }

    Object.entries(ativos).forEach(([chave, valor]) => {
      if (valor != null && String(valor).trim() !== '') params.set(chave, valor);
    });

    return params.toString();
  }

  function obterFiltrosParaApi() {
    return validarPeriodoFiltros(obterFiltros());
  }

  function statusDot(status) {
    const cls = status === 'Finalizada' ? 'status-dot--finalizada'
      : status === 'Em andamento' ? 'status-dot--andamento' : 'status-dot--aberta';
    return `<span class="status-dot ${cls}">${esc(status)}</span>`;
  }

  function techNum(n) {
    return `<span class="os-tech-num${n ? '' : ' is-zero'}">${n}</span>`;
  }

  function renderTabelaTecnicos(porTecnico) {
    const wrap = document.getElementById('tabela-tecnicos-wrap');
    if (!porTecnico.length) {
      wrap.innerHTML = '<div class="os-empty">Nenhuma OS com os filtros atuais</div>';
      return;
    }
    wrap.innerHTML = `
      <div class="os-tech-head">
        <span>Técnico</span>
        <span title="Aberta">Ab</span>
        <span title="Em andamento">And</span>
        <span title="Finalizada">Fin</span>
        <span>Total</span>
      </div>
      <div class="os-tech-list">
        ${porTecnico.map(row => `
          <div class="os-tech-row row-tecnico-filter" data-tecnico="${esc(row.tecnico)}">
            <div>
              <div class="os-tech-name">${esc(row.tecnico)}</div>
              ${row.regiao ? `<div class="os-tech-reg">${esc(row.regiao)}</div>` : ''}
            </div>
            ${techNum(row.aberta)}
            ${techNum(row.em_andamento)}
            ${techNum(row.finalizada)}
            <span class="os-tech-total">${row.total}</span>
          </div>
        `).join('')}
      </div>`;
  }

  function renderListaRegioes(porRegiao) {
    const el = document.getElementById('lista-regioes');
    if (!porRegiao.length) {
      el.innerHTML = '<div class="os-empty">—</div>';
      return;
    }
    el.innerHTML = porRegiao.map(r => `
      <div class="os-mini-item row-regiao-filter" data-regiao="${esc(r.regiao)}">
        <span class="os-mini-label">${esc(r.regiao)}</span>
        <span class="os-mini-count">${r.total}</span>
      </div>
    `).join('');
  }

  function renderListaCategorias(porCategoria) {
    const el = document.getElementById('lista-categorias');
    if (!porCategoria.length) {
      el.innerHTML = '<div class="os-empty">—</div>';
      return;
    }
    el.innerHTML = porCategoria.map(c => `
      <div class="os-mini-item">
        <span class="os-mini-label">${esc(c.categoria)}</span>
        <span class="os-mini-count">${c.total}</span>
      </div>
    `).join('');
  }

  function renderTabelaOs(items) {
    const wrap = document.getElementById('tabela-os-wrap');
    if (!items.length) {
      wrap.innerHTML = '<div class="os-empty">Nenhuma ordem de serviço encontrada</div>';
      return;
    }
    wrap.innerHTML = `
      <table class="os-table">
        <thead>
          <tr>
            <th>OS</th>
            <th>Técnico</th>
            <th>Status</th>
            <th>Origem</th>
            <th>Data</th>
          </tr>
        </thead>
        <tbody>
          ${items.map(os => `
            <tr data-id="${os.id}" class="os-row-detalhe">
              <td>
                <div class="os-cell-main">${esc(os.numero_os || os.taskCode || '—')}</div>
                ${os.titulo ? `<div class="os-cell-sub">${esc(os.titulo)}</div>` : ''}
              </td>
              <td>
                <div>${esc(os.tecnico)}</div>
                ${os.regiao ? `<div class="os-cell-sub">${esc(os.regiao)}</div>` : ''}
              </td>
              <td>${statusDot(os.status)}</td>
              <td class="os-cell-muted">${esc(os.categoria_pai_label || '—')}</td>
              <td class="os-cell-muted">${formatarData(os.data_criacao || os.criadaEm)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>`;
  }

  function formatarData(valor) {
    if (!valor) return '—';
    const d = new Date(valor);
    if (isNaN(d)) return esc(String(valor).slice(0, 10));
    return d.toLocaleDateString('pt-BR');
  }

  function formatarDataHora(valor) {
    if (!valor) return '—';
    const d = new Date(valor);
    if (isNaN(d)) return esc(String(valor));
    return d.toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  function preenchido(valor) {
    if (valor == null || valor === undefined) return false;
    if (typeof valor === 'number' && !Number.isNaN(valor)) return true;
    const texto = String(valor).trim();
    return texto !== '' && texto !== '—' && texto !== '-';
  }

  function dataValida(valor) {
    if (!preenchido(valor)) return false;
    const d = new Date(valor);
    return !Number.isNaN(d.getTime());
  }

  function campoDetalhe(label, valor, span = 1) {
    if (!preenchido(valor)) return '';
    const spanClass = span === 3 ? ' span-3' : span === 2 ? ' span-2' : '';
    return `
      <div class="detail-field${spanClass}">
        <span class="detail-label">${label}</span>
        <div class="detail-value">${valor}</div>
      </div>`;
  }

  function renderDescricaoDetalhe(descricao) {
    const bruto = String(descricao || '').trim();
    if (!bruto) return '';
    const texto = /<[a-z][\s\S]*>/i.test(bruto)
      ? bruto.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
      : bruto;
    if (!texto) return '';
    return esc(texto).replace(/\r?\n/g, '<br>');
  }

  async function carregarBlobAutenticado(url) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(url, {
      headers: { Authorization: 'Bearer ' + token },
      cache: 'no-store',
    });
    if (!response.ok) return null;
    const blob = await response.blob();
    return URL.createObjectURL(blob);
  }

  async function montarGaleriaAnexosDetalhe(osId) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/op-tasks/${osId}/anexos`, {
      headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
      cache: 'no-store',
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || !Array.isArray(payload.anexos) || !payload.anexos.length) {
      return '<div class="os-anexos-vazio">Nenhum anexo vinculado a esta OS.</div>';
    }

    const cards = await Promise.all(payload.anexos.map(async (anexo) => {
      const blobUrl = await carregarBlobAutenticado(anexo.url);
      if (!blobUrl) return '';
      const nome = esc(anexo.nome_arquivo || 'Imagem');
      return `
        <div class="os-anexo-detalhe-item">
          <button type="button" class="os-anexo-detalhe-card"
            data-anexo-src="${blobUrl}"
            data-anexo-nome="${nome}"
            data-anexo-download="${anexo.url}"
            title="Clique para ampliar">
            <img src="${blobUrl}" alt="${nome}">
          </button>
          <button type="button" class="os-anexo-detalhe-remover" data-anexo-id="${anexo.id}" title="Excluir anexo">
            <i class="ti ti-trash"></i>
          </button>
        </div>`;
    }));

    const conteudo = cards.filter(Boolean).join('');
    return conteudo || '<div class="os-anexos-vazio">Nenhum anexo vinculado a esta OS.</div>';
  }

  async function montarAnexosDetalhe(osId) {
    const galeria = await montarGaleriaAnexosDetalhe(osId);

    return `
      <div class="detail-field span-3" id="ordem-os-detalhe-anexos-wrap" style="margin-top:16px">
        <span class="detail-label">Anexos</span>
        <div class="detail-value" style="min-height:auto;padding:10px">
          <div class="os-anexos-detalhe" id="ordem-os-detalhe-anexos-galeria">${galeria}</div>
        </div>
      </div>`;
  }

  async function atualizarAnexosOrdemOs(osId) {
    const galeria = document.getElementById('ordem-os-detalhe-anexos-galeria');
    if (!galeria) return;
    galeria.innerHTML = '<div class="os-anexos-vazio"><i class="ti ti-loader-2"></i> Atualizando anexos…</div>';
    galeria.innerHTML = await montarGaleriaAnexosDetalhe(osId);
  }

  function renderDetalheLoading() {
    document.getElementById('detalhe-titulo').textContent = 'Ordem de serviço';
    document.getElementById('detalhe-subtitulo').textContent = 'Carregando…';
    document.getElementById('detalhe-conteudo').innerHTML = '<div class="os-loading"><i class="ti ti-loader-2"></i> Carregando detalhes…</div>';
  }

  function renderDetalheErro(mensagem) {
    document.getElementById('detalhe-titulo').textContent = 'Ordem de serviço';
    document.getElementById('detalhe-subtitulo').textContent = 'Erro';
    document.getElementById('detalhe-conteudo').innerHTML = `<div class="detail-error">${esc(mensagem)}</div>`;
  }

  async function renderDetalheOs(os) {
    const tituloModal = os.numero_os || os.taskCode || `OS #${os.id}`;
    const setorCto = [os.setor, os.cto].filter((valor) => preenchido(valor)).join(' · ');
    const regiao = os.regiao || os.tecnico_regiao || '';
    const numeroOs = os.numero_os || os.ordem_servico || '';
    const origemLabel = os.categoria_pai_label || '';
    const origemCompleta = preenchido(os.task_code_pai)
      ? `${origemLabel} · ${os.task_code_pai}`
      : origemLabel;
    const tecnicos = Array.isArray(os.tecnicos) && os.tecnicos.length
      ? os.tecnicos.map((nome) => esc(nome)).join(', ')
      : (preenchido(os.tecnico) ? esc(os.tecnico) : '');
    const dataCriacao = os.criadaEm || os.data_criacao;
    const dataConclusao = os.data_conclusao || os.assinada_em;
    const descricaoHtml = renderDescricaoDetalhe(os.descricao);
    const campos = [
      campoDetalhe('Técnico(s)', tecnicos),
      campoDetalhe('Status', preenchido(os.status) ? statusDot(os.status) : ''),
      campoDetalhe('Região', preenchido(regiao) ? esc(regiao) : ''),
      campoDetalhe('Número da OS', preenchido(numeroOs) ? esc(numeroOs) : ''),
      campoDetalhe('Código', preenchido(os.taskCode) ? esc(os.taskCode) : ''),
      campoDetalhe('Prioridade', preenchido(os.prioridade) ? esc(os.prioridade) : ''),
      campoDetalhe('Título', preenchido(os.titulo) ? esc(os.titulo) : '', 3),
      campoDetalhe('Origem', preenchido(origemCompleta) && origemCompleta !== 'Sem vínculo' ? esc(origemCompleta) : ''),
      campoDetalhe('Protocolo', preenchido(os.protocolo) ? esc(os.protocolo) : ''),
      campoDetalhe('Cliente', preenchido(os.nome_cliente) ? esc(os.nome_cliente) : ''),
      campoDetalhe('Setor / CTO', preenchido(setorCto) ? esc(setorCto) : '', 2),
      campoDetalhe('Localização', preenchido(os.localizacao_texto) ? esc(os.localizacao_texto) : '', 2),
      campoDetalhe('Coordenadas', preenchido(os.coordenadas) ? esc(os.coordenadas) : ''),
      campoDetalhe('Criada em', dataValida(dataCriacao) ? formatarDataHora(dataCriacao) : ''),
      campoDetalhe('Concluída em', dataValida(dataConclusao) ? formatarData(dataConclusao) : ''),
      campoDetalhe('Assinada por', preenchido(os.assinada_por) ? esc(os.assinada_por) : ''),
      campoDetalhe('Descrição', descricaoHtml, 3),
    ].filter(Boolean).join('');

    const anexosHtml = await montarAnexosDetalhe(os.id);

    document.getElementById('detalhe-titulo').textContent = tituloModal;
    document.getElementById('detalhe-subtitulo').textContent = os.titulo || 'Ordem de serviço';
    document.getElementById('detalhe-conteudo').innerHTML = campos || anexosHtml
      ? `<div class="detail-grid">${campos}${anexosHtml}</div>`
      : `<div class="detail-grid">${anexosHtml || ''}<div class="os-empty">Nenhum detalhe adicional para esta OS.</div></div>`;
  }

  window.getOrdemOsDetalheAtualId = () => osDetalheAtivaId;
  window.atualizarAnexosOrdemOs = atualizarAnexosOrdemOs;

  function atualizarMetricas(totais) {
    document.getElementById('metric-total').textContent = totais.total;
    document.getElementById('metric-aberta').textContent = totais.aberta;
    document.getElementById('metric-andamento').textContent = totais.em_andamento;
    document.getElementById('metric-finalizada').textContent = totais.finalizada;
    document.getElementById('metric-tecnicos').textContent = totais.tecnicos;
    document.getElementById('total-tecnicos-label').textContent = totais.tecnicos + ' técnicos';
  }

  function atualizarPaginacao() {
    const inicio = totalLista === 0 ? 0 : offsetAtual + 1;
    const fim = Math.min(offsetAtual + PAGE_SIZE, totalLista);
    document.getElementById('lista-total').textContent = totalLista;
    document.getElementById('lista-paginacao-info').textContent =
      totalLista ? `${inicio}–${fim} de ${totalLista}` : 'Nenhum registro';
    document.getElementById('btn-pag-anterior').disabled = offsetAtual <= 0;
    document.getElementById('btn-pag-proxima').disabled = offsetAtual + PAGE_SIZE >= totalLista;
  }

  async function carregarTecnicosSelect(regiao) {
    const select = document.getElementById('filtro-tecnico');
    const valorAtual = select.value;
    const qs = regiao ? `?regiao=${encodeURIComponent(regiao)}` : '';
    try {
      const tecnicos = await getUrl('tecnicos' + qs);
      select.innerHTML = '<option value="">Técnico</option>' +
        (Array.isArray(tecnicos) ? tecnicos : []).map(t =>
          `<option value="${esc(t.nome)}">${esc(t.nome)}</option>`
        ).join('');
      if (valorAtual) select.value = valorAtual;
    } catch (e) {
      console.error(e);
    }
  }

  function validarPeriodoFiltros(filtros) {
    if (filtros.dataInicio && filtros.dataFim && filtros.dataInicio > filtros.dataFim) {
      document.getElementById('filtro-data-fim').value = filtros.dataInicio;
      filtros.dataFim = filtros.dataInicio;
    }
    return filtros;
  }

  async function carregarOrdemServicoDashboard(resetPagina = true) {
    const gen = window.plannerBeginReload?.() ?? 0;
    if (resetPagina) offsetAtual = 0;
    const filtros = obterFiltrosParaApi();
    const qs = filtrosParaQuery(filtros);
    atualizarIndicadorPeriodo(filtros);

    try {
      const [dashboard, lista] = await Promise.all([
        getUrl('ordem-servico/dashboard' + (qs ? '?' + qs : '')),
        getUrl(`ordem-servico?limit=${PAGE_SIZE}&offset=${offsetAtual}` + (qs ? '&' + qs : '')),
      ]);

      if (window.plannerIsReloadCurrent && !window.plannerIsReloadCurrent(gen)) return;

      atualizarMetricas(dashboard.totais);
      renderTabelaTecnicos(dashboard.por_tecnico || []);
      renderListaRegioes(dashboard.por_regiao || []);
      renderListaCategorias(dashboard.por_categoria_pai || []);

      totalLista = lista.total || 0;
      renderTabelaOs(lista.items || []);
      atualizarPaginacao();
    } catch (err) {
      console.error(err);
      document.getElementById('tabela-tecnicos-wrap').innerHTML =
        '<div class="os-empty" style="color:#dc2626;">Erro ao carregar. Verifique o login.</div>';
    }
  }

  window.carregarOrdemServicoDashboard = carregarOrdemServicoDashboard;
  window.abrirNovoItem = function () {
    carregarOrdemServicoDashboard(true);
  };

  window.exportarPlanilha = async function () {
    const btn = document.getElementById('btn-exportar-planilha');
    const filtros = obterFiltrosParaApi();
    const qs = filtrosParaQuery(filtros);
    const token = localStorage.getItem('planner_token');

    if (btn) btn.disabled = true;

    try {
      const response = await fetch('/api/ordem-servico/exportar' + (qs ? '?' + qs : ''), {
        headers: { Authorization: 'Bearer ' + token },
      });

      if (!response.ok) {
        const erro = await response.json().catch(() => ({}));
        throw new Error(erro.message || 'Não foi possível gerar a planilha.');
      }

      const blob = await response.blob();
      const disposition = response.headers.get('Content-Disposition') || '';
      const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
      const nomeArquivo = match?.[1] || `ordens-servico-${new Date().toISOString().slice(0, 10)}.xlsx`;

      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = nomeArquivo;
      link.click();
      URL.revokeObjectURL(link.href);
    } catch (err) {
      console.error(err);
      alert(err.message || 'Erro ao exportar planilha.');
    } finally {
      if (btn) btn.disabled = false;
    }
  };

  window.aplicarFiltros = function() {
    carregarTecnicosSelect(document.getElementById('filtro-regiao').value)
      .then(() => carregarOrdemServicoDashboard(true));
  };

  window.aplicarFiltrosDebounce = function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => carregarOrdemServicoDashboard(true), 400);
  };

  window.limparFiltros = function() {
    ['filtro-busca','filtro-regiao','filtro-tecnico','filtro-status','filtro-categoria-pai',
     'filtro-prioridade','filtro-data-inicio','filtro-data-fim'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    document.getElementById('filtro-tipo-data').value = 'criacao';
    atualizarIndicadorPeriodo(obterFiltros());
    carregarTecnicosSelect('').then(() => carregarOrdemServicoDashboard(true));
  };

  window.filtrarPorTecnico = function(nome) {
    document.getElementById('filtro-tecnico').value = nome;
    carregarOrdemServicoDashboard(true);
  };

  window.filtrarPorRegiao = function(regiao) {
    if (regiao === 'Sem região') return;
    document.getElementById('filtro-regiao').value = regiao;
    aplicarFiltros();
  };

  window.paginaAnterior = function() {
    offsetAtual = Math.max(0, offsetAtual - PAGE_SIZE);
    carregarOrdemServicoDashboard(false);
  };

  window.paginaProxima = function() {
    if (offsetAtual + PAGE_SIZE < totalLista) {
      offsetAtual += PAGE_SIZE;
      carregarOrdemServicoDashboard(false);
    }
  };

  window.abrirDetalhe = async function(id) {
    const overlay = document.getElementById('detalhe-overlay');
    if (!overlay || !id) return;

    osDetalheAtivaId = String(id);
    document.querySelectorAll('.os-table tbody tr.is-active').forEach((row) => {
      row.classList.toggle('is-active', row.dataset.id === osDetalheAtivaId);
    });

    overlay.classList.add('open');
    renderDetalheLoading();

    try {
      const resp = await getUrl('ordem-servico/' + id);
      if (osDetalheAtivaId !== String(id)) return;
      await renderDetalheOs(resp.os || {});
    } catch (e) {
      if (osDetalheAtivaId !== String(id)) return;
      renderDetalheErro(e.message || 'Não foi possível carregar os detalhes.');
    }
  };

  document.getElementById('tabela-os-wrap').addEventListener('click', (e) => {
    const row = e.target.closest('.os-row-detalhe');
    if (!row?.dataset.id) return;
    window.abrirDetalhe(row.dataset.id);
  });

  document.getElementById('detalhe-overlay')?.addEventListener('click', (e) => {
    if (e.target.id === 'detalhe-overlay') window.fecharDetalhe();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('detalhe-overlay')?.classList.contains('open')) {
      window.fecharDetalhe();
    }
  });

  document.getElementById('filtro-regiao').addEventListener('change', () => {
    carregarTecnicosSelect(document.getElementById('filtro-regiao').value);
  });

  carregarTecnicosSelect('').then(() => carregarOrdemServicoDashboard(true));

  document.getElementById('tabela-tecnicos-wrap').addEventListener('click', (e) => {
    const row = e.target.closest('.row-tecnico-filter');
    if (row?.dataset.tecnico) filtrarPorTecnico(row.dataset.tecnico);
  });

  document.getElementById('lista-regioes').addEventListener('click', (e) => {
    const row = e.target.closest('.row-regiao-filter');
    if (row?.dataset.regiao && row.dataset.regiao !== 'Sem região') filtrarPorRegiao(row.dataset.regiao);
  });
</script>
@endsection
