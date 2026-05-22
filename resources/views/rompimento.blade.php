@extends('layouts.app')

@section('title', 'Rompimentos — Planner Telecom')
@section('page-title', 'Rompimentos')
@section('btn-label', 'Novo rompimento')

@section('content')

<!--MODAL-->
<div id="modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:100;align-items:center;justify-content:center;padding:16px">
  <div style="background:var(--white);border-radius:var(--radius);border:1px solid var(--gray-200);width:100%;max-width:680px;overflow:hidden">

    <div style="padding:16px 24px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between">
      <div>
        <p style="font-size:15px;font-weight:600;color:var(--gray-950);margin:0">Novo rompimento</p>
        <p style="font-size:12px;color:var(--gray-500);margin:0">Preencha os dados do rompimento</p>
      </div>
      <button onclick="fecharModal()" style="background:transparent;border:none;cursor:pointer;color:var(--gray-500);font-size:18px;display:flex;align-items:center;padding:4px"><i class="ti ti-x"></i></button>
    </div>

    <div style="padding:20px 24px;display:flex;flex-direction:column;gap:16px">

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

    <div style="padding:14px 24px;border-top:1px solid var(--gray-200);display:flex;justify-content:flex-end;gap:8px">
      <button onclick="fecharModal()" style="padding:0 16px;height:36px;border-radius:var(--radius-sm);border:1px solid var(--gray-200);background:transparent;color:var(--gray-500);font-size:13px;cursor:pointer;font-family:inherit">Cancelar</button>
      <button onclick="criarRompimento()" style="padding:0 16px;height:36px;border-radius:var(--radius-sm);border:none;background:#166ac4;color:#fff;font-size:13px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit">
        <i class="ti ti-bolt" style="font-size:14px"></i> Criar rompimento
      </button>
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
                <div class="kcol-body" id="col-criada"></div>
            </div>
            <div class="kcol">
                <div class="kcol-head">
                    <div class="kcol-name"><div class="dot d-amber"></div> Em andamento</div>
                    <span class="kcol-count" id="count-andamento">0</span>
                </div>
                <div class="kcol-body" id="col-andamento"></div>
            </div>
            <div class="kcol">
                <div class="kcol-head">
                    <div class="kcol-name"><div class="dot d-red"></div> Impedimento</div>
                    <span class="kcol-count" id="count-impedimento">0</span>
                </div>
                <div class="kcol-body" id="col-impedimento"></div>
            </div>
            <div class="kcol">
                <div class="kcol-head">
                    <div class="kcol-name"><div class="dot d-green"></div> Finalizada</div>
                    <span class="kcol-count" id="count-finalizada">0</span>
                </div>
                <div class="kcol-body" id="col-finalizada"></div>
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

function abrirNovoItem() {
    document.getElementById('modal-overlay').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modal-overlay').style.display = 'none';
}

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
    tipo: document.getElementById('input-tipo').value,
    regiao: document.getElementById('input-regiao').value,
    jstecnicos: tecnicosSelecionados.map(t => t.nome).join(', '),
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

window.abrirModal = function() {
  document.getElementById('modal-overlay').style.display = 'flex';
}
</script>

<script type="module">

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
        <div class="kcard" onclick="abrirDetalhe(${r.id})">
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

    function abrirDetalhe(id) {
        console.log('abrir detalhe rompimento:', id);
    }

    function abrirModal() {
        console.log('abrir modal novo rompimento');
    }

    window.abrirModal = abrirModal;

    carregarRompimentos();
</script>
@endsection