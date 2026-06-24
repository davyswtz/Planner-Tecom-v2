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
  .caixa-empty, .caixa-loading, .caixa-erro {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 48px 16px; color: var(--gray-500); font-size: 13px; text-align: center;
  }
  .caixa-loading i { animation: caixa-spin 0.9s linear infinite; }
  .caixa-erro { color: #dc2626; }
  [data-theme="dark"] .caixa-input,
  [data-theme="dark"] .caixa-select {
    background: #21262d; border-color: #30363d; color: #e6edf3;
  }
  [data-theme="dark"] .caixa-badge--ok { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
  [data-theme="dark"] .caixa-badge--off { background: rgba(239, 68, 68, 0.15); color: #f87171; }
  [data-theme="dark"] .caixa-badge--muted { background: #21262d; color: #8b949e; }
  @keyframes caixa-spin { to { transform: rotate(360deg); } }
  @media (max-width: 768px) {
    .caixa-filtros { grid-template-columns: 1fr; }
  }
</style>
@endsection

@section('content')
<div class="caixa-page">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Consulta Nicon</span>
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
      Busca os clientes da caixa no Nicon e consulta o sinal RX de cada ONU. A lista de clientes aparece primeiro; os sinais são carregados em seguida. Buscas repetidas na mesma cidade ficam mais rápidas por cache.
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

  const form = document.getElementById('form-buscar-caixa');
  const btn = document.getElementById('btn-buscar-caixa');
  const btnIcon = document.getElementById('btn-buscar-caixa-icon');
  const btnText = document.getElementById('btn-buscar-caixa-text');
  const wrap = document.getElementById('caixa-resultado-wrap');
  const resumo = document.getElementById('caixa-resumo');
  const statusLabel = document.getElementById('caixa-status-label');

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
    if (typeof sinal === 'number' || typeof sinal === 'string') return sinal;
    if (typeof sinal === 'object') {
      if (sinal.rx != null) return sinal.rx;
      if (sinal.sinal?.rx != null) return sinal.sinal.rx;
    }
    return null;
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

    if (rx == null && consultado) {
      return `
        <td class="text-center" data-sinal-id="${esc(cliente.id_cliente_servico)}">
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
      return;
    }

    atualizarResumo(clientes, nomeCaixa);

    const linhas = clientes.map((cliente) => {
      const badgeConexao = cliente.conectado
        ? '<span class="caixa-badge caixa-badge--ok">Conectado</span>'
        : '<span class="caixa-badge caixa-badge--off">Desconectado</span>';

      return `
        <tr>
          <td>
            <div>${esc(cliente.nome)}</div>
            <div style="font-size:11px;color:var(--gray-500);margin-top:2px;">#${esc(cliente.codigo_cliente)}</div>
          </td>
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
  }

  function aplicarSinais(clientesComSinal, clientesAtuais, nomeCaixa) {
    const mapa = new Map(
      (clientesComSinal || []).map((item) => [Number(item.id_cliente_servico), item.sinal ?? null])
    );

    const mesclados = clientesAtuais.map((cliente) => {
      const id = Number(cliente.id_cliente_servico);
      const consultado = mapa.has(id);

      return {
        ...cliente,
        sinal: consultado ? mapa.get(id) : cliente.sinal,
        sinal_consultado: cliente.sinal_consultado || consultado,
      };
    });

    renderClientes(mesclados, nomeCaixa, false);
    return mesclados;
  }

  async function buscarSinaisParaIds(ids) {
    if (!ids.length) return [];
    const data = await requestNicon({
      clientes_servicos: ids,
      completar_sinais: true,
    });
    return data.clientes || [];
  }

  async function recarregarSinalIndividual(idClienteServico, serial, clientes, nomeCaixa) {
    const celula = document.querySelector(`[data-sinal-id="${idClienteServico}"]`);
    const botao = celula?.querySelector('[data-retry-sinal]');

    if (botao) {
      botao.disabled = true;
      botao.querySelector('i')?.classList.add('spin');
    } else if (celula) {
      celula.innerHTML = '<i class="ti ti-loader-2" style="animation:caixa-spin 0.9s linear infinite;font-size:16px;color:var(--gray-400);"></i>';
    }

    try {
      const payload = {
        id_cliente_servico: Number(idClienteServico),
        forcar_refresh_tr069: true,
      };

      if (serial) {
        payload.serial = serial;
      }

      const response = await fetch('/api/nicon/sinal-atual-cliente', {
        method: 'POST',
        headers: {
          'Authorization': 'Bearer ' + token(),
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(data.message || 'Falha ao buscar sinal.');
      }

      const atualizados = clientes.map((cliente) => (
        Number(cliente.id_cliente_servico) === Number(idClienteServico)
          ? { ...cliente, sinal: data.sinal ?? null, sinal_consultado: true }
          : cliente
      ));

      return aplicarSinais(
        [{ id_cliente_servico: Number(idClienteServico), sinal: data.sinal ?? null }],
        atualizados,
        nomeCaixa
      );
    } catch (error) {
      alert(error.message || 'Não foi possível buscar o sinal deste cliente.');
      renderClientes(clientes, nomeCaixa, false);
      return clientes;
    }
  }

  let clientesAtuais = [];
  let caixaAtual = '';

  async function requestNicon(payload) {
    const response = await fetch('/api/nicon/sinal-caixa', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token(),
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.message || `Erro HTTP ${response.status}`);
    }

    return data;
  }

  async function buscarCaixa(event) {
    event.preventDefault();

    const idCidade = document.getElementById('caixa-cidade').value;
    const caixa = document.getElementById('caixa-nome').value.trim();

    if (!idCidade || !caixa) return;

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

      statusLabel.textContent = `${clientes.length} cliente(s) · buscando sinais...`;

      const sinais = await buscarSinaisParaIds(clientes.map((c) => c.id_cliente_servico));
      clientesAtuais = aplicarSinais(sinais, clientes, caixa);
    } catch (error) {
      renderErro(error.message || 'Falha ao consultar o Nicon.');
      statusLabel.textContent = 'Erro na busca';
    } finally {
      setLoading(false);
    }
  }

  form.addEventListener('submit', buscarCaixa);

  wrap.addEventListener('click', async (event) => {
    const botao = event.target.closest('[data-retry-sinal]');
    if (!botao) return;

    const id = Number(botao.dataset.retrySinal);
    const serial = botao.dataset.retrySerial || '';
    if (!id || !clientesAtuais.length) return;

    clientesAtuais = await recarregarSinalIndividual(id, serial, clientesAtuais, caixaAtual);
  });
</script>
@endsection
