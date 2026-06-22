@extends('layouts.app')

@section('title', 'Usuários — Planner Telecom')
@section('page-title', 'Usuários')
@section('btn-label', 'Novo usuário')

@section('styles')
<style>
  .usuarios-page { width: 100%; max-width: 100%; }
  .usuarios-actions { display: flex; justify-content: flex-end; margin-bottom: 12px; }
  .usuarios-filtros {
    display: flex; align-items: center; gap: 8px; padding: 10px 16px;
    border-bottom: 1px solid var(--gray-100);
  }
  .usuarios-filtro-label {
    font-size: 12px; font-weight: 600; color: var(--gray-500);
    text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap;
  }
  .usuarios-filtro-select {
    height: 34px; padding: 0 10px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); background: var(--white); color: var(--gray-800);
    font: inherit; font-size: 13px; outline: none; cursor: pointer;
  }
  .usuarios-filtro-select:focus { border-color: var(--blue-600); }
  .usuarios-table-wrap { overflow-x: auto; }
  .usuarios-table { width: 100%; border-collapse: collapse; font-size: 13px; }
  .usuarios-table th {
    text-align: left; padding: 10px 12px; font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.04em; color: var(--gray-500);
    border-bottom: 1px solid var(--gray-200); white-space: nowrap;
  }
  .usuarios-table td {
    padding: 12px; border-bottom: 1px solid var(--gray-100);
    color: var(--gray-800); vertical-align: middle;
  }
  .usuario-name { font-weight: 600; color: var(--gray-950); }
  .usuario-muted { color: var(--gray-500); font-size: 12px; }
  .usuario-acoes { display: flex; justify-content: flex-end; gap: 8px; white-space: nowrap; }
  .usuario-action-btn {
    height: 30px; padding: 0 10px; border: 1px solid var(--gray-200);
    border-radius: var(--radius-sm); background: var(--white); color: var(--gray-700);
    font: inherit; font-size: 12px; cursor: pointer; display: inline-flex;
    align-items: center; gap: 5px;
  }
  .usuario-action-btn:hover { border-color: var(--blue-600); color: var(--blue-600); }
  .usuario-action-btn.danger:hover { border-color: #dc2626; color: #dc2626; }
  .usuario-empty, .usuario-loading {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 42px 16px; color: var(--gray-500); font-size: 13px;
  }
  .usuario-loading i { animation: spin 0.9s linear infinite; }
  .usuario-form { display: flex; flex-direction: column; gap: 14px; }
  .usuario-field { display: flex; flex-direction: column; gap: 5px; }
  .usuario-label {
    font-size: 12px; font-weight: 600; color: var(--gray-600);
    text-transform: uppercase; letter-spacing: 0.04em;
  }
  .usuario-input {
    width: 100%; min-height: 38px; padding: 8px 10px;
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    background: var(--white); color: var(--gray-950); font: inherit; outline: none;
  }
  .usuario-select {
    width: 100%; height: 38px; padding: 0 10px;
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    background: var(--white); color: var(--gray-950); font: inherit; outline: none;
  }
  .usuario-input:focus { border-color: var(--blue-600); }
  .usuario-select:focus { border-color: var(--blue-600); }
  .usuario-help { font-size: 12px; color: var(--gray-500); }
  .usuario-error {
    display: none; padding: 10px 12px; border-radius: var(--radius-sm);
    background: #fef2f2; color: #b91c1c; font-size: 13px;
  }
  .permissoes-wrap {
    display: flex; flex-direction: column; gap: 8px;
    border: 1px solid var(--gray-200); border-radius: var(--radius-sm);
    padding: 8px 10px; background: var(--white);
  }
  .permissoes-tags { display: flex; flex-wrap: wrap; gap: 6px; min-height: 24px; }
  .permissao-tag {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 8px; border-radius: 20px; background: #eff6ff;
    color: #1d4ed8; font-size: 12px; font-weight: 500;
  }
  .permissao-tag button {
    border: none; background: transparent; color: inherit; cursor: pointer;
    padding: 0; display: inline-flex; font-size: 13px; line-height: 1;
  }
  .permissoes-empty { font-size: 12px; color: var(--gray-400); }
  [data-theme="dark"] .permissoes-wrap {
    background: #21262d; border-color: #30363d;
  }
  [data-theme="dark"] .permissao-tag { background: #0d2340; color: #79c0ff; }
  [data-theme="dark"] .usuario-input,
  [data-theme="dark"] .usuario-select {
    background: #21262d; border-color: #30363d; color: #e6edf3;
  }
  [data-theme="dark"] .usuarios-filtro-select {
    background: #21262d; border-color: #30363d; color: #e6edf3;
  }
  [data-theme="dark"] .usuario-action-btn {
    background: #21262d; border-color: #30363d; color: #e6edf3;
  }
  [data-theme="dark"] .usuario-error { background: #2d1117; color: #ff7b72; }
</style>
@endsection

@section('content')
<div class="usuarios-page">
  <div class="usuarios-actions">
    <button type="button" class="btn-primary" onclick="abrirModalUsuario()">
      <i class="ti ti-user-plus"></i> Novo usuário
    </button>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Usuários cadastrados</span>
      <span class="card-action">total: <span id="total-usuarios">0</span></span>
    </div>
    <div class="usuarios-filtros">
      <span class="usuarios-filtro-label">Função</span>
      <select id="filtro-funcao" class="usuarios-filtro-select" onchange="aplicarFiltroFuncao()">
        <option value="">Todos</option>
        <option value="projetista">Usuário</option>
        <option value="tecnico">Téc</option>
      </select>
    </div>
    <div class="usuarios-table-wrap" id="usuarios-wrap">
      <div class="usuario-loading"><i class="ti ti-loader-2"></i> Carregando usuários...</div>
    </div>
  </div>
</div>

<x-modal
  id="modal-usuario"
  titulo="Novo usuário"
  subtitulo="Crie um acesso para o Planner"
  titulo-id="usuario-modal-titulo"
  subtitulo-id="usuario-modal-subtitulo"
  fechar="fecharModalUsuario()">
  <div class="usuario-form">
    <div class="usuario-error" id="usuario-erro"></div>

    <div class="usuario-field">
      <label class="usuario-label" for="usuario-username">Usuário</label>
      <input type="text" id="usuario-username" class="usuario-input" autocomplete="off"
        placeholder="Ex: joao.silva"/>
      <span class="usuario-help">Use letras, números, ponto, hífen ou underline.</span>
    </div>

    <div class="usuario-field">
      <label class="usuario-label" for="usuario-funcao">Função</label>
      <select id="usuario-funcao" class="usuario-select">
        <option value="projetista">Projetista</option>
        <option value="tecnico">Técnico</option>
      </select>
      <span class="usuario-help">Ao escolher Técnico, o usuário também entra na lista de técnicos.</span>
    </div>

    <div class="usuario-field" id="usuario-campos-senha">
      <label class="usuario-label" for="usuario-password">Senha</label>
      <input type="password" id="usuario-password" class="usuario-input" autocomplete="new-password"
        placeholder="Mínimo de 4 caracteres"/>
      <span class="usuario-help" id="usuario-password-help">Informe a senha do novo usuário.</span>
    </div>

    <div class="usuario-field" id="usuario-campo-confirmar-senha">
      <label class="usuario-label" for="usuario-password-confirmation">Confirmar senha</label>
      <input type="password" id="usuario-password-confirmation" class="usuario-input" autocomplete="new-password"/>
    </div>

    <div class="usuario-field" id="usuario-campo-regiao" style="display:none">
      <label class="usuario-label" for="usuario-regiao">Região</label>
      <select id="usuario-regiao" class="usuario-select">
        <option value="">Selecione a região</option>
        <option value="Governador Valadares">Governador Valadares</option>
        <option value="Vale do Aço">Vale do Aço</option>
      </select>
      <span class="usuario-help">A região será vinculada ao cadastro do técnico.</span>
    </div>

    <div class="usuario-field" id="usuario-campo-permissoes" style="display:none">
      <label class="usuario-label" for="usuario-permissoes-select">Permissões</label>
      <div class="permissoes-wrap">
        <div class="permissoes-tags" id="permissoes-tags"></div>
        <select id="usuario-permissoes-select" class="usuario-select" onchange="adicionarPermissaoUsuario(this.value)">
          <option value="">Adicionar permissão...</option>
        </select>
      </div>
      <span class="usuario-help">Selecione as permissões que este usuário terá no sistema.</span>
    </div>
  </div>

  <x-slot name="footer">
    <button type="button" onclick="fecharModalUsuario()" class="btn-modal btn-modal-ghost">Cancelar</button>
    <button type="button" onclick="salvarUsuario()" class="btn-modal btn-modal-primary" id="btn-salvar-usuario">
      <i class="ti ti-user-plus" style="font-size:14px"></i> Criar usuário
    </button>
  </x-slot>
</x-modal>
@endsection

@section('scripts')
<script>
  if (typeof window.plannerPossuiPermissao === 'function' && !window.plannerPossuiPermissao('visualizar_aba_usuarios')) {
    window.location.replace('/dashboard');
  }
</script>
<script type="module">
  let usuarioEditando = null;
  let todosUsuarios = [];
  let permissoesDisponiveis = [];
  let permissoesSelecionadas = [];

  function token() {
    return localStorage.getItem('planner_token');
  }

  function esc(valor) {
    if (valor == null || valor === '') return '—';
    return String(valor).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function formatarData(valor) {
    if (!valor) return '—';
    const data = new Date(valor);
    if (isNaN(data.getTime())) return esc(valor);
    return data.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
  }

  function mostrarErro(mensagem) {
    const el = document.getElementById('usuario-erro');
    el.textContent = mensagem || 'Não foi possível criar o usuário.';
    el.style.display = 'block';
  }

  function limparFormularioUsuario() {
    usuarioEditando = null;
    document.getElementById('usuario-username').value = '';
    document.getElementById('usuario-funcao').value = 'projetista';
    document.getElementById('usuario-password').value = '';
    document.getElementById('usuario-password-confirmation').value = '';
    document.getElementById('usuario-regiao').value = '';
    document.getElementById('usuario-erro').style.display = 'none';
    document.getElementById('usuario-campo-permissoes').style.display = 'none';
    permissoesSelecionadas = [];
    renderPermissoesTags();
    atualizarSelectPermissoes();
    document.getElementById('usuario-modal-titulo').textContent = 'Novo usuário';
    document.getElementById('usuario-modal-subtitulo').textContent = 'Crie um acesso para o Planner';
    document.getElementById('usuario-password').placeholder = 'Mínimo de 4 caracteres';
    document.getElementById('usuario-password-help').textContent = 'Informe a senha do novo usuário.';
    document.getElementById('btn-salvar-usuario').innerHTML = '<i class="ti ti-user-plus" style="font-size:14px"></i> Criar usuário';
    atualizarCamposFuncao();
  }

  function labelPermissao(key) {
    const item = permissoesDisponiveis.find(p => p.key === key);
    return item ? item.label : key;
  }

  function renderPermissoesTags() {
    const wrap = document.getElementById('permissoes-tags');
    if (!permissoesSelecionadas.length) {
      wrap.innerHTML = '<span class="permissoes-empty">Nenhuma permissão selecionada.</span>';
      return;
    }

    wrap.innerHTML = permissoesSelecionadas.map(key => `
      <span class="permissao-tag">
        ${esc(labelPermissao(key))}
        <button type="button" title="Remover" onclick="removerPermissaoUsuario('${esc(key)}')">
          <i class="ti ti-x"></i>
        </button>
      </span>
    `).join('');
  }

  function todasPermissoesSelecionadas() {
    return permissoesDisponiveis.length > 0
      && permissoesDisponiveis.every(item => permissoesSelecionadas.includes(item.key));
  }

  function selecionarTodasPermissoes() {
    permissoesSelecionadas = permissoesDisponiveis.map(item => item.key);
    renderPermissoesTags();
    atualizarSelectPermissoes();
  }

  function atualizarSelectPermissoes() {
    const select = document.getElementById('usuario-permissoes-select');
    const opcoes = permissoesDisponiveis
      .filter(item => !permissoesSelecionadas.includes(item.key))
      .map(item => `<option value="${esc(item.key)}">${esc(item.label)}</option>`)
      .join('');

    const opcaoTodas = !todasPermissoesSelecionadas()
      ? '<option value="__todas__">Todas permissões</option>'
      : '';

    select.innerHTML = `<option value="">Adicionar permissão...</option>${opcaoTodas}${opcoes}`;
    select.value = '';
  }

  window.adicionarPermissaoUsuario = function (key) {
    if (!key) return;
    if (key === '__todas__') {
      selecionarTodasPermissoes();
      return;
    }
    if (permissoesSelecionadas.includes(key)) return;
    permissoesSelecionadas.push(key);
    renderPermissoesTags();
    atualizarSelectPermissoes();
  };

  window.removerPermissaoUsuario = function (key) {
    permissoesSelecionadas = permissoesSelecionadas.filter(item => item !== key);
    renderPermissoesTags();
    atualizarSelectPermissoes();
  };

  function definirPermissoesSelecionadas(permissoes) {
    permissoesSelecionadas = Array.isArray(permissoes) ? [...permissoes] : [];
    renderPermissoesTags();
    atualizarSelectPermissoes();
  }

  function atualizarCamposFuncao() {
    const funcao = document.getElementById('usuario-funcao').value;
    const ehTecnico = funcao === 'tecnico';

    document.getElementById('usuario-campos-senha').style.display = ehTecnico ? 'none' : 'flex';
    document.getElementById('usuario-campo-confirmar-senha').style.display = ehTecnico ? 'none' : 'flex';
    document.getElementById('usuario-campo-regiao').style.display = ehTecnico ? 'flex' : 'none';

    const password = document.getElementById('usuario-password');
    const help = document.getElementById('usuario-password-help');

    if (ehTecnico) {
      password.value = '';
      document.getElementById('usuario-password-confirmation').value = '';
      return;
    }

    if (usuarioEditando) {
      password.placeholder = 'Deixe em branco para manter a senha atual';
      help.textContent = 'Preencha apenas se quiser trocar a senha.';
      return;
    }

    password.placeholder = 'Mínimo de 4 caracteres';
    help.textContent = 'Informe a senha do novo usuário.';
  }

  window.abrirModalUsuario = function () {
    limparFormularioUsuario();
    document.getElementById('usuario-campo-permissoes').style.display = 'flex';
    document.getElementById('modal-usuario').classList.add('open');
    setTimeout(() => document.getElementById('usuario-username').focus(), 0);
  };

  window.abrirEditarUsuario = function (username) {
    const usuario = todosUsuarios.find(item => item.username === username);
    if (!usuario) return;

    limparFormularioUsuario();
    usuarioEditando = username;
    document.getElementById('usuario-modal-titulo').textContent = 'Editar usuário';
    document.getElementById('usuario-modal-subtitulo').textContent = username;
    document.getElementById('usuario-username').value = username;
    document.getElementById('usuario-funcao').value = usuario.funcao === 'tecnico' ? 'tecnico' : 'projetista';
    document.getElementById('usuario-regiao').value = usuario.regiao || '';
    document.getElementById('usuario-campo-permissoes').style.display = 'flex';
    definirPermissoesSelecionadas(usuario.permissoes || []);
    document.getElementById('btn-salvar-usuario').innerHTML = '<i class="ti ti-device-floppy" style="font-size:14px"></i> Salvar alterações';
    atualizarCamposFuncao();
    document.getElementById('modal-usuario').classList.add('open');
    setTimeout(() => document.getElementById('usuario-username').focus(), 0);
  };

  window.fecharModalUsuario = function () {
    document.getElementById('modal-usuario').classList.remove('open');
  };

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
      const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
      throw new Error(errors || data.message || 'Erro na requisição.');
    }
    return data;
  }

  function renderUsuarios(usuarios) {
    const wrap = document.getElementById('usuarios-wrap');
    document.getElementById('total-usuarios').textContent = usuarios.length;

    if (!usuarios.length) {
      const filtro = document.getElementById('filtro-funcao').value;
      const msg = filtro
        ? 'Nenhum usuário encontrado para este filtro.'
        : 'Nenhum usuário cadastrado.';
      wrap.innerHTML = `<div class="usuario-empty">${msg}</div>`;
      return;
    }

    wrap.innerHTML = `
      <table class="usuarios-table">
        <thead>
          <tr>
            <th>Usuário</th>
            <th>Função</th>
            <th>Região</th>
            <th>Permissões</th>
            <th>Criado em</th>
            <th style="text-align:right">Ações</th>
          </tr>
        </thead>
        <tbody>
          ${usuarios.map(usuario => `
            <tr>
              <td>
                <div class="usuario-name">${esc(usuario.username)}</div>
                <div class="usuario-muted">Acesso ao Planner</div>
              </td>
              <td>${usuario.funcao === 'tecnico' ? 'Técnico' : 'Projetista'}</td>
              <td>${usuario.funcao === 'tecnico' ? esc(usuario.regiao) : '—'}</td>
              <td>${(usuario.permissoes || []).length
                ? (usuario.permissoes || []).map(key => esc(labelPermissao(key))).join(', ')
                : '—'}</td>
              <td>${formatarData(usuario.created_at)}</td>
              <td>
                <div class="usuario-acoes">
                  <button type="button" class="usuario-action-btn" onclick="abrirEditarUsuario('${esc(usuario.username)}')">
                    <i class="ti ti-pencil"></i> Editar
                  </button>
                  <button type="button" class="usuario-action-btn danger" onclick="excluirUsuario('${esc(usuario.username)}')">
                    <i class="ti ti-trash"></i> Excluir
                  </button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>`;
  }

  function filtrarUsuarios() {
    const funcao = document.getElementById('filtro-funcao').value;
    if (!funcao) return todosUsuarios;
    return todosUsuarios.filter(usuario => usuario.funcao === funcao);
  }

  window.aplicarFiltroFuncao = function () {
    renderUsuarios(filtrarUsuarios());
  };

  async function carregarUsuarios() {
    const wrap = document.getElementById('usuarios-wrap');
    wrap.innerHTML = '<div class="usuario-loading"><i class="ti ti-loader-2"></i> Carregando usuários...</div>';
    try {
      const data = await requestJson('/api/usuarios');
      todosUsuarios = data.usuarios || [];
      permissoesDisponiveis = data.permissoes_disponiveis || [];
      atualizarSelectPermissoes();
      renderUsuarios(filtrarUsuarios());
    } catch (err) {
      wrap.innerHTML = `<div class="usuario-empty" style="color:#dc2626">${esc(err.message)}</div>`;
    }
  }

  window.salvarUsuario = async function () {
    const btn = document.getElementById('btn-salvar-usuario');
    const username = document.getElementById('usuario-username').value.trim();
    const funcao = document.getElementById('usuario-funcao').value;
    const regiao = document.getElementById('usuario-regiao').value;
    const password = document.getElementById('usuario-password').value;
    const passwordConfirmation = document.getElementById('usuario-password-confirmation').value;

    document.getElementById('usuario-erro').style.display = 'none';

    const senhaObrigatoria = !usuarioEditando && funcao === 'projetista';

    if (!username || (senhaObrigatoria && (!password || !passwordConfirmation))) {
      mostrarErro('Preencha usuário, senha e confirmação.');
      return;
    }

    if (funcao === 'tecnico' && !regiao) {
      mostrarErro('Selecione a região do técnico.');
      return;
    }

    if (funcao !== 'tecnico' && (password || passwordConfirmation)) {
      if (!password || !passwordConfirmation) {
        mostrarErro('Preencha senha e confirmação.');
        return;
      }
      if (password.length < 4) {
        mostrarErro('A senha precisa ter pelo menos 4 caracteres.');
        return;
      }
      if (password !== passwordConfirmation) {
        mostrarErro('A confirmação da senha não confere.');
        return;
      }
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader-2" style="font-size:14px"></i> Salvando...';

    const payload = { username, funcao };
    if (funcao === 'tecnico') {
      payload.regiao = regiao;
    } else if (password || passwordConfirmation) {
      payload.password = password;
      payload.password_confirmation = passwordConfirmation;
    }
    payload.permissoes = [...permissoesSelecionadas];

    try {
      await requestJson(usuarioEditando ? `/api/usuarios/${encodeURIComponent(usuarioEditando)}` : '/api/usuarios', {
        method: usuarioEditando ? 'PUT' : 'POST',
        body: JSON.stringify(payload),
      });
      fecharModalUsuario();
      await carregarUsuarios();
    } catch (err) {
      mostrarErro(err.message);
    } finally {
      btn.disabled = false;
      btn.innerHTML = usuarioEditando
        ? '<i class="ti ti-device-floppy" style="font-size:14px"></i> Salvar alterações'
        : '<i class="ti ti-user-plus" style="font-size:14px"></i> Criar usuário';
    }
  };

  window.excluirUsuario = async function (username) {
    if (!confirm(`Excluir o usuário "${username}"? Esta ação não pode ser desfeita.`)) return;

    try {
      await requestJson(`/api/usuarios/${encodeURIComponent(username)}`, { method: 'DELETE' });
      await carregarUsuarios();
    } catch (err) {
      alert(err.message || 'Não foi possível excluir o usuário.');
    }
  };

  window.abrirNovoItem = window.abrirModalUsuario;
  document.getElementById('usuario-funcao').addEventListener('change', atualizarCamposFuncao);

  carregarUsuarios();
</script>
@endsection
