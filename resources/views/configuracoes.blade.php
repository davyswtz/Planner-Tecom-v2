@extends('layouts.app')

@section('title', 'Configurações — Planner Telecom')
@section('page-title', 'Configurações')
@section('hide-topbar-btn', true)

@section('styles')
<style>
  .config-page { width: 100%; max-width: 100%; }
  .config-section + .config-section { margin-top: 16px; }
  .config-table-wrap { overflow-x: auto; }
  .config-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .config-table th {
    text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.04em; color: var(--gray-500);
    border-bottom: 1px solid var(--gray-200); white-space: nowrap;
  }
  .config-table td {
    padding: 12px; border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800); vertical-align: middle;
  }
  .config-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px; color: var(--gray-600); background: var(--gray-50);
    padding: 4px 8px; border-radius: var(--radius-sm); border: 1px solid var(--gray-200);
    word-break: break-all;
  }
  .config-status {
    display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500;
  }
  .config-status-dot { width: 8px; height: 8px; border-radius: 50%; }
  .config-status--ativo { color: #15803d; }
  .config-status--ativo .config-status-dot { background: #22c55e; }
  .config-status--inativo { color: var(--gray-500); }
  .config-status--inativo .config-status-dot { background: var(--gray-400); }
  .config-actions { display: flex; justify-content: flex-end; gap: 8px; white-space: nowrap; }
  .config-btn {
    height: 32px; padding: 0 12px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); background: var(--white); color: var(--gray-700);
    font: inherit; font-size: 12px; cursor: pointer; display: inline-flex;
    align-items: center; gap: 6px;
  }
  .config-btn:hover:not(:disabled) { border-color: var(--blue-600); color: var(--blue-600); }
  .config-btn:disabled { opacity: 0.6; cursor: not-allowed; }
  .config-btn--test.loading i { animation: spin 0.9s linear infinite; }
  .config-feedback {
    margin-top: 6px; font-size: 12px; line-height: 1.4;
  }
  .config-feedback.ok { color: #15803d; }
  .config-feedback.err { color: #dc2626; }
  .config-empty, .config-loading, .config-erro {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 42px 16px; color: var(--gray-500); font-size: 13px;
  }
  .config-loading i { animation: spin 0.9s linear infinite; }
  .config-help {
    padding: 12px 16px; border-top: 1px solid var(--gray-100);
    font-size: 12px; color: var(--gray-500); line-height: 1.5;
  }
  [data-theme="dark"] .config-code {
    background: #21262d; border-color: #30363d; color: #8b949e;
  }
  [data-theme="dark"] .config-btn {
    background: #21262d; border-color: #30363d; color: #e6edf3;
  }
  [data-theme="dark"] .config-help { border-color: #30363d; color: #8b949e; }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endsection

@section('content')
<div class="config-page">
  <div class="card config-section">
    <div class="card-header">
      <span class="card-title">Webhooks Google Chat</span>
      <span class="card-action">total: <span id="total-webhooks">0</span></span>
    </div>
    <div class="config-table-wrap" id="webhooks-wrap">
      <div class="config-loading"><i class="ti ti-loader-2"></i> Carregando webhooks...</div>
    </div>
    <div class="config-help">
      Ao arrastar um card no kanban, o sistema envia a notificação para o webhook da <strong>região</strong> da tarefa.
      Use <strong>Testar</strong> para enviar uma mensagem de verificação ao Google Chat.
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  if (typeof window.plannerPossuiPermissao === 'function' && !window.plannerPossuiPermissao('adicionar_webhook')) {
    window.location.replace('/dashboard');
  }
</script>
<script type="module">
  function token() {
    return localStorage.getItem('planner_token');
  }

  function esc(valor) {
    if (valor == null || valor === '') return '';
    return String(valor)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  async function requestJson(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        'Authorization': 'Bearer ' + token(),
        'Accept': 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(options.headers || {}),
      },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.message || 'Erro na requisição.');
    }
    return data;
  }

  function renderWebhooks(webhooks) {
    const wrap = document.getElementById('webhooks-wrap');
    document.getElementById('total-webhooks').textContent = webhooks.length;

    if (!webhooks.length) {
      wrap.innerHTML = '<div class="config-empty"><i class="ti ti-webhook"></i> Nenhum webhook cadastrado.</div>';
      return;
    }

    wrap.innerHTML = `
      <table class="config-table">
        <thead>
          <tr>
            <th>Região</th>
            <th>Nome</th>
            <th>Webhook</th>
            <th>Status</th>
            <th style="text-align:right">Ações</th>
          </tr>
        </thead>
        <tbody>
          ${webhooks.map(w => `
            <tr>
              <td><code>${esc(w.regiao)}</code></td>
              <td><strong>${esc(w.nome)}</strong></td>
              <td><span class="config-code">${esc(w.url_mascarada || '—')}</span></td>
              <td>
                <span class="config-status ${w.ativo ? 'config-status--ativo' : 'config-status--inativo'}">
                  <span class="config-status-dot"></span>
                  ${w.ativo ? 'Ativo' : 'Inativo'}
                </span>
              </td>
              <td>
                <div class="config-actions">
                  <button type="button" class="config-btn config-btn--test" data-id="${w.id}" ${w.ativo ? '' : 'disabled'} onclick="testarWebhook(${w.id}, this)">
                    <i class="ti ti-send"></i> Testar
                  </button>
                </div>
                <div class="config-feedback" id="feedback-webhook-${w.id}"></div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>`;
  }

  async function carregarWebhooks() {
    const wrap = document.getElementById('webhooks-wrap');
    wrap.innerHTML = '<div class="config-loading"><i class="ti ti-loader-2"></i> Carregando webhooks...</div>';

    try {
      const data = await requestJson('/api/webhook-config');
      renderWebhooks(data.webhooks || []);
    } catch (err) {
      wrap.innerHTML = `<div class="config-erro"><i class="ti ti-alert-circle"></i> ${esc(err.message || 'Erro ao carregar webhooks.')}</div>`;
    }
  }

  window.testarWebhook = async function (id, btn) {
    const feedback = document.getElementById(`feedback-webhook-${id}`);
    if (feedback) {
      feedback.className = 'config-feedback';
      feedback.textContent = '';
    }

    btn.disabled = true;
    btn.classList.add('loading');
    btn.innerHTML = '<i class="ti ti-loader-2"></i> Enviando...';

    try {
      const response = await fetch(`/api/webhook-config/${id}/testar`, {
        method: 'POST',
        headers: {
          'Authorization': 'Bearer ' + token(),
          'Accept': 'application/json',
        },
      });
      const data = await response.json().catch(() => ({}));

      if (feedback) {
        feedback.className = 'config-feedback ' + (response.ok ? 'ok' : 'err');
        feedback.textContent = data.message || (response.ok ? 'Teste enviado.' : 'Falha no teste.');
      }
    } catch {
      if (feedback) {
        feedback.className = 'config-feedback err';
        feedback.textContent = 'Erro de conexão ao testar webhook.';
      }
    } finally {
      btn.disabled = false;
      btn.classList.remove('loading');
      btn.innerHTML = '<i class="ti ti-send"></i> Testar';
    }
  };

  carregarWebhooks();
</script>
@endsection
