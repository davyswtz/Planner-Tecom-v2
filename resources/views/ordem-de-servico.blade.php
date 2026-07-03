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
    gap: 32px;
  }

  .os-panel {
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    background: var(--white);
    overflow: hidden;
    margin-top: 20px;
    margin-bottom: 20px;
  }

  .os-panel-head {
    padding: 12px 14px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
  }

  .os-panel-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-950);
  }

  .os-panel-meta {
    font-size: 12px;
    color: var(--gray-500);
  }

  .os-analytics-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    align-items: stretch;
    margin-bottom: 2px;
  }

  .os-heatmap-panel,
  .os-donut-panel,
  .os-kpi-panel {
    min-width: 0;
    display: flex;
    flex-direction: column;
  }

  .os-heatmap-controls {
    display: inline-flex;
    gap: 4px;
    padding: 4px;
    border: 1px solid var(--gray-200);
    border-radius: 999px;
    background: var(--gray-50);
  }

  .os-toggle-btn {
    border: none;
    background: transparent;
    color: var(--gray-500);
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 12px;
    font-family: inherit;
    cursor: pointer;
  }

  .os-toggle-btn.is-active {
    background: var(--white);
    color: var(--gray-950);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
  }

  .os-heatmap-body {
    padding: 14px 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: center;
    flex: 1;
  }

  #heatmap-content {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-width: 0;
  }

  .os-heatmap-month {
    font-size: 11px;
    color: var(--gray-500);
  }

  .os-heatmap-month strong {
    color: var(--gray-900);
    font-weight: 600;
  }

  .os-heatmap-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(20px, 26px));
    gap: 6px;
    width: max-content;
    max-width: 100%;
    margin: 0 auto;
  }

  .os-heatmap-header {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--gray-400);
    text-align: center;
    line-height: 1;
  }

  .os-heatmap-cell,
  .os-heatmap-cell-empty {
    width: 100%;
    aspect-ratio: 1 / 1;
    border-radius: 4px;
  }

  .os-heatmap-cell {
    border: 2px solid transparent;
    padding: 0;
    display: block;
    color: #fff;
    cursor: default;
    position: relative;
  }

  .os-heatmap-cell-empty {
    background: transparent;
  }

  .os-heatmap-day,
  .os-heatmap-score {
    display: none;
  }

  .os-heatmap-cell--success { background: #22c55e; }
  .os-heatmap-cell--good { background: #84cc16; }
  .os-heatmap-cell--alert { background: #eab308; color: #111827; }
  .os-heatmap-cell--critical { background: #f97316; }
  .os-heatmap-cell--danger { background: #ef4444; }
  .os-heatmap-cell--nodata {
    background: transparent;
    color: var(--gray-500);
    
    box-shadow: inset 0 0 0 1px #374151;
  }
  .os-heatmap-cell--future {
    background: transparent;
    color: #cbd5e1;
   
    box-shadow: inset 0 0 0 1px #374151;
  }

  .os-heatmap-week {
    display: grid;
    grid-template-columns: 92px repeat(7, minmax(20px, 26px));
    gap: 6px;
    align-items: center;
    width: max-content;
    max-width: 100%;
    margin: 0 auto;
  }

  .os-heatmap-week-head {
    font-size: 9px;
    color: var(--gray-400);
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    line-height: 1;
  }

  .os-heatmap-week-label {
    font-size: 11px;
    color: var(--gray-900);
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.1;
  }

  .os-heatmap-week-row {
    grid-column: 2 / span 7;
    display: grid;
    grid-template-columns: repeat(7, minmax(20px, 26px));
    gap: 6px;
  }

  .os-heatmap-legend {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 10px;
    color: var(--gray-500);
    width: 100%;
  }

  .os-heatmap-legend span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .os-heatmap-legend i {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
  }

  .os-donut-body {
    padding: 14px 16px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14px;
    flex: 1;
    justify-content: center;
  }

  .os-donut-chart {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: conic-gradient(#e5e7eb 0deg 360deg);
    position: relative;
  }

  .os-donut-chart::after {
    content: '';
    width: 82px;
    height: 82px;
    border-radius: 50%;
    background: var(--white);
    border: 1px solid var(--gray-100);
  }

  .os-donut-center {
    position: absolute;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
  }

  .os-donut-total {
    font-size: 28px;
    font-weight: 700;
    color: var(--gray-950);
    line-height: 1;
  }

  .os-donut-caption {
    font-size: 11px;
    color: var(--gray-500);
  }

  .os-donut-legend {
    width: 100%;
    display: flex;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
  }

  .os-donut-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--gray-700);
  }

  .os-donut-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
  }

  .os-donut-empty {
    font-size: 13px;
    color: var(--gray-400);
    text-align: center;
    padding: 20px 0;
  }

  .os-kpi-panel {
    background: var(--white);
    border-color: var(--gray-200);
  }

  .os-kpi-panel .os-panel-head {
    border-bottom-color: var(--gray-100);
  }

  .os-kpi-panel .os-panel-title,
  .os-kpi-panel .os-panel-meta {
    color: var(--gray-900);
  }

  .os-kpi-grid {
    padding: 14px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    flex: 1;
  }

  .os-kpi-card {
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    background: var(--gray-50);
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 72px;
  }

  .os-kpi-value {
    font-size: 22px;
    line-height: 1;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
  }

  .os-kpi-label {
    margin-top: 4px;
    font-size: 14px;
    color: var(--gray-500);
  }

  .os-kpi-card--aberta .os-kpi-value { color: #2563eb; }
  .os-kpi-card--andamento .os-kpi-value { color: #d97706; }
  .os-kpi-card--tecnicos .os-kpi-value { color: var(--gray-950); }
  .os-kpi-card--finalizada .os-kpi-value { color: #16a34a; }

  .os-filters-panel {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-top: 10px;
    margin-bottom: 10px;
  }

  .os-filters-fields {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px 18px;
  }

  .os-filter-group--search {
    grid-column: span 2;
  }

  .os-filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 0;
  }

  .os-filter-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--gray-400);
    padding-left: 2px;
  }

  .os-filter-actions {
    display: flex;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid var(--gray-100);
  }

  .os-filter-actions .os-toolbar-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  [data-theme="dark"] .os-filter-actions {
    border-top-color: #21262d;
  }

  .os-toolbar-search,
  .os-toolbar-select,
  .os-toolbar-date {
    width: 100%;
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

  .os-toolbar-search::placeholder { color: var(--gray-400); }

  .os-toolbar-search:focus,
  .os-toolbar-select:focus,
  .os-toolbar-date:focus {
    border-color: var(--blue-600);
  }

  .os-toolbar-btn {
    height: 34px;
    border-radius: 10px;
    border: 1px solid var(--gray-200);
    background: var(--white);
    color: var(--gray-700);
    font-family: inherit;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    padding: 0 14px;
    white-space: nowrap;
  }

  .os-toolbar-btn:hover {
    border-color: var(--gray-400);
    color: var(--gray-950);
  }

  .os-toolbar-btn--primary {
    background: var(--blue-600);
    border-color: var(--blue-600);
    color: #fff;
    min-width: 110px;
  }

  .os-toolbar-btn--primary:hover {
    background: var(--blue-700);
    border-color: var(--blue-700);
    color: #fff;
  }

  .os-toolbar-btn--ghost {
    background: transparent;
    border-color: transparent;
    color: var(--gray-500);
  }

  .os-toolbar-btn--ghost:hover {
    background: var(--gray-50);
    border-color: var(--gray-100);
    color: var(--gray-950);
  }

  .os-toolbar-btn--export {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .os-toolbar-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
  }

  .os-filtros-extra {
    display: none;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px 18px;
    padding-top: 4px;
  }

  .os-filtros-extra.open {
    display: grid;
  }

  .os-periodo-hint {
    font-size: 12px;
    color: var(--gray-500);
    display: none;
    padding-top: 2px;
  }

  .os-periodo-hint.visible {
    display: block;
  }

  .os-summary-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(0, 1.05fr) minmax(0, 0.85fr);
    gap: 14px;
    align-items: start;
    margin-top: 2px;
  }

  .os-summary-panel {
    min-width: 0;
  }

  .os-tech-list {
    padding: 10px 0 14px;
  }

  .os-tech-row {
    display: grid;
    grid-template-columns: minmax(0, 130px) minmax(140px, 1fr) auto;
    gap: 14px;
    align-items: center;
    padding: 10px 16px;
    cursor: pointer;
    transition: background 0.12s ease;
  }

  .os-tech-row:hover { background: var(--gray-50); }

  .os-tech-row + .os-tech-row { border-top: 1px solid var(--gray-50); }

  .os-tech-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-950);
  }

  .os-tech-reg {
    margin-top: 3px;
    font-size: 11px;
    color: var(--gray-400);
  }

  .os-tech-bar-wrap {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .os-tech-bar {
    width: 100%;
    height: 12px;
    border-radius: 999px;
    overflow: hidden;
    background: var(--gray-100);
    display: flex;
  }

  .os-tech-bar-segment--aberta { background: #3b82f6; }
  .os-tech-bar-segment--andamento { background: #eab308; }
  .os-tech-bar-segment--finalizada { background: #22c55e; }

  .os-tech-legend {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 10px;
    color: var(--gray-400);
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .os-tech-legend span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .os-tech-legend i {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    display: inline-block;
  }

  .os-tech-total {
    min-width: 36px;
    text-align: right;
    font-size: 14px;
    font-weight: 700;
    color: var(--gray-950);
    font-variant-numeric: tabular-nums;
  }

  .os-mini-list {
    padding: 8px 0;
  }

  .os-mini-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 16px;
    font-size: 13px;
    transition: background 0.12s ease;
  }

  .os-mini-item + .os-mini-item { border-top: 1px solid var(--gray-50); }

  .os-mini-item--clickable {
    cursor: pointer;
  }

  .os-mini-item--clickable:hover { background: var(--gray-50); }

  .os-mini-label {
    color: var(--gray-700);
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .os-mini-count {
    font-weight: 700;
    color: var(--gray-950);
    font-variant-numeric: tabular-nums;
  }

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

  .os-cell-main {
    font-weight: 500;
    color: var(--gray-950);
  }

  .os-cell-sub {
    font-size: 11px;
    color: var(--gray-400);
    margin-top: 2px;
  }

  .os-cell-muted {
    color: var(--gray-500);
    font-size: 12px;
  }

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

  .os-list-foot-info {
    font-size: 12px;
    color: var(--gray-500);
  }

  .os-pag {
    display: flex;
    gap: 4px;
  }

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

  .os-pag-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
  }

  .os-pag-btn:not(:disabled):hover {
    border-color: var(--gray-400);
    color: var(--gray-950);
  }

  .os-empty,
  .os-loading {
    padding: 32px 16px;
    text-align: center;
    font-size: 13px;
    color: var(--gray-400);
  }

  .os-loading i {
    animation: os-spin 0.8s linear infinite;
    display: inline-block;
  }

  @keyframes os-spin {
    to { transform: rotate(360deg); }
  }

  .detail-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
  }

  .detail-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .detail-field.span-2 { grid-column: span 2; }
  .detail-field.span-3 { grid-column: span 3; }

  .detail-label {
    font-size: 11px;
    color: var(--gray-400);
  }

  .detail-value {
    font-size: 13px;
    color: var(--gray-950);
    line-height: 1.45;
    word-break: break-word;
  }

  [data-theme="dark"] .os-panel {
    background: #161b22;
    border-color: #30363d;
  }

  [data-theme="dark"] .os-kpi-panel {
    background: #161b22;
    border-color: #30363d;
  }

  [data-theme="dark"] .os-kpi-card {
    border-color: #4b5563;
    background: rgba(13, 17, 23, 0.55);
  }

  [data-theme="dark"] .os-kpi-label {
    color: #8b949e;
  }

  [data-theme="dark"] .os-kpi-card--tecnicos .os-kpi-value {
    color: #e6edf3;
  }

  [data-theme="dark"] .os-panel-head { border-bottom-color: #21262d; }
  [data-theme="dark"] .os-panel-title { color: #e6edf3; }
  [data-theme="dark"] .os-panel-meta { color: #8b949e; }
  [data-theme="dark"] .os-toggle-btn.is-active {
    background: #161b22;
    color: #e6edf3;
  }
  [data-theme="dark"] .os-heatmap-controls {
    background: #0d1117;
    border-color: #30363d;
  }
  [data-theme="dark"] .os-toolbar-search,
  [data-theme="dark"] .os-toolbar-select,
  [data-theme="dark"] .os-toolbar-date,
  [data-theme="dark"] .os-toolbar-btn,
  [data-theme="dark"] .os-pag-btn {
    background: #0d1117;
    border-color: #30363d;
    color: #e6edf3;
  }
  [data-theme="dark"] .os-toolbar-btn--ghost {
    background: transparent;
    color: #8b949e;
  }
  [data-theme="dark"] .os-toolbar-btn--ghost:hover {
    background: #21262d;
    border-color: #30363d;
    color: #e6edf3;
  }
  [data-theme="dark"] .os-toolbar-btn--primary {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #fff;
  }
  [data-theme="dark"] .os-toolbar-btn--primary:hover {
    background: #2563eb;
    border-color: #2563eb;
  }
  [data-theme="dark"] .os-tech-row:hover,
  [data-theme="dark"] .os-mini-item--clickable:hover,
  [data-theme="dark"] .os-table tbody tr:hover {
    background: #21262d;
  }
  [data-theme="dark"] .os-tech-row + .os-tech-row,
  [data-theme="dark"] .os-mini-item + .os-mini-item,
  [data-theme="dark"] .os-table td,
  [data-theme="dark"] .os-table th,
  [data-theme="dark"] .os-list-foot {
    border-color: #21262d;
  }
  [data-theme="dark"] .os-tech-name,
  [data-theme="dark"] .os-tech-total,
  [data-theme="dark"] .os-mini-count,
  [data-theme="dark"] .os-cell-main,
  [data-theme="dark"] .detail-value,
  [data-theme="dark"] .os-donut-total,
  [data-theme="dark"] .os-heatmap-week-label,
  [data-theme="dark"] .os-heatmap-month strong {
    color: #e6edf3;
  }
  [data-theme="dark"] .os-mini-label,
  [data-theme="dark"] .status-dot,
  [data-theme="dark"] .os-donut-legend-item,
  [data-theme="dark"] .os-cell-muted {
    color: #c9d1d9;
  }
  [data-theme="dark"] .os-donut-chart::after {
    background: #161b22;
    border-color: #30363d;
  }
  [data-theme="dark"] .os-tech-bar { background: transparent; }
  [data-theme="dark"] .os-heatmap-cell--nodata {
    background: transparent;
    box-shadow: inset 0 0 0 1px #30363d;
    color: #cbd5e1;
  }

  @media (max-width: 1180px) {
    .os-analytics-grid {
      grid-template-columns: 1fr;
    }

    .os-summary-grid {
      grid-template-columns: 1fr;
      gap: 10px;
    }
  }

  @media (max-width: 960px) {
    .os-page {
      gap: 24px;
    }

    .os-filters-fields,
    .os-filtros-extra {
      grid-template-columns: 1fr;
    }

    .os-filter-group--search {
      grid-column: auto;
    }

    .os-filter-actions {
      flex-wrap: wrap;
    }

    .os-filter-actions .os-toolbar-btn {
      flex: 1 1 calc(50% - 5px);
    }

    .os-heatmap-week {
      grid-template-columns: 110px repeat(7, minmax(20px, 26px));
    }
  }

  @media (max-width: 700px) {
    .os-page {
      gap: 20px;
    }

    .os-panel-head,
    .os-heatmap-body,
    .os-donut-body,
    .os-kpi-grid,
    .os-filters-panel {
      padding-left: 12px;
      padding-right: 12px;
    }

    .detail-grid { grid-template-columns: 1fr 1fr; }
    .detail-field.span-2,
    .detail-field.span-3 { grid-column: span 2; }

    .os-tech-row {
      grid-template-columns: 1fr;
      gap: 10px;
    }

    .os-tech-total {
      text-align: left;
    }

    #heatmap-content {
      justify-content: flex-start;
      overflow-x: auto;
      padding-bottom: 4px;
    }

    .os-heatmap-grid {
      grid-template-columns: repeat(7, 14px);
      gap: 4px;
      min-width: max-content;
    }

    .os-heatmap-cell,
    .os-heatmap-cell-empty {
      width: 14px;
      height: 14px;
      min-height: 14px;
    }

    .os-heatmap-week {
      grid-template-columns: 84px repeat(7, 14px);
      gap: 4px;
      min-width: max-content;
      width: max-content;
      margin: 0;
    }

    .os-heatmap-week-head {
      display: block;
      font-size: 8px;
    }

    .os-heatmap-week-label {
      font-size: 10px;
    }

    .os-heatmap-week-row {
      grid-column: 2 / span 7;
      display: grid;
      grid-template-columns: repeat(7, 14px);
      gap: 4px;
    }

    .os-filter-actions .os-toolbar-btn {
      flex: 1 1 100%;
    }
  }

  @media (max-width: 480px) {
    .os-page {
      gap: 18px;
    }

    .os-kpi-grid {
      grid-template-columns: 1fr;
    }

    .os-analytics-grid,
    .os-summary-grid {
      grid-template-columns: 1fr;
      gap: 14px;
    }

    .os-filters-panel {
      gap: 14px;
    }

    .os-filters-fields,
    .os-filtros-extra {
      gap: 12px;
    }

    .detail-grid { grid-template-columns: 1fr; }
    .detail-field.span-2,
    .detail-field.span-3 { grid-column: span 1; }
  }
</style>
@endsection

@section('content')
<div class="os-page">
  <section class="os-analytics-grid">
    <div class="os-panel os-heatmap-panel">
      <div class="os-panel-head">
        <div>
          <div class="os-panel-title">Atividade</div>
          <div class="os-panel-meta" id="heatmap-subtitle">Eficiência diária das ordens de serviço</div>
        </div>
        <div class="os-heatmap-controls">
          <button type="button" class="os-toggle-btn is-active" data-heatmap-tipo="mensal">mês</button>
          <button type="button" class="os-toggle-btn" data-heatmap-tipo="semanal">semana</button>
        </div>
      </div>
      <div class="os-heatmap-body">
        <div class="os-heatmap-month" id="heatmap-month-label">Mês atual</div>
        <div id="heatmap-content">
          <div class="os-loading">Carregando atividade…</div>
        </div>
        <div class="os-heatmap-legend">
          <span><i style="background:#22c55e"></i>91–100</span>
          <span><i style="background:#84cc16"></i>71–90</span>
          <span><i style="background:#eab308"></i>51–70</span>
          <span><i style="background:#f97316"></i>31–50</span>
          <span><i style="background:#ef4444"></i>0–30</span>
          <span><i style="background:#1f2937"></i>Hoje / futuro</span>
        </div>
      </div>
    </div>

    <div class="os-panel os-donut-panel">
      <div class="os-panel-head">
        <div class="os-panel-title">Prioridade</div>
        <div class="os-panel-meta">Abertas + em andamento</div>
      </div>
      <div class="os-donut-body" id="donut-wrap">
        <div class="os-loading">Carregando prioridades…</div>
      </div>
    </div>

    <div class="os-panel os-kpi-panel">
      <div class="os-panel-head">
        <div class="os-panel-title">Panorama de OS</div>
        <div class="os-panel-meta">Resumo operacional</div>
      </div>
      <div class="os-kpi-grid">
        <div class="os-kpi-card os-kpi-card--aberta">
          <div class="os-kpi-value" id="metric-aberta">—</div>
          <div class="os-kpi-label">abertas</div>
        </div>
        <div class="os-kpi-card os-kpi-card--andamento">
          <div class="os-kpi-value" id="metric-andamento">—</div>
          <div class="os-kpi-label">em andamento</div>
        </div>
        <div class="os-kpi-card os-kpi-card--tecnicos">
          <div class="os-kpi-value" id="metric-tecnicos">—</div>
          <div class="os-kpi-label">técnicos</div>
        </div>
        <div class="os-kpi-card os-kpi-card--finalizada">
          <div class="os-kpi-value" id="metric-finalizada">—</div>
          <div class="os-kpi-label">finalizadas</div>
        </div>
      </div>
    </div>
  </section>

  <div class="os-panel os-filters-panel">
    <div class="os-filters-fields">
      <div class="os-filter-group os-filter-group--search">
        <label class="os-filter-label" for="filtro-busca">Buscar</label>
        <input type="text" id="filtro-busca" class="os-toolbar-search" placeholder="OS, código, técnico...." oninput="aplicarFiltrosDebounce()" />
      </div>

      <div class="os-filter-group">
        <label class="os-filter-label" for="filtro-data-inicio">Início</label>
        <input type="date" id="filtro-data-inicio" class="os-toolbar-date" title="Data início" />
      </div>

      <div class="os-filter-group">
        <label class="os-filter-label" for="filtro-data-fim">Fim</label>
        <input type="date" id="filtro-data-fim" class="os-toolbar-date" title="Data fim" />
      </div>

      <div class="os-filter-group">
        <label class="os-filter-label" for="filtro-tecnico">Técnicos</label>
        <select id="filtro-tecnico" class="os-toolbar-select">
          <option value="">Todos os técnicos</option>
        </select>
      </div>

      <div class="os-filter-group">
        <label class="os-filter-label" for="filtro-tipo-data">Filtrar por data</label>
        <select id="filtro-tipo-data" class="os-toolbar-select">
          <option value="criacao">Data criação</option>
          <option value="conclusao">Data conclusão</option>
        </select>
      </div>
    </div>

    <div class="os-filter-actions">
      <button type="button" class="os-toolbar-btn os-toolbar-btn--primary" onclick="aplicarFiltros()">Filtrar</button>
      <button type="button" class="os-toolbar-btn" onclick="toggleFiltrosExtra()"><span id="btn-filtros-extra-label">Mais filtros</span></button>
      <button type="button" class="os-toolbar-btn os-toolbar-btn--ghost" onclick="limparFiltros()">Limpar</button>
      <button type="button" class="os-toolbar-btn os-toolbar-btn--export" id="btn-exportar-planilha" onclick="exportarPlanilha()" title="Exportar planilha com os filtros atuais">
        <i class="ti ti-download" style="font-size:14px;"></i> Exportar
      </button>
    </div>

    <div class="os-filtros-extra" id="filtros-extra">
      <select id="filtro-regiao" class="os-toolbar-select">
        <option value="">Todas as regiões</option>
        <option>Goval</option>
        <option>Vale do Aço</option>
        <option>Caratinga</option>
        <option>Teste</option>
      </select>
      <select id="filtro-status" class="os-toolbar-select">
        <option value="">Todos os status</option>
        <option value="Aberta">Aberta</option>
        <option value="Em andamento">Em andamento</option>
        <option value="Finalizada">Finalizada</option>
      </select>
      <select id="filtro-categoria-pai" class="os-toolbar-select">
        <option value="">Todas as origens</option>
        <option value="rompimentos">Rompimentos</option>
        <option value="troca-poste">Troca de poste</option>
        <option value="troca-etiqueta">Troca de etiqueta</option>
        <option value="otimizacao-rede">Otimização de rede</option>
        <option value="atendimento-cliente">Atendimento</option>
      </select>
      <select id="filtro-prioridade" class="os-toolbar-select">
        <option value="">Todas as prioridades</option>
        <option>Alta</option>
        <option>Média</option>
        <option>Baixa</option>
      </select>
    </div>

    <div id="filtro-periodo-ativo" class="os-periodo-hint"></div>
  </div>

  <section class="os-summary-grid">
    <div class="os-panel os-summary-panel">
      <div class="os-panel-head">
        <div class="os-panel-title">Por técnico</div>
        <div class="os-panel-meta" id="total-tecnicos-label">—</div>
      </div>
      <div id="tabela-tecnicos-wrap">
        <div class="os-loading">Carregando…</div>
      </div>
    </div>

    <div class="os-panel os-summary-panel">
      <div class="os-panel-head">
        <div class="os-panel-title">Origem</div>
        <div class="os-panel-meta">Totais</div>
      </div>
      <div class="os-mini-list" id="lista-categorias">
        <div class="os-loading">…</div>
      </div>
    </div>

    <div class="os-panel os-summary-panel">
      <div class="os-panel-head">
        <div class="os-panel-title">Região</div>
        <div class="os-panel-meta">Totais</div>
      </div>
      <div class="os-mini-list" id="lista-regioes">
        <div class="os-loading">…</div>
      </div>
    </div>
  </section>

  <div class="os-panel">
    <div class="os-panel-head">
      <div class="os-panel-title">Ordens de serviço</div>
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
    <button onclick="fecharDetalhe()" class="btn-modal btn-modal-ghost">Fechar</button>
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
  const HEATMAP_MONTH = new Date().toISOString().slice(0, 7);
  const DIA_SIGLAS = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
  const DIA_ROTULOS = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

  let offsetAtual = 0;
  let totalLista = 0;
  let debounceTimer = null;
  let filtrosExtraAbertos = false;
  let heatmapTipoAtual = 'mensal';

  function esc(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function criarDataLocal(iso) {
    if (!iso) return null;
    const [ano, mes, dia] = String(iso).slice(0, 10).split('-').map(Number);
    if (!ano || !mes || !dia) return null;
    return new Date(ano, mes - 1, dia);
  }

  function hojeIso() {
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
  }

  function formatarData(valor) {
    if (!valor) return '—';
    const d = criarDataLocal(valor);
    if (!d) return esc(String(valor).slice(0, 10));
    return d.toLocaleDateString('pt-BR');
  }

  function formatarMesAno(valor) {
    const d = criarDataLocal(`${valor}-01`);
    if (!d) return valor;
    return d.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
  }

  function statusDot(status) {
    const cls = status === 'Finalizada'
      ? 'status-dot--finalizada'
      : status === 'Em andamento'
        ? 'status-dot--andamento'
        : 'status-dot--aberta';

    return `<span class="status-dot ${cls}">${esc(status)}</span>`;
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

  function validarPeriodoFiltros(filtros) {
    if (filtros.dataInicio && filtros.dataFim && filtros.dataInicio > filtros.dataFim) {
      document.getElementById('filtro-data-fim').value = filtros.dataInicio;
      filtros.dataFim = filtros.dataInicio;
    }
    return filtros;
  }

  function obterFiltrosParaApi() {
    return validarPeriodoFiltros(obterFiltros());
  }

  function filtrosParaQuery(filtros) {
    const params = new URLSearchParams();
    const ativos = { ...filtros };

    if (!ativos.dataInicio && !ativos.dataFim) {
      delete ativos.tipoData;
    }

    Object.entries(ativos).forEach(([chave, valor]) => {
      if (valor != null && String(valor).trim() !== '') {
        params.set(chave, valor);
      }
    });

    return params.toString();
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
  }

  function percentual(parte, total) {
    if (!total) return 0;
    return (parte / total) * 100;
  }

  function corHeatmapPorEficiencia(eficiencia) {
    if (eficiencia >= 91) return 'os-heatmap-cell--success';
    if (eficiencia >= 71) return 'os-heatmap-cell--good';
    if (eficiencia >= 51) return 'os-heatmap-cell--alert';
    if (eficiencia >= 31) return 'os-heatmap-cell--critical';
    return 'os-heatmap-cell--danger';
  }

  function obterEstadoCelulaHeatmap(dataIso, info) {
    const hoje = hojeIso();

    if (!dataIso) {
      return {
        classe: 'os-heatmap-cell-empty',
        dia: '',
        valor: '',
        title: '',
      };
    }

    const dia = Number(dataIso.slice(-2));

    if (dataIso >= hoje) {
      return {
        classe: 'os-heatmap-cell os-heatmap-cell--future',
        dia,
        valor: '—',
        title: `${formatarData(dataIso)} · sem dado`,
      };
    }

    if (!info || !info.total) {
      return {
        classe: 'os-heatmap-cell os-heatmap-cell--nodata',
        dia,
        valor: '0',
        title: `${formatarData(dataIso)} · sem registros`,
      };
    }

    return {
      classe: `os-heatmap-cell ${corHeatmapPorEficiencia(info.eficiencia || 0)}`,
      dia,
      valor: `${Math.round(info.eficiencia || 0)}`,
      title: `${formatarData(dataIso)} · ${info.finalizadas} finalizadas, ${info.em_andamento} em andamento, ${info.abertas} abertas · eficiência ${Number(info.eficiencia || 0).toFixed(1)}`,
    };
  }

  function renderHeatmapMensal(payload) {
    const diasPorData = Object.fromEntries((payload.dias || []).map(item => [item.dia, item]));
    const dataBase = criarDataLocal(`${payload.mes}-01`);
    const ano = dataBase.getFullYear();
    const mes = dataBase.getMonth();
    const totalDias = new Date(ano, mes + 1, 0).getDate();
    const primeiroDiaSemana = new Date(ano, mes, 1).getDay();

    const cabecalho = DIA_ROTULOS.map(dia => `<div class="os-heatmap-header">${dia}</div>`).join('');
    const celulas = [];

    for (let i = 0; i < primeiroDiaSemana; i += 1) {
      celulas.push('<div class="os-heatmap-cell-empty"></div>');
    }

    for (let dia = 1; dia <= totalDias; dia += 1) {
      const iso = `${payload.mes}-${String(dia).padStart(2, '0')}`;
      const estado = obterEstadoCelulaHeatmap(iso, diasPorData[iso]);
      celulas.push(`
        <div class="${estado.classe}" title="${esc(estado.title)}">
          <span class="os-heatmap-day">${estado.dia}</span>
          <span class="os-heatmap-score">${estado.valor}</span>
        </div>
      `);
    }

    while (celulas.length % 7 !== 0) {
      celulas.push('<div class="os-heatmap-cell-empty"></div>');
    }

    return `<div class="os-heatmap-grid">${cabecalho}${celulas.join('')}</div>`;
  }

  function obterSemanaReferencia(mesIso) {
    const agora = new Date();
    const mesAtual = `${agora.getFullYear()}-${String(agora.getMonth() + 1).padStart(2, '0')}`;
    const referencia = mesIso === mesAtual
      ? new Date(agora.getFullYear(), agora.getMonth(), agora.getDate())
      : criarDataLocal(`${mesIso}-01`);

    const inicio = new Date(referencia);
    inicio.setDate(referencia.getDate() - referencia.getDay());
    return inicio;
  }

  function adicionarDias(data, quantidade) {
    const d = new Date(data);
    d.setDate(d.getDate() + quantidade);
    return d;
  }

  function dataParaIso(data) {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
  }

  function renderHeatmapSemanal(payload) {
    const inicioSemana = obterSemanaReferencia(payload.mes);
    const headers = DIA_SIGLAS.map(sigla => `<div class="os-heatmap-week-head">${sigla}</div>`).join('');
    const regioes = Array.isArray(payload.regioes) ? payload.regioes : [];

    if (!regioes.length) {
      return '<div class="os-empty">Nenhum registro na semana atual.</div>';
    }

    return `
      <div class="os-heatmap-week">
        <div></div>
        ${headers}
        ${regioes.map(regiao => {
          const mapaDias = Object.fromEntries((regiao.dias || []).map(item => [item.dia, item]));
          const blocos = Array.from({ length: 7 }, (_, indice) => {
            const data = adicionarDias(inicioSemana, indice);
            const iso = dataParaIso(data);
            const estado = obterEstadoCelulaHeatmap(iso, mapaDias[iso]);
            return `
              <div class="${estado.classe}" title="${esc(`${regiao.regiao} · ${estado.title}`)}">
                <span class="os-heatmap-day">${DIA_SIGLAS[indice]}</span>
                <span class="os-heatmap-score">${estado.valor}</span>
              </div>
            `;
          }).join('');

          return `
            <div class="os-heatmap-week-label" title="${esc(regiao.regiao)}">${esc(regiao.regiao)}</div>
            <div class="os-heatmap-week-row">${blocos}</div>
          `;
        }).join('')}
      </div>
    `;
  }

  function atualizarHeatmap(payload) {
    const label = document.getElementById('heatmap-month-label');
    const wrap = document.getElementById('heatmap-content');

    label.innerHTML = `Visão de <strong>${formatarMesAno(payload.mes)}</strong>`;
    wrap.innerHTML = payload.tipo === 'semanal'
      ? renderHeatmapSemanal(payload)
      : renderHeatmapMensal(payload);
  }

  function renderDonut(porPrioridade) {
    const wrap = document.getElementById('donut-wrap');
    const prioridades = {
      Alta: { total: 0, cor: '#ef4444' },
      'Média': { total: 0, cor: '#f97316' },
      Baixa: { total: 0, cor: '#22c55e' },
    };

    (porPrioridade || []).forEach(item => {
      if (prioridades[item.prioridade]) {
        prioridades[item.prioridade].total = Number(item.total || 0);
      }
    });

    const total = Object.values(prioridades).reduce((sum, item) => sum + item.total, 0);

    if (!total) {
      wrap.innerHTML = '<div class="os-donut-empty">Nenhuma OS aberta ou em andamento no período.</div>';
      return;
    }

    let acumulado = 0;
    const gradiente = Object.entries(prioridades).map(([, item]) => {
      const inicio = acumulado;
      acumulado += (item.total / total) * 360;
      return `${item.cor} ${inicio}deg ${acumulado}deg`;
    }).join(', ');

    wrap.innerHTML = `
      <div class="os-donut-chart" style="background: conic-gradient(${gradiente});">
        <div class="os-donut-center">
          <div class="os-donut-total">${total}</div>
          <div class="os-donut-caption">ativas</div>
        </div>
      </div>
      <div class="os-donut-legend">
        ${Object.entries(prioridades).map(([label, item]) => `
          <div class="os-donut-legend-item">
            <span class="os-donut-legend-dot" style="background:${item.cor}"></span>
            <span>${esc(label)} <strong>${item.total}</strong></span>
          </div>
        `).join('')}
      </div>
    `;
  }

  function renderTabelaTecnicos(porTecnico) {
    const wrap = document.getElementById('tabela-tecnicos-wrap');

    if (!porTecnico.length) {
      wrap.innerHTML = '<div class="os-empty">Nenhuma OS com os filtros atuais</div>';
      return;
    }

    wrap.innerHTML = `
      <div class="os-tech-list">
        <div class="os-tech-row" style="cursor:default;">
          <div class="os-tech-legend">
            <span><i style="background:#3b82f6"></i>Ab</span>
            <span><i style="background:#eab308"></i>And</span>
            <span><i style="background:#22c55e"></i>Fin</span>
          </div>
          <div></div>
          <div></div>
        </div>
        ${porTecnico.map(row => `
          <div class="os-tech-row row-tecnico-filter" data-tecnico="${esc(row.tecnico)}">
            <div>
              <div class="os-tech-name">${esc(row.tecnico)}</div>
              ${row.regiao ? `<div class="os-tech-reg">${esc(row.regiao)}</div>` : ''}
            </div>
            <div class="os-tech-bar-wrap">
              <div class="os-tech-bar">
                <span class="os-tech-bar-segment--aberta" style="width:${percentual(row.aberta, row.total)}%"></span>
                <span class="os-tech-bar-segment--andamento" style="width:${percentual(row.em_andamento, row.total)}%"></span>
                <span class="os-tech-bar-segment--finalizada" style="width:${percentual(row.finalizada, row.total)}%"></span>
              </div>
            </div>
            <div class="os-tech-total">${row.total}</div>
          </div>
        `).join('')}
      </div>
    `;
  }

  function renderListaRegioes(porRegiao) {
    const el = document.getElementById('lista-regioes');

    if (!porRegiao.length) {
      el.innerHTML = '<div class="os-empty">—</div>';
      return;
    }

    el.innerHTML = porRegiao.map(r => `
      <div class="os-mini-item os-mini-item--clickable row-regiao-filter" data-regiao="${esc(r.regiao)}">
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
            <tr data-id="${os.id}" onclick="abrirDetalhe(${os.id})">
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
      </table>
    `;
  }

  function atualizarMetricas(totais) {
    document.getElementById('metric-aberta').textContent = totais.aberta;
    document.getElementById('metric-andamento').textContent = totais.em_andamento;
    document.getElementById('metric-finalizada').textContent = totais.finalizada;
    document.getElementById('metric-tecnicos').textContent = totais.tecnicos;
    document.getElementById('total-tecnicos-label').textContent = `${totais.tecnicos} técnicos`;
  }

  function atualizarPaginacao() {
    const inicio = totalLista === 0 ? 0 : offsetAtual + 1;
    const fim = Math.min(offsetAtual + PAGE_SIZE, totalLista);

    document.getElementById('lista-total').textContent = totalLista;
    document.getElementById('lista-paginacao-info').textContent = totalLista
      ? `${inicio}–${fim} de ${totalLista}`
      : 'Nenhum registro';
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

  async function carregarHeatmap() {
    const payload = await getUrl(`ordem-servico/heatmap?tipo=${heatmapTipoAtual}&mes=${HEATMAP_MONTH}`);
    atualizarHeatmap(payload);
  }

  async function carregarOrdemServicoDashboard(resetPagina = true) {
    const gen = window.plannerBeginReload?.() ?? 0;
    if (resetPagina) offsetAtual = 0;

    const filtros = obterFiltrosParaApi();
    const qs = filtrosParaQuery(filtros);
    atualizarIndicadorPeriodo(filtros);

    try {
      const [dashboard, lista, heatmap] = await Promise.all([
        getUrl('ordem-servico/dashboard' + (qs ? '?' + qs : '')),
        getUrl(`ordem-servico?limit=${PAGE_SIZE}&offset=${offsetAtual}` + (qs ? '&' + qs : '')),
        getUrl(`ordem-servico/heatmap?tipo=${heatmapTipoAtual}&mes=${HEATMAP_MONTH}`),
      ]);

      if (window.plannerIsReloadCurrent && !window.plannerIsReloadCurrent(gen)) return;

      atualizarMetricas(dashboard.totais);
      renderDonut(dashboard.por_prioridade || []);
      atualizarHeatmap(heatmap);
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
      document.getElementById('heatmap-content').innerHTML =
        '<div class="os-empty" style="color:#dc2626;">Não foi possível carregar o heatmap.</div>';
      document.getElementById('donut-wrap').innerHTML =
        '<div class="os-empty" style="color:#dc2626;">Não foi possível carregar o gráfico.</div>';
    }
  }

  window.carregarOrdemServicoDashboard = carregarOrdemServicoDashboard;
  window.abrirNovoItem = function () {
    carregarOrdemServicoDashboard(true);
  };

  window.toggleFiltrosExtra = function() {
    filtrosExtraAbertos = !filtrosExtraAbertos;
    document.getElementById('filtros-extra').classList.toggle('open', filtrosExtraAbertos);
    document.getElementById('btn-filtros-extra-label').textContent = filtrosExtraAbertos
      ? 'Menos filtros'
      : 'Mais filtros';
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
    const regiao = document.getElementById('filtro-regiao').value;
    carregarTecnicosSelect(regiao).then(() => carregarOrdemServicoDashboard(true));
  };

  window.aplicarFiltrosDebounce = function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => carregarOrdemServicoDashboard(true), 400);
  };

  window.limparFiltros = function() {
    ['filtro-busca', 'filtro-regiao', 'filtro-tecnico', 'filtro-status', 'filtro-categoria-pai', 'filtro-prioridade', 'filtro-data-inicio', 'filtro-data-fim']
      .forEach(id => {
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

  window.fecharDetalhe = function() {
    document.getElementById('detalhe-overlay').classList.remove('open');
  };

  window.abrirDetalhe = async function(id) {
    const overlay = document.getElementById('detalhe-overlay');
    const conteudo = document.getElementById('detalhe-conteudo');
    document.getElementById('detalhe-titulo').textContent = 'Ordem de serviço';
    document.getElementById('detalhe-subtitulo').textContent = 'Carregando…';
    conteudo.innerHTML = '<div class="os-loading">Carregando…</div>';
    overlay.classList.add('open');

    try {
      const resp = await getUrl('ordem-servico/' + id);
      const os = resp.os;
      document.getElementById('detalhe-subtitulo').textContent = os.numero_os || os.taskCode || ('#' + os.id);
      conteudo.innerHTML = `
        <div class="detail-grid">
          <div class="detail-field"><span class="detail-label">Técnico</span><div class="detail-value">${esc(os.tecnico)}</div></div>
          <div class="detail-field"><span class="detail-label">Status</span><div class="detail-value">${statusDot(os.status)}</div></div>
          <div class="detail-field"><span class="detail-label">Região</span><div class="detail-value">${esc(os.regiao || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Número OS</span><div class="detail-value">${esc(os.numero_os || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Código</span><div class="detail-value">${esc(os.taskCode || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Prioridade</span><div class="detail-value">${esc(os.prioridade)}</div></div>
          <div class="detail-field span-3"><span class="detail-label">Título</span><div class="detail-value">${esc(os.titulo || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Origem</span><div class="detail-value">${esc(os.categoria_pai_label)}${os.task_code_pai ? ' · ' + esc(os.task_code_pai) : ''}</div></div>
          <div class="detail-field"><span class="detail-label">Protocolo</span><div class="detail-value">${esc(os.protocolo || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Cliente</span><div class="detail-value">${esc(os.nome_cliente || '—')}</div></div>
          <div class="detail-field span-2"><span class="detail-label">Localização</span><div class="detail-value">${esc(os.localizacao_texto || '—')}</div></div>
          <div class="detail-field"><span class="detail-label">Criada em</span><div class="detail-value">${formatarData(os.data_criacao || os.criadaEm)}</div></div>
          <div class="detail-field"><span class="detail-label">Concluída em</span><div class="detail-value">${formatarData(os.data_conclusao) || '—'}</div></div>
          <div class="detail-field span-3"><span class="detail-label">Descrição</span><div class="detail-value">${esc(os.descricao || '—')}</div></div>
        </div>`;
    } catch (e) {
      conteudo.innerHTML = '<div class="os-empty" style="color:#dc2626;">Não foi possível carregar.</div>';
    }
  };

  document.getElementById('filtro-regiao').addEventListener('change', () => {
    carregarTecnicosSelect(document.getElementById('filtro-regiao').value);
  });

  document.getElementById('filtro-data-inicio').addEventListener('change', () => {
    atualizarIndicadorPeriodo(obterFiltrosParaApi());
  });

  document.getElementById('filtro-data-fim').addEventListener('change', () => {
    atualizarIndicadorPeriodo(obterFiltrosParaApi());
  });

  document.getElementById('filtro-tipo-data').addEventListener('change', () => {
    atualizarIndicadorPeriodo(obterFiltrosParaApi());
  });

  document.querySelectorAll('[data-heatmap-tipo]').forEach(button => {
    button.addEventListener('click', async () => {
      const novoTipo = button.dataset.heatmapTipo;
      if (!novoTipo || novoTipo === heatmapTipoAtual) return;

      heatmapTipoAtual = novoTipo;
      document.querySelectorAll('[data-heatmap-tipo]').forEach(btn => {
        btn.classList.toggle('is-active', btn.dataset.heatmapTipo === heatmapTipoAtual);
      });

      document.getElementById('heatmap-content').innerHTML = '<div class="os-loading">Carregando atividade…</div>';

      try {
        await carregarHeatmap();
      } catch (error) {
        console.error(error);
        document.getElementById('heatmap-content').innerHTML =
          '<div class="os-empty" style="color:#dc2626;">Não foi possível carregar o heatmap.</div>';
      }
    });
  });

  document.getElementById('tabela-tecnicos-wrap').addEventListener('click', (e) => {
    const row = e.target.closest('.row-tecnico-filter');
    if (row?.dataset.tecnico) filtrarPorTecnico(row.dataset.tecnico);
  });

  document.getElementById('lista-regioes').addEventListener('click', (e) => {
    const row = e.target.closest('.row-regiao-filter');
    if (row?.dataset.regiao && row.dataset.regiao !== 'Sem região') filtrarPorRegiao(row.dataset.regiao);
  });

  carregarTecnicosSelect('').then(() => carregarOrdemServicoDashboard(true));
</script>
@endsection
