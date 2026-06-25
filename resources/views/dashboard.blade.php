@extends('layouts.app')

@section('title', 'Dashboard — Planner Telecom')
@section('page-title', 'Dashboard')
@section('hide-topbar-btn')
@endsection

@section('styles')
<style>
  .suas-tarefas-card .card-header {
    padding-bottom: 10px;
  }
  .suas-tarefas-count {
    font-size: 11px;
    color: var(--gray-400);
    font-weight: 500;
  }
  .suas-tarefas-card {
    min-height: 0;
    max-height: 100%;
  }
  .suas-tarefas-body {
    flex: 1;
    min-height: 0;
    max-height: 380px;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px 6px 12px 14px;
    margin-right: 2px;
    scrollbar-width: thin;
    scrollbar-color: var(--gray-300) var(--gray-50);
  }
  .suas-tarefas-body::-webkit-scrollbar {
    width: 8px;
  }
  .suas-tarefas-body::-webkit-scrollbar-track {
    background: var(--gray-50);
    border-radius: 8px;
    margin: 4px 0;
  }
  .suas-tarefas-body::-webkit-scrollbar-thumb {
    background: var(--gray-300);
    border-radius: 8px;
    border: 2px solid var(--gray-50);
    min-height: 40px;
  }
  .suas-tarefas-body::-webkit-scrollbar-thumb:hover {
    background: var(--gray-400);
  }
  [data-theme="dark"] .suas-tarefas-body {
    scrollbar-color: #484f58 #161b22;
  }
  [data-theme="dark"] .suas-tarefas-body::-webkit-scrollbar-track {
    background: #161b22;
  }
  [data-theme="dark"] .suas-tarefas-body::-webkit-scrollbar-thumb {
    background: #484f58;
    border-color: #161b22;
  }
  [data-theme="dark"] .suas-tarefas-body::-webkit-scrollbar-thumb:hover {
    background: #6e7681;
  }
  .starefa-item {
    display: flex;
    align-items: stretch;
    gap: 10px;
    padding: 12px 12px 12px 14px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    background: var(--white);
    cursor: pointer;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
  }
  .starefa-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--gray-300);
  }
  .starefa-item--criada::before { background: var(--blue-600); }
  .starefa-item--andamento::before { background: var(--amber); }
  .starefa-item--concluida::before { background: #0d9488; }
  .starefa-item--finalizar::before { background: var(--green); }
  .starefa-item:hover {
    border-color: var(--blue-200);
    box-shadow: 0 4px 14px rgba(22, 106, 196, 0.08);
    transform: translateY(-1px);
  }
  .starefa-item-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
  }
  .starefa-item-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
  }
  .starefa-item-title {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-950);
    line-height: 1.35;
    flex: 1;
    min-width: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .starefa-item-status {
    flex-shrink: 0;
    font-size: 10px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 20px;
    white-space: nowrap;
    letter-spacing: 0.01em;
  }
  .starefa-item--criada .starefa-item-status {
    background: var(--blue-50);
    color: var(--blue-800);
  }
  .starefa-item--andamento .starefa-item-status {
    background: #fff7ed;
    color: #c2410c;
  }
  .starefa-item--concluida .starefa-item-status {
    background: #ecfdf5;
    color: #047857;
  }
  .starefa-item--finalizar .starefa-item-status {
    background: #f0fdf4;
    color: #15803d;
  }
  .starefa-item-desc {
    margin: 0;
    font-size: 11px;
    color: var(--gray-500);
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .starefa-item-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 12px;
    margin-top: 2px;
  }
  .starefa-item-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    color: var(--gray-400);
    font-family: inherit;
  }
  .starefa-item-meta-item i { font-size: 12px; opacity: 0.85; }
  .starefa-item-code {
    font-family: 'Courier New', monospace;
    font-size: 10px;
    letter-spacing: 0.02em;
  }
  .starefa-item-prazo--vencido { color: #dc2626; font-weight: 600; }
  .starefa-item-prazo--hoje { color: #d97706; font-weight: 600; }
  .starefa-item-action {
    display: flex;
    align-items: center;
    color: var(--gray-300);
    font-size: 16px;
    padding-left: 2px;
    transition: color 0.15s ease, transform 0.15s ease;
  }
  .starefa-item:hover .starefa-item-action {
    color: var(--blue-600);
    transform: translateX(2px);
  }
  [data-theme="dark"] .starefa-item {
    background: #161b22;
    border-color: #30363d;
  }
  [data-theme="dark"] .starefa-item:hover {
    border-color: #388bfd66;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
  }
  [data-theme="dark"] .starefa-item-title { color: #e6edf3; }
  [data-theme="dark"] .starefa-item-desc { color: #8b949e; }
  [data-theme="dark"] .starefa-item-meta-item { color: #8b949e; }
  [data-theme="dark"] .starefa-item--criada .starefa-item-status { background: #132f4c; color: #79c0ff; }
  [data-theme="dark"] .starefa-item--andamento .starefa-item-status { background: #3d2a12; color: #fbbf24; }
  [data-theme="dark"] .starefa-item--concluida .starefa-item-status { background: #0f2f27; color: #34d399; }
  [data-theme="dark"] .starefa-item--finalizar .starefa-item-status { background: #0f2f1a; color: #4ade80; }
  [data-theme="dark"] .starefa-item-action { color: #484f58; }
  [data-theme="dark"] .starefa-item:hover .starefa-item-action { color: #58a6ff; }
  .suas-tarefas-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 48px 16px;
    color: var(--gray-400);
    font-size: 13px;
    text-align: center;
  }
  .suas-tarefas-empty i { font-size: 28px; opacity: 0.5; }
  .suas-tarefas-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 48px 16px;
    color: var(--gray-500);
    font-size: 13px;
  }
  .suas-tarefas-loading i { animation: spin 0.9s linear infinite; }
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
  .tarefa-status-checks {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    padding: 10px 12px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm);
    background: var(--gray-50);
  }
  .tarefa-status-check {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--gray-700);
    cursor: pointer;
    user-select: none;
  }
  .tarefa-status-check input {
    width: 16px;
    height: 16px;
    accent-color: var(--blue-600);
    cursor: pointer;
  }
  [data-theme="dark"] .tarefa-status-checks {
    background: #161b22;
    border-color: #30363d;
  }
  [data-theme="dark"] .tarefa-status-check { color: #e6edf3; }
</style>
@endsection

@section('content')
<style>
  .card-action-btn {
    border: none;
    background: transparent;
    cursor: pointer;
    font: inherit;
    color: inherit;
    padding: 0;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: color 0.15s;
  }
  .card-action-btn:hover { color: var(--blue-800); }
  #mapa-expandido-overlay .modal-box {
    max-width: min(1200px, 96vw);
    height: min(88vh, 900px);
  }
  #mapa-expandido-overlay .modal-body {
    padding: 0;
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
  }
  #mapa-calor-expandido {
    flex: 1;
    width: 100%;
    min-height: 420px;
    background: #1a2744;
  }
  #mapa-expandido-overlay .leaflet-pane,
  #mapa-expandido-overlay .leaflet-top,
  #mapa-expandido-overlay .leaflet-bottom {
    z-index: 1 !important;
  }
  .mapa-pin-caixa {
    background: transparent;
    border: none;
  }
  .mapa-pin-caixa__dot {
    width: 12px;
    height: 12px;
    margin-left: -6px;
    margin-top: -6px;
    border-radius: 50% 50% 50% 0;
    background: #166ac4;
    border: 2px solid #fff;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
    transform: rotate(-45deg);
  }
  .mapa-caixas-loading {
    position: absolute;
    bottom: 8px;
    left: 8px;
    z-index: 500;
    font-size: 10px;
    padding: 4px 8px;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.92);
    color: var(--gray-600);
    display: flex;
    align-items: center;
    gap: 4px;
    pointer-events: none;
  }
  .mapa-caixas-loading i { animation: spin 0.9s linear infinite; }
  .card-mapa .map-body { position: relative; }
</style>
    <div class="metrics-row">
      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-label">Criadas</div>
          <div class="metric-icon mi-blue"><i class="ti ti-list"></i></div>
        </div>
        <div class="metric-value">91</div>
        <div class="metric-sub"><span class="up">+28 hoje</span> &middot; 692 no pipeline</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-label">Em andamento</div>
          <div class="metric-icon mi-amber"><i class="ti ti-loader"></i></div>
        </div>
        <div class="metric-value">67</div>
        <div class="metric-sub">em execução agora</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-label">Impedimentos</div>
          <div class="metric-icon mi-red"><i class="ti ti-alert-triangle"></i></div>
        </div>
        <div class="metric-value">7</div>
        <div class="metric-sub"><span class="down">atenção necessária</span></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-label">Finalizadas</div>
          <div class="metric-icon mi-green"><i class="ti ti-circle-check"></i></div>
        </div>
        <div class="metric-value">532</div>
        <div class="metric-sub"><span class="up">+12 hoje</span> &middot; este mês</div>
      </div>
    </div>

    <div class="bottom-grid">
      <div class="card suas-tarefas-card">
        <div class="card-header">
          <span class="card-title">Suas tarefas</span>
          <div style="display:flex;align-items:center;gap:10px">
            <span class="suas-tarefas-count" id="suas-tarefas-count"></span>
            <a href="/tarefas" id="link-ver-todas-tarefas" data-permissao="visualizar_aba_tarefas" class="card-action" style="text-decoration:none;color:inherit">
              ver todas <i class="ti ti-arrow-right" style="font-size:11px"></i>
            </a>
          </div>
        </div>
        <div id="suas-tarefas-body" class="suas-tarefas-body">
          <div class="suas-tarefas-loading">
            <i class="ti ti-loader"></i> Carregando...
          </div>
        </div>
      </div>

      <div class="card card-mapa">
        <div class="card-header">
          <span class="card-title">Mapa de calor</span>
          <button type="button" class="card-action card-action-btn" onclick="abrirMapaExpandido()" title="Expandir mapa">
            <i class="ti ti-arrows-maximize" style="font-size:11px"></i> expandir
          </button>
        </div>
        <div class="map-body">
          <div id="mapa-calor" style="width:100%;height:100%;min-height:260px;"></div>
          <div id="mapa-caixas-loading" class="mapa-caixas-loading" style="display:none">
            <i class="ti ti-loader"></i> Carregando caixas...
          </div>
        </div>
        <div class="region-list">
          <div class="region-row">
            <div class="region-name">Goval</div>
            <div class="region-bar-wrap"><div class="region-bar" id="region-bar-goval" style="width:100%"></div></div>
            <div class="region-n" id="region-n-goval">—</div>
          </div>
          <div class="region-row">
            <div class="region-name">Vale do Aço</div>
            <div class="region-bar-wrap"><div class="region-bar" style="width:67%"></div></div>
            <div class="region-n">84</div>
          </div>
          <div class="region-row">
            <div class="region-name">Caratinga</div>
            <div class="region-bar-wrap"><div class="region-bar" style="width:3%"></div></div>
            <div class="region-n">4</div>
          </div>
          <div class="region-row">
            <div class="region-name">N/D</div>
            <div class="region-bar-wrap"><div class="region-bar" style="width:2%"></div></div>
            <div class="region-n">3</div>
          </div>
        </div>
      </div>
    </div>

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

<!-- MODAL MAPA EXPANDIDO -->
<x-modal
  id="mapa-expandido-overlay"
  titulo="Mapa de calor"
  subtitulo="Visualização ampliada"
  fechar="fecharMapaExpandido()">

  <div id="mapa-calor-expandido"></div>

  <x-slot name="footer">
    <button type="button" onclick="fecharMapaExpandido()" class="btn-modal btn-modal-ghost">Fechar</button>
  </x-slot>

</x-modal>

@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
  const MAPA_CENTER = [-18.8517, -41.9494];
  const MAPA_ZOOM = 11;
  let mapaExpandido = null;
  let caixasGeoGrid = [];
  const camadasCaixasPorMapa = new WeakMap();

  function criarIconeCaixa() {
    return L.divIcon({
      className: 'mapa-pin-caixa',
      html: '<div class="mapa-pin-caixa__dot"></div>',
      iconSize: [12, 12],
      iconAnchor: [6, 12],
    });
  }

  function escMapa(valor) {
    if (valor == null || valor === '') return '';
    return String(valor).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function plotarCaixasNoMapa(map, caixas) {
    if (!map || !Array.isArray(caixas) || !caixas.length) return;

    const anterior = camadasCaixasPorMapa.get(map);
    if (anterior) {
      map.removeLayer(anterior);
    }

    const cluster = L.markerClusterGroup({
      maxClusterRadius: 45,
      disableClusteringAtZoom: 16,
      spiderfyOnMaxZoom: true,
      showCoverageOnHover: false,
    });

    caixas.forEach((caixa) => {
      const lat = Number(caixa.latitude);
      const lng = Number(caixa.longitude);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

      const marker = L.marker([lat, lng], { icon: criarIconeCaixa() });
      const sigla = escMapa(caixa.sigla || 'Caixa');
      const status = caixa.status ? `<br><span style="color:#6b7280">${escMapa(caixa.status)}</span>` : '';
      marker.bindPopup(`<strong>${sigla}</strong>${status}`);
      cluster.addLayer(marker);
    });

    map.addLayer(cluster);
    camadasCaixasPorMapa.set(map, cluster);
  }

  function atualizarContadorGoval(total) {
    const el = document.getElementById('region-n-goval');
    if (el) el.textContent = String(total);
  }

  async function carregarCaixasGeoGrid() {
    const loading = document.getElementById('mapa-caixas-loading');
    if (loading) loading.style.display = 'flex';

    const token = localStorage.getItem('planner_token');
    try {
      const response = await fetch('/api/geogrid/caixas', {
        headers: {
          Authorization: 'Bearer ' + token,
          Accept: 'application/json',
        },
        cache: 'no-store',
      });
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || 'Erro ao carregar caixas.');
      }

      caixasGeoGrid = Array.isArray(data.caixas) ? data.caixas : [];
      plotarCaixasNoMapa(mapaPreview, caixasGeoGrid);
      if (mapaExpandido) {
        plotarCaixasNoMapa(mapaExpandido, caixasGeoGrid);
      }
      atualizarContadorGoval(data.total_com_coordenadas ?? caixasGeoGrid.length);
    } catch (err) {
      console.warn('Mapa de calor — caixas GeoGrid:', err.message || err);
    } finally {
      if (loading) loading.style.display = 'none';
    }
  }

  function criarMapa(containerId) {
    const map = L.map(containerId, {
      zoomControl: true,
      scrollWheelZoom: true,
    }).setView(MAPA_CENTER, MAPA_ZOOM);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap',
    }).addTo(map);

    return map;
  }

  const mapaPreview = criarMapa('mapa-calor');
  window.mapaLeaflet = mapaPreview;
  carregarCaixasGeoGrid();

  window.abrirMapaExpandido = function () {
    document.getElementById('mapa-expandido-overlay').classList.add('open');

    requestAnimationFrame(() => {
      if (!mapaExpandido) {
        mapaExpandido = criarMapa('mapa-calor-expandido');
        plotarCaixasNoMapa(mapaExpandido, caixasGeoGrid);
      }

      mapaExpandido.setView(mapaPreview.getCenter(), mapaPreview.getZoom());
      setTimeout(() => mapaExpandido.invalidateSize(), 150);
    });
  };

  window.fecharMapaExpandido = function () {
    document.getElementById('mapa-expandido-overlay').classList.remove('open');
  };

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.getElementById('mapa-expandido-overlay')?.classList.contains('open')) {
      fecharMapaExpandido();
    }
  });
</script>

<script type="module">
  import { listarTarefas, atualizarTarefa as atualizarTarefaApi, deletarTarefa as deletarTarefaApi } from '{{ asset("js/modules/opTask.js") }}';
  import {
    renderDescricaoView,
    mountDescricaoEditor,
    getDescricaoEditorValue,
  } from '{{ asset("js/planner-descricao-editor.js") }}';

  let tarefasMap = {};
  let usuariosSistema = [];

  function esc(valor) {
    if (valor == null || valor === '') return '';
    return String(valor).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function textoResponsavel(responsavel) {
    const valor = (responsavel || '').trim();
    return valor ? esc(valor) : 'Não tem responsável pela tarefa';
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

  function normalizarStatus(status) {
    if (status === 'Finalizada') return 'Finalizar';
    if (status === 'Impedimento') return 'Em andamento';
    return status || 'Criada';
  }

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

  function resumoDescricao(valor) {
    if (!valor) return '';
    const tmp = document.createElement('div');
    tmp.innerHTML = String(valor);
    const texto = (tmp.textContent || '').replace(/\s+/g, ' ').trim();
    if (!texto && String(valor).includes('<img')) return 'Contém imagem anexada';
    if (!texto) return '';
    return texto.length > 140 ? `${texto.slice(0, 140)}…` : texto;
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

  function renderStatusChecks(t) {
    const status = normalizarStatus(t.status);
    return `
      <div class="tarefa-status-checks">
        <label class="tarefa-status-check">
          <input type="checkbox" id="check-em-andamento" ${status === 'Em andamento' ? 'checked' : ''} onchange="alterarStatusTarefa('Em andamento', this)">
          <span>Em andamento</span>
        </label>
        <label class="tarefa-status-check">
          <input type="checkbox" id="check-concluida" ${status === 'Concluída' ? 'checked' : ''} onchange="alterarStatusTarefa('Concluída', this)">
          <span>Concluída</span>
        </label>
      </div>`;
  }

  function desmarcarOutrosChecks(tipoAtivo) {
    const mapa = {
      'Em andamento': 'check-em-andamento',
      'Concluída': 'check-concluida',
    };
    Object.entries(mapa).forEach(([tipo, id]) => {
      if (tipo !== tipoAtivo) {
        const el = document.getElementById(id);
        if (el) el.checked = false;
      }
    });
  }

  function restaurarChecks(status) {
    const normalizado = normalizarStatus(status);
    const checkAndamento = document.getElementById('check-em-andamento');
    const checkConcluida = document.getElementById('check-concluida');
    if (checkAndamento) checkAndamento.checked = normalizado === 'Em andamento';
    if (checkConcluida) checkConcluida.checked = normalizado === 'Concluída';
  }

  async function alterarStatusTarefa(tipo, checkbox) {
    const id = document.getElementById('detalhe-conteudo')?.dataset?.id;
    if (!id) return;

    const statusAnterior = normalizarStatus(tarefasMap[id]?.status);

    let novoStatus = 'Criada';
    if (checkbox.checked && ['Em andamento', 'Concluída'].includes(tipo)) {
      novoStatus = tipo;
      desmarcarOutrosChecks(tipo);
    }

    if (novoStatus === statusAnterior) return;

    const checks = ['check-em-andamento', 'check-concluida'];
    checks.forEach(cid => {
      const el = document.getElementById(cid);
      if (el) el.disabled = true;
    });

    try {
      const resultado = await atualizarTarefaApi(id, { status: novoStatus, categoria: 'tarefas' });
      const tarefa = resultado.opTask || resultado.tarefa || resultado;
      tarefasMap[id] = tarefa;

      const badgesEl = document.querySelector('#detalhe-conteudo .detail-badges');
      if (badgesEl) {
        badgesEl.innerHTML = `${badgeStatus(tarefa.status)}<span class="badge b-cat-gen">Tarefa</span>`;
      }

      atualizarCardSuasTarefas(tarefa);
      window.plannerSyncTarefa?.(tarefa);
      await window.plannerAposMutacaoLocal?.();
    } catch (err) {
      restaurarChecks(statusAnterior);
      alert(err.message || 'Erro ao atualizar status.');
    } finally {
      checks.forEach(cid => {
        const el = document.getElementById(cid);
        if (el) el.disabled = false;
      });
    }
  }

  function statusClasseItem(status) {
    const normalizado = normalizarStatus(status);
    const mapa = {
      'Criada': 'criada',
      'Em andamento': 'andamento',
      'Concluída': 'concluida',
      'Finalizar': 'finalizar',
    };
    return mapa[normalizado] || 'criada';
  }

  function prazoInfo(valor) {
    if (!valor) return null;
    const data = new Date(valor);
    if (isNaN(data.getTime())) return null;

    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    data.setHours(0, 0, 0, 0);
    const diff = Math.round((data - hoje) / (1000 * 60 * 60 * 24));

    let classe = '';
    if (diff < 0) classe = 'starefa-item-prazo--vencido';
    else if (diff === 0) classe = 'starefa-item-prazo--hoje';

    return {
      texto: formatarPrazo(valor),
      classe,
      label: diff < 0 ? 'Vencida' : diff === 0 ? 'Hoje' : null,
    };
  }

  function renderCardTarefa(t) {
    tarefasMap[t.id] = t;
    const statusLabel = normalizarStatus(t.status);
    const statusClass = statusClasseItem(t.status);
    const prazo = prazoInfo(t.prazo);
    const descricao = resumoDescricao(t.descricao);

    return `
      <article class="starefa-item starefa-item--${statusClass}" data-id="${t.id}">
        <div class="starefa-item-main">
          <div class="starefa-item-top">
            <h4 class="starefa-item-title">${esc(t.titulo) || 'Sem título'}</h4>
            <span class="starefa-item-status">${esc(statusLabel)}</span>
          </div>
          ${descricao ? `<p class="starefa-item-desc">${esc(descricao)}</p>` : ''}
          <div class="starefa-item-meta">
            <span class="starefa-item-meta-item starefa-item-code">
              <i class="ti ti-hash"></i>${esc(t.taskCode || 'S/C')}
            </span>
            ${prazo ? `
              <span class="starefa-item-meta-item starefa-item-prazo ${prazo.classe}">
                <i class="ti ti-calendar"></i>
                ${prazo.label ? `<span>${prazo.label} ·</span>` : ''}
                ${prazo.texto}
              </span>
            ` : ''}
          </div>
        </div>
        <div class="starefa-item-action" aria-hidden="true">
          <i class="ti ti-chevron-right"></i>
        </div>
      </article>`;
  }

  function atualizarContadorSuasTarefas(qtd) {
    const el = document.getElementById('suas-tarefas-count');
    if (!el) return;
    if (!qtd) {
      el.textContent = '';
      return;
    }
    el.textContent = qtd === 1 ? '1 tarefa' : `${qtd} tarefas`;
  }

  function atualizarContadorSuasTarefasFromDom() {
    const qtd = document.querySelectorAll('#suas-tarefas-body .starefa-item').length;
    atualizarContadorSuasTarefas(qtd);
  }

  function renderVazio() {
    return `
      <div class="suas-tarefas-empty">
        <i class="ti ti-checklist"></i>
        <span>Nenhuma tarefa atribuída a você.</span>
      </div>`;
  }

  function renderErro(mensagem) {
    return `<div class="suas-tarefas-empty"><i class="ti ti-alert-circle"></i><span>${esc(mensagem)}</span></div>`;
  }

  function tarefaEstaFinalizada(status) {
    return normalizarStatus(status) === 'Finalizar';
  }

  function removerCardSuasTarefas(id) {
    const card = document.querySelector(`#suas-tarefas-body .starefa-item[data-id="${CSS.escape(String(id))}"]`);
    if (card) card.remove();
    delete tarefasMap[id];

    const container = document.getElementById('suas-tarefas-body');
    const qtd = container?.querySelectorAll('.starefa-item').length || 0;
    if (qtd === 0 && container) {
      container.innerHTML = renderVazio();
    }
    atualizarContadorSuasTarefas(qtd);
  }

  function atualizarCardSuasTarefas(tarefa) {
    if (!tarefa?.id) return;
    if (tarefa.categoria && tarefa.categoria !== 'tarefas') return;
    if (window.plannerEstaExcluida?.(tarefa.id)) return;

    if (tarefaEstaFinalizada(tarefa.status)) {
      removerCardSuasTarefas(tarefa.id);
      return;
    }

    tarefasMap[tarefa.id] = tarefa;
    const seletor = `#suas-tarefas-body .starefa-item[data-id="${CSS.escape(String(tarefa.id))}"]`;
    const card = document.querySelector(seletor);

    if (card) {
      card.outerHTML = renderCardTarefa(tarefa);
      atualizarContadorSuasTarefasFromDom();
      return;
    }

    const user = JSON.parse(localStorage.getItem('planner_user') || 'null');
    if (user?.username && tarefa.responsavel === user.username) {
      const container = document.getElementById('suas-tarefas-body');
      if (container?.querySelector('.suas-tarefas-empty')) {
        container.innerHTML = renderCardTarefa(tarefa);
      } else {
        container?.insertAdjacentHTML('afterbegin', renderCardTarefa(tarefa));
      }
      atualizarContadorSuasTarefasFromDom();
    }
  }

  function filtrarTarefasDoUsuario(tarefas) {
    const user = JSON.parse(localStorage.getItem('planner_user') || 'null');
    const username = user?.username || '';
    return (Array.isArray(tarefas) ? tarefas : []).filter((t) => {
      if (t.categoria && t.categoria !== 'tarefas') return false;
      if (!username || t.responsavel !== username) return false;
      return !tarefaEstaFinalizada(t.status);
    });
  }

  async function carregarSuasTarefas() {
    const container = document.getElementById('suas-tarefas-body');
    if (!container) return;

    const gen = window.plannerBeginReload?.() ?? 0;
    try {
      const tarefas = await listarTarefas({ categoria: 'tarefas', minhas: true, limit: 50 });
      if (window.plannerIsReloadCurrent && !window.plannerIsReloadCurrent(gen)) return;

      const base = window.plannerFiltrarExcluidas ? window.plannerFiltrarExcluidas(tarefas) : tarefas;
      const lista = filtrarTarefasDoUsuario(base);

      Object.keys(tarefasMap).forEach(k => delete tarefasMap[k]);

      if (!lista.length) {
        container.innerHTML = renderVazio();
        atualizarContadorSuasTarefas(0);
        return;
      }

      container.innerHTML = lista.map(renderCardTarefa).join('');
      atualizarContadorSuasTarefas(lista.length);
    } catch (err) {
      container.innerHTML = renderErro(err.message || 'Erro ao carregar tarefas.');
    }
  }

  async function carregarUsuariosSistema() {
    const token = localStorage.getItem('planner_token');
    const response = await fetch('/api/usuarios/opcoes', {
      headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
      cache: 'no-store',
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Erro ao carregar usuários.');
    usuariosSistema = (data.usuarios || []).filter(u => u.funcao !== 'tecnico');
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
        </div>
        ${renderStatusChecks(t)}
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
          <div class="detail-field">
            <span class="detail-label">Código da tarefa</span>
            <div class="detail-value">${esc(t.taskCode) || '—'}</div>
          </div>
          <div class="detail-field">
            <span class="detail-label">Atualizado em</span>
            <div class="detail-value">${formatarData(t.updated_at)}</div>
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
      mountDescricaoEditor(descEl, { html: tarefa?.descricao || '', placeholder: 'Detalhes da tarefa (opcional)' });
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
      const valor = prazoParaInput(tarefa?.prazo) || '';
      prazoEl.innerHTML = `<input type="date" value="${valor}" style="${inputStyle}"/>`;
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

    const dados = { titulo, descricao, responsavel, categoria: 'tarefas' };
    if (prazo) dados.prazo = prazo;

    try {
      const resultado = await atualizarTarefaApi(id, dados);
      const tarefa = resultado.opTask || resultado.tarefa || resultado;
      fecharDetalhe();
      atualizarCardSuasTarefas(tarefa);
      window.plannerSyncTarefa?.(tarefa);
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
      fecharConfirmacaoExclusao();
      fecharDetalhe();
      delete tarefasMap[id];
      window.plannerSyncExclusaoTarefa?.(id);
      window.plannerPausarPolling?.(60000);
      await window.plannerNotifyLocalMutation?.();
    } catch (err) {
      window.plannerDesmarcarExcluida?.(id);
      await carregarSuasTarefas();
      alert(err.message || 'Erro ao excluir tarefa.');
    } finally {
      btn.disabled = false;
    }
  }

  window.abrirDetalhe = abrirDetalhe;
  window.fecharDetalhe = fecharDetalhe;
  window.alterarStatusTarefa = alterarStatusTarefa;
  window.ativarEdicao = ativarEdicao;
  window.salvarEdicao = salvarEdicao;
  window.cancelarEdicao = cancelarEdicao;
  window.abrirConfirmacaoExclusao = abrirConfirmacaoExclusao;
  window.fecharConfirmacaoExclusao = fecharConfirmacaoExclusao;
  window.confirmarExclusaoTarefa = confirmarExclusaoTarefa;
  window.carregarSuasTarefas = carregarSuasTarefas;
  window.atualizarCardSuasTarefas = atualizarCardSuasTarefas;

  document.getElementById('suas-tarefas-body')?.addEventListener('click', (e) => {
    const card = e.target.closest('.starefa-item[data-id]');
    if (card?.dataset.id) abrirDetalhe(card.dataset.id);
  });

  document.getElementById('detalhe-overlay')?.addEventListener('click', function (e) {
    if (e.target === this) fecharDetalhe();
  });

  document.getElementById('confirm-excluir-overlay')?.addEventListener('click', function (e) {
    if (e.target === this) fecharConfirmacaoExclusao();
  });

  carregarSuasTarefas();
</script>
@endsection
