<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Planner Telecom')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<style>
  :root {
    --blue-950: #0a3d7a;
    --blue-900: #0d5aaa;
    --blue-800: #166ac4;
    --blue-600: #3d8fe0;
    --blue-200: #90bfed;
    --blue-100: #bcd6f5;
    --blue-50: #e8f2fc;
    --gray-950: #0f172a;
    --gray-700: #334155;
    --gray-500: #64748b;
    --gray-400: #94a3b8;
    --gray-200: #e2e8f0;
    --gray-100: #f1f5f9;
    --gray-50: #f8fafc;
    --white: #ffffff;
    --red-bg: #fef2f2;
    --red-text: #991b1b;
    --red: #ef4444;
    --amber-bg: #fffbeb;
    --amber-text: #92400e;
    --amber: #f59e0b;
    --green-bg: #f0fdf4;
    --green-text: #166534;
    --green: #22c55e;
    --sidebar-w: 252px;
    --topbar-h: 56px;
    --radius: 10px;
    --radius-sm: 6px;
    --shadow-card: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  }

  [data-theme="dark"] {
    --gray-950: #f1f5f9;
    --gray-700: #cbd5e1;
    --gray-500: #94a3b8;
    --gray-400: #64748b;
    --gray-200: #1e293b;
    --gray-100: #0f172a;
    --gray-50: #0a0f1a;
    --white: #111827;
    --red-bg: #2d0a0a;
    --red-text: #fca5a5;
    --amber-bg: #2d1a00;
    --amber-text: #fcd34d;
    --green-bg: #052e16;
    --green-text: #86efac;
    --blue-50: #0c1e35;
    --shadow-card: 0 1px 3px rgba(0,0,0,0.3);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--gray-50);
    color: var(--gray-950);
    display: flex;
    height: 100vh;
    overflow: hidden;
  }

  /* SIDEBAR */
  .sidebar {
    width: var(--sidebar-w);
    background: #0d5aaa;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    overflow: hidden;
    transition: width 0.25s ease;
  }
  .sidebar-top {
    padding: 20px 14px 14px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }
  .brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
  }
  .brand-icon {
    width: 34px; height: 34px;
    background: #166ac4;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 600; color: #fff;
    flex-shrink: 0;
    border: 1px solid rgba(255,255,255,0.15);
  }
  .brand-text { flex: 1; }
  .brand-name { color: #fff; font-size: 14px; font-weight: 600; letter-spacing: -0.01em; }
  .brand-sub { color: var(--blue-200); font-size: 11px; }
  .search-box {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: var(--radius-sm);
    padding: 7px 10px; cursor: text;
    transition: background 0.15s;
  }
  .search-box:hover { background: rgba(255,255,255,0.1); }
  .search-box i { color: rgba(255,255,255,0.35); font-size: 14px; }
  .search-box span { color: rgba(255,255,255,0.3); font-size: 12px; }
  .sidebar-body {
    flex: 1; overflow-y: auto; padding: 10px 8px; scrollbar-width: none;
  }
  .sidebar-body::-webkit-scrollbar { display: none; }
  .nav-section { margin-bottom: 18px; }
  .nav-section-label {
    font-size: 10px; font-weight: 600;
    color: rgba(255,255,255,0.28);
    text-transform: uppercase; letter-spacing: 0.09em;
    padding: 0 8px; margin-bottom: 4px;
  }
  .nav-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 7px 8px; border-radius: var(--radius-sm);
    cursor: pointer; color: rgba(255,255,255,0.55);
    font-size: 13px; font-weight: 400;
    transition: all 0.12s; user-select: none;
    text-decoration: none;
  }
  .nav-item:hover { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.85); }
  .nav-item.active { background: rgba(255,255,255,0.13); color: #fff; font-weight: 500; }
  .nav-left { display: flex; align-items: center; gap: 9px; }
  .nav-left i { font-size: 15px; flex-shrink: 0; }
  .nav-badge {
    font-size: 10px; font-weight: 500; padding: 1px 6px;
    border-radius: 20px; background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.7); min-width: 20px; text-align: center;
  }
  .nav-badge.crit { background: var(--red); color: #fff; }
  .sidebar-footer {
    padding: 10px 8px;
    border-top: 1px solid rgba(255,255,255,0.08);
  }
  .user-card {
    display: flex; align-items: center; gap: 10px;
    padding: 8px; border-radius: var(--radius-sm);
    cursor: pointer; transition: background 0.12s;
  }
  .user-card:hover { background: rgba(255,255,255,0.07); }
  .avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: #166ac4; border: 1px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 600; color: #fff; flex-shrink: 0;
  }
  .user-info { flex: 1; overflow: hidden; }
  .user-name { color: #fff; font-size: 12px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .user-role { color: var(--blue-200); font-size: 11px; }
  .theme-toggle {
    background: transparent; border: none;
    color: rgba(255,255,255,0.4); cursor: pointer;
    font-size: 15px; padding: 2px; transition: color 0.15s; display: flex;
  }
  .theme-toggle:hover { color: rgba(255,255,255,0.8); }

  /* SIDEBAR COLLAPSED */
  .sidebar.collapsed { width: 60px; }
  .sidebar.collapsed .brand-text,
  .sidebar.collapsed .search-box,
  .sidebar.collapsed .nav-section-label,
  .sidebar.collapsed .nav-badge,
  .sidebar.collapsed .user-info,
  .sidebar.collapsed .nav-left span,
  .sidebar.collapsed .theme-toggle { display: none; }
  .sidebar.collapsed .nav-item { justify-content: center; padding: 8px; overflow: hidden; }
  .sidebar.collapsed .nav-left { gap: 0; overflow: hidden; white-space: nowrap; }
  .sidebar.collapsed .nav-left i { flex-shrink: 0; }
  .sidebar.collapsed .user-card { justify-content: center; }
  .sidebar.collapsed .brand { justify-content: center; }
  .sidebar.collapsed .brand-icon { margin: 0 auto; }

  /* MAIN */
  .main { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-width: 0; }
  .topbar {
    height: var(--topbar-h);
    background: var(--white);
    border-bottom: 1px solid var(--gray-200);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 24px; flex-shrink: 0;
  }
  .topbar-left { display: flex; flex-direction: column; }
  .page-title { font-size: 15px; font-weight: 600; color: var(--gray-950); letter-spacing: -0.01em; }
  .page-date { font-size: 11px; color: var(--gray-400); }
  .topbar-right { display: flex; align-items: center; gap: 8px; }
  .status-pill {
    display: flex; align-items: center; gap: 6px;
    background: var(--green-bg); border: 1px solid #bbf7d0;
    border-radius: 20px; padding: 4px 10px;
    font-size: 11px; font-weight: 500; color: var(--green-text);
  }
  .pulse {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--green); position: relative;
  }
  .pulse::after {
    content: ''; position: absolute; inset: -3px; border-radius: 50%;
    background: var(--green); opacity: 0.3; animation: pulse 2s infinite;
  }
  @keyframes pulse { 0%,100%{transform:scale(1);opacity:0.3} 50%{transform:scale(1.6);opacity:0} }
  .icon-btn {
    width: 34px; height: 34px;
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    background: var(--white); color: var(--gray-500);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 15px; transition: all 0.12s; position: relative;
  }
  .icon-btn:hover { background: var(--gray-50); color: var(--gray-700); }
  .notif-dot {
    position: absolute; top: 6px; right: 6px;
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--red); border: 1.5px solid var(--white);
  }
  .btn-primary {
    display: flex; align-items: center; gap: 6px;
    background: #166ac4; color: #fff; border: none;
    padding: 0 14px; height: 34px; border-radius: var(--radius-sm);
    font-size: 13px; font-weight: 500; cursor: pointer;
    transition: background 0.12s; font-family: inherit;
  }
  .btn-primary:hover { background: #0d5aaa; }
  .btn-primary i { font-size: 14px; }

  /* CONTENT */
  .content {
    flex: 1; overflow-y: auto; padding: 20px 24px;
    display: flex; flex-direction: column; gap: 16px;
  }

  /* METRICS */
  .metrics-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
  .metric-card {
    background: var(--white); border: 1px solid var(--gray-200);
    border-radius: var(--radius); padding: 16px;
    box-shadow: var(--shadow-card); transition: border-color 0.15s;
  }
  .metric-card:hover { border-color: var(--blue-100); }
  .metric-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
  .metric-label { font-size: 12px; color: var(--gray-500); font-weight: 500; }
  .metric-icon { width: 30px; height: 30px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; font-size: 15px; }
  .mi-blue { background: var(--blue-50); color: var(--blue-800); }
  .mi-amber { background: var(--amber-bg); color: #b45309; }
  .mi-red { background: var(--red-bg); color: #b91c1c; }
  .mi-green { background: var(--green-bg); color: #15803d; }
  .metric-value { font-size: 28px; font-weight: 600; color: var(--gray-950); letter-spacing: -0.02em; line-height: 1; margin-bottom: 4px; }
  .metric-sub { font-size: 11px; color: var(--gray-400); }
  .metric-sub .up { color: #15803d; font-weight: 500; }
  .metric-sub .down { color: #b91c1c; font-weight: 500; }

  /* BOTTOM GRID */
  .bottom-grid { display: grid; grid-template-columns: 1fr 380px; gap: 12px; flex: 1; min-height: 0; }
  .card {
    background: var(--white); border: 1px solid var(--gray-200);
    border-radius: var(--radius); box-shadow: var(--shadow-card);
    display: flex; flex-direction: column; overflow: hidden;
  }
  .card-header {
    padding: 12px 16px; border-bottom: 1px solid var(--gray-200);
    display: flex; align-items: center; justify-content: space-between; flex-shrink: 0;
  }
  .card-title { font-size: 13px; font-weight: 600; color: var(--gray-950); }
  .card-action { font-size: 12px; color: var(--blue-800); cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 4px; }
  .card-action:hover { color: var(--blue-900); }

  /* KANBAN */
  .kanban-cols { display: grid; grid-template-columns: repeat(4, 1fr); flex: 1; overflow: hidden; }
  .kcol { border-right: 1px solid var(--gray-200); display: flex; flex-direction: column; overflow: hidden; }
  .kcol:last-child { border-right: none; }
  .kcol-head {
    padding: 8px 10px; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--gray-200); background: var(--gray-50); flex-shrink: 0;
  }
  .kcol-name { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.05em; }
  .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
  .d-blue { background: var(--blue-600); }
  .d-amber { background: var(--amber); }
  .d-red { background: var(--red); }
  .d-green { background: var(--green); }
  .kcol-count { font-size: 11px; color: var(--gray-400); background: var(--gray-100); padding: 1px 6px; border-radius: 20px; }
  .kcol-body { flex: 1; overflow-y: auto; padding: 8px; display: flex; flex-direction: column; gap: 6px; scrollbar-width: thin; scrollbar-color: var(--gray-200) transparent; }
  .kcard {
    background: var(--gray-50); border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); padding: 9px 10px;
    cursor: pointer; transition: all 0.12s;
  }
  .kcard:hover { background: var(--white); border-color: var(--blue-200); box-shadow: 0 2px 6px rgba(22,106,196,0.1); }
  .kcard.urgent { border-left: 3px solid var(--red); padding-left: 8px; }
  .kcard.late { border-left: 3px solid var(--amber); padding-left: 8px; }
  .kcard-title { font-size: 12px; font-weight: 500; color: var(--gray-950); line-height: 1.4; margin-bottom: 7px; }
  .kcard-foot { display: flex; align-items: center; justify-content: space-between; gap: 4px; flex-wrap: wrap; }
  .badge { font-size: 10px; font-weight: 500; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
  .b-alta { background: var(--red-bg); color: var(--red-text); }
  .b-media { background: var(--amber-bg); color: var(--amber-text); }
  .b-baixa { background: var(--green-bg); color: var(--green-text); }
  .b-regiao-gv { background: var(--blue-50); color: var(--blue-800); }
  .b-regiao-va { background: #f3e8ff; color: #7c3aed; }
  .b-cat-rom { background: #fef2f2; color: #991b1b; }
  .b-cat-ate { background: #eff6ff; color: #1d4ed8; }
  .b-cat-otm { background: #f0fdf4; color: #166534; }
  .b-cat-man { background: #fffbeb; color: #92400e; }
  .b-cat-tro { background: #f5f3ff; color: #6d28d9; }
  .b-cat-etq { background: #fdf4ff; color: #86198f; }
  .b-cat-cer { background: #fff7ed; color: #c2410c; }
  .b-cat-cor { background: #ecfdf5; color: #065f46; }
  .b-cat-qua { background: #f0f9ff; color: #0369a1; }
  .b-cat-gen { background: var(--gray-100); color: var(--gray-500); }
  .kcard-code { font-size: 10px; color: var(--gray-400); font-family: 'Courier New', monospace; }
  .kcard-meta { display: flex; align-items: center; gap: 4px; margin-top: 6px; }
  .kcard-avatar { width: 18px; height: 18px; border-radius: 50%; background: var(--blue-50); color: var(--blue-800); font-size: 9px; font-weight: 600; display: flex; align-items: center; justify-content: center; }
  .kcard-time { font-size: 10px; color: var(--gray-400); margin-left: auto; }

  /* MAP */
  .map-body { flex: 1; background: #1a2744; position: relative; overflow: hidden; min-height: 260px; }
  .map-grid {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 30px 30px;
  }
  .map-label { position: absolute; bottom: 8px; left: 10px; font-size: 10px; color: rgba(255,255,255,0.3); font-weight: 500; }
  .map-expand-btn {
    position: absolute; top: 8px; right: 8px;
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);
    border-radius: 5px; color: rgba(255,255,255,0.6);
    padding: 4px 6px; cursor: pointer; display: flex; align-items: center; gap: 4px;
    font-size: 11px; font-family: inherit; transition: background 0.12s;
  }
  .map-expand-btn:hover { background: rgba(255,255,255,0.18); }
  .map-expand-btn i { font-size: 13px; }
  .region-list { padding: 10px 14px; display: flex; flex-direction: column; gap: 7px; flex-shrink: 0; }
  .region-row { display: flex; align-items: center; gap: 8px; }
  .region-name { font-size: 11px; font-weight: 500; color: var(--gray-700); width: 80px; flex-shrink: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .region-bar-wrap { flex: 1; height: 4px; background: var(--gray-100); border-radius: 2px; overflow: hidden; }
  .region-bar { height: 4px; border-radius: 2px; background: var(--blue-600); transition: width 0.6s ease; }
  .region-n { font-size: 11px; color: var(--gray-400); min-width: 28px; text-align: right; }

  /* SCROLLBAR */
  ::-webkit-scrollbar { width: 4px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--gray-200); border-radius: 2px; }

  /* DARK MODE TRANSITION */
  body, .sidebar, .main, .topbar, .metric-card, .card, .kcard, .icon-btn, .btn-primary {
    transition: background 0.2s, border-color 0.2s, color 0.2s;
  }

  /* OVERLAY */
  .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 40; }
  .sidebar-overlay.active { display: block; }
  .hamburger { display: none; background: transparent; border: none; color: var(--gray-500); font-size: 20px; cursor: pointer; padding: 4px; align-items: center; justify-content: center; }
  .fab { position: fixed; bottom: 20px; right: 20px; width: 52px; height: 52px; border-radius: 50%; background: #166ac4; color: #fff; border: none; font-size: 22px; cursor: pointer; z-index: 50; box-shadow: 0 4px 16px rgba(22,106,196,0.4); display: none; align-items: center; justify-content: center; transition: background 0.15s, transform 0.1s; }
  .fab:active { transform: scale(0.94); }

  /* LEAFLET */
  #mapa-calor { z-index: 0; }
  .leaflet-pane, .leaflet-top, .leaflet-bottom { z-index: 0 !important; }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .kcol { overflow: visible; }
    .kcol-body { max-height: none; overflow-y: visible; }
    .card-mapa { display: none; }
    .sidebar { position: fixed; left: -100%; top: 0; bottom: 0; width: 280px; z-index: 50; transition: left 0.25s ease; }
    .sidebar.open { left: 0; }
    .main { width: 100%; }
    .topbar { padding: 0 16px; }
    .status-pill { display: none; }
    .hamburger { display: flex; }
    .btn-primary { display: none; }
    .content { padding: 14px 16px; }
    .metrics-row { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .metric-value { font-size: 22px; }
    .bottom-grid { grid-template-columns: 1fr; gap: 12px; }
    .kanban-cols { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; }
    .kcol { min-width: 260px; scroll-snap-align: start; flex-shrink: 0; }
    .kcol-body { max-height: 320px; }
    .map-body { min-height: 180px; }
    .fab { display: flex; }
    .card { max-height: none; }
  }

  @media (max-width: 480px) {
    .metrics-row { grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .metric-card { padding: 12px; }
    .metric-value { font-size: 20px; }
    .kcol { min-width: 240px; }
    #btn-colapsar { display: none; }
  }

  @yield('styles')
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-top">
    <div class="brand">
      <div class="brand-icon">FT</div>
      <div class="brand-text">
        <div class="brand-name">Planner Telecom</div>
        <div class="brand-sub">Painel operacional</div>
      </div>
    </div>
    <div class="search-box">
      <i class="ti ti-search"></i>
      <span>Buscar tarefas...</span>
    </div>
  </div>

  <div class="sidebar-body">
    <div class="nav-section">
      <div class="nav-section-label">Principal</div>
      <a href="/dashboard" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-layout-dashboard"></i><span>Dashboard</span></div></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-layout-kanban"></i><span>Kanban</span></div></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-map-pin"></i><span>Mapa de calor</span></div></a>
    </div>

    <div class="nav-section">
      <div class="nav-section-label">Categorias</div>
      <a href="/rompimento" class="nav-item {{ request()->is('rompimento*') ? 'active' : '' }}">
        <div class="nav-left"><i class="ti ti-bolt"></i><span>Rompimentos</span></div>
        <span class="nav-badge crit">2</span>
      </a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-tools"></i><span>Troca de poste</span></div><span class="nav-badge">2</span></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-wifi"></i><span>Otimização de rede</span></div><span class="nav-badge">4</span></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-headset"></i><span>Atendimento</span></div><span class="nav-badge">49</span></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-tool"></i><span>Manutenção</span></div><span class="nav-badge">3</span></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-file-check"></i><span>Ordem de serviço</span></div></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-certificate"></i><span>Certificação</span></div><span class="nav-badge">5</span></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-wave-sine"></i><span>Correção de sinal</span></div></a>
    </div>

    <div class="nav-section">
      <div class="nav-section-label">Gestão</div>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-users"></i><span>Usuários</span></div></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-activity"></i><span>Atividade</span></div></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-settings"></i><span>Configurações</span></div></a>
    </div>
  </div>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="avatar">DV</div>
      <div class="user-info">
        <div class="user-name">davyibipar</div>
        <div class="user-role">Desenvolvedor</div>
      </div>
      <button class="theme-toggle" onclick="toggleTheme()" title="Alternar tema">
        <i class="ti ti-moon" id="theme-icon"></i>
      </button>
    </div>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:8px;">
      <button class="icon-btn" onclick="toggleSidebar()" id="btn-colapsar" title="Colapsar sidebar">
        <i class="ti ti-layout-sidebar-left-collapse" id="icon-colapsar"></i>
      </button>
      <button class="hamburger" onclick="openSidebar()"><i class="ti ti-menu-2"></i></button>
    </div>
    <div class="topbar-left">
      <div class="page-title">@yield('page-title', 'Dashboard')</div>
      <div class="page-date" id="current-date"></div>
    </div>
    <div class="topbar-right">
      <div class="status-pill">
        <div class="pulse"></div>
        todos os sistemas ok
      </div>
      <button class="icon-btn">
        <i class="ti ti-bell"></i>
        <div class="notif-dot"></div>
      </button>
      <button class="icon-btn"><i class="ti ti-refresh"></i></button>
      <button class="btn-primary" onclick="abrirNovoItem()">
        <i class="ti ti-plus"></i> @yield('btn-label', 'Nova tarefa')
      </button>
    </div>
  </div>

  <div class="content">
    @yield('content')
  </div>
</div>

<button class="fab" onclick="abrirNovoItem()"><i class="ti ti-plus"></i></button>

<script>
  const days = ['dom.','seg.','ter.','qua.','qui.','sex.','sáb.'];
  const months = ['jan.','fev.','mar.','abr.','mai.','jun.','jul.','ago.','set.','out.','nov.','dez.'];
  function updateDate() {
    const d = new Date();
    const str = `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]} · ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
    document.getElementById('current-date').textContent = str;
  }
  updateDate();
  setInterval(updateDate, 60000);

  function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.remove('collapsed');
    sidebar.classList.add('open');
    document.getElementById('sidebar-overlay').classList.add('active');
  }
  function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').classList.remove('active');
  }
  function toggleTheme() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    document.documentElement.setAttribute('data-theme', isDark ? '' : 'dark');
    document.getElementById('theme-icon').className = isDark ? 'ti ti-moon' : 'ti ti-sun';
  }
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const icon = document.getElementById('icon-colapsar');
    sidebar.classList.toggle('collapsed');
    const isCollapsed = sidebar.classList.contains('collapsed');
    icon.className = isCollapsed ? 'ti ti-layout-sidebar-left-expand' : 'ti ti-layout-sidebar-left-collapse';
  }
  function abrirNovoItem() {
    if (typeof abrirModal === 'function') abrirModal();
    else console.log('abrir modal');
  }
</script>

@yield('scripts')

</body>
</html>