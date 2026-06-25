<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Planner Telecom')</title>
<script>
  (function(){
    var s=localStorage.getItem('planner_theme');
    var dark=s?s==='dark':window.matchMedia('(prefers-color-scheme:dark)').matches;
    if(dark)document.documentElement.setAttribute('data-theme','dark');
  })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=DM+Sans:wght@400;500;600&family=Barlow+Condensed:wght@700&display=swap" rel="stylesheet">
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
    /* ── Texto ── */
    --gray-950: #e6edf3;
    --gray-700: #b1bac4;
    --gray-500: #8b949e;
    --gray-400: #6e7681;

    /* ── Backgrounds (camadas) ── */
    --gray-200: #30363d;
    --gray-100: #21262d;
    --gray-50:  #0d1117;
    --white:    #161b22;

    /* ── Semânticas ── */
    --red-bg:      #2a0e0e;
    --red-text:    #ff7b72;
    --red:         #f85149;
    --amber-bg:    #201800;
    --amber-text:  #ffa657;
    --amber:       #e3b341;
    --green-bg:    #0b2215;
    --green-text:  #56d364;
    --green:       #3fb950;

    /* ── Azul (mantém identidade da marca) ── */
    --blue-950: #0a2044;
    --blue-900: #0d2f6e;
    --blue-800: #2f81f7;
    --blue-600: #58a6ff;
    --blue-200: #388bfd;
    --blue-100: #1f6feb;
    --blue-50:  #0d1f3c;

    /* ── Sombra ── */
    --shadow-card: 0 1px 3px rgba(0,0,0,0.5), 0 0 0 1px rgba(48,54,61,0.6);
  }

  /* ── Elementos com cores hardcoded ── */

  [data-theme="dark"] body { background: #0d1117; }

  [data-theme="dark"] .sidebar {
    background: linear-gradient(180deg, #0f1c2e 0%, #0d1829 100%);
    border-right: 1px solid #1c2d40;
  }
  [data-theme="dark"] .sidebar-top { border-bottom-color: rgba(255,255,255,0.06); }
  [data-theme="dark"] .sidebar-footer { border-top-color: rgba(255,255,255,0.06); }
  [data-theme="dark"] .nav-item:hover   { background: rgba(255,255,255,0.06); }
  [data-theme="dark"] .nav-item.active  { background: rgba(47,129,247,0.18); color: #79c0ff; }

  [data-theme="dark"] .topbar {
    background: #161b22;
    border-bottom-color: #30363d;
  }
  [data-theme="dark"] .icon-btn {
    background: #21262d;
    border-color: #30363d;
    color: #8b949e;
  }
  [data-theme="dark"] .icon-btn:hover { background: #30363d; color: #e6edf3; }
  [data-theme="dark"] .notif-dot { border-color: #161b22; }
  [data-theme="dark"] .btn-primary { background: #238636; }
  [data-theme="dark"] .btn-primary:hover { background: #2ea043; }

  [data-theme="dark"] .metric-card:hover { border-color: #388bfd; }
  [data-theme="dark"] .mi-blue  { background: #0d2340; color: #58a6ff; }
  [data-theme="dark"] .mi-amber { background: #201800; color: #ffa657; }
  [data-theme="dark"] .mi-red   { background: #2a0e0e; color: #ff7b72; }
  [data-theme="dark"] .mi-green { background: #0b2215; color: #56d364; }

  [data-theme="dark"] .kcol-head { background: #0d1117; }
  [data-theme="dark"] .kcol { border-right-color: #21262d; }
  [data-theme="dark"] .kcol-body {
    scrollbar-color: #30363d transparent;
  }
  [data-theme="dark"] .kcard {
    background: #1c2128;
    border-color: #30363d;
  }
  [data-theme="dark"] .kcard:hover {
    background: #22272e;
    border-color: #388bfd;
    box-shadow: 0 2px 8px rgba(47,129,247,0.12);
  }

  /* ── Badges de categoria no dark ── */
  [data-theme="dark"] .b-regiao-gv { background: #0d1f3c; color: #79c0ff; }
  [data-theme="dark"] .b-regiao-va { background: #1a1035; color: #c4b5fd; }
  [data-theme="dark"] .b-cat-rom   { background: #2a0e0e; color: #ff7b72; }
  [data-theme="dark"] .b-cat-ate   { background: #0d1f3c; color: #79c0ff; }
  [data-theme="dark"] .b-cat-otm   { background: #0b2215; color: #56d364; }
  [data-theme="dark"] .b-cat-man   { background: #201800; color: #ffa657; }
  [data-theme="dark"] .b-cat-tro   { background: #1a1035; color: #c4b5fd; }
  [data-theme="dark"] .b-cat-etq   { background: #1c0d22; color: #d2a8ff; }
  [data-theme="dark"] .b-cat-cer   { background: #201200; color: #ffa657; }
  [data-theme="dark"] .b-cat-cor   { background: #091a10; color: #56d364; }
  [data-theme="dark"] .b-cat-qua   { background: #081826; color: #79c0ff; }
  [data-theme="dark"] .b-cat-gen   { background: #21262d; color: #8b949e; }

  [data-theme="dark"] .map-body { background: #090f1a; }

  /* ── Scrollbar global no dark ── */
  [data-theme="dark"] ::-webkit-scrollbar-thumb { background: #30363d; }

  /* ── Transições suaves na troca de tema ── */
  [data-theme="dark"] * { color-scheme: dark; }

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
  .brand { margin-bottom: 4px; }
  .sidebar-logo { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; }
  .sidebar-logo-text {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 26px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.06em;
    line-height: 1;
  }
  .sidebar-logo-svg { width: 140px; opacity: 0.92; }
  .sidebar-logo-collapsed {
    display: none;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.06em;
  }
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
  .sidebar.collapsed .sidebar-logo,
  .sidebar.collapsed .nav-section-label,
  .sidebar.collapsed .user-info,
  .sidebar.collapsed .nav-left span,
  .sidebar.collapsed .theme-toggle { display: none; }
  .sidebar.collapsed .sidebar-logo-collapsed { display: block; }
  .sidebar.collapsed .brand { display: flex; justify-content: center; }
  .sidebar.collapsed .nav-item { justify-content: center; padding: 8px; overflow: hidden; }
  .sidebar.collapsed .nav-left { gap: 0; overflow: hidden; white-space: nowrap; }
  .sidebar.collapsed .nav-left i { flex-shrink: 0; }
  .sidebar.collapsed .user-card { justify-content: center; }

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
    display: none;
  }
  .notif-wrap { position: relative; }
  .notif-count {
    position: absolute; top: -4px; right: -4px;
    min-width: 16px; height: 16px; padding: 0 4px;
    border-radius: 999px; background: var(--red); color: #fff;
    font-size: 10px; font-weight: 600; line-height: 16px;
    display: none; align-items: center; justify-content: center;
    border: 1.5px solid var(--white);
  }
  .notif-panel {
    display: none;
    position: absolute; top: calc(100% + 8px); right: 0;
    width: min(360px, calc(100vw - 24px));
    max-height: 420px;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
    z-index: 500;
    overflow: hidden;
  }
  .notif-panel.open { display: flex; flex-direction: column; }
  .notif-panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px; border-bottom: 1px solid var(--gray-200);
  }
  .notif-panel-title { font-size: 13px; font-weight: 600; color: var(--gray-950); }
  .notif-panel-action {
    border: none; background: transparent; color: var(--blue-600);
    font-size: 12px; font-weight: 500; cursor: pointer; font-family: inherit;
  }
  .notif-panel-action:hover { text-decoration: underline; }
  .notif-panel-body { overflow-y: auto; max-height: 360px; }
  .notif-empty {
    padding: 28px 16px; text-align: center; color: var(--gray-400); font-size: 13px;
  }
  .notif-item {
    width: 100%; text-align: left; border: none; background: transparent;
    padding: 12px 14px; border-bottom: 1px solid var(--gray-100);
    cursor: pointer; font-family: inherit;
    transition: background 0.12s;
  }
  .notif-item:hover { background: var(--gray-50); }
  .notif-item--lida { opacity: 0.72; }
  .notif-item-title {
    display: block; font-size: 13px; font-weight: 600; color: var(--gray-950); margin-bottom: 4px;
  }
  .notif-item-msg {
    display: block; font-size: 12px; color: var(--gray-600); line-height: 1.45; margin-bottom: 6px;
  }
  .notif-item-time { display: block; font-size: 11px; color: var(--gray-400); }
  [data-theme="dark"] .notif-panel {
    background: #161b22; border-color: #30363d;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
  }
  [data-theme="dark"] .notif-panel-head { border-color: #30363d; }
  [data-theme="dark"] .notif-panel-title { color: #e6edf3; }
  [data-theme="dark"] .notif-item { border-color: #21262d; }
  [data-theme="dark"] .notif-item:hover { background: #21262d; }
  [data-theme="dark"] .notif-item-title { color: #e6edf3; }
  [data-theme="dark"] .notif-item-msg { color: #8b949e; }
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
  .d-purple { background: #a855f7; }
  .kcol-count { font-size: 11px; color: var(--gray-400); background: var(--gray-100); padding: 1px 6px; border-radius: 20px; }
  .kcol-body { flex: 1; min-height: 0; overflow-y: auto; padding: 8px; display: flex; flex-direction: column; gap: 6px; scrollbar-width: thin; scrollbar-color: var(--gray-200) transparent; }
  .kcol-body > .kcard,
  .kcol-body > * { flex-shrink: 0; }
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
  .b-aberta { background: #dbeafe; color: #1d4ed8; }
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
  *, *::before, *::after {
    transition: background-color 0.22s ease, border-color 0.22s ease, color 0.22s ease, box-shadow 0.22s ease;
  }
  /* remove transição de elementos que não precisam */
  .kcard[draggable="true"], .modal-overlay, .modal-box { transition: none; }

  /* OVERLAY */
  .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 40; }
  .sidebar-overlay.active { display: block; }
  .hamburger { display: none; background: transparent; border: none; color: var(--gray-500); font-size: 20px; cursor: pointer; padding: 4px; align-items: center; justify-content: center; }
  .fab { position: fixed; bottom: 20px; right: 20px; width: 52px; height: 52px; border-radius: 50%; background: #166ac4; color: #fff; border: none; font-size: 22px; cursor: pointer; z-index: 50; box-shadow: 0 4px 16px rgba(22,106,196,0.4); display: none; align-items: center; justify-content: center; transition: background 0.15s, transform 0.1s; }
  .fab:active { transform: scale(0.94); }

  /* LEAFLET */
  #mapa-calor { z-index: 0; }
  .leaflet-pane, .leaflet-top, .leaflet-bottom { z-index: 0 !important; }

  /* MODAL */
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
    transition: background 0.32s cubic-bezier(0.16,1,0.3,1), visibility 0.32s;
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
    transition: transform 0.38s cubic-bezier(0.16,1,0.3,1), opacity 0.38s cubic-bezier(0.16,1,0.3,1);
  }
  .modal-overlay.open .modal-box { opacity: 1; transform: scale(1) translateY(0); }
  .modal-head { padding: 16px 24px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
  .modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
  .modal-foot { padding: 14px 24px; border-top: 1px solid var(--gray-200); display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0; }
  .modal-title { font-size: 15px; font-weight: 600; color: var(--gray-950); margin: 0; }
  .modal-sub { font-size: 12px; color: var(--gray-500); margin: 0; }
  .modal-close { background: transparent; border: none; cursor: pointer; color: var(--gray-500); font-size: 18px; display: flex; align-items: center; padding: 4px; transition: color 0.15s; }
  .modal-close:hover { color: var(--gray-950); }
  .modal-tabs { display: flex; border-bottom: 1px solid var(--gray-200); padding: 0 24px; flex-shrink: 0; gap: 0; }
  .modal-tab { padding: 10px 16px; font-size: 13px; font-weight: 500; color: var(--gray-500); border: none; background: transparent; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; display: inline-flex; align-items: center; gap: 6px; font-family: inherit; transition: color 0.15s, border-color 0.15s; }
  .modal-tab:hover { color: var(--gray-700); }
  .modal-tab.active { color: #166ac4; border-bottom-color: #166ac4; }
  .detail-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
  .detail-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .detail-field { display: flex; flex-direction: column; gap: 5px; }
  .detail-field.span-2 { grid-column: span 2; }
  .detail-field.span-3 { grid-column: span 3; }
  .detail-label { font-size: 12px; font-weight: 500; color: var(--gray-500); }
  .detail-value { border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 8px 10px; min-height: 38px; font-size: 13px; color: var(--gray-950); background: var(--gray-50); line-height: 1.4; word-break: break-word; }
  .detail-badges { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; min-height: 38px; }
  .detail-loading { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: var(--gray-500); font-size: 13px; }
  .detail-loading i { animation: spin 0.9s linear infinite; }
  .detail-error { padding: 16px; border-radius: var(--radius-sm); background: var(--red-bg); color: var(--red-text); font-size: 13px; }
  .detail-enter { animation: conteudoEntrada 0.42s cubic-bezier(0.16,1,0.3,1) forwards; }
  .btn-modal { padding: 0 16px; height: 36px; border-radius: var(--radius-sm); font-size: 13px; cursor: pointer; font-family: inherit; }
  .btn-modal-ghost { border: 1px solid var(--gray-200); background: transparent; color: var(--gray-500); }
  .btn-modal-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }
  .btn-modal-primary { border: none; background: #166ac4; color: #fff; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
  .btn-modal-primary:hover { background: #0d5aaa; }
  @keyframes conteudoEntrada { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .kcol { overflow: visible; }
    .kcol-body { max-height: none; overflow-y: visible; }
    .card-mapa { display: none; }
    .sidebar { position: fixed; left: -100%; top: 0; bottom: 0; width: 280px; z-index: 50; transition: left 0.25s ease; }
    .sidebar.open { left: 0; }
    .main { width: 100%; }
    .topbar { padding: 0 16px; }
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
      <div class="sidebar-logo">
        <div class="sidebar-logo-text">PLANNER</div>
        <div class="sidebar-logo-svg">
          <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="100%" height="100%" version="1.1" style="shape-rendering:geometricPrecision;text-rendering:geometricPrecision;image-rendering:optimizeQuality;fill-rule:evenodd;clip-rule:evenodd" viewBox="0 0 2728.77 550.48">
            <defs><style type="text/css">.sfl0{fill:#fff}.sfl1{fill:#fff;fill-rule:nonzero}.sfl2{fill:#fff;opacity:.7}.sfl3{fill:#fff;opacity:.85}</style></defs>
            <g><g><g><g>
              <path class="sfl0" d="M2609.16 537.98l112.98 0.07c1.83,0 3.4,-0.65 4.69,-1.93 1.29,-1.29 1.94,-2.87 1.94,-4.69 0,-152.65 0,-306.74 0,-459.35 0,-18.08 -14.75,-32.84 -32.83,-32.84 -30.36,-0 -60.7,0.02 -91.05,0.04 -3.64,0.01 -6.62,2.98 -6.62,6.62l0 241.79c0,2.96 -1.87,5.48 -4.69,6.34 -2.82,0.86 -5.77,-0.18 -7.42,-2.63l-162.1 -240.51c-5.03,-7.47 -12.77,-11.59 -21.79,-11.59l-136.09 0c-3.64,0 -6.62,2.98 -6.62,6.62l0 485.48c0,3.65 2.98,6.62 6.62,6.62l91.2 0c9.15,0 16.84,-3.21 23.29,-9.7 6.44,-6.48 9.6,-14.21 9.54,-23.35l-1.58 -252.34c-0.02,-2.96 1.83,-5.48 4.67,-6.37 2.84,-0.87 5.79,0.17 7.45,2.64l186.63 277.47c5.03,7.48 12.77,11.61 21.79,11.61z"/>
              <path class="sfl1" d="M1880.36 49.76c-98.02,35.43 -161.88,127.21 -162.71,241.81 0,143.09 112.29,258.91 255.3,258.91 143.38,0 256.08,-115.17 256.08,-258.91 -0.82,-112.89 -62.8,-203.65 -158.34,-240.21 2.73,9.07 4.21,18.66 4.21,28.58l0 125.15c15.44,22.42 24.96,51.67 25.33,87.43 2.2,183.41 -255.98,183.41 -253.78,0 0.4,-38.67 11.48,-69.72 29.2,-92.75l0 -119.82c0,-10.51 1.65,-20.66 4.71,-30.2z"/>
              <path class="sfl2" d="M1975.27 0c44.16,0 79.97,24.92 79.97,55.65l0.01 194.57c-0.01,30.72 -35.81,55.64 -79.97,55.64c-44.16,-0.01 -79.97,-24.94 -79.97,-55.65l0 -194.57c0,-30.73 35.81,-55.65 79.97,-55.65z"/>
            </g></g>
            <path class="sfl0" d="M954.11 523.1l112.98 0.07c1.83,0 3.4,-0.65 4.69,-1.93 1.29,-1.29 1.94,-2.87 1.94,-4.69 0,-152.65 0,-306.74 0,-459.35 0,-18.08 -14.75,-32.84 -32.83,-32.84 -30.36,-0.01 -60.7,0.02 -91.05,0.04 -3.64,0.01 -6.62,2.98 -6.62,6.62l0 241.79c0,2.96 -1.87,5.47 -4.69,6.33 -2.82,0.86 -5.77,-0.18 -7.42,-2.63l-162.11 -240.51c-5.03,-7.47 -12.77,-11.59 -21.79,-11.59l-136.09 0c-3.64,0 -6.62,2.97 -6.62,6.62l0 485.48c0,3.65 2.98,6.62 6.62,6.62l91.19 0c9.15,0 16.84,-3.2 23.3,-9.7 6.44,-6.48 9.6,-14.21 9.55,-23.35l-1.58 -252.33c-0.02,-2.96 1.83,-5.49 4.67,-6.37 2.84,-0.87 5.79,0.17 7.45,2.64l186.62 277.47c5.03,7.48 12.78,11.61 21.8,11.61z"/>
            <path class="sfl0" d="M1245.64 516.61c0,-153.05 0,-306.12 0,-459.17 0,-18.16 -14.75,-33 -32.84,-33l-91.05 0c-3.64,0 -6.62,2.98 -6.62,6.62l0 429.04c-0.75,30.13 15.85,62.77 49.35,62.88l74.51 0.26c1.83,0.01 3.4,-0.63 4.69,-1.94 1.3,-1.29 1.95,-2.87 1.95,-4.69z"/>
            <path class="sfl1" d="M1712.52 92.48c-36.3,-49.88 -113.25,-73.41 -179.56,-73.41 -162.46,0.96 -254.73,125.94 -255.7,257.62 0,131.69 90.36,254.72 255.7,254.72 72.32,0 129.97,-18.35 183.67,-82.85 2.18,-2.61 2.02,-6.42 -0.35,-8.87l-44.94 -46.09c-13.67,-14.02 -35.13,-15.95 -51.08,-4.57 -28.55,20.35 -59.73,28.5 -89.23,27.02 -67.29,-3.84 -125.93,-58.64 -124.98,-138.42 0.96,-94.22 65.36,-143.24 134.59,-141.3 25.37,0.7 51.79,8.13 75.82,23.03 15.93,9.89 36.2,7.03 48.79,-6.87l46.82 -51.7c2.12,-2.34 2.31,-5.77 0.45,-8.34z"/>
            <g>
              <polygon class="sfl0" points="327.73,355.2 491.58,355.2 491.58,520.2 327.73,520.2"/>
              <polygon class="sfl3" points="327.73,190.19 491.58,190.19 491.58,355.2 327.73,355.2"/>
              <polygon class="sfl2" points="327.73,25.16 491.58,25.16 491.58,190.19 327.73,190.19"/>
              <polygon class="sfl3" points="163.85,355.2 327.73,355.2 327.73,520.2 163.85,520.2"/>
              <polygon class="sfl2" points="0,355.2 163.85,355.2 163.85,520.2 0,520.2"/>
              <polygon class="sfl2" points="163.85,190.19 327.73,190.19 327.73,355.2 163.85,355.2"/>
            </g>
            </g></g>
          </svg>
        </div>
      </div>
      <div class="sidebar-logo-collapsed">P</div>
    </div>
  </div>

  <div class="sidebar-body">
    <div class="nav-section">
      <div class="nav-section-label">Principal</div>
      <a href="/dashboard" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-layout-dashboard"></i><span>Dashboard</span></div></a>
      <a href="/tarefas" id="nav-item-tarefas" data-permissao="visualizar_aba_tarefas" class="nav-item {{ request()->is('tarefas*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-checklist"></i><span>Tarefas</span></div></a>
    </div>

    <div class="nav-section">
      <div class="nav-section-label">Categorias</div>
      <a href="/rompimento" class="nav-item {{ request()->is('rompimento*') ? 'active' : '' }}">
        <div class="nav-left"><i class="ti ti-bolt"></i><span>Rompimentos</span></div>
      </a>
      <a href="/troca-de-poste" class="nav-item {{ request()->is('troca-de-poste*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-tools"></i><span>Troca de poste</span></div></a>
      <a href="/otimizacao-de-rede" class="nav-item {{ request()->is('otimizacao-de-rede*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-wifi"></i><span>Otimização de rede</span></div></a>
      <a href="/atendimento" class="nav-item {{ request()->is('atendimento*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-headset"></i><span>Atendimento</span></div></a>
      <a href="#" class="nav-item"><div class="nav-left"><i class="ti ti-tool"></i><span>Manutenção</span></div></a>
      <a href="/ordem-de-servico" class="nav-item {{ request()->is('ordem-de-servico*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-file-check"></i><span>Ordem de serviço</span></div></a>
      <a href="/certificacao-cemig" class="nav-item {{ request()->is('certificacao-cemig*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-certificate"></i><span>Certificação</span></div></a>
      <a href="/correcao-de-sinal" class="nav-item {{ request()->is('correcao-de-sinal*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-wave-sine"></i><span>Correção de sinal</span></div></a>
    </div>

    <div class="nav-section">
      <div class="nav-section-label">Ferramentas</div>
      <a href="/buscar-caixa" class="nav-item {{ request()->is('buscar-caixa*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-box"></i><span>Buscar caixa</span></div></a>
    </div>

    <div class="nav-section">
      <div class="nav-section-label">Gestão</div>
      <a href="/usuarios" id="nav-item-usuarios" data-permissao="visualizar_aba_usuarios" class="nav-item {{ request()->is('usuarios*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-users"></i><span>Usuários</span></div></a>
      <a href="/configuracoes" id="nav-item-configuracoes" data-permissao="adicionar_webhook" class="nav-item {{ request()->is('configuracoes*') ? 'active' : '' }}"><div class="nav-left"><i class="ti ti-settings"></i><span>Configurações</span></div></a>
    </div>
  </div>

  <div class="sidebar-footer">
    <div class="user-card">
      <div class="avatar" id="sidebar-user-avatar">--</div>
      <div class="user-info">
        <div class="user-name" id="sidebar-user-name">Usuário</div>
        <div class="user-role" id="sidebar-user-role">—</div>
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
      <div class="notif-wrap">
        <button class="icon-btn" id="btn-notificacoes" type="button" title="Notificações" aria-label="Notificações">
          <i class="ti ti-bell"></i>
          <div class="notif-dot" id="notif-dot"></div>
          <span class="notif-count" id="notificacoes-badge">0</span>
        </button>
        <div class="notif-panel" id="notificacoes-painel">
          <div class="notif-panel-head">
            <span class="notif-panel-title">Notificações</span>
            <button type="button" class="notif-panel-action" id="btn-notificacoes-marcar-todas">Marcar todas como lidas</button>
          </div>
          <div class="notif-panel-body">
            <div class="notif-empty" id="notificacoes-vazio">Nenhuma notificação.</div>
            <div id="notificacoes-lista"></div>
          </div>
        </div>
      </div>
      <button class="icon-btn"><i class="ti ti-refresh"></i></button>
      @unless(View::hasSection('hide-topbar-btn'))
      <button class="btn-primary" onclick="abrirNovoItem()">
        <i class="ti ti-plus"></i> @yield('btn-label', 'Nova tarefa')
      </button>
      @endunless
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

  function iniciaisDeUsuario(username) {
    if (!username) return '--';
    const partes = String(username).split(/[._-]/).filter(Boolean);
    if (partes.length >= 2) {
      return (partes[0].charAt(0) + partes[1].charAt(0)).toUpperCase();
    }
    return String(username).slice(0, 2).toUpperCase();
  }

  function rotuloFuncao(funcao) {
    if (funcao === 'tecnico') return 'Técnico';
    if (funcao === 'projetista') return 'Projetista';
    return 'Usuário';
  }

  function possuiPermissao(key) {
    let user = null;
    try {
      user = JSON.parse(localStorage.getItem('planner_user') || 'null');
    } catch {
      return false;
    }
    const permissoes = user?.permissoes || [];
    if (permissoes.includes(key)) return true;
    if (key === 'adicionar_webhook' && permissoes.includes('conectar_webhook')) return true;
    return false;
  }
  window.plannerPossuiPermissao = possuiPermissao;

  function aplicarPermissoesNav() {
    document.querySelectorAll('[data-permissao]').forEach((el) => {
      if (possuiPermissao(el.dataset.permissao)) {
        el.style.removeProperty('display');
      } else {
        el.style.display = 'none';
      }
    });
  }

  async function sincronizarPermissoesUsuario() {
    const token = localStorage.getItem('planner_token');
    if (!token) return;

    try {
      const response = await fetch('/api/me', {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
        cache: 'no-store',
      });
      if (!response.ok) return;

      const data = await response.json();
      if (!data.user) return;

      let stored = null;
      try {
        stored = JSON.parse(localStorage.getItem('planner_user') || 'null');
      } catch {
        stored = null;
      }

      const user = {
        ...(stored && typeof stored === 'object' ? stored : {}),
        ...data.user,
        permissoes: data.user.permissoes || [],
      };
      localStorage.setItem('planner_user', JSON.stringify(user));
    } catch (_) {}
  }

  function atualizarUsuarioSidebar() {
    const nomeEl = document.getElementById('sidebar-user-name');
    const roleEl = document.getElementById('sidebar-user-role');
    const avatarEl = document.getElementById('sidebar-user-avatar');
    if (!nomeEl || !roleEl || !avatarEl) return;

    let user = null;
    try {
      user = JSON.parse(localStorage.getItem('planner_user') || 'null');
    } catch (_) {}

    const username = user?.username || '';
    if (!username) {
      nomeEl.textContent = 'Usuário';
      roleEl.textContent = '—';
      avatarEl.textContent = '--';
      return;
    }

    nomeEl.textContent = username;
    roleEl.textContent = rotuloFuncao(user.funcao);
    avatarEl.textContent = iniciaisDeUsuario(username);
    aplicarPermissoesNav();
  }

  async function initUsuarioSidebar() {
    await sincronizarPermissoesUsuario();
    atualizarUsuarioSidebar();
  }

  initUsuarioSidebar();

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
  function applyTheme(dark) {
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : '');
    const icon = document.getElementById('theme-icon');
    if (icon) icon.className = dark ? 'ti ti-sun' : 'ti ti-moon';
    localStorage.setItem('planner_theme', dark ? 'dark' : 'light');
  }
  function toggleTheme() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    applyTheme(!isDark);
  }
  (function() {
    const saved = localStorage.getItem('planner_theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(saved ? saved === 'dark' : prefersDark);
  })();
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

@php
  $realtimePage = match (true) {
    request()->is('rompimento*') => ['categorias' => ['rompimentos', 'rompimento'], 'reload' => 'carregarRompimentos'],
    request()->is('troca-de-poste*') => ['categorias' => ['troca-poste'], 'reload' => 'carregarTrocas'],
    request()->is('otimizacao-de-rede*') => ['categorias' => ['otimizacao-rede', 'otimizacao de rede', 'otimização de rede', 'OTIMIZACAO DE REDE', 'OTIMIZAÇÃO DE REDE'], 'reload' => 'carregarOtimizacoes'],
    request()->is('atendimento*') => ['categorias' => ['atendimento-cliente', 'atendimento ao cliente'], 'reload' => 'carregarAtendimentos'],
    request()->is('correcao-de-sinal*') => ['categorias' => ['correcao-atenuacao', 'correção de atenuação'], 'reload' => 'carregarCorrecoes'],
    request()->is('certificacao-cemig*') => ['categorias' => ['certificacao-cemig', 'certificação cemig'], 'reload' => 'carregarCertificacoes'],
    request()->is('ordem-de-servico*') => ['categorias' => ['ordem-servico'], 'reload' => 'carregarOrdemServicoDashboard'],
    request()->is('tarefas*') => ['categorias' => ['tarefas'], 'reload' => 'carregarTarefas'],
    request()->is('dashboard*') => ['categorias' => ['tarefas'], 'reload' => 'carregarSuasTarefas'],
    default => null,
  };

  $broadcastDriver = config('broadcasting.default');
  $reverbHost = config('broadcasting.connections.reverb.options.host');
  $requestHost = request()->getHost();
  $localHosts = ['localhost', '127.0.0.1', '::1'];
  $reverbHostIsLocal = in_array($reverbHost, $localHosts, true);
  $requestHostIsLocal = in_array($requestHost, $localHosts, true);
  $realtimeEnabled = match ($broadcastDriver) {
    'reverb' => filled(config('broadcasting.connections.reverb.key')) && (! $reverbHostIsLocal || $requestHostIsLocal),
    'pusher' => filled(config('broadcasting.connections.pusher.key')),
    default => false,
  };
@endphp

<script src="{{ asset('js/planner-notificacoes.js') }}"></script>

@if($realtimePage)
<script src="{{ asset('js/planner-reload-guard.js') }}"></script>
<script src="{{ asset('js/planner-kanban.js') }}"></script>
@endif

@if(request()->is('tarefas*', 'dashboard*'))
<script src="{{ asset('js/planner-tarefas-sync.js') }}"></script>
@endif

@yield('scripts')

@if($realtimePage && $realtimeEnabled)
<script>
  window.plannerRealtimeCategorias = @json($realtimePage['categorias']);
  window.plannerRealtimeReload = async function () {
    const gen = window.plannerBeginReload?.() ?? 0;
    if (typeof window.{{ $realtimePage['reload'] }} === 'function') {
      await window.{{ $realtimePage['reload'] }}();
    }
    if (window.plannerIsReloadCurrent && !window.plannerIsReloadCurrent(gen)) return;
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.19.0/dist/echo.iife.js"></script>
<script>
  window.PLANNER_REALTIME = {
    enabled: true,
    driver: @json($broadcastDriver),
    key: @json($broadcastDriver === 'pusher'
      ? config('broadcasting.connections.pusher.key')
      : config('broadcasting.connections.reverb.key')),
    host: @json($reverbHost),
    port: @json((int) config('broadcasting.connections.reverb.options.port')),
    scheme: @json(config('broadcasting.connections.reverb.options.scheme')),
    cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
    authEndpoint: @json(url('/broadcasting/auth')),
  };
</script>
<script src="{{ asset('js/planner-realtime.js') }}"></script>
@elseif($realtimePage)
<script>
  window.plannerRealtimeReload = async function () {
    const gen = window.plannerBeginReload?.() ?? 0;
    if (typeof window.{{ $realtimePage['reload'] }} === 'function') {
      await window.{{ $realtimePage['reload'] }}();
    }
    if (window.plannerIsReloadCurrent && !window.plannerIsReloadCurrent(gen)) return;
  };
  window.PLANNER_POLLING = {
    enabled: true,
    categorias: @json($realtimePage['categorias']),
    timeoutSec: 25,
    fallbackIntervalMs: 1500,
  };
</script>
<script src="{{ asset('js/planner-polling.js') }}"></script>
@endif

</body>
</html>