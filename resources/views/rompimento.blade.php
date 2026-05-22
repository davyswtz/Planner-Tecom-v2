@extends('layouts.app')

@section('title', 'Rompimentos — Planner Telecom')
@section('page-title', 'Rompimentos')
@section('btn-label', 'Novo rompimento')

@section('styles')
  .kcard[draggable="true"] {
    cursor: grab;
    user-select: none;
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                box-shadow 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                border-color 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: transform;
  }
  .kcard[draggable="true"]:active { cursor: grabbing; }
  @media (pointer: coarse) {
    .kcard { cursor: pointer; }
  }
  .kcard-dragging {
    opacity: 0.55;
    cursor: grabbing;
    transform: scale(1.02) rotate(1deg);
    box-shadow: 0 10px 28px rgba(22,106,196,0.16);
    transition: none;
  }
  .kcol-body {
    transition: background 0.28s cubic-bezier(0.16, 1, 0.3, 1),
                outline-color 0.28s cubic-bezier(0.16, 1, 0.3, 1),
                outline-offset 0.28s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .kcol-body.drag-over {
    background: var(--blue-50);
    outline: 2px dashed var(--blue-600);
    outline-offset: -4px;
    border-radius: var(--radius-sm);
  }
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
    transition: background 0.32s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.32s;
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
    transition: transform 0.38s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.38s cubic-bezier(0.16, 1, 0.3, 1);
    will-change: transform, opacity;
  }
  .modal-overlay.open .modal-box {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
  .modal-head { padding: 16px 24px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
  .modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
  .modal-foot { padding: 14px 24px; border-top: 1px solid var(--gray-200); display: flex; justify-content: flex-end; gap: 8px; flex-shrink: 0; }
  .modal-title { font-size: 15px; font-weight: 600; color: var(--gray-950); margin: 0; }
  .modal-sub { font-size: 12px; color: var(--gray-500); margin: 0; }
  .modal-close { background: transparent; border: none; cursor: pointer; color: var(--gray-500); font-size: 18px; display: flex; align-items: center; padding: 4px; transition: color 0.15s ease, transform 0.15s ease; }
  .modal-close:hover { color: var(--gray-950); transform: scale(1.08); }
  .detail-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
  .detail-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .detail-field { display: flex; flex-direction: column; gap: 5px; }
  .detail-field.span-2 { grid-column: span 2; }
  .detail-field.span-3 { grid-column: span 3; }
  .detail-label { font-size: 12px; font-weight: 500; color: var(--gray-500); }
  .detail-value { border: 1px solid var(--gray-200); border-radius: var(--radius-sm); padding: 8px 10px; min-height: 38px; font-size: 13px; color: var(--gray-950); background: var(--gray-50); line-height: 1.4; word-break: break-word; }
  .detail-badges { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; min-height: 38px; }
  .detail-loading { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 40px 0; color: var(--gray-500); font-size: 13px; }
  .detail-loading i { animation: spin 0.9s cubic-bezier(0.4, 0, 0.2, 1) infinite; }
  .detail-error { padding: 16px; border-radius: var(--radius-sm); background: var(--red-bg); color: var(--red-text); font-size: 13px; }
  .detail-enter { animation: conteudoEntrada 0.42s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
  .btn-modal { padding: 0 16px; height: 36px; border-radius: var(--radius-sm); font-size: 13px; cursor: pointer; font-family: inherit; transition: background 0.15s ease, transform 0.15s ease, border-color 0.15s ease; }
  .btn-modal:active { transform: scale(0.97); }
  .btn-modal-ghost { border: 1px solid var(--gray-200); background: transparent; color: var(--gray-500); }
  .btn-modal-ghost:hover { background: var(--gray-50); border-color: var(--gray-400); }
  .btn-modal-primary { border: none; background: #166ac4; color: #fff; font-weight: 500; }
  .btn-modal-primary:hover { background: #0d5aaa; }
  @keyframes conteudoEntrada {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
  @media (max-width: 768px) {
    .detail-grid, .detail-grid-2 { grid-template-columns: 1fr; }
    .detail-field.span-2, .detail-field.span-3 { grid-column: span 1; }
  }
  @media (prefers-reduced-motion: reduce) {
    .kcard[draggable="true"], .kcol-body, .modal-overlay, .modal-box, .modal-close, .btn-modal { transition: none; }
    .detail-enter, .detail-loading i { animation: none; }
    .modal-overlay.open .modal-box { opacity: 1; transform: none; }
  }
@endsection

@section('content')

<!--MODAL CRIAR-->
<div id="modal-overlay" class="modal-overlay">
  <div class="modal-box">

    <div class="modal-head">
      <div>
        <p class="modal-title">Novo rompimento</p>
        <p class="modal-sub">Preencha os dados do rompimento</p>
      </div>
      <button onclick="fecharModal()" class="modal-close"><i class="ti ti-x"></i></button>
    </div>

    <div class="modal-body" style="display:flex;flex-direction:column;gap:16px">

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div>
          <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Elemento(s)</label>
          <input type="text" id="input-cto" placeholder="Ex: GVA1210" oninput="this.value = this.value.toUpperCase(); buscarCTO(this.value)" style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none"/>
          <p style="font-size:11px;color:var(--gray-400);margin-top:4px">Coordenadas preenchidas automaticamente</p>
        </div>
        <div>
          <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Tipo de rompimento</label>
          <select id="input-tipo" style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none;background:var(--white)">
            <option value="">Selecione...</option>
            <option>Fibra cortada</option>
            <option>CTO offline</option>
            <option>Queda de sinal</option>
            <option>OLT offline</option>
            <option>Cabo subterrâneo</option>
          </select>
        </div>
        <div>
          <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Região</label>
          <select id="input-regiao" onchange="carregarTecnicos(this.value)" style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none;background:var(--white)">
            <option value="">Selecione...</option>
            <option>Goval</option>
            <option>Vale do Aço</option>
            <option>Caratinga</option>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      <div>
        <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Técnico(s) responsável(is)</label>
        <div id="tecnicos-wrap" style="position:relative;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:6px 10px;min-height:38px;display:flex;flex-wrap:wrap;gap:5px;align-items:center;cursor:text">
           <span id="tecnicos-tags"></span>
            <input id="input-tec" type="text" placeholder="Selecione uma região primeiro..." readonly
             style="border:none;outline:none;font-size:12px;background:transparent;flex:1;min-width:80px;box-shadow:none;height:24px;font-family:inherit;cursor:pointer"
            onclick="toggleDropdownTecnicos()"/>
        <div id="dropdown-tecnicos" style="display:none;position:absolute;top:100%;left:0;right:0;margin-top:4px;background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius-sm);z-index:200;max-height:180px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,0.1)"></div>
        </div>
      </div>
        <div>
          <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Cliente(s) afetado(s)</label>
          <input type="number" id="input-clientes" placeholder="0" min="0" style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none"/>
        </div>
      </div>

      <div>
        <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:8px">Prioridade</label>
        <div style="display:flex;gap:8px">
          <button onclick="selecionarPrioridade(this,'Baixa')" class="btn-prioridade" style="flex:1;padding:8px 0;border-radius:var(--radius-sm);border:1px solid #86efac;background:#f0fdf4;color:#166534;font-size:13px;font-weight:500;cursor:pointer">Baixa</button>
          <button onclick="selecionarPrioridade(this,'Média')" class="btn-prioridade" style="flex:1;padding:8px 0;border-radius:var(--radius-sm);border:2px solid var(--amber);background:var(--amber-bg);color:var(--amber-text);font-size:13px;font-weight:500;cursor:pointer">Média ✓</button>
          <button onclick="selecionarPrioridade(this,'Alta')" class="btn-prioridade" style="flex:1;padding:8px 0;border-radius:var(--radius-sm);border:1px solid #fca5a5;background:var(--red-bg);color:var(--red-text);font-size:13px;font-weight:500;cursor:pointer">Alta</button>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Coordenadas</label>
          <input id="input-coords" type="text" placeholder="Preenchido pela CTO automaticamente" readonly style="width:100%;height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:0 10px;font-size:13px;font-family:inherit;outline:none;background:var(--gray-50);color:var(--gray-500)"/>
        </div>
        <div>
          <label style="font-size:12px;font-weight:500;color:var(--gray-500);display:block;margin-bottom:5px">Endereço</label>
          <div id="endereco-box" style="border:1px solid var(--gray-200);border-radius:var(--radius-sm);padding:8px 10px;min-height:38px;font-size:13px;color:var(--gray-400);background:var(--gray-50)">
            Gerado pelas coordenadas...
          </div>
        </div>
      </div>

    </div>

    <div class="modal-foot">
      <button onclick="fecharModal()" class="btn-modal btn-modal-ghost">Cancelar</button>
      <button onclick="criarRompimento()" class="btn-modal btn-modal-primary" style="display:flex;align-items:center;gap:6px">
        <i class="ti ti-bolt" style="font-size:14px"></i> Criar rompimento
      </button>
    </div>

  </div>
</div>

<!--MODAL DETALHE-->
<div id="detalhe-overlay" class="modal-overlay">
  <div class="modal-box">

    <div class="modal-head">
      <div>
        <p class="modal-title" id="detalhe-titulo">Rompimento</p>
        <p class="modal-sub" id="detalhe-subtitulo"></p>
      </div>
      <button onclick="fecharDetalhe()" class="modal-close"><i class="ti ti-x"></i></button>
    </div>

    <div class="modal-body" id="detalhe-conteudo"></div>

    <div class="modal-foot">
      <button onclick="fecharDetalhe()" class="btn-modal btn-modal-ghost">Fechar</button>
    </div>

  </div>
</div>

    <div class="card" style="flex:1">
        <div class="card-header">
            <span class="card-title">Kanban de Rompimentos</span>
            <span class="card-action">total: <span id="total-rompimentos">0</span></span>
        </div>
        <div class="kanban-cols">
            <div class="kcol">
                <div class="kcol-head">
                    <div class="kcol-name"><div class="dot d-blue"></div> Criada</div>
                    <span class="kcol-count" id="count-criada">0</span>
                </div>
                <div class="kcol-body" id="col-criada" data-status="Criada"></div>
            </div>
            <div class="kcol">
                <div class="kcol-head">
                    <div class="kcol-name"><div class="dot d-amber"></div> Em andamento</div>
                    <span class="kcol-count" id="count-andamento">0</span>
                </div>
                <div class="kcol-body" id="col-andamento" data-status="Em andamento"></div>
            </div>
            <div class="kcol">
                <div class="kcol-head">
                    <div class="kcol-name"><div class="dot d-red"></div> Impedimento</div>
                    <span class="kcol-count" id="count-impedimento">0</span>
                </div>
                <div class="kcol-body" id="col-impedimento" data-status="Impedimento"></div>
            </div>
            <div class="kcol">
                <div class="kcol-head">
                    <div class="kcol-name"><div class="dot d-green"></div> Finalizada</div>
                    <span class="kcol-count" id="count-finalizada">0</span>
                </div>
                <div class="kcol-body" id="col-finalizada" data-status="Finalizada"></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

<script>

let tecnicosSelecionados = [];

async function carregarTecnicos(regiao) {
    if (!regiao) return;

    document.getElementById('input-tec').placeholder = 'Carregando...';

    const token = localStorage.getItem('planner_token');
    const res = await fetch(`/api/tecnicos?regiao=${regiao}`, {
        headers: { 'Authorization': 'Bearer ' + token }
    });
    const tecnicos = await res.json();

    const dropdown = document.getElementById('dropdown-tecnicos');
    dropdown.innerHTML = tecnicos.map(t => `
        <div onclick="selecionarTecnico(${t.id}, '${t.nome}')"
            style="padding:8px 12px;cursor:pointer;font-size:13px;color:var(--gray-950)"
            onmouseover="this.style.background='var(--gray-50)'"
            onmouseout="this.style.background='transparent'">
            ${t.nome}
        </div>
    `).join('');

    document.getElementById('input-tec').placeholder = tecnicos.length ? 'Selecionar técnico...' : 'Nenhum técnico nessa região';
}

function toggleDropdownTecnicos() {
    const dropdown = document.getElementById('dropdown-tecnicos');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

function selecionarTecnico(id, nome) {
    if (tecnicosSelecionados.find(t => t.id === id)) return;

    tecnicosSelecionados.push({ id, nome });
    renderizarTags();
    document.getElementById('dropdown-tecnicos').style.display = 'none';
}

function removerTecnico(id) {
    tecnicosSelecionados = tecnicosSelecionados.filter(t => t.id !== id);
    renderizarTags();
}

function renderizarTags() {
    document.getElementById('tecnicos-tags').innerHTML = tecnicosSelecionados.map(t => `
        <span style="background:#e8f2fc;color:#0c447c;font-size:11px;font-weight:500;padding:3px 8px;border-radius:20px;display:inline-flex;align-items:center;gap:4px">
            ${t.nome}
            <i class="ti ti-x" style="font-size:10px;cursor:pointer" onclick="removerTecnico(${t.id})"></i>
        </span>
    `).join('');
}

document.addEventListener('click', function(e) {
    const wrap = document.getElementById('tecnicos-wrap');
    const dropdown = document.getElementById('dropdown-tecnicos');
    if (wrap && !wrap.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

    let prioridadeSelecionada = 'Média';

function fecharModal() {
    document.getElementById('modal-overlay').classList.remove('open');
}

function abrirModal() {
    document.getElementById('modal-overlay').classList.add('open');
}

function fecharDetalhe() {
    document.getElementById('detalhe-overlay').classList.remove('open');
}

window.abrirModal = abrirModal;
window.fecharDetalhe = fecharDetalhe;

document.getElementById('modal-overlay').addEventListener('click', function(e) {
    if (e.target.id === 'modal-overlay') fecharModal();
});

document.getElementById('detalhe-overlay').addEventListener('click', function(e) {
    if (e.target.id === 'detalhe-overlay') fecharDetalhe();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharDetalhe();
        fecharModal();
    }
});

function selecionarPrioridade(btn, nivel) {
    document.querySelectorAll('.btn-prioridade').forEach(b => {
        b.textContent = b.textContent.replace(' ✓', '');
        b.style.borderWidth = '1px';
    });
    btn.style.borderWidth = '2px';
    btn.textContent += ' ✓';
    prioridadeSelecionada = nivel;
}

const  CTO_SOURCES = [
  '{{ asset("/json/cto-gv-viabilidade.json") }}',
  '{{ asset("/json/cto-ipatinga-viabilidade.json") }}'
];

let CTOs = [];

async function carregarCTOs() {
  for (const url of CTO_SOURCES) {
        try {
            const res = await fetch(url);
            const data = await res.json();
            CTOs = CTOs.concat(data);
        } catch (e) {
            console.warn('Erro ao carregar CTOs de:', url);
        }
    }
    console.log(`Total de CTOs carregadas: ${CTOs.length}`);
}

function buscarCTO(valor) {
    const termo = valor.trim().toUpperCase();

    if (termo.length < 3) {
        document.getElementById('input-coords').value = '';
        document.getElementById('endereco-box').textContent = 'Gerado pelas coordenadas...';
        return;
    }

    const encontrada = CTOs.find(cto => cto.nome && cto.nome.toUpperCase() === termo);

    if (encontrada) {
        document.getElementById('input-coords').value = `${encontrada.lat}, ${encontrada.lng}`;
        document.getElementById('endereco-box').textContent = 'Buscando endereço...';
        buscarEndereco(encontrada.lat, encontrada.lng);
    } else {
        document.getElementById('input-coords').value = '';
        document.getElementById('endereco-box').textContent = 'CTO não encontrada — preencha manualmente';
    }
}

async function buscarEndereco(lat, lng) {
  try {
    const res = await fetch(
      `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`, { headers: { 'Accept-Language': 'pt-BR' } }
    );
    const data = await res.json();
    document.getElementById('endereco-box').textContent = data.display_name || 'Não encontrado';
  }catch(e){
    document.getElementById('endereco-box').textContent = 'Erro ao buscar endereço';
  }
}

carregarCTOs();

async function criarRompimento() {

  const dados = {
    titulo: `Rompimento — ${document.getElementById('input-cto').value}`,
    cto: document.getElementById('input-cto').value,
    descricao: document.getElementById('input-tipo').value,
    regiao: document.getElementById('input-regiao').value,
    responsavel: tecnicosSelecionados.map(t => t.nome).join(', '),
    clientesAfetados: document.getElementById('input-clientes').value,
    prioridade: prioridadeSelecionada,
    coordenadas: document.getElementById('input-coords').value,
    localizacao_texto: document.getElementById('endereco-box').textContent,
    status: 'Criada',
    categoria: 'rompimentos'
  }

  const token = localStorage.getItem('planner_token');
  const response = await fetch('/api/rompimentos',{
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(dados)
  })

  const resultado = await response.json();
  if(response.ok){
    fecharModal();
    carregarRompimentos();
  }else{
    console.error('Erro ao criar rompimento:', resultado.message);
  }
}

</script>

<script type="module">

    let draggedId = null;
    let draggedStatus = null;
    let wasDragged = false;

    const isTouchDevice = window.matchMedia('(pointer: coarse)').matches;

    async function carregarRompimentos() {
        const token = localStorage.getItem('planner_token');
        const response = await fetch('/api/rompimentos', {
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        const rompimentos = data.rompimentos || data;

        const criadas    = rompimentos.filter(r => r.status === 'Criada').slice(0, 10);
        const andamento  = rompimentos.filter(r => r.status === 'Em andamento').slice(0, 10);
        const impedimento = rompimentos.filter(r => r.status === 'Impedimento').slice(0, 10);
        const finalizadas = rompimentos.filter(r => r.status === 'Finalizada').slice(0, 10);

        document.getElementById('col-criada').innerHTML = criadas.map(renderCard).join('');
        document.getElementById('col-andamento').innerHTML = andamento.map(renderCard).join('');
        document.getElementById('col-impedimento').innerHTML = impedimento.map(renderCard).join('');
        document.getElementById('col-finalizada').innerHTML = finalizadas.map(renderCard).join('');
        document.getElementById('count-criada').textContent = criadas.length;
        document.getElementById('count-andamento').textContent = andamento.length;
        document.getElementById('count-impedimento').textContent = impedimento.length;
        document.getElementById('count-finalizada').textContent = finalizadas.length;
        document.getElementById('total-rompimentos').textContent = rompimentos.length;
    }
    window.carregarRompimentos = carregarRompimentos;

    function renderCard(r) {
        const prioridadeClass = r.prioridade?.toLowerCase() === 'alta' ? 'b-alta'
            : r.prioridade?.toLowerCase() === 'baixa' ? 'b-baixa'
            : 'b-media';
        const regiaoClass = r.regiao && r.regiao.toLowerCase().includes('vale')
            ? 'b-regiao-va' : 'b-regiao-gv';

        return `
        <div class="kcard"${isTouchDevice ? '' : ' draggable="true"'} data-id="${r.id}" data-status="${r.status}">
            <div class="kcard-title">${r.titulo}</div>
            <div class="kcard-foot">
                <span class="badge ${prioridadeClass}">${r.prioridade || 'Média'}</span>
                <span class="badge ${regiaoClass}">${r.regiao || 'Sem região'}</span>
                <span class="kcard-code">${r.taskCode || 'S/C'}</span>
            </div>
            ${r.cto ? `<div class="kcard-meta"><span style="font-size:10px;color:var(--gray-500)">CTO: ${r.cto}</span></div>` : ''}
            ${r.clientesAfetados ? `<div class="kcard-meta"><span style="font-size:10px;color:var(--gray-500)">👥 ${r.clientesAfetados} clientes</span></div>` : ''}
        </div>`;
    }

    function atualizarContadores() {
        ['criada', 'andamento', 'impedimento', 'finalizada'].forEach(col => {
            const body = document.getElementById(`col-${col}`);
            document.getElementById(`count-${col}`).textContent = body.querySelectorAll('.kcard').length;
        });
    }

    function limparDragOver() {
        document.querySelectorAll('.kcol-body.drag-over').forEach(el => el.classList.remove('drag-over'));
    }

    async function moverRompimento(id, novoStatus, colDestino) {
        const card = document.querySelector(`.kcard[data-id="${id}"]`);
        const colOrigem = card?.closest('.kcol-body');
        const statusAnterior = card?.dataset.status;

        if (card) {
            card.dataset.status = novoStatus;
            colDestino.appendChild(card);
            atualizarContadores();
        }

        const token = localStorage.getItem('planner_token');
        const response = await fetch(`/api/rompimentos/${id}`, {
            method: 'PUT',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: novoStatus })
        });

        if (!response.ok) {
            if (card && colOrigem && statusAnterior) {
                card.dataset.status = statusAnterior;
                colOrigem.appendChild(card);
                atualizarContadores();
            } else {
                carregarRompimentos();
            }
        }
    }

    function esc(valor) {
        if (valor == null || valor === '') return '—';
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatarData(valor) {
        if (!valor) return '—';
        const data = new Date(valor);
        if (isNaN(data.getTime())) return esc(valor);
        return data.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
    }

    function badgePrioridade(prioridade) {
        const nivel = prioridade?.toLowerCase();
        const cls = nivel === 'alta' ? 'b-alta' : nivel === 'baixa' ? 'b-baixa' : 'b-media';
        return `<span class="badge ${cls}">${esc(prioridade || 'Média')}</span>`;
    }

    function badgeStatus(status) {
        const mapa = {
            'Criada': 'd-blue',
            'Em andamento': 'd-amber',
            'Impedimento': 'd-red',
            'Finalizada': 'd-green',
        };
        const dot = mapa[status] || 'd-blue';
        return `<span class="badge" style="display:inline-flex;align-items:center;gap:5px;background:var(--gray-100);color:var(--gray-700)"><span class="dot ${dot}"></span>${esc(status || '—')}</span>`;
    }

    function badgeRegiao(regiao) {
        const cls = regiao && regiao.toLowerCase().includes('vale') ? 'b-regiao-va' : 'b-regiao-gv';
        return `<span class="badge ${cls}">${esc(regiao || 'Sem região')}</span>`;
    }

    function campoDetalhe(label, valor, span = 1) {
        const spanClass = span === 3 ? ' span-3' : span === 2 ? ' span-2' : '';
        return `
        <div class="detail-field${spanClass}">
            <span class="detail-label">${label}</span>
            <div class="detail-value">${valor}</div>
        </div>`;
    }

    function renderDetalhe(r) {
        document.getElementById('detalhe-titulo').textContent = r.titulo || 'Rompimento';
        document.getElementById('detalhe-subtitulo').textContent = r.taskCode ? `Código: ${r.taskCode}` : '';

        const tecnicos = r.responsavel || '—';

        document.getElementById('detalhe-conteudo').innerHTML = `
            <div style="display:flex;flex-direction:column;gap:16px" class="detail-enter">
                <div class="detail-badges">
                    ${badgeStatus(r.status)}
                    ${badgePrioridade(r.prioridade)}
                    ${badgeRegiao(r.regiao)}
                </div>
                <div class="detail-grid">
                    ${campoDetalhe('CTO / Elemento', esc(r.cto))}
                    ${campoDetalhe('Tipo de rompimento', esc(r.descricao))}
                    ${campoDetalhe('Região', esc(r.regiao))}
                </div>
                <div class="detail-grid-2">
                    ${campoDetalhe('Técnico(s) responsável(is)', esc(tecnicos))}
                    ${campoDetalhe('Clientes afetados', esc(r.clientesAfetados ?? '0'))}
                </div>
                <div class="detail-grid-2">
                    ${campoDetalhe('Coordenadas', esc(r.coordenadas))}
                    ${campoDetalhe('Código da tarefa', esc(r.taskCode))}
                </div>
                <div class="detail-grid">
                    ${campoDetalhe('Endereço', esc(r.localizacao_texto), 3)}
                </div>
                <div class="detail-grid-2">
                    ${campoDetalhe('Criado em', formatarData(r.criadaEm))}
                    ${campoDetalhe('Atualizado em', formatarData(r.updated_at))}
                </div>
            </div>`;
    }

    function renderDetalheLoading() {
        document.getElementById('detalhe-titulo').textContent = 'Carregando...';
        document.getElementById('detalhe-subtitulo').textContent = '';
        document.getElementById('detalhe-conteudo').innerHTML = `
            <div class="detail-loading detail-enter">
                <i class="ti ti-loader"></i>
                Buscando informações...
            </div>`;
    }

    function renderDetalheErro(mensagem) {
        document.getElementById('detalhe-titulo').textContent = 'Erro ao carregar';
        document.getElementById('detalhe-subtitulo').textContent = '';
        document.getElementById('detalhe-conteudo').innerHTML = `
            <div class="detail-error detail-enter">${esc(mensagem)}</div>`;
    }

    async function abrirDetalhe(id) {
        document.getElementById('detalhe-overlay').classList.add('open');
        renderDetalheLoading();

        const token = localStorage.getItem('planner_token');
        try {
            const response = await fetch(`/api/rompimentos/${id}`, {
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (!response.ok) {
                renderDetalheErro(data.message || 'Não foi possível carregar o rompimento.');
                return;
            }
            renderDetalhe(data.rompimento || data);
        } catch {
            renderDetalheErro('Erro de conexão ao buscar o rompimento.');
        }
    }

    window.abrirDetalhe = abrirDetalhe;

    function initKanbanDragDrop() {
        const kanban = document.querySelector('.kanban-cols');
        if (!kanban || kanban.dataset.dragInit) return;
        kanban.dataset.dragInit = '1';

        kanban.addEventListener('click', (e) => {
            if (wasDragged) {
                wasDragged = false;
                return;
            }
            const card = e.target.closest('.kcard');
            if (card) abrirDetalhe(card.dataset.id);
        });

        if (isTouchDevice) return;

        kanban.addEventListener('dragstart', (e) => {
            const card = e.target.closest('.kcard');
            if (!card) return;
            draggedId = card.dataset.id;
            draggedStatus = card.dataset.status;
            wasDragged = false;
            card.classList.add('kcard-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', draggedId);
        });

        kanban.addEventListener('drag', () => {
            wasDragged = true;
        });

        kanban.addEventListener('dragend', (e) => {
            const card = e.target.closest('.kcard');
            if (card) card.classList.remove('kcard-dragging');
            limparDragOver();
            setTimeout(() => {
                draggedId = null;
                draggedStatus = null;
            }, 0);
        });

        kanban.addEventListener('dragover', (e) => {
            const col = e.target.closest('.kcol-body');
            if (!col) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            document.querySelectorAll('.kcol-body').forEach(el => {
                el.classList.toggle('drag-over', el === col);
            });
        });

        kanban.addEventListener('drop', async (e) => {
            e.preventDefault();
            const col = e.target.closest('.kcol-body');
            if (!col || !draggedId) return;
            limparDragOver();
            const novoStatus = col.dataset.status;
            if (novoStatus === draggedStatus) return;
            await moverRompimento(draggedId, novoStatus, col);
        });
    }

    initKanbanDragDrop();
    carregarRompimentos();
</script>
@endsection