@extends('layouts.app')

@section('title', 'Ordem de Serviço — Planner Telecom')
@section('page-title', 'Ordem de Serviço')
@section('btn-label', 'Atualizar')

@section('styles')
<style>
  .os-page { width: 100%; max-width: 100%; min-width: 0; }

  .filtros-card { margin-bottom: 0; }
  .filtros-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    padding: 16px;
    width: 100%;
    box-sizing: border-box;
  }
  .filtro-campo { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
  .filtro-campo--full { grid-column: 1 / -1; }
  .filtro-campo--periodo { grid-column: 1 / -1; }
  .filtro-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .filtro-input,
  .filtro-select,
  .filtro-date {
    width: 100%;
    height: 38px;
    padding: 0 10px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    background: var(--white);
    color: var(--gray-950);
    font-family: inherit;
    font-size: 13px;
    box-sizing: border-box;
    outline: none;
    min-width: 0;
  }
  .filtro-input:focus,
  .filtro-select:focus,
  .filtro-date:focus { border-color: var(--blue-600); }
  .filtro-periodo-linha {
    display: grid;
    grid-template-columns: minmax(140px, 1fr) minmax(140px, 1fr) minmax(140px, 1fr) auto;
    gap: 12px;
    align-items: end;
    padding-top: 12px;
    margin-top: 4px;
    border-top: 1px solid var(--gray-200);
  }
  .filtro-periodo-ativo {
    display: none;
    align-items: center;
    min-height: 38px;
    padding: 0 10px;
    font-size: 12px;
    color: var(--gray-600);
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .filtro-limpar-btn {
    height: 38px;
    padding: 0 14px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    background: var(--white);
    color: var(--gray-600);
    font-size: 12px;
    font-family: inherit;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    white-space: nowrap;
  }
  .filtro-limpar-btn:hover { background: var(--gray-50); color: var(--gray-950); }

  [data-theme="dark"] .filtro-input,
  [data-theme="dark"] .filtro-select,
  [data-theme="dark"] .filtro-date,
  [data-theme="dark"] .filtro-limpar-btn {
    background: #21262d;
    border-color: #30363d;
    color: #e6edf3;
  }
  [data-theme="dark"] .filtro-periodo-ativo {
    background: #161b22;
    border-color: #30363d;
    color: #8b949e;
  }
  [data-theme="dark"] .filtro-periodo-linha { border-top-color: #30363d; }

  @media (max-width: 1100px) {
    .filtros-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .filtro-periodo-linha { grid-template-columns: 1fr 1fr; }
    .filtro-periodo-ativo { grid-column: 1 / -1; }
    .filtro-limpar-btn { grid-column: 1 / -1; width: 100%; }
  }
  @media (max-width: 600px) {
    .filtros-grid { grid-template-columns: 1fr; }
    .filtro-periodo-linha { grid-template-columns: 1fr; }
  }

  .os-dashboard-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
  }
  @media (max-width: 1100px) { .os-dashboard-grid { grid-template-columns: 1fr; } }

  .os-table-wrap { overflow-x: auto; }
  .os-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }
  .os-table th {
    text-align: left;
    padding: 10px 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--gray-500);
    border-bottom: 1px solid var(--gray-200);
    white-space: nowrap;
  }
  .os-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800);
    vertical-align: middle;
  }
  .os-table tbody tr { cursor: pointer; transition: background 0.12s; }
  .row-tecnico-filter { cursor: pointer; }
  .row-regiao-filter { cursor: pointer; }
  .os-table tbody tr:hover { background: var(--gray-50); }
  .os-table .num { text-align: center; font-variant-numeric: tabular-nums; font-weight: 600; }
  .os-table .num-zero { color: var(--gray-300); font-weight: 400; }
  .os-table .tecnico-cell { font-weight: 500; color: var(--gray-950); }
  .os-table .tecnico-sub { font-size: 11px; color: var(--gray-500); margin-top: 2px; }

  .os-bar-cell { min-width: 120px; }
  .os-mini-bar {
    height: 6px;
    border-radius: 99px;
    background: var(--gray-100);
    overflow: hidden;
    display: flex;
  }
  .os-mini-bar span { height: 100%; }
  .os-bar-aberta { background: #3b82f6; }
  .os-bar-andamento { background: #f59e0b; }
  .os-bar-finalizada { background: #22c55e; }

  .region-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; }
  .region-name { width: 110px; font-size: 12px; color: var(--gray-700); flex-shrink: 0; }
  .region-bar-wrap { flex: 1; height: 8px; background: var(--gray-100); border-radius: 99px; overflow: hidden; }
  .region-bar { height: 100%; background: linear-gradient(90deg, #166ac4, #3b82f6); border-radius: 99px; transition: width 0.4s ease; }
  .region-n { width: 36px; text-align: right; font-size: 12px; font-weight: 600; color: var(--gray-800); }

  .cat-pill-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid var(--gray-100); font-size: 13px; }
  .cat-pill-row:last-child { border-bottom: none; }
  .cat-pill-label { color: var(--gray-700); }
  .cat-pill-count { font-weight: 600; color: var(--gray-950); }

  .status-pill {
    display: inline-flex; align-items: center; padding: 2px 8px;
    border-radius: 99px; font-size: 11px; font-weight: 500; white-space: nowrap;
  }
  .status-aberta { background: #dbeafe; color: #1d4ed8; }
  .status-andamento { background: #fef3c7; color: #b45309; }
  .status-finalizada { background: #dcfce7; color: #15803d; }

  .os-list-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-top: 1px solid var(--gray-200); gap: 12px; flex-wrap: wrap;
  }
  .os-pagination { display: flex; align-items: center; gap: 6px; }
  .os-page-btn {
    border: 1px solid var(--gray-200); background: var(--white); color: var(--gray-700);
    border-radius: var(--radius-sm); padding: 4px 10px; font-size: 12px; cursor: pointer;
    font-family: inherit;
  }
  .os-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
  .os-page-btn:not(:disabled):hover { border-color: var(--blue-600); color: var(--blue-600); }

  .os-loading {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 48px 16px; color: var(--gray-500); font-size: 13px;
  }
  .os-loading i { animation: spin 0.9s linear infinite; }
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

  .detail-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
  .detail-field { display: flex; flex-direction: column; gap: 5px; }
  .detail-field.span-2 { grid-column: span 2; }
  .detail-field.span-3 { grid-column: span 3; }
  .detail-label { font-size: 12px; font-weight: 500; color: var(--gray-500); }
  .detail-value {
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    padding: 8px 10px; min-height: 38px; font-size: 13px; color: var(--gray-950);
    background: var(--gray-50); line-height: 1.4; word-break: break-word;
  }

  @media (max-width: 900px) {
    .detail-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 600px) {
    .detail-grid { grid-template-columns: 1fr; }
    .detail-field.span-2, .detail-field.span-3 { grid-column: span 1; }
  }
</style>
@endsection

@section('content')

<div class="os-page">

<div class="card filtros-card">
  <div class="card-header">
    <span class="card-title"><i class="ti ti-filter" style="font-size:14px;margin-right:6px;"></i>Filtros</span>
    <button type="button" onclick="limparFiltros()" class="filtro-limpar-btn">
      <i class="ti ti-x" style="font-size:12px"></i> Limpar
    </button>
  </div>
  <div class="filtros-grid">
    <div class="filtro-campo filtro-campo--full">
      <label class="filtro-label" for="filtro-busca">Buscar</label>
      <input type="text" id="filtro-busca" placeholder="OS, código, técnico..."
        oninput="aplicarFiltrosDebounce()" class="filtro-input"/>
    </div>
    <div class="filtro-campo">
      <label class="filtro-label" for="filtro-regiao">Região</label>
      <select id="filtro-regiao" onchange="aplicarFiltros()" class="filtro-select">
        <option value="">Todas</option>
        <option>Goval</option>
        <option>Vale do Aço</option>
        <option>Caratinga</option>
        <option>Teste</option>
      </select>
    </div>
    <div class="filtro-campo">
      <label class="filtro-label" for="filtro-tecnico">Técnico</label>
      <select id="filtro-tecnico" onchange="aplicarFiltros()" class="filtro-select">
        <option value="">Todos</option>
      </select>
    </div>
    <div class="filtro-campo">
      <label class="filtro-label" for="filtro-status">Status</label>
      <select id="filtro-status" onchange="aplicarFiltros()" class="filtro-select">
        <option value="">Todos</option>
        <option value="Aberta">Aberta</option>
        <option value="Em andamento">Em andamento</option>
        <option value="Finalizada">Finalizada</option>
      </select>
    </div>
    <div class="filtro-campo">
      <label class="filtro-label" for="filtro-categoria-pai">Origem</label>
      <select id="filtro-categoria-pai" onchange="aplicarFiltros()" class="filtro-select">
        <option value="">Todas</option>
        <option value="rompimentos">Rompimentos</option>
        <option value="troca-poste">Troca de poste</option>
        <option value="otimizacao-rede">Otimização de rede</option>
        <option value="atendimento-cliente">Atendimento</option>
      </select>
    </div>
    <div class="filtro-campo">
      <label class="filtro-label" for="filtro-prioridade">Prioridade</label>
      <select id="filtro-prioridade" onchange="aplicarFiltros()" class="filtro-select">
        <option value="">Todas</option>
        <option>Alta</option>
        <option>Média</option>
        <option>Baixa</option>
      </select>
    </div>

    <div class="filtro-campo filtro-campo--periodo">
      <div class="filtro-periodo-linha">
        <div class="filtro-campo">
          <label class="filtro-label" for="filtro-tipo-data">Filtrar por data</label>
          <select id="filtro-tipo-data" onchange="aplicarFiltros()" class="filtro-select">
            <option value="criacao">Data de criação</option>
            <option value="conclusao">Data de conclusão</option>
          </select>
        </div>
        <div class="filtro-campo">
          <label class="filtro-label" for="filtro-data-inicio">Início</label>
          <input type="date" id="filtro-data-inicio" onchange="aplicarFiltros()" class="filtro-date"/>
        </div>
        <div class="filtro-campo">
          <label class="filtro-label" for="filtro-data-fim">Fim</label>
          <input type="date" id="filtro-data-fim" onchange="aplicarFiltros()" class="filtro-date"/>
        </div>
        <span id="filtro-periodo-ativo" class="filtro-periodo-ativo"></span>
      </div>
    </div>
  </div>
</div>

<div class="metrics-row" id="metrics-row">
  <div class="metric-card">
    <div class="metric-top">
      <div class="metric-label">Total de OS</div>
      <div class="metric-icon mi-blue"><i class="ti ti-clipboard-list"></i></div>
    </div>
    <div class="metric-value" id="metric-total">—</div>
    <div class="metric-sub"><span id="metric-tecnicos">—</span> técnicos com OS</div>
  </div>
  <div class="metric-card">
    <div class="metric-top">
      <div class="metric-label">Abertas</div>
      <div class="metric-icon mi-blue"><i class="ti ti-circle-dotted"></i></div>
    </div>
    <div class="metric-value" id="metric-aberta">—</div>
    <div class="metric-sub">aguardando execução</div>
  </div>
  <div class="metric-card">
    <div class="metric-top">
      <div class="metric-label">Em andamento</div>
      <div class="metric-icon mi-amber"><i class="ti ti-loader"></i></div>
    </div>
    <div class="metric-value" id="metric-andamento">—</div>
    <div class="metric-sub">em execução agora</div>
  </div>
  <div class="metric-card">
    <div class="metric-top">
      <div class="metric-label">Finalizadas</div>
      <div class="metric-icon mi-green"><i class="ti ti-circle-check"></i></div>
    </div>
    <div class="metric-value" id="metric-finalizada">—</div>
    <div class="metric-sub">concluídas no período</div>
  </div>
</div>

<div class="os-dashboard-grid">
  <div class="card">
    <div class="card-header">
      <span class="card-title">OS por técnico</span>
      <span class="card-action" id="total-tecnicos-label">—</span>
    </div>
    <div class="os-table-wrap" id="tabela-tecnicos-wrap">
      <div class="os-loading"><i class="ti ti-loader-2"></i> Carregando...</div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:12px;">
    <div class="card">
      <div class="card-header">
        <span class="card-title">Por região</span>
      </div>
      <div style="padding: 8px 16px 12px;" id="lista-regioes">
        <div class="os-loading"><i class="ti ti-loader-2"></i></div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <span class="card-title">Por origem (tarefa pai)</span>
      </div>
      <div style="padding: 8px 16px 12px;" id="lista-categorias">
        <div class="os-loading"><i class="ti ti-loader-2"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">Lista de ordens de serviço</span>
    <span class="card-action">total: <span id="lista-total">0</span></span>
  </div>
  <div class="os-table-wrap" id="tabela-os-wrap">
    <div class="os-loading"><i class="ti ti-loader-2"></i> Carregando...</div>
  </div>
  <div class="os-list-footer">
    <span style="font-size:12px;color:var(--gray-500);" id="lista-paginacao-info">—</span>
    <div class="os-pagination">
      <button class="os-page-btn" id="btn-pag-anterior" onclick="paginaAnterior()" disabled>Anterior</button>
      <button class="os-page-btn" id="btn-pag-proxima" onclick="paginaProxima()" disabled>Próxima</button>
    </div>
  </div>
</div>

</div>{{-- .os-page --}}

<x-modal id="detalhe-overlay" titulo-id="detalhe-titulo" subtitulo-id="detalhe-subtitulo" fechar="fecharDetalhe()">
  <div id="detalhe-conteudo"></div>
  <x-slot name="footer">
    <button onclick="fecharDetalhe()" class="btn-modal btn-modal-ghost">Fechar</button>
  </x-slot>
</x-modal>

@endsection

@section('scripts')
<script type="module">
  import { getUrl } from '{{ asset("js/api/client.js") }}';

  const PAGE_SIZE = 50;
  let offsetAtual = 0;
  let totalLista = 0;
  let debounceTimer = null;

  function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

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
      el.style.display = 'none';
      el.textContent = '';
      return;
    }
    const tipo = filtros.tipoData === 'conclusao' ? 'conclusão' : 'criação';
    const de = filtros.dataInicio ? formatarData(filtros.dataInicio) : '…';
    const ate = filtros.dataFim ? formatarData(filtros.dataFim) : '…';
    el.textContent = `Período (${tipo}): ${de} – ${ate}`;
    el.style.display = 'flex';
  }

  function filtrosParaQuery(filtros) {
    const params = new URLSearchParams();
    Object.entries(filtros).forEach(([chave, valor]) => {
      if (valor != null && String(valor).trim() !== '') params.set(chave, valor);
    });
    return params.toString();
  }

  function statusPill(status) {
    const cls = status === 'Finalizada' ? 'status-finalizada'
      : status === 'Em andamento' ? 'status-andamento' : 'status-aberta';
    return `<span class="status-pill ${cls}">${esc(status)}</span>`;
  }

  function numCell(n, tipo) {
    const cls = n > 0 ? `num os-bar-${tipo}` : 'num num-zero';
    return `<td class="${cls}">${n}</td>`;
  }

  function renderBarraTecnico(row) {
    const total = row.total || 1;
    const pAberta = Math.round((row.aberta / total) * 100);
    const pAndamento = Math.round((row.em_andamento / total) * 100);
    const pFinalizada = Math.round((row.finalizada / total) * 100);
    return `<div class="os-mini-bar" title="Aberta ${row.aberta} · Andamento ${row.em_andamento} · Finalizada ${row.finalizada}">
      ${row.aberta ? `<span class="os-bar-aberta" style="width:${pAberta}%"></span>` : ''}
      ${row.em_andamento ? `<span class="os-bar-andamento" style="width:${pAndamento}%"></span>` : ''}
      ${row.finalizada ? `<span class="os-bar-finalizada" style="width:${pFinalizada}%"></span>` : ''}
    </div>`;
  }

  function renderTabelaTecnicos(porTecnico) {
    const wrap = document.getElementById('tabela-tecnicos-wrap');
    if (!porTecnico.length) {
      wrap.innerHTML = '<div class="os-loading">Nenhuma OS encontrada com os filtros atuais.</div>';
      return;
    }
    wrap.innerHTML = `
      <table class="os-table">
        <thead>
          <tr>
            <th>Técnico</th>
            <th class="num">Aberta</th>
            <th class="num">Andamento</th>
            <th class="num">Finalizada</th>
            <th class="num">Total</th>
            <th>Distribuição</th>
          </tr>
        </thead>
        <tbody>
          ${porTecnico.map(row => `
            <tr data-tecnico="${esc(row.tecnico)}" class="row-tecnico-filter">
              <td>
                <div class="tecnico-cell">${esc(row.tecnico)}</div>
                ${row.regiao ? `<div class="tecnico-sub">${esc(row.regiao)}</div>` : ''}
              </td>
              ${numCell(row.aberta, 'aberta')}
              ${numCell(row.em_andamento, 'andamento')}
              ${numCell(row.finalizada, 'finalizada')}
              <td class="num">${row.total}</td>
              <td class="os-bar-cell">${renderBarraTecnico(row)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>`;
  }

  function renderListaRegioes(porRegiao) {
    const el = document.getElementById('lista-regioes');
    if (!porRegiao.length) {
      el.innerHTML = '<div style="font-size:13px;color:var(--gray-500);padding:8px 0;">Sem dados</div>';
      return;
    }
    const max = Math.max(...porRegiao.map(r => r.total), 1);
    el.innerHTML = porRegiao.map(r => `
      <div class="region-row row-regiao-filter" data-regiao="${esc(r.regiao)}">
        <div class="region-name">${esc(r.regiao)}</div>
        <div class="region-bar-wrap"><div class="region-bar" style="width:${Math.round((r.total / max) * 100)}%"></div></div>
        <div class="region-n">${r.total}</div>
      </div>
    `).join('');
  }

  function renderListaCategorias(porCategoria) {
    const el = document.getElementById('lista-categorias');
    if (!porCategoria.length) {
      el.innerHTML = '<div style="font-size:13px;color:var(--gray-500);padding:8px 0;">Sem dados</div>';
      return;
    }
    el.innerHTML = porCategoria.map(c => `
      <div class="cat-pill-row">
        <span class="cat-pill-label">${esc(c.categoria)}</span>
        <span class="cat-pill-count">${c.total}</span>
      </div>
    `).join('');
  }

  function renderTabelaOs(items) {
    const wrap = document.getElementById('tabela-os-wrap');
    if (!items.length) {
      wrap.innerHTML = '<div class="os-loading">Nenhuma ordem de serviço encontrada.</div>';
      return;
    }
    wrap.innerHTML = `
      <table class="os-table">
        <thead>
          <tr>
            <th>OS / Código</th>
            <th>Técnico</th>
            <th>Região</th>
            <th>Status</th>
            <th>Origem</th>
            <th>Prioridade</th>
            <th>Criada em</th>
          </tr>
        </thead>
        <tbody>
          ${items.map(os => `
            <tr data-id="${os.id}" onclick="abrirDetalhe(${os.id})">
              <td>
                <div style="font-weight:500;">${esc(os.numero_os || os.taskCode || '—')}</div>
                <div style="font-size:11px;color:var(--gray-500);">${esc(os.titulo || '')}</div>
              </td>
              <td>${esc(os.tecnico)}</td>
              <td>${esc(os.regiao || '—')}</td>
              <td>${statusPill(os.status)}</td>
              <td>
                <div>${esc(os.categoria_pai_label)}</div>
                ${os.task_code_pai ? `<div style="font-size:11px;color:var(--gray-500);">${esc(os.task_code_pai)}</div>` : ''}
              </td>
              <td>${esc(os.prioridade)}</td>
              <td style="white-space:nowrap;font-size:12px;color:var(--gray-600);">${formatarData(os.data_criacao || os.criadaEm)}</td>
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

  function atualizarMetricas(totais) {
    document.getElementById('metric-total').textContent = totais.total;
    document.getElementById('metric-aberta').textContent = totais.aberta;
    document.getElementById('metric-andamento').textContent = totais.em_andamento;
    document.getElementById('metric-finalizada').textContent = totais.finalizada;
    document.getElementById('metric-tecnicos').textContent = totais.tecnicos;
    document.getElementById('total-tecnicos-label').textContent = totais.tecnicos + ' técnicos';
    const badge = document.getElementById('nav-badge-os');
    if (badge) badge.textContent = totais.total;
  }

  function atualizarPaginacao() {
    const inicio = totalLista === 0 ? 0 : offsetAtual + 1;
    const fim = Math.min(offsetAtual + PAGE_SIZE, totalLista);
    document.getElementById('lista-total').textContent = totalLista;
    document.getElementById('lista-paginacao-info').textContent =
      totalLista ? `Exibindo ${inicio}–${fim} de ${totalLista}` : 'Nenhum registro';
    document.getElementById('btn-pag-anterior').disabled = offsetAtual <= 0;
    document.getElementById('btn-pag-proxima').disabled = offsetAtual + PAGE_SIZE >= totalLista;
  }

  async function carregarTecnicosSelect(regiao) {
    const select = document.getElementById('filtro-tecnico');
    const valorAtual = select.value;
    const qs = regiao ? `?regiao=${encodeURIComponent(regiao)}` : '';
    try {
      const tecnicos = await getUrl('tecnicos' + qs);
      select.innerHTML = '<option value="">Todos os técnicos</option>' +
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
      const fim = document.getElementById('filtro-data-fim');
      if (fim) fim.value = filtros.dataInicio;
      filtros.dataFim = filtros.dataInicio;
    }
    return filtros;
  }

  async function carregarOrdemServicoDashboard(resetPagina = true) {
    const gen = window.plannerBeginReload?.() ?? 0;
    if (resetPagina) offsetAtual = 0;
    const filtros = validarPeriodoFiltros(obterFiltros());
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
        '<div class="os-loading" style="color:#dc2626;">Erro ao carregar dashboard. Verifique o login.</div>';
    }
  }

  window.carregarOrdemServicoDashboard = carregarOrdemServicoDashboard;

  window.aplicarFiltros = function() {
    const regiao = document.getElementById('filtro-regiao').value;
    carregarTecnicosSelect(regiao).then(() => carregarOrdemServicoDashboard(true));
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
    const tipoData = document.getElementById('filtro-tipo-data');
    if (tipoData) tipoData.value = 'criacao';
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

  window.fecharDetalhe = function() {
    document.getElementById('detalhe-overlay').classList.remove('open');
  };

  window.abrirDetalhe = async function(id) {
    const overlay = document.getElementById('detalhe-overlay');
    const conteudo = document.getElementById('detalhe-conteudo');
    document.getElementById('detalhe-titulo').textContent = 'Ordem de Serviço';
    document.getElementById('detalhe-subtitulo').textContent = 'Carregando...';
    conteudo.innerHTML = '<div class="os-loading"><i class="ti ti-loader-2"></i> Carregando...</div>';
    overlay.classList.add('open');

    try {
      const resp = await getUrl('ordem-servico/' + id);
      const os = resp.os;
      document.getElementById('detalhe-subtitulo').textContent = os.numero_os || os.taskCode || ('OS #' + os.id);
      conteudo.innerHTML = `
        <div class="detail-grid">
          <div class="detail-field"><span class="detail-label">Técnico</span><div class="detail-value">${esc(os.tecnico)}</div></div>
          <div class="detail-field"><span class="detail-label">Status</span><div class="detail-value">${statusPill(os.status)}</div></div>
          <div class="detail-field"><span class="detail-label">Região</span><div class="detail-value">${esc(os.regiao || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Número OS</span><div class="detail-value">${esc(os.numero_os || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Ordem de serviço</span><div class="detail-value">${esc(os.ordem_servico || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Código</span><div class="detail-value">${esc(os.taskCode || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Categoria</span><div class="detail-value">${esc(os.categoria || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Prioridade</span><div class="detail-value">${esc(os.prioridade)}</div></div>
          <div class="detail-field span-2"><span class="detail-label">Título</span><div class="detail-value">${esc(os.titulo || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Origem</span><div class="detail-value">${esc(os.categoria_pai_label)}${os.task_code_pai ? ' · ' + esc(os.task_code_pai) : ''}</div></div>
          <div class="detail-field"><span class="detail-label">Protocolo</span><div class="detail-value">${esc(os.protocolo || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Cliente</span><div class="detail-value">${esc(os.nome_cliente || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Subprocesso</span><div class="detail-value">${esc(os.sub_processo || '—')}</div></div>
          <div class="detail-field span-2"><span class="detail-label">Endereço / Localização</span><div class="detail-value">${esc(os.localizacao_texto || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Coordenadas</span><div class="detail-value">${esc(os.coordenadas || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Criada em</span><div class="detail-value">${formatarData(os.data_criacao || os.criadaEm)}</div></div>
          <div class="detail-field"><span class="detail-label">Data de entrada</span><div class="detail-value">${formatarData(os.data_entrada)}</div></div>
          <div class="detail-field"><span class="detail-label">Data de instalação</span><div class="detail-value">${formatarData(os.data_instalacao)}</div></div>
          <div class="detail-field"><span class="detail-label">Concluída em</span><div class="detail-value">${formatarData(os.data_conclusao) || '—'}</div></div>
          <div class="detail-field"><span class="detail-label">Assinada por</span><div class="detail-value">${esc(os.assinada_por || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Assinada em</span><div class="detail-value">${formatarData(os.assinada_em)}</div></div>
          <div class="detail-field span-3"><span class="detail-label">Descrição</span><div class="detail-value">${esc(os.descricao || '—')}</div></div>
        </div>`;
    } catch (e) {
      conteudo.innerHTML = '<div class="os-loading" style="color:#dc2626;">Não foi possível carregar os detalhes.</div>';
    }
  };

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

  window.abrirNovoItem = function() {
    carregarOrdemServicoDashboard(true);
  };
</script>
@endsection
