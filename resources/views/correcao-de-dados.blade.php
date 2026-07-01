@extends('layouts.app')

@section('title', 'Correção de dados — Planner Telecom')
@section('page-title', 'Correção de dados')
@section('btn-label', 'Novo registro')

@section('styles')
<style>
  .cd-page { width: 100%; max-width: 100%; }
  .cd-banner {
    display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px;
    margin-bottom: 14px; border-radius: var(--radius-sm);
    background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;
    font-size: 13px; line-height: 1.5;
  }
  .cd-banner i { font-size: 20px; margin-top: 1px; flex-shrink: 0; }
  .cd-actions { display: flex; justify-content: flex-end; margin-bottom: 12px; }
  .cd-table-wrap { overflow-x: auto; }
  .cd-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .cd-table th {
    text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.04em; color: var(--gray-500);
    border-bottom: 1px solid var(--gray-200); white-space: nowrap;
  }
  .cd-table td {
    padding: 12px; border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800); vertical-align: middle;
  }
  .cd-titulo { font-weight: 600; color: var(--gray-950); max-width: 280px; }
  .cd-muted { color: var(--gray-500); font-size: 12px; }
  .cd-badge {
    display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px;
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;
  }
  .cd-badge--tarefa { background: #eff6ff; color: #1d4ed8; }
  .cd-badge--os { background: #f0fdf4; color: #166534; }
  .cd-acoes { display: flex; justify-content: flex-end; gap: 8px; white-space: nowrap; }
  .cd-btn {
    height: 30px; padding: 0 10px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); background: var(--white); color: var(--gray-700);
    font: inherit; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
  }
  .cd-btn:hover { border-color: #166534; color: #166534; }
  .cd-btn.danger:hover { border-color: #dc2626; color: #dc2626; }
  .cd-empty, .cd-loading {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 42px 16px; color: var(--gray-500); font-size: 13px;
  }
  .cd-loading i { animation: spin 0.9s linear infinite; }
  .cd-form { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .cd-form .full { grid-column: 1 / -1; }
  .cd-field { display: flex; flex-direction: column; gap: 5px; }
  .cd-label {
    font-size: 12px; font-weight: 600; color: var(--gray-600);
    text-transform: uppercase; letter-spacing: 0.04em;
  }
  .cd-input, .cd-select, .cd-textarea {
    width: 100%; min-height: 38px; padding: 8px 10px;
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    background: var(--white); color: var(--gray-950); font: inherit; outline: none;
  }
  .cd-textarea { min-height: 80px; resize: vertical; }
  .cd-input:focus, .cd-select:focus, .cd-textarea:focus { border-color: #166534; }
  .cd-help { font-size: 12px; color: var(--gray-500); }
  .cd-error {
    display: none; padding: 10px 12px; border-radius: var(--radius-sm);
    background: #fef2f2; color: #b91c1c; font-size: 13px;
  }
  [data-theme="dark"] .cd-banner { background: #052e16; border-color: #166534; color: #86efac; }
  [data-theme="dark"] .cd-input, [data-theme="dark"] .cd-select, [data-theme="dark"] .cd-textarea,
  [data-theme="dark"] .cd-btn { background: #21262d; border-color: #30363d; color: #e6edf3; }
</style>
@endsection

@section('content')
<div class="cd-page">
  <div class="cd-banner">
    <i class="ti ti-info-circle"></i>
    <div>
      Registros criados aqui usam as <strong>datas informadas por você</strong>, não a data atual.
      Isso vale apenas para itens desta tela — aparecem normalmente nos filtros e relatórios do período escolhido.
    </div>
  </div>

  <div class="cd-actions">
    <button type="button" class="btn-primary" onclick="abrirModalCorrecao()" style="background:#166534;">
      <i class="ti ti-plus"></i> Novo registro
    </button>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Registros de correção</span>
      <span class="card-action">total: <span id="cd-total">0</span></span>
    </div>
    <div class="cd-table-wrap" id="cd-wrap">
      <div class="cd-loading"><i class="ti ti-loader-2"></i> Carregando...</div>
    </div>
  </div>
</div>

<x-modal
  id="modal-correcao"
  titulo="Novo registro"
  subtitulo="Tarefa ou O.S. com datas personalizadas"
  titulo-id="cd-modal-titulo"
  subtitulo-id="cd-modal-subtitulo"
  fechar="fecharModalCorrecao()">
  <div class="cd-form">
    <div class="cd-error full" id="cd-erro"></div>

    <div class="cd-field">
      <label class="cd-label" for="cd-categoria">Categoria (tela)</label>
      <select id="cd-categoria" class="cd-select">
        <option value="tarefas">Tarefas</option>
        <option value="rompimentos" selected>Rompimentos</option>
        <option value="troca-poste">Troca de poste</option>
        <option value="troca-etiqueta">Troca de etiqueta</option>
        <option value="otimizacao-rede">Otimização de rede</option>
        <option value="atendimento-cliente">Atendimento</option>
        <option value="manutencao-corretiva">Manutenção</option>
        <option value="certificacao-cemig">Certificação</option>
        <option value="correcao-atenuacao">Correção de sinal</option>
      </select>
    </div>

    <div class="cd-field">
      <label class="cd-label" for="cd-registro">Registro</label>
      <select id="cd-registro" class="cd-select" onchange="atualizarFormCorrecao()">
        <option value="os">Ordem de serviço (O.S.)</option>
        <option value="tarefa">Tarefa</option>
      </select>
      <span class="cd-help" id="cd-registro-hint">O.S. avulsa, sem vínculo com tarefa pai — contabilizada para o técnico.</span>
    </div>

    <div class="cd-field full">
      <label class="cd-label" for="cd-titulo">Título</label>
      <input type="text" id="cd-titulo" class="cd-input" placeholder="Descrição do serviço"/>
    </div>

    <div class="cd-field" id="cd-campo-tecnico">
      <label class="cd-label" for="cd-tecnico">Técnico</label>
      <select id="cd-tecnico" class="cd-select">
        <option value="">Selecione...</option>
      </select>
    </div>

    <div class="cd-field">
      <label class="cd-label" for="cd-regiao">Região</label>
      <select id="cd-regiao" class="cd-select">
        <option value="">—</option>
        <option>Goval</option>
        <option>Vale do Aço</option>
        <option>Caratinga</option>
      </select>
    </div>

    <div class="cd-field">
      <label class="cd-label" for="cd-status">Status</label>
      <select id="cd-status" class="cd-select" onchange="atualizarConclusaoCorrecao()">
        <option>Aberta</option>
        <option>Em andamento</option>
        <option>Finalizada</option>
        <option>Concluída</option>
      </select>
    </div>

    <div class="cd-field">
      <label class="cd-label" for="cd-prioridade">Prioridade</label>
      <select id="cd-prioridade" class="cd-select">
        <option>Alta</option>
        <option selected>Média</option>
        <option>Baixa</option>
      </select>
    </div>

    <div class="cd-field">
      <label class="cd-label" for="cd-data-criacao">Data de criação</label>
      <input type="date" id="cd-data-criacao" class="cd-input"/>
      <span class="cd-help">Será usada nos filtros por período.</span>
    </div>

    <div class="cd-field" id="cd-campo-conclusao">
      <label class="cd-label" for="cd-data-conclusao">Data de conclusão</label>
      <input type="date" id="cd-data-conclusao" class="cd-input"/>
    </div>

    <div class="cd-field full">
      <label class="cd-label" for="cd-descricao">Descrição (opcional)</label>
      <textarea id="cd-descricao" class="cd-textarea" placeholder="Observações"></textarea>
    </div>
  </div>

  <x-slot name="footer">
    <button type="button" class="btn-modal btn-modal-ghost" onclick="fecharModalCorrecao()">Cancelar</button>
    <button type="button" class="btn-modal btn-modal-primary" id="btn-salvar-correcao" onclick="salvarCorrecao()" style="background:#166534;">
      <i class="ti ti-device-floppy" style="font-size:14px"></i> Salvar
    </button>
  </x-slot>
</x-modal>
@endsection

@section('scripts')
<script>
  if (typeof window.plannerPossuiPermissao === 'function' && !window.plannerPossuiPermissao('corrigir_dados')) {
    window.location.replace('/dashboard');
  }
</script>
<script type="module">
  let editandoId = null;
  let itens = [];

  function token() {
    return localStorage.getItem('planner_token');
  }

  function esc(v) {
    if (v == null || v === '') return '—';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function fmtData(iso) {
    if (!iso) return '—';
    const [y, m, d] = iso.split('-');
    if (!y || !m || !d) return esc(iso);
    return `${d}/${m}/${y}`;
  }

  function labelCategoria(cat) {
    const map = {
      'ordem-servico': 'Ordem de serviço',
      'rompimentos': 'Rompimentos',
      'troca-poste': 'Troca de poste',
      'troca-etiqueta': 'Troca de etiqueta',
      'otimizacao-rede': 'Otimização de rede',
      'atendimento-cliente': 'Atendimento',
      'correcao-atenuacao': 'Correção de sinal',
      'certificacao-cemig': 'Certificação',
      'manutencao-corretiva': 'Manutenção',
      'tarefas': 'Tarefas',
    };
    return map[cat] || cat;
  }

  function mostrarErro(msg) {
    const el = document.getElementById('cd-erro');
    el.textContent = msg || 'Não foi possível salvar.';
    el.style.display = 'block';
  }

  function statusFinalizado() {
    const s = document.getElementById('cd-status').value.toLowerCase();
    return ['finalizada', 'concluída', 'concluida'].includes(s);
  }

  window.atualizarConclusaoCorrecao = function () {
    const campo = document.getElementById('cd-campo-conclusao');
    campo.style.display = statusFinalizado() ? '' : 'none';
  };

  window.atualizarFormCorrecao = function () {
    const registro = document.getElementById('cd-registro').value;
    const campoTecnico = document.getElementById('cd-campo-tecnico');
    const hint = document.getElementById('cd-registro-hint');

    campoTecnico.style.display = registro === 'os' ? '' : 'none';
    hint.textContent = registro === 'os'
      ? 'O.S. avulsa, sem vínculo com tarefa pai — contabilizada para o técnico.'
      : 'Tarefa pai da categoria selecionada — não gera vínculo em os_tecnicos.';

    const status = document.getElementById('cd-status');
    if (registro === 'tarefa') {
      status.innerHTML = '<option>Criada</option><option>Em andamento</option><option>Finalizada</option><option>Concluída</option>';
    } else {
      status.innerHTML = '<option>Aberta</option><option>Em andamento</option><option>Finalizada</option><option>Concluída</option>';
    }
    atualizarConclusaoCorrecao();
  };

  async function carregarTecnicos() {
    const select = document.getElementById('cd-tecnico');
    try {
      const res = await fetch('/api/tecnicos', { headers: { Authorization: 'Bearer ' + token() } });
      const data = await res.json();
      const lista = Array.isArray(data) ? data : (data.tecnicos || data.items || []);
      select.innerHTML = '<option value="">Selecione...</option>' + lista.map(t =>
        `<option value="${esc(t.nome)}">${esc(t.nome)}</option>`
      ).join('');
    } catch {
      select.innerHTML = '<option value="">Erro ao carregar</option>';
    }
  }

  function limparForm() {
    editandoId = null;
    document.getElementById('cd-categoria').value = 'rompimentos';
    document.getElementById('cd-registro').value = 'os';
    document.getElementById('cd-titulo').value = '';
    document.getElementById('cd-tecnico').value = '';
    document.getElementById('cd-regiao').value = '';
    document.getElementById('cd-status').value = 'Aberta';
    document.getElementById('cd-prioridade').value = 'Média';
    document.getElementById('cd-data-criacao').value = '';
    document.getElementById('cd-data-conclusao').value = '';
    document.getElementById('cd-descricao').value = '';
    document.getElementById('cd-erro').style.display = 'none';
    document.getElementById('cd-modal-titulo').textContent = 'Novo registro';
    document.getElementById('cd-modal-subtitulo').textContent = 'Tarefa ou O.S. com datas personalizadas';
    document.getElementById('btn-salvar-correcao').innerHTML = '<i class="ti ti-device-floppy" style="font-size:14px"></i> Salvar';
    atualizarFormCorrecao();
  }

  window.abrirModalCorrecao = function () {
    limparForm();
    document.getElementById('modal-correcao').classList.add('open');
  };

  window.fecharModalCorrecao = function () {
    document.getElementById('modal-correcao').classList.remove('open');
  };

  window.editarCorrecao = function (id) {
    const item = itens.find(i => i.id === id);
    if (!item) return;
    editandoId = id;
    document.getElementById('cd-categoria').value = item.categoria || 'rompimentos';
    document.getElementById('cd-registro').value = item.registro || item.tipo || 'os';
    atualizarFormCorrecao();
    document.getElementById('cd-titulo').value = item.titulo || '';
    document.getElementById('cd-tecnico').value = item.tecnico || '';
    document.getElementById('cd-regiao').value = item.regiao || '';
    document.getElementById('cd-status').value = item.status || 'Aberta';
    document.getElementById('cd-prioridade').value = item.prioridade || 'Média';
    document.getElementById('cd-data-criacao').value = item.data_criacao || '';
    document.getElementById('cd-data-conclusao').value = item.data_conclusao || '';
    document.getElementById('cd-descricao').value = item.descricao || '';
    document.getElementById('cd-modal-titulo').textContent = 'Editar registro';
    document.getElementById('cd-modal-subtitulo').textContent = item.taskCode || '';
    document.getElementById('btn-salvar-correcao').innerHTML = '<i class="ti ti-device-floppy" style="font-size:14px"></i> Atualizar';
    atualizarConclusaoCorrecao();
    document.getElementById('modal-correcao').classList.add('open');
  };

  window.excluirCorrecao = async function (id) {
    if (!confirm('Excluir este registro de correção?')) return;
    const res = await fetch('/api/correcao-dados/' + id, {
      method: 'DELETE',
      headers: { Authorization: 'Bearer ' + token(), Accept: 'application/json' },
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      alert(err.message || 'Não foi possível excluir.');
      return;
    }
    await carregarLista();
  };

  window.salvarCorrecao = async function () {
    const registro = document.getElementById('cd-registro').value;
    const payload = {
      registro,
      categoria: document.getElementById('cd-categoria').value,
      titulo: document.getElementById('cd-titulo').value.trim(),
      tecnico: document.getElementById('cd-tecnico').value,
      regiao: document.getElementById('cd-regiao').value,
      status: document.getElementById('cd-status').value,
      prioridade: document.getElementById('cd-prioridade').value,
      data_criacao: document.getElementById('cd-data-criacao').value,
      data_conclusao: document.getElementById('cd-data-conclusao').value || null,
      descricao: document.getElementById('cd-descricao').value.trim(),
    };

    if (!payload.titulo) return mostrarErro('Informe o título.');
    if (!payload.categoria) return mostrarErro('Selecione a categoria.');
    if (!payload.data_criacao) return mostrarErro('Informe a data de criação.');
    if (registro === 'os' && !payload.tecnico) return mostrarErro('Selecione o técnico para a O.S.');

    const url = editandoId ? '/api/correcao-dados/' + editandoId : '/api/correcao-dados';
    const method = editandoId ? 'PUT' : 'POST';

    const res = await fetch(url, {
      method,
      headers: {
        Authorization: 'Bearer ' + token(),
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      return mostrarErro(err.message || 'Erro ao salvar.');
    }

    fecharModalCorrecao();
    await carregarLista();
  };

  function renderTabela() {
    const wrap = document.getElementById('cd-wrap');
    document.getElementById('cd-total').textContent = itens.length;

    if (!itens.length) {
      wrap.innerHTML = '<div class="cd-empty"><i class="ti ti-database-off"></i> Nenhum registro de correção ainda.</div>';
      return;
    }

    wrap.innerHTML = `
      <table class="cd-table">
        <thead>
          <tr>
            <th>Categoria</th>
            <th>Registro</th>
            <th>Código</th>
            <th>Título</th>
            <th>Técnico</th>
            <th>Status</th>
            <th>Criação</th>
            <th>Conclusão</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          ${itens.map(item => `
            <tr>
              <td>${esc(item.categoria_label || labelCategoria(item.categoria))}</td>
              <td><span class="cd-badge cd-badge--${item.registro || item.tipo}">${(item.registro || item.tipo) === 'os' ? 'O.S.' : 'Tarefa'}</span></td>
              <td class="cd-muted">${esc(item.taskCode)}</td>
              <td class="cd-titulo">${esc(item.titulo)}</td>
              <td>${esc(item.tecnico)}</td>
              <td>${esc(item.status)}</td>
              <td>${fmtData(item.data_criacao)}</td>
              <td>${fmtData(item.data_conclusao)}</td>
              <td class="cd-acoes">
                <button type="button" class="cd-btn" onclick="editarCorrecao(${item.id})"><i class="ti ti-pencil"></i></button>
                <button type="button" class="cd-btn danger" onclick="excluirCorrecao(${item.id})"><i class="ti ti-trash"></i></button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  async function carregarLista() {
    const wrap = document.getElementById('cd-wrap');
    wrap.innerHTML = '<div class="cd-loading"><i class="ti ti-loader-2"></i> Carregando...</div>';

    const res = await fetch('/api/correcao-dados', {
      headers: { Authorization: 'Bearer ' + token(), Accept: 'application/json' },
    });

    if (res.status === 403) {
      window.location.replace('/dashboard');
      return;
    }

    if (!res.ok) {
      wrap.innerHTML = '<div class="cd-empty">Não foi possível carregar os registros.</div>';
      return;
    }

    const data = await res.json();
    itens = data.items || [];
    renderTabela();
  }

  window.abrirNovoItem = function () {
    abrirModalCorrecao();
  };

  await carregarTecnicos();
  await carregarLista();
</script>
@endsection
