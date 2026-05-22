@extends('layouts.app')

@section('title', 'Dashboard — Planner Telecom')
@section('page-title', 'Dashboard')
@section('btn-label', 'Nova tarefa')

@section('content')
    <div class="metrics-row">
      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-label">Criadas</div>
          <div class="metric-icon mi-blue"><i class="ti ti-list"></i></div>
        </div>
        <div class="metric-value">91</div>
        <div class="metric-sub"><span class="up">+28 hoje</span> &middot; 692 no pipeline</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-label">Em andamento</div>
          <div class="metric-icon mi-amber"><i class="ti ti-loader"></i></div>
        </div>
        <div class="metric-value">67</div>
        <div class="metric-sub">em execução agora</div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-label">Impedimentos</div>
          <div class="metric-icon mi-red"><i class="ti ti-alert-triangle"></i></div>
        </div>
        <div class="metric-value">7</div>
        <div class="metric-sub"><span class="down">atenção necessária</span></div>
      </div>
      <div class="metric-card">
        <div class="metric-top">
          <div class="metric-label">Finalizadas</div>
          <div class="metric-icon mi-green"><i class="ti ti-circle-check"></i></div>
        </div>
        <div class="metric-value">532</div>
        <div class="metric-sub"><span class="up">+12 hoje</span> &middot; este mês</div>
      </div>
    </div>

    <div class="bottom-grid">
      <div class="card">
        <div class="card-header">
          <span class="card-title">Kanban — visão geral</span>
          <span class="card-action">ver tudo <i class="ti ti-arrow-right" style="font-size:11px"></i></span>
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

      <div class="card card-mapa">
        <div class="card-header">
          <span class="card-title">Mapa de calor</span>
          <span class="card-action"><i class="ti ti-arrows-maximize" style="font-size:11px"></i> expandir</span>
        </div>
        <div class="map-body">
          <div id="mapa-calor" style="width:100%;height:100%;min-height:260px;"></div>
        </div>
        <div class="region-list">
          <div class="region-row">
            <div class="region-name">Goval</div>
            <div class="region-bar-wrap"><div class="region-bar" style="width:100%"></div></div>
            <div class="region-n">126</div>
          </div>
          <div class="region-row">
            <div class="region-name">Vale do Aço</div>
            <div class="region-bar-wrap"><div class="region-bar" style="width:67%"></div></div>
            <div class="region-n">84</div>
          </div>
          <div class="region-row">
            <div class="region-name">Caratinga</div>
            <div class="region-bar-wrap"><div class="region-bar" style="width:3%"></div></div>
            <div class="region-n">4</div>
          </div>
          <div class="region-row">
            <div class="region-name">N/D</div>
            <div class="region-bar-wrap"><div class="region-bar" style="width:2%"></div></div>
            <div class="region-n">3</div>
          </div>
        </div>
      </div>
    </div>
@endsection

@section('scripts')
<script>
  function iniciarMapa() {
    const mapa = L.map('mapa-calor', {
      zoomControl: true,
      scrollWheelZoom: true
    }).setView([-18.8517, -41.9494], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapa);
    window.mapaLeaflet = mapa;
  }
  iniciarMapa();
</script>

<script type="module">
    import { listarTarefas } from '{{ asset("js/modules/opTask.js") }}';

    function filtrarPorStatus(tarefas, status) {
        return tarefas.filter(t => t.status === status).slice(0, 10);
    }

    function renderCard(tarefa){
        const regiaoClass = tarefa.regiao && tarefa.regiao.toLowerCase().includes('vale') ? 'b-regiao-va' : 'b-regiao-gv';
        const categoriasClasses = {
            'rompimentos': 'b-cat-rom', 'atendimento-cliente': 'b-cat-ate',
            'otimizacao-rede': 'b-cat-otm', 'manutencao-corretiva': 'b-cat-man',
            'troca-poste': 'b-cat-tro', 'troca-etiqueta': 'b-cat-etq',
            'certificacao-cemig': 'b-cat-cer', 'correcao-atenuacao': 'b-cat-cor',
            'qualidade-potencia': 'b-cat-qua',
        };
        const categoriaClass = categoriasClasses[tarefa.categoria?.toLowerCase()] || 'b-cat-gen';
        const prioridadeClass = tarefa.prioridade?.toLowerCase() === 'alta' ? 'b-alta' : tarefa.prioridade?.toLowerCase() === 'baixa' ? 'b-baixa' : 'b-media';
        return `
        <div class="kcard">
          <div class="kcard-title">${tarefa.titulo}</div>
          <div class="kcard-foot">
            <span class="badge ${prioridadeClass}">${tarefa.prioridade || 'Média'}</span>
            <span class="badge ${categoriaClass}">${tarefa.categoria || 'Sem categoria'}</span>
            <span class="badge ${regiaoClass}">${tarefa.regiao || 'Sem região'}</span>
            <span class="kcard-code">${tarefa.taskCode || 'S/C'}</span>
          </div>
        </div>`;
    }

    async function carregarDashboard() {
        const tarefas = await listarTarefas();
        const criadas = filtrarPorStatus(tarefas, 'Criada');
        const andamento = filtrarPorStatus(tarefas, 'Em andamento');
        const impedimento = filtrarPorStatus(tarefas, 'Impedimento');
        const finalizadas = filtrarPorStatus(tarefas, 'Finalizada');
        document.getElementById('col-criada').innerHTML = criadas.map(renderCard).join('');
        document.getElementById('col-andamento').innerHTML = andamento.map(renderCard).join('');
        document.getElementById('col-impedimento').innerHTML = impedimento.map(renderCard).join('');
        document.getElementById('col-finalizada').innerHTML = finalizadas.map(renderCard).join('');
        document.getElementById('count-criada').textContent = criadas.length;
        document.getElementById('count-andamento').textContent = andamento.length;
        document.getElementById('count-impedimento').textContent = impedimento.length;
        document.getElementById('count-finalizada').textContent = finalizadas.length;
    }

    carregarDashboard();
</script>
@endsection