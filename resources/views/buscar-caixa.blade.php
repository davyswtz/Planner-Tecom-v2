@extends('layouts.app')

@section('title', 'Buscar caixa — Planner Telecom')
@section('page-title', 'Buscar caixa')
@section('hide-topbar-btn', true)

@section('styles')
<style>
  .caixa-page { width: 100%; max-width: 100%; min-width: 0; }
  .caixa-filtros {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) minmax(220px, 1.4fr) auto;
    gap: 12px;
    padding: 16px;
    align-items: end;
  }
  .caixa-campo { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
  .caixa-label {
    font-size: 11px; font-weight: 600; color: var(--gray-500);
    text-transform: uppercase; letter-spacing: 0.04em;
  }
  .caixa-input, .caixa-select {
    width: 100%; height: 38px; padding: 0 10px;
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    background: var(--white); color: var(--gray-950);
    font: inherit; font-size: 13px; box-sizing: border-box; outline: none;
  }
  .caixa-input:focus, .caixa-select:focus { border-color: var(--blue-600); }
  .caixa-btn {
    height: 38px; padding: 0 16px; border: none; border-radius: var(--radius-sm);
    background: var(--blue-600); color: #fff; font: inherit; font-size: 13px;
    font-weight: 600; cursor: pointer; display: inline-flex; align-items: center;
    gap: 8px; white-space: nowrap;
  }
  .caixa-btn:hover:not(:disabled) { background: #1d4ed8; }
  .caixa-btn:disabled { opacity: 0.65; cursor: not-allowed; }
  .caixa-btn i.spin { animation: caixa-spin 0.9s linear infinite; }
  .caixa-help {
    padding: 0 16px 16px; font-size: 12px; color: var(--gray-500); line-height: 1.5;
  }
  .caixa-resumo {
    display: flex; flex-wrap: wrap; gap: 12px; padding: 12px 16px;
    border-top: 1px solid var(--gray-100); font-size: 12px; color: var(--gray-600);
  }
  .caixa-resumo strong { color: var(--gray-900); }
  .caixa-table-wrap { overflow-x: auto; }
  .caixa-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .caixa-table th {
    text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.04em; color: var(--gray-500);
    border-bottom: 1px solid var(--gray-200); white-space: nowrap;
  }
  .caixa-table td {
    padding: 12px; border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800); vertical-align: middle;
  }
  .caixa-table .text-center { text-align: center; }
  .caixa-badge {
    display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px;
    border-radius: 999px; font-size: 11px; font-weight: 600;
  }
  .caixa-badge--ok { background: #dcfce7; color: #15803d; }
  .caixa-badge--off { background: #fee2e2; color: #b91c1c; }
  .caixa-badge--muted { background: var(--gray-100); color: var(--gray-600); }
  .caixa-sinal {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px; font-weight: 600;
  }
  .caixa-sinal--bom { color: #15803d; }
  .caixa-sinal--medio { color: #ca8a04; }
  .caixa-sinal--ruim { color: #dc2626; }
  .caixa-sinal-data { display: block; font-size: 10px; color: var(--gray-500); font-weight: 400; margin-top: 2px; }
  .caixa-sinal-retry {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; margin-top: 2px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); background: var(--white); color: var(--blue-600);
    cursor: pointer; padding: 0;
  }
  .caixa-sinal-retry:hover { border-color: var(--blue-600); background: var(--gray-50); }
  .caixa-sinal-retry:disabled { opacity: 0.6; cursor: not-allowed; }
  .caixa-sinal-retry i.spin { animation: caixa-spin 0.9s linear infinite; }
  .caixa-sinal-off { font-size: 11px; color: var(--gray-500); }
  .caixa-sinal-celula {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 4px; flex-wrap: wrap;
  }
  .caixa-sinal-erro {
    font-size: 15px; color: #dc2626; flex-shrink: 0;
  }
  .caixa-cliente-linha { display: flex; align-items: flex-start; gap: 6px; }
  .caixa-cliente-info { flex: 1; min-width: 0; }
  .caixa-cliente-menu { position: relative; flex-shrink: 0; margin-top: 1px; }
  .caixa-cliente-menu-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border: 1px solid transparent; border-radius: var(--radius-sm);
    background: transparent; color: var(--gray-500); cursor: pointer; padding: 0;
  }
  .caixa-cliente-menu-btn:hover { background: var(--gray-100); color: var(--gray-800); border-color: var(--gray-200); }
  .caixa-cliente-menu-drop {
    position: absolute; top: calc(100% + 4px); right: 0; left: auto; z-index: 20; min-width: 240px;
    background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12); padding: 4px;
  }
  .caixa-cliente-menu-drop button {
    width: 100%; text-align: left; border: none; background: transparent;
    padding: 8px 10px; font: inherit; font-size: 12px; color: var(--gray-800);
    border-radius: 6px; cursor: pointer;
  }
  .caixa-cliente-menu-drop button:hover { background: var(--gray-50); color: var(--blue-600); }
  [data-theme="dark"] .caixa-cliente-menu-drop {
    background: #12151a; border-color: #1e2228;
  }
  [data-theme="dark"] .caixa-cliente-menu-drop button { color: #e6edf3; }
  [data-theme="dark"] .caixa-cliente-menu-drop button:hover { background: #1e2228; }
  .caixa-empty, .caixa-loading, .caixa-erro {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 48px 16px; color: var(--gray-500); font-size: 13px; text-align: center;
  }
  .caixa-loading i { animation: caixa-spin 0.9s linear infinite; }
  .caixa-erro { color: #dc2626; }
  [data-theme="dark"] .caixa-input,
  [data-theme="dark"] .caixa-select {
    background: #12151a; border-color: #1e2228; color: #e6edf3;
  }
  [data-theme="dark"] .caixa-badge--ok { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
  [data-theme="dark"] .caixa-badge--off { background: rgba(239, 68, 68, 0.15); color: #f87171; }
  [data-theme="dark"] .caixa-badge--muted { background: #12151a; color: #8b949e; }
  @keyframes caixa-spin { to { transform: rotate(360deg); } }
  @media (max-width: 768px) {
    .caixa-filtros { grid-template-columns: 1fr; }
  }

  .caixa-beta-modal-body {
    display: flex; flex-direction: column; align-items: center; text-align: center; gap: 14px;
  }
  .caixa-beta-modal-icon {
    width: 64px; height: 64px; border-radius: 50%;
    background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
    color: var(--blue-600); display: flex; align-items: center; justify-content: center;
    font-size: 30px; box-shadow: 0 8px 24px rgba(37, 99, 235, 0.15);
  }
  .caixa-beta-modal-kicker {
    font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    color: #ca8a04;
  }
  .caixa-beta-modal-title {
    font-size: 22px; font-weight: 700; color: var(--gray-950); line-height: 1.2; margin: 0;
  }
  .caixa-beta-modal-feature {
    font-size: 15px; font-weight: 600; color: var(--blue-600); margin: 0;
  }
  .caixa-beta-modal-text {
    font-size: 13px; color: var(--gray-600); line-height: 1.55; margin: 0; max-width: 420px;
  }
  .caixa-beta-modal-obs {
    width: 100%; padding: 12px 14px; border-radius: var(--radius-sm);
    background: #fffbeb; border: 1px solid #fde68a; color: #92400e;
    font-size: 12px; line-height: 1.5; text-align: left;
  }
  .caixa-beta-modal-obs strong { display: block; margin-bottom: 4px; font-size: 12px; }
  [data-theme="dark"] .caixa-beta-modal-icon {
    background: linear-gradient(135deg, #1e3a5f 0%, #1f2937 100%);
    color: #60a5fa;
  }
  [data-theme="dark"] .caixa-beta-modal-obs {
    background: rgba(146, 64, 14, 0.15); border-color: rgba(251, 191, 36, 0.35); color: #fbbf24;
  }
  .caixa-beta-modal-foot {
    justify-content: center;
  }
  .caixa-beta-modal-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 160px;
    height: 40px;
    padding: 0 20px;
    border: none;
    border-radius: var(--radius-sm);
    background: #166ac4;
    color: #fff;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    cursor: pointer;
  }
  .caixa-beta-modal-btn:hover { background: #0d5aaa; }
</style>
@endsection

@section('content')
<div id="modal-buscar-caixa-beta" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="caixa-beta-modal-title">
  <div class="modal-box" style="max-width: 460px;">
    <div class="modal-body caixa-beta-modal-body">
      <div class="caixa-beta-modal-icon" aria-hidden="true">
        <i class="ti ti-box"></i>
      </div>
      <div class="caixa-beta-modal-kicker">Função nova desbloqueada</div>
      <h2 class="caixa-beta-modal-title" id="caixa-beta-modal-title">Buscar caixa</h2>
      <p class="caixa-beta-modal-text">
        Consulte clientes e sinais RX de uma caixa direto do Nicon, sem sair do Planner.
      </p>
      <div class="caixa-beta-modal-obs">
        <strong>Observação — versão beta</strong>
        Esta funcionalidade está em fase de testes. Os resultados podem demorar ou variar conforme a resposta do Nicon.
      </div>
    </div>
    <div class="modal-foot caixa-beta-modal-foot">
      <button type="button" class="caixa-beta-modal-btn" id="btn-fechar-beta-caixa">
        Entendi
      </button>
    </div>
  </div>
</div>

<div class="caixa-page">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Consulta Nicon <span class="caixa-badge caixa-badge--muted" style="margin-left:6px;font-size:10px;vertical-align:middle;">Beta</span></span>
      <span class="card-action" id="caixa-status-label">Informe cidade e caixa</span>
    </div>
    <form class="caixa-filtros" id="form-buscar-caixa">
      <div class="caixa-campo">
        <label class="caixa-label" for="caixa-cidade">Cidade</label>
        <select id="caixa-cidade" class="caixa-select" required>
          <option value="">Selecione...</option>
          @foreach($cidadesNicon as $id => $nome)
            <option value="{{ $id }}">{{ $nome }}</option>
          @endforeach
        </select>
      </div>
      <div class="caixa-campo">
        <label class="caixa-label" for="caixa-nome">Caixa</label>
        <input id="caixa-nome" class="caixa-input" type="text" placeholder="Ex.: p1209, P1209_P12 ou Caixa-P1209_P12" required autocomplete="off">
      </div>
      <button type="submit" class="caixa-btn" id="btn-buscar-caixa">
        <i class="ti ti-search" id="btn-buscar-caixa-icon"></i>
        <span id="btn-buscar-caixa-text">Buscar</span>
      </button>
    </form>
    <div class="caixa-help">
      Busca os clientes da caixa no Nicon e consulta o sinal RX de cada cliente individualmente. Se vier vazio ou 0 dBm, a consulta é repetida automaticamente uma vez; se ainda assim não retornar, use o botão ↻ na linha.
    </div>
    <div class="caixa-resumo" id="caixa-resumo" hidden></div>
    <div class="caixa-table-wrap" id="caixa-resultado-wrap">
      <div class="caixa-empty" id="caixa-empty">Nenhuma busca realizada.</div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="module">
  const token = () => localStorage.getItem('planner_token');

  if (!token()) {
    window.location.replace('/login');
  }

  const modalBeta = document.getElementById('modal-buscar-caixa-beta');
  const btnFecharBeta = document.getElementById('btn-fechar-beta-caixa');

  function abrirModalBeta() {
    if (!modalBeta) return;
    modalBeta.classList.add('open');
  }

  function fecharModalBeta() {
    if (!modalBeta) return;
    modalBeta.classList.remove('open');
  }

  const form = document.getElementById('form-buscar-caixa');
  const btn = document.getElementById('btn-buscar-caixa');
  const btnIcon = document.getElementById('btn-buscar-caixa-icon');
  const btnText = document.getElementById('btn-buscar-caixa-text');
  const wrap = document.getElementById('caixa-resultado-wrap');
  const resumo = document.getElementById('caixa-resumo');
  const statusLabel = document.getElementById('caixa-status-label');
  const cidadeSelect = document.getElementById('caixa-cidade');
  const caixaInput = document.getElementById('caixa-nome');

  const STORAGE_KEY = 'planner_buscar_caixa_estado';
  let restaurandoEstado = false;
  let clientesAtuais = [];
  let caixaAtual = '';

  function detectarRecarregamentoPagina() {
    const nav = performance.getEntriesByType('navigation')[0];
    return nav?.type === 'reload';
  }

  function limparEstadoBusca() {
    sessionStorage.removeItem(STORAGE_KEY);
  }

  function persistirEstadoBusca({ clientes = null, erro = null, sinaisCompletos = true } = {}) {
    if (restaurandoEstado) return;

    const lista = clientes ?? clientesAtuais;
    const idCidade = cidadeSelect?.value || '';
    const nomeCaixa = caixaAtual || caixaInput?.value?.trim() || '';

    if (!idCidade && !nomeCaixa && !lista.length && !erro) {
      limparEstadoBusca();
      return;
    }

    sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
      idCidade,
      nomeCaixa,
      clientes: lista,
      statusText: statusLabel.textContent,
      erro,
      sinaisCompletos: erro ? false : sinaisCompletos,
    }));
  }

  function restaurarEstadoBusca() {
    if (detectarRecarregamentoPagina()) {
      limparEstadoBusca();
      return false;
    }

    const raw = sessionStorage.getItem(STORAGE_KEY);
    if (!raw) return false;

    try {
      const estado = JSON.parse(raw);
      restaurandoEstado = true;

      if (estado.idCidade && cidadeSelect) {
        cidadeSelect.value = estado.idCidade;
      }
      if (estado.nomeCaixa && caixaInput) {
        caixaInput.value = estado.nomeCaixa;
      }

      caixaAtual = estado.nomeCaixa || '';
      clientesAtuais = Array.isArray(estado.clientes) ? estado.clientes : [];

      if (estado.sinaisCompletos === false && clientesAtuais.length > 0) {
        clientesAtuais = clientesAtuais.map((c) => {
          if (c.sinal_consultado || c.sinal_interrompido) return c;
          return { ...c, sinal_interrompido: true };
        });
      }

      if (estado.erro) {
        renderErro(estado.erro);
        statusLabel.textContent = estado.statusText || 'Erro na busca';
      } else if (clientesAtuais.length > 0) {
        const comSinal = clientesAtuais.filter((c) => extrairRx(c.sinal) != null).length;
        const interrompidos = clientesAtuais.filter((c) => c.sinal_interrompido).length;
        renderClientes(clientesAtuais, caixaAtual, false);
        if (estado.statusText) {
          statusLabel.textContent = estado.statusText;
        } else if (estado.sinaisCompletos === false && interrompidos > 0) {
          statusLabel.textContent = `${clientesAtuais.length} cliente(s) · ${comSinal} com sinal · ${interrompidos} interrompido(s)`;
        } else {
          statusLabel.textContent = `${clientesAtuais.length} cliente(s)${comSinal ? ` · ${comSinal} com sinal` : ''}`;
        }
      } else if (estado.nomeCaixa) {
        resumo.hidden = true;
        wrap.innerHTML = '<div class="caixa-empty">Nenhum cliente encontrado nesta caixa.</div>';
        statusLabel.textContent = estado.statusText || '0 clientes';
      } else {
        restaurandoEstado = false;
        return false;
      }

      restaurandoEstado = false;
      return true;
    } catch {
      limparEstadoBusca();
      restaurandoEstado = false;
      return false;
    }
  }

  function esc(valor) {
    if (valor == null || valor === '') return '';
    return String(valor)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function extrairRx(sinal) {
    if (sinal == null) return null;

    let bruto = null;
    if (typeof sinal === 'number' || typeof sinal === 'string') {
      bruto = sinal;
    } else if (typeof sinal === 'object') {
      if (sinal.rx != null) bruto = sinal.rx;
      else if (sinal.sinal?.rx != null) bruto = sinal.sinal.rx;
    }

    if (bruto == null || bruto === '') return null;
    const valor = Number(bruto);
    if (!Number.isNaN(valor) && valor === 0) return null;
    return bruto;
  }

  function extrairDataSinal(sinal) {
    if (!sinal || typeof sinal !== 'object') return '';
    return sinal.data_atualizacao || sinal.sinal?.data_atualizacao || '';
  }

  function classeSinal(rx) {
    if (rx == null || rx === '') return 'caixa-sinal';
    const valor = Number(rx);
    if (Number.isNaN(valor)) return 'caixa-sinal';
    if (valor >= -24) return 'caixa-sinal caixa-sinal--bom';
    if (valor >= -27) return 'caixa-sinal caixa-sinal--medio';
    return 'caixa-sinal caixa-sinal--ruim';
  }

  function formatarRx(rx) {
    if (rx == null || rx === '') return '—';
    const valor = Number(rx);
    if (Number.isNaN(valor)) return esc(rx);
    return `${valor.toFixed(2)} dBm`;
  }

  function setLoading(ativo) {
    btn.disabled = ativo;
    btnIcon.className = ativo ? 'ti ti-loader-2 spin' : 'ti ti-search';
    btnText.textContent = ativo ? 'Buscando...' : 'Buscar';
    statusLabel.textContent = ativo ? 'Consultando Nicon...' : 'Informe cidade e caixa';
  }

  function renderErro(mensagem) {
    resumo.hidden = true;
    wrap.innerHTML = `<div class="caixa-erro"><i class="ti ti-alert-circle"></i> ${esc(mensagem)}</div>`;
    persistirEstadoBusca({ erro: mensagem });
  }

  function sinalPrecisaCorrecao(rx) {
    const valor = Number(rx);
    if (Number.isNaN(valor)) return false;
    return valor < -24;
  }

  function renderMenuCorrecao(cliente) {
    const rx = extrairRx(cliente.sinal);
    if (!sinalPrecisaCorrecao(rx)) {
      return '';
    }

    return `
      <div class="caixa-cliente-menu">
        <button
          type="button"
          class="caixa-cliente-menu-btn"
          data-menu-toggle="${esc(cliente.id_cliente_servico)}"
          title="Opções"
          aria-label="Opções do cliente"
        >
          <i class="ti ti-dots-vertical"></i>
        </button>
        <div class="caixa-cliente-menu-drop" id="menu-cliente-${esc(cliente.id_cliente_servico)}" hidden>
          <button type="button" data-criar-correcao="${esc(cliente.id_cliente_servico)}">
            Criar tarefa de correção de sinal
          </button>
        </div>
      </div>
    `;
  }

  function fecharMenusCliente() {
    document.querySelectorAll('.caixa-cliente-menu-drop').forEach((menu) => {
      menu.hidden = true;
    });
  }

  function renderCelulaCliente(cliente) {
    return `
      <td>
        <div class="caixa-cliente-linha">
          <div class="caixa-cliente-info">
            <div>${esc(cliente.nome)}</div>
            <div style="font-size:11px;color:var(--gray-500);margin-top:2px;">#${esc(cliente.codigo_cliente)}</div>
          </div>
          ${renderMenuCorrecao(cliente)}
        </div>
      </td>
    `;
  }

  function renderCelulaSinal(cliente, carregandoSinais = false) {
    if (carregandoSinais) {
      return `
        <td class="text-center" data-sinal-id="${esc(cliente.id_cliente_servico)}">
          <i class="ti ti-loader-2" style="animation:caixa-spin 0.9s linear infinite;font-size:16px;color:var(--gray-400);"></i>
        </td>
      `;
    }

    const rx = extrairRx(cliente.sinal);
    const dataSinal = extrairDataSinal(cliente.sinal);
    const consultado = cliente.sinal_consultado === true;

    if (cliente.sinal_interrompido && rx == null) {
      return `
        <td class="text-center" data-sinal-id="${esc(cliente.id_cliente_servico)}">
          <div class="caixa-sinal-celula">
            <i class="ti ti-alert-circle caixa-sinal-erro" title="Busca interrompida"></i>
            <span class="caixa-sinal-off">—</span>
            <button
              type="button"
              class="caixa-sinal-retry"
              data-retry-sinal="${esc(cliente.id_cliente_servico)}"
              data-retry-serial="${esc(cliente.serial || '')}"
              title="Buscar sinal novamente"
              aria-label="Buscar sinal novamente"
            >
              <i class="ti ti-refresh"></i>
            </button>
          </div>
        </td>
      `;
    }

    if (rx == null && consultado) {
      return `
        <td class="text-center" data-sinal-id="${esc(cliente.id_cliente_servico)}">
          <div class="caixa-sinal-celula">
            <span class="caixa-sinal-off">—</span>
            <button
              type="button"
              class="caixa-sinal-retry"
              data-retry-sinal="${esc(cliente.id_cliente_servico)}"
              data-retry-serial="${esc(cliente.serial || '')}"
              title="Atualizar sinal"
              aria-label="Atualizar sinal"
            >
              <i class="ti ti-refresh"></i>
            </button>
          </div>
        </td>
      `;
    }

    if (rx == null) {
      return `
        <td class="text-center" data-sinal-id="${esc(cliente.id_cliente_servico)}">
          <span class="caixa-sinal-off">—</span>
        </td>
      `;
    }

    return `
      <td class="text-center" data-sinal-id="${esc(cliente.id_cliente_servico)}">
        <span class="${classeSinal(rx)}">${formatarRx(rx)}</span>
        ${dataSinal ? `<span class="caixa-sinal-data">${esc(dataSinal)}</span>` : ''}
      </td>
    `;
  }

  function atualizarResumo(clientes, nomeCaixa) {
    const conectados = clientes.filter((c) => c.conectado).length;
    const comSinal = clientes.filter((c) => extrairRx(c.sinal) != null).length;

    resumo.hidden = false;
    resumo.innerHTML = `
      <span><strong>Caixa:</strong> ${esc(clientes[0]?.caixa || nomeCaixa)}</span>
      <span><strong>Clientes:</strong> ${clientes.length}</span>
      <span><strong>Conectados:</strong> ${conectados}</span>
      <span><strong>Com sinal:</strong> ${comSinal}</span>
    `;
    statusLabel.textContent = `${clientes.length} cliente(s)`;
  }

  function renderClientes(clientes, nomeCaixa, carregandoSinais = false) {
    if (!Array.isArray(clientes) || clientes.length === 0) {
      resumo.hidden = true;
      wrap.innerHTML = '<div class="caixa-empty">Nenhum cliente encontrado nesta caixa.</div>';
      statusLabel.textContent = '0 clientes';
      if (!restaurandoEstado) {
        clientesAtuais = [];
        persistirEstadoBusca({ clientes: [], sinaisCompletos: true });
      }
      return;
    }

    atualizarResumo(clientes, nomeCaixa);

    const linhas = clientes.map((cliente) => {
      const badgeConexao = cliente.conectado
        ? '<span class="caixa-badge caixa-badge--ok">Conectado</span>'
        : '<span class="caixa-badge caixa-badge--off">Desconectado</span>';

      return `
        <tr data-cliente-id="${esc(cliente.id_cliente_servico)}">
          ${renderCelulaCliente(cliente)}
          <td class="text-center">${esc(cliente.porta ?? '—')}</td>
          <td class="text-center"><span class="caixa-badge caixa-badge--muted">${esc(cliente.status_servico || '—')}</span></td>
          <td class="text-center">${badgeConexao}</td>
          <td><code style="font-size:11px;">${esc(cliente.serial || '—')}</code></td>
          ${renderCelulaSinal(cliente, carregandoSinais)}
        </tr>
      `;
    }).join('');

    wrap.innerHTML = `
      <table class="caixa-table">
        <thead>
          <tr>
            <th>Cliente</th>
            <th class="text-center">Porta</th>
            <th class="text-center">Status</th>
            <th class="text-center">Conexão</th>
            <th>Serial</th>
            <th class="text-center">Sinal RX</th>
          </tr>
        </thead>
        <tbody>${linhas}</tbody>
      </table>
    `;

    if (!carregandoSinais && !restaurandoEstado) {
      clientesAtuais = clientes;
      persistirEstadoBusca({ clientes, sinaisCompletos: true });
    }
  }

  function atualizarStatusBuscaSinais() {
    const total = clientesAtuais.length;
    if (!total) return;

    const comSinal = clientesAtuais.filter((c) => extrairRx(c.sinal) != null).length;
    const consultados = clientesAtuais.filter((c) => c.sinal_consultado).length;

    if (consultados < total) {
      statusLabel.textContent = `${total} cliente(s) · consultando sinal (${consultados + 1}/${total})...`;
      return;
    }

    statusLabel.textContent = `${total} cliente(s) · ${comSinal} com sinal`;
  }

  function mostrarLoadingSinal(idClienteServico) {
    const celula = document.querySelector(`[data-sinal-id="${idClienteServico}"]`);
    if (!celula) return;
    celula.innerHTML = '<i class="ti ti-loader-2" style="animation:caixa-spin 0.9s linear infinite;font-size:16px;color:var(--gray-400);"></i>';
  }

  function normalizarSinalApi(sinal) {
    if (sinal == null) return null;
    if (extrairRx(sinal) == null) return null;
    return sinal;
  }

  function atualizarLinhaCliente(idClienteServico) {
    const cliente = clientesAtuais.find(
      (item) => Number(item.id_cliente_servico) === Number(idClienteServico)
    );
    if (!cliente) return;

    const linha = document.querySelector(`tr[data-cliente-id="${idClienteServico}"]`);
    if (!linha) return;

    const temp = document.createElement('tbody');
    temp.innerHTML = `
      <tr>
        ${renderCelulaCliente(cliente)}
        <td></td><td></td><td></td><td></td>
        ${renderCelulaSinal(cliente, false)}
      </tr>
    `;

    const novaCelulaCliente = temp.querySelector('td:first-child');
    const novaCelulaSinal = temp.querySelector('[data-sinal-id]');
    const celulaCliente = linha.querySelector('td:first-child');
    const celulaSinal = linha.querySelector('[data-sinal-id]');

    if (novaCelulaCliente && celulaCliente) {
      celulaCliente.replaceWith(novaCelulaCliente);
    }
    if (novaCelulaSinal && celulaSinal) {
      celulaSinal.replaceWith(novaCelulaSinal);
    }
  }

  function aplicarSinalNoCliente(idClienteServico, sinal, nomeCaixa, { persistir = true } = {}) {
    const indice = clientesAtuais.findIndex(
      (item) => Number(item.id_cliente_servico) === Number(idClienteServico)
    );
    if (indice === -1) return;

    clientesAtuais[indice] = {
      ...clientesAtuais[indice],
      sinal,
      sinal_consultado: true,
      sinal_interrompido: false,
    };

    atualizarLinhaCliente(idClienteServico);
    atualizarResumo(clientesAtuais, nomeCaixa);
    atualizarStatusBuscaSinais();

    if (persistir && !restaurandoEstado) {
      const todosConsultados = clientesAtuais.every((c) => c.sinal_consultado);
      persistirEstadoBusca({
        clientes: clientesAtuais,
        sinaisCompletos: todosConsultados,
      });
    }
  }

  async function requestSinalAtualCliente(cliente, forcarRefresh = false, signal = null) {
    const payload = {
      id_cliente_servico: Number(cliente.id_cliente_servico),
      forcar_refresh_tr069: forcarRefresh,
    };

    if (cliente.serial) {
      payload.serial = cliente.serial;
    }

    const response = await fetch('/api/nicon/sinal-atual-cliente', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token(),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
      signal,
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.message || 'Falha ao buscar sinal.');
    }

    return data.sinal ?? null;
  }

  let buscaSinaisToken = 0;
  let buscaSinaisAtiva = false;
  let abortControllersSinais = [];

  function registrarAbortControllerSinais() {
    const controller = new AbortController();
    abortControllersSinais.push(controller);
    return controller;
  }

  function abortarRequisicoesSinais() {
    abortControllersSinais.forEach((controller) => {
      try { controller.abort(); } catch (_) {}
    });
    abortControllersSinais = [];
  }

  function marcarClientesNaoConsultadosComoInterrompidos() {
    clientesAtuais = clientesAtuais.map((cliente) => {
      if (cliente.sinal_consultado) return cliente;
      return { ...cliente, sinal_interrompido: true };
    });
  }

  function cancelarBuscaSinais({ persistir = true } = {}) {
    if (!buscaSinaisAtiva) return;

    buscaSinaisToken += 1;
    buscaSinaisAtiva = false;
    abortarRequisicoesSinais();
    marcarClientesNaoConsultadosComoInterrompidos();

    const total = clientesAtuais.length;
    if (total) {
      const comSinal = clientesAtuais.filter((c) => extrairRx(c.sinal) != null).length;
      const interrompidos = clientesAtuais.filter((c) => c.sinal_interrompido).length;
      statusLabel.textContent = `${total} cliente(s) · ${comSinal} com sinal · ${interrompidos} interrompido(s)`;
    }

    if (persistir && !restaurandoEstado && clientesAtuais.length) {
      persistirEstadoBusca({ clientes: clientesAtuais, sinaisCompletos: false });
    }
  }

  async function buscarSinalClienteIndividual(cliente, signal = null) {
    let sinal = normalizarSinalApi(
      await requestSinalAtualCliente(cliente, false, signal)
    );

    if (sinal != null) return sinal;
    if (signal?.aborted) return null;

    mostrarLoadingSinal(Number(cliente.id_cliente_servico));

    sinal = normalizarSinalApi(
      await requestSinalAtualCliente(cliente, true, signal)
    );

    return sinal;
  }

  async function processarClienteSinal(cliente, nomeCaixa, tokenBusca) {
    if (tokenBusca !== buscaSinaisToken) return;

    const id = Number(cliente.id_cliente_servico);
    mostrarLoadingSinal(id);

    const controller = registrarAbortControllerSinais();

    try {
      const sinal = await buscarSinalClienteIndividual(cliente, controller.signal);
      if (tokenBusca !== buscaSinaisToken) return;
      aplicarSinalNoCliente(id, sinal, nomeCaixa);
    } catch (error) {
      if (tokenBusca !== buscaSinaisToken || error?.name === 'AbortError') return;

      try {
        mostrarLoadingSinal(id);
        const sinal = normalizarSinalApi(
          await requestSinalAtualCliente(cliente, true, controller.signal)
        );
        if (tokenBusca !== buscaSinaisToken) return;
        aplicarSinalNoCliente(id, sinal, nomeCaixa);
      } catch (retryError) {
        if (tokenBusca !== buscaSinaisToken || retryError?.name === 'AbortError') return;
        aplicarSinalNoCliente(id, null, nomeCaixa);
      }
    }
  }

  async function buscarSinaisClientePorCliente(clientes, nomeCaixa) {
    const tokenBusca = ++buscaSinaisToken;
    abortarRequisicoesSinais();
    buscaSinaisAtiva = true;
    atualizarStatusBuscaSinais();

    for (const cliente of clientes) {
      if (tokenBusca !== buscaSinaisToken) return;
      if (cliente.sinal_consultado) continue;
      await processarClienteSinal(cliente, nomeCaixa, tokenBusca);
    }

    if (tokenBusca !== buscaSinaisToken) return;

    buscaSinaisAtiva = false;
    abortarRequisicoesSinais();
    persistirEstadoBusca({ clientes: clientesAtuais, sinaisCompletos: true });
    atualizarStatusBuscaSinais();
  }

  async function recarregarSinalIndividual(idClienteServico, serial, nomeCaixa) {
    const cliente = clientesAtuais.find(
      (item) => Number(item.id_cliente_servico) === Number(idClienteServico)
    );
    if (!cliente) return;

    const celula = document.querySelector(`[data-sinal-id="${idClienteServico}"]`);
    const botao = celula?.querySelector('[data-retry-sinal]');

    if (botao) {
      botao.disabled = true;
      botao.querySelector('i')?.classList.add('spin');
    } else {
      mostrarLoadingSinal(idClienteServico);
    }

    try {
      const sinal = await buscarSinalClienteIndividual(
        { ...cliente, serial: serial || cliente.serial || '' }
      );
      aplicarSinalNoCliente(idClienteServico, sinal, nomeCaixa);
    } catch (error) {
      aplicarSinalNoCliente(idClienteServico, null, nomeCaixa);
      alert(error.message || 'Não foi possível buscar o sinal deste cliente.');
    } finally {
      if (botao) {
        botao.disabled = false;
        botao.querySelector('i')?.classList.remove('spin');
      }
    }
  }

  async function criarTarefaCorrecao(idClienteServico) {
    const cliente = clientesAtuais.find(
      (item) => Number(item.id_cliente_servico) === Number(idClienteServico)
    );

    if (!cliente) {
      alert('Cliente não encontrado na listagem atual.');
      return;
    }

    const regiao = cidadeSelect?.options[cidadeSelect.selectedIndex]?.text || 'Governador Valadares';
    const botao = document.querySelector(`[data-criar-correcao="${idClienteServico}"]`);

    if (botao) {
      botao.disabled = true;
      botao.textContent = 'Criando tarefa...';
    }

    try {
      const response = await fetch('/api/correcao-sinal/from-caixa', {
        method: 'POST',
        headers: {
          'Authorization': 'Bearer ' + token(),
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          nome_cliente: cliente.nome,
          caixa: cliente.caixa || caixaAtual,
          porta: cliente.porta,
          serial: cliente.serial,
          sinal_rx: extrairRx(cliente.sinal),
          codigo_cliente: cliente.codigo_cliente,
          id_cliente_servico: cliente.id_cliente_servico,
          regiao,
        }),
      });

      const data = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(data.message || 'Não foi possível criar a tarefa.');
      }

      fecharMenusCliente();

      const codigo = data.correcaoDeSinal?.taskCode || '';
      const ir = confirm(
        `Tarefa criada com sucesso${codigo ? ` (${codigo})` : ''}.\n\nAbrir o kanban de Correção de sinal?`
      );

      if (ir) {
        window.location.href = '/correcao-de-sinal';
      }
    } catch (error) {
      alert(error.message || 'Falha ao criar tarefa de correção de sinal.');
    } finally {
      if (botao) {
        botao.disabled = false;
        botao.textContent = 'Criar tarefa de correção de sinal';
      }
    }
  }

  async function requestNicon(payload, signal = null) {
    const response = await fetch('/api/nicon/sinal-caixa', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token(),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
      signal,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.message || `Erro HTTP ${response.status}`);
    }

    return data;
  }

  async function buscarCaixa(event) {
    event.preventDefault();

    const idCidade = cidadeSelect.value;
    const caixa = caixaInput.value.trim();

    if (!idCidade || !caixa) return;

    limparEstadoBusca();
    buscaSinaisToken += 1;
    abortarRequisicoesSinais();
    buscaSinaisAtiva = false;
    setLoading(true);
    wrap.innerHTML = '<div class="caixa-loading"><i class="ti ti-loader-2"></i> Buscando clientes da caixa...</div>';
    resumo.hidden = true;

    try {
      const data = await requestNicon({
        id_cidade: Number(idCidade),
        caixa,
        completar_sinais: false,
      });

      const clientes = data.clientes || [];
      clientesAtuais = clientes;
      caixaAtual = caixa;
      renderClientes(clientes, caixa, true);
      setLoading(false);

      if (clientes.length === 0) {
        return;
      }

      void buscarSinaisClientePorCliente(clientes, caixa);
    } catch (error) {
      renderErro(error.message || 'Falha ao consultar o Nicon.');
      statusLabel.textContent = 'Erro na busca';
    } finally {
      setLoading(false);
    }
  }

  form.addEventListener('submit', buscarCaixa);

  wrap.addEventListener('click', async (event) => {
    const botaoRetry = event.target.closest('[data-retry-sinal]');
    if (botaoRetry) {
      const id = Number(botaoRetry.dataset.retrySinal);
      const serial = botaoRetry.dataset.retrySerial || '';
      if (!id || !clientesAtuais.length) return;
      await recarregarSinalIndividual(id, serial, caixaAtual);
      return;
    }

    const botaoMenu = event.target.closest('[data-menu-toggle]');
    if (botaoMenu) {
      event.stopPropagation();
      const id = botaoMenu.dataset.menuToggle;
      const menu = document.getElementById(`menu-cliente-${id}`);
      if (!menu) return;
      const abrir = menu.hidden;
      fecharMenusCliente();
      menu.hidden = !abrir;
      return;
    }

    const botaoCorrecao = event.target.closest('[data-criar-correcao]');
    if (botaoCorrecao) {
      event.stopPropagation();
      await criarTarefaCorrecao(botaoCorrecao.dataset.criarCorrecao);
    }
  });

  document.addEventListener('click', () => fecharMenusCliente());

  window.addEventListener('pagehide', () => {
    cancelarBuscaSinais({ persistir: true });
  });

  window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    buscaSinaisToken += 1;
    abortarRequisicoesSinais();
    buscaSinaisAtiva = false;
    restaurarEstadoBusca();
  });

  const estadoRestaurado = restaurarEstadoBusca();
  if (estadoRestaurado) {
    fecharModalBeta();
  } else {
    abrirModalBeta();
  }

  btnFecharBeta?.addEventListener('click', fecharModalBeta);
  modalBeta?.addEventListener('click', (event) => {
    if (event.target === modalBeta) {
      fecharModalBeta();
    }
  });
</script>
@endsection
