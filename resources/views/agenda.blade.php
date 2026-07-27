@extends('layouts.app')

@section('title', 'Agenda — Planner Telecom')
@section('page-title', 'Agenda Operacional')
@section('hide-topbar-btn')
@endsection

@section('styles')
  .content:has(.agenda-page){overflow:hidden;min-height:0;gap:12px}
  .agenda-page{display:flex;flex-direction:column;gap:12px;flex:1;min-height:0;width:100%;box-sizing:border-box}
  .agenda-controls-shell{width:100%;padding:0;display:grid;grid-template-columns:minmax(240px,320px) minmax(0,1fr);gap:12px;align-items:stretch;flex:0 0 auto;box-sizing:border-box}
  .agenda-toolbar{display:grid;grid-template-columns:1fr 1fr;gap:8px;width:100%;min-width:0;align-content:start}
  .agenda-toolbar .agenda-field{display:flex;flex-direction:column;gap:4px;width:100%;min-width:0}
  .agenda-toolbar #agenda-tecnico-wrap,.agenda-toolbar .agenda-date-row{grid-column:1/-1}
  .agenda-field label{font-size:11px;color:var(--gray-500)}
  .agenda-toolbar select{width:100%}
  .agenda-date-row{display:grid;grid-template-columns:40px minmax(0,1fr) 40px auto;gap:8px;align-items:end;margin-top:0}
  .agenda-date-row .agenda-field{min-width:0}
  .agenda-date-row input{width:100%}
  .agenda-field select,.agenda-field input,.agenda-btn{height:36px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);background:var(--white);color:var(--gray-950);padding:0 10px;width:100%;box-sizing:border-box}
  .agenda-btn{cursor:pointer;width:auto;white-space:nowrap;flex-shrink:0}
  .agenda-date-row .agenda-btn{width:40px;padding:0;display:inline-flex;align-items:center;justify-content:center}
  .agenda-date-row #agenda-hoje{width:auto;padding:0 12px}
  .agenda-frame{flex:1 1 auto;min-height:280px;overflow:auto;-webkit-overflow-scrolling:touch;overscroll-behavior:contain;border:1px solid var(--gray-200);border-radius:var(--radius);background:var(--white);touch-action:pan-x pan-y}
  .agenda-grid{display:flex;width:100%;min-width:100%;gap:1px;background:var(--gray-200);box-sizing:border-box}
  .agenda-col{flex:1 1 0;min-width:200px;width:auto;background:var(--white)}
  .agenda-col.semanal{min-width:150px}
  .agenda-col-head{position:sticky;top:0;z-index:5;min-height:48px;height:auto;padding:9px 12px;background:var(--blue-600);color:#fff;font-weight:600;box-sizing:border-box}
  .agenda-col-head small{display:block;opacity:.75;font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .agenda-hours{height:1536px;position:relative}.agenda-slot{height:32px;border-bottom:1px dashed var(--gray-200);padding:3px 7px;font-size:10px;color:var(--gray-400);box-sizing:border-box}.agenda-slot.over{background:var(--blue-50)}
  .agenda-card{position:absolute;left:43px;right:6px;z-index:2;overflow:hidden;border-left:4px solid var(--blue-600);border-radius:5px;background:var(--blue-50);color:var(--gray-950);padding:6px 7px 10px;box-shadow:0 2px 7px rgba(0,0,0,.12);cursor:grab;font-size:12px;box-sizing:border-box;touch-action:none}
  .agenda-card strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .agenda-card>span:not(.agenda-time){display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .agenda-time{font-size:10px;color:var(--blue-600);font-weight:600}
  .agenda-resize{position:absolute;bottom:0;left:0;right:0;height:8px;cursor:ns-resize}.agenda-resize:after{content:'';display:block;width:30px;height:3px;background:var(--blue-400);border-radius:3px;margin:3px auto}
  .agenda-empty{padding:30px;color:var(--gray-500)}
  .agenda-pendentes{width:100%;min-width:0;height:auto;max-height:240px;border:1px solid var(--gray-200);border-radius:var(--radius);background:var(--white);overflow:hidden;flex:0 0 auto;box-sizing:border-box;display:flex;flex-direction:column}
  .agenda-pendentes-head{min-height:42px;padding:0 13px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid var(--gray-200);font-size:12px;font-weight:600;color:var(--gray-950);flex-shrink:0}
  .agenda-pendentes-count{min-width:22px;padding:2px 7px;border-radius:999px;background:var(--blue-50);color:var(--blue-600);text-align:center}
  .agenda-pendentes-tools{display:grid;grid-template-columns:minmax(0,1fr) minmax(160px,220px);gap:8px;padding:8px 12px;border-bottom:1px solid var(--gray-200);flex-shrink:0;box-sizing:border-box}
  .agenda-pendentes-tools .agenda-field{gap:0}
  .agenda-pendentes-tools input,.agenda-pendentes-tools select{height:34px;font-size:12px}
  .agenda-pendentes-list{flex:1;min-height:0;max-height:150px;overflow-y:auto;-webkit-overflow-scrolling:touch}
  .agenda-pendente-item{position:relative;padding:9px 34px 9px 12px;border-bottom:1px solid var(--gray-200);cursor:grab;user-select:none;transition:background .15s,opacity .15s}
  .agenda-pendente-item:after{content:'⋮⋮';position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);letter-spacing:-2px}
  .agenda-pendente-item:hover{background:var(--blue-50)}.agenda-pendente-item.dragging{opacity:.45;cursor:grabbing}.agenda-pendente-item:last-child{border-bottom:0}
  .agenda-pendente-codigo{font-size:11px;font-weight:600;color:var(--blue-600)}
  .agenda-pendente-titulo{margin-top:2px;font-size:12px;color:var(--gray-950);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .agenda-pendente-meta{margin-top:3px;font-size:10px;color:var(--gray-500)}
  .agenda-pendentes-empty{padding:28px 14px;text-align:center;font-size:12px;color:var(--gray-500)}
  .agenda-disponibilidade{height:200px;min-width:0;border:1px solid var(--gray-200);border-radius:var(--radius);background:var(--white);overflow:hidden;display:flex;flex-direction:column}
  .agenda-disponibilidade-head{min-height:42px;height:auto;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-bottom:1px solid var(--gray-200);font-size:12px;font-weight:600;flex-shrink:0}
  .agenda-disponibilidade-head>span{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .agenda-disponibilidade-list{flex:1;min-height:0;overflow-y:auto;-webkit-overflow-scrolling:touch}
  .agenda-disponibilidade-item{min-height:42px;padding:7px 12px;display:flex;align-items:center;gap:9px;border-bottom:1px solid var(--gray-200);font-size:12px}
  .agenda-disponibilidade-item:last-child{border-bottom:0}
  .agenda-disponibilidade-info{min-width:0;flex:1}.agenda-disponibilidade-info strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .agenda-disponibilidade-info small{display:block;color:var(--gray-500);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .agenda-status-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;flex:none}.agenda-status-dot.off{background:#ef4444}
  .agenda-remover-ausencia{border:0;background:transparent;color:var(--gray-500);cursor:pointer;padding:4px;flex-shrink:0}
  .agenda-slot.indisponivel{background:repeating-linear-gradient(135deg,transparent,transparent 7px,rgba(239,68,68,.08) 7px,rgba(239,68,68,.08) 14px);cursor:not-allowed}
  .agenda-col-head.indisponivel{background:#7f1d1d}
  .agenda-ausencia-label{position:absolute;top:54px;left:45px;right:8px;z-index:3;padding:5px 7px;border:1px solid rgba(239,68,68,.35);border-radius:4px;background:rgba(127,29,29,.9);color:#fff;font-size:11px;pointer-events:none}
  .agenda-btn-short{display:none}
  .agenda-modal-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .agenda-modal-fields .agenda-field:first-child{grid-column:1/-1}
  .agenda-modal-fields input,.agenda-modal-fields select{height:38px;border:1px solid var(--gray-200);border-radius:var(--radius-sm);background:var(--white);color:var(--gray-950);padding:0 10px;width:100%;box-sizing:border-box}
  .agenda-os-list{grid-column:1/-1;max-height:210px;overflow:auto;border:1px solid var(--gray-200);border-radius:var(--radius-sm)}
  .agenda-os-option{display:block;padding:10px;border-bottom:1px solid var(--gray-200);cursor:pointer}.agenda-os-option:last-child{border:0}.agenda-os-option:hover{background:var(--blue-50)}.agenda-os-option small{display:block;color:var(--gray-500)}
  #agenda-modal .modal-box,#agenda-ausencia-modal .modal-box{width:min(620px,calc(100vw - 24px));max-width:calc(100vw - 24px);margin:12px;max-height:calc(100vh - 24px);overflow:auto}
  #agenda-ausencia-modal .modal-box{width:min(520px,calc(100vw - 24px))}
  #agenda-modal .btn-primary,#agenda-ausencia-modal .btn-primary{display:inline-flex!important}

  @media(max-width:1100px){
    .agenda-controls-shell{grid-template-columns:1fr}
    .agenda-toolbar{grid-template-columns:repeat(3,minmax(0,1fr))}
    .agenda-toolbar #agenda-tecnico-wrap{grid-column:auto}
    .agenda-toolbar .agenda-date-row{grid-column:1/-1}
    .agenda-disponibilidade{height:160px}
  }

  @media(max-width:768px){
    .agenda-toolbar{grid-template-columns:1fr 1fr}
    .agenda-toolbar #agenda-tecnico-wrap{grid-column:1/-1}
    .agenda-date-row{grid-template-columns:40px minmax(0,1fr) 40px}
    .agenda-date-row #agenda-hoje{grid-column:1/-1;width:100%}
    .agenda-disponibilidade{height:150px}
    .agenda-disponibilidade-head{flex-wrap:wrap}
    .agenda-btn-full{display:none}.agenda-btn-short{display:inline}
    .agenda-pendentes{max-height:220px}
    .agenda-pendentes-tools{grid-template-columns:1fr}
    .agenda-pendentes-list{max-height:120px}
    .agenda-col{flex:0 0 min(240px,78vw);min-width:min(240px,78vw)}
    .agenda-col.semanal{flex:0 0 min(200px,70vw);min-width:min(200px,70vw)}
    .agenda-card{left:36px;right:4px;font-size:11px;padding:4px 6px 8px}
    .agenda-ausencia-label{left:38px;right:4px}
    .agenda-frame{min-height:340px}
    .agenda-modal-fields{grid-template-columns:1fr}
    .agenda-modal-fields .agenda-field:first-child{grid-column:auto}
    .agenda-os-list{max-height:160px}
  }

  @media(max-width:480px){
    .agenda-toolbar{grid-template-columns:1fr}
    .agenda-col{flex:0 0 min(220px,85vw);min-width:min(220px,85vw)}
    .agenda-col.semanal{flex:0 0 min(180px,78vw);min-width:min(180px,78vw)}
    .agenda-col-head{padding:8px 10px;font-size:13px}
    .agenda-pendentes{max-height:200px}
    .agenda-pendentes-list{max-height:100px}
    .agenda-disponibilidade{height:140px}
    .agenda-frame{min-height:300px}
  }
@endsection

@section('content')
<div class="agenda-page">
<div class="agenda-controls-shell">
<div class="agenda-toolbar">
  <div class="agenda-field"><label>Visualização</label><select id="agenda-visao"><option value="diaria">Diária</option><option value="semanal">Semanal</option></select></div>
  <div class="agenda-field"><label>Região</label><select id="agenda-regiao"><option>Vale do Aço</option><option value="Goval">Governador Valadares</option></select></div>
  <div class="agenda-field" id="agenda-tecnico-wrap"><label>Técnico</label><select id="agenda-tecnico"><option value="">Todos os técnicos</option></select></div>
  <div class="agenda-date-row">
    <button class="agenda-btn" id="agenda-anterior" title="Período anterior" type="button"><i class="ti ti-chevron-left"></i></button>
    <div class="agenda-field"><label>Data</label><input type="date" id="agenda-data" value="{{ now()->toDateString() }}"></div>
    <button class="agenda-btn" id="agenda-proximo" title="Próximo período" type="button"><i class="ti ti-chevron-right"></i></button>
    <button class="agenda-btn" id="agenda-hoje" type="button">Hoje</button>
  </div>
</div>
<section class="agenda-disponibilidade" aria-labelledby="agenda-disponibilidade-titulo">
  <div class="agenda-disponibilidade-head"><span id="agenda-disponibilidade-titulo">Disponibilidade dos técnicos</span><button class="agenda-btn" id="agenda-registrar-ausencia" type="button"><span class="agenda-btn-full">Registrar ausência</span><span class="agenda-btn-short">Ausência</span></button></div>
  <div class="agenda-disponibilidade-list" id="agenda-disponibilidade-list"><div class="agenda-pendentes-empty">Carregando técnicos…</div></div>
</section>
</div>
<aside class="agenda-pendentes" aria-labelledby="agenda-pendentes-titulo">
  <div class="agenda-pendentes-head"><span id="agenda-pendentes-titulo">OS disponíveis</span><span class="agenda-pendentes-count" id="agenda-pendentes-count">0</span></div>
  <div class="agenda-pendentes-tools">
    <div class="agenda-field"><input type="search" id="agenda-pendentes-busca" placeholder="Pesquisar por nome ou código da OS" autocomplete="off"></div>
    <div class="agenda-field"><select id="agenda-pendentes-ordem" title="Ordenação"><option value="recentes">Atuais → mais antigas</option><option value="antigas">Antigas → mais atuais</option></select></div>
  </div>
  <div class="agenda-pendentes-list" id="agenda-pendentes-list"><div class="agenda-pendentes-empty">Carregando OS…</div></div>
</aside>
<div class="agenda-frame"><div class="agenda-grid" id="agenda-grid"><div class="agenda-empty">Carregando agenda…</div></div></div>
</div>

<div class="modal-overlay" id="agenda-modal">
  <div class="modal-box">
    <div class="modal-head"><div><h3 class="modal-title">Programar OS</h3><p class="modal-sub" id="agenda-modal-contexto"></p></div><button class="modal-close" id="agenda-modal-fechar"><i class="ti ti-x"></i></button></div>
    <form id="agenda-form"><div class="modal-body agenda-modal-fields">
      <div class="agenda-field"><label>Buscar tarefa-filho/OS</label><input id="agenda-os-busca" placeholder="Código ou título" autocomplete="off"></div>
      <input type="hidden" id="agenda-os-id">
      <div class="agenda-os-list" id="agenda-os-list"><div class="agenda-empty">Carregando OS…</div></div>
      <div class="agenda-field"><label>Início</label><input type="time" step="1800" id="agenda-inicio" required></div>
      <div class="agenda-field"><label>Fim</label><input type="time" step="1800" id="agenda-fim" required></div>
    </div><div class="modal-foot"><button type="button" class="btn-secondary" id="agenda-modal-cancelar">Cancelar</button><button class="btn-primary" type="submit">Programar</button></div></form>
  </div>
</div>

<div class="modal-overlay" id="agenda-ausencia-modal">
  <div class="modal-box">
    <div class="modal-head"><div><h3 class="modal-title">Registrar ausência</h3><p class="modal-sub">O técnico ficará bloqueado no período informado.</p></div><button class="modal-close" id="agenda-ausencia-fechar"><i class="ti ti-x"></i></button></div>
    <form id="agenda-ausencia-form"><div class="modal-body agenda-modal-fields">
      <div class="agenda-field"><label>Técnico</label><select id="agenda-ausencia-tecnico" required></select></div>
      <div class="agenda-field"><label>Motivo</label><select id="agenda-ausencia-motivo" required><option value="ferias">Férias</option><option value="atestado">Atestado</option><option value="folga">Folga</option><option value="outro">Outro</option></select></div>
      <div class="agenda-field"><label>Data inicial</label><input type="date" id="agenda-ausencia-inicio" required></div>
      <div class="agenda-field"><label>Data final</label><input type="date" id="agenda-ausencia-fim" required></div>
      <div class="agenda-field"><label>Observação</label><input id="agenda-ausencia-observacao" maxlength="255" placeholder="Opcional"></div>
    </div><div class="modal-foot"><button type="button" class="btn-secondary" id="agenda-ausencia-cancelar">Cancelar</button><button class="btn-primary" type="submit">Salvar ausência</button></div></form>
  </div>
</div>
@endsection

@section('scripts')
<script>
(() => {
  const grid=document.getElementById('agenda-grid'), dataEl=document.getElementById('agenda-data'), regiaoEl=document.getElementById('agenda-regiao'), visaoEl=document.getElementById('agenda-visao'), tecnicoEl=document.getElementById('agenda-tecnico'), tecnicoWrap=document.getElementById('agenda-tecnico-wrap');
  const pendentesList=document.getElementById('agenda-pendentes-list'),pendentesCount=document.getElementById('agenda-pendentes-count');
  const pendentesBusca=document.getElementById('agenda-pendentes-busca'),pendentesOrdem=document.getElementById('agenda-pendentes-ordem');
  const token=()=>localStorage.getItem('planner_token'); let payload=null,slotProgramacao=null,buscaTimer=null,pendentesBuscaTimer=null;
  const chaveFiltros='planner_agenda_filtros';let filtrosSalvos={};try{filtrosSalvos=JSON.parse(localStorage.getItem(chaveFiltros)||'{}')||{}}catch{filtrosSalvos={}}if(['diaria','semanal'].includes(filtrosSalvos.visao))visaoEl.value=filtrosSalvos.visao;if(['Vale do Aço','Goval'].includes(filtrosSalvos.regiao))regiaoEl.value=filtrosSalvos.regiao;if(/^\d{4}-\d{2}-\d{2}$/.test(filtrosSalvos.data||''))dataEl.value=filtrosSalvos.data;if(['recentes','antigas'].includes(filtrosSalvos.pendentes_ordem))pendentesOrdem.value=filtrosSalvos.pendentes_ordem;let tecnicoPreferido=String(filtrosSalvos.tecnico_id||'');
  const api=async(url,options={})=>{const r=await fetch('/api/'+url,{...options,headers:{Authorization:'Bearer '+token(),Accept:'application/json','Content-Type':'application/json',...(options.headers||{})}});const body=await r.json().catch(()=>({}));if(!r.ok)throw new Error(body.message||'Não foi possível atualizar a agenda.');return body};
  function salvarFiltros(){localStorage.setItem(chaveFiltros,JSON.stringify({visao:visaoEl.value,regiao:regiaoEl.value,data:dataEl.value,tecnico_id:tecnicoPreferido||null,pendentes_ordem:pendentesOrdem.value}))}
  const escaparHtml=valor=>String(valor??'').replace(/[&<>"']/g,caractere=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[caractere]));
  const minutos=t=>{const [h,m]=t.slice(0,5).split(':').map(Number);return h*60+m};
  const localDate=s=>new Date(s+'T12:00:00'); const iso=d=>d.toISOString().slice(0,10);
  const rotuloMotivo=motivo=>({ferias:'Férias',atestado:'Atestado',folga:'Folga',outro:'Indisponível'}[motivo]||'Indisponível');
  function indisponibilidadeEm(tecnicoId,data){const tecnico=(payload.tecnicos_regiao||[]).find(t=>Number(t.id)===Number(tecnicoId));return tecnico?.indisponibilidades?.find(periodo=>periodo.data_inicio.slice(0,10)<=data&&periodo.data_fim.slice(0,10)>=data)||null}
  function colunas(){if(visaoEl.value==='diaria')return payload.tecnicos.map(t=>({titulo:t.nome,sub:'',tecnico:t.id,data:payload.data,ausencia:indisponibilidadeEm(t.id,payload.data)}));const ini=localDate(payload.inicio_semana);return Array.from({length:7},(_,i)=>{const d=new Date(ini);d.setDate(d.getDate()+i);const data=iso(d);return{titulo:d.toLocaleDateString('pt-BR',{weekday:'short'}),sub:d.toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'}),tecnico:payload.tecnicoId,data,ausencia:indisponibilidadeEm(payload.tecnicoId,data)}})}
  function render(){grid.innerHTML='';const cols=colunas();if(!cols.length){grid.innerHTML='<div class="agenda-empty">Nenhum técnico em atividade nesta região e data.</div>';return}cols.forEach(c=>{const col=document.createElement('section');col.className='agenda-col '+(visaoEl.value==='semanal'?'semanal':'');col.innerHTML=`<header class="agenda-col-head ${c.ausencia?'indisponivel':''}">${escaparHtml(c.titulo)}<small>${escaparHtml(c.sub+(c.ausencia?' · '+rotuloMotivo(c.ausencia.motivo):''))}</small></header><div class="agenda-hours"></div>`;const hours=col.lastElementChild;for(let i=0;i<48;i++){const slot=document.createElement('div');slot.className='agenda-slot'+(c.ausencia?' indisponivel':'');slot.dataset.hora=String(Math.floor(i/2)).padStart(2,'0')+':'+(i%2?'30':'00');slot.dataset.tecnico=c.tecnico;slot.dataset.data=c.data;slot.textContent=i%2?'':slot.dataset.hora;if(!c.ausencia){slot.onclick=()=>abrirProgramacao(slot);slot.ondragover=e=>{e.preventDefault();slot.classList.add('over')};slot.ondragleave=()=>slot.classList.remove('over');slot.ondrop=e=>soltarNoSlot(e,slot)}hours.appendChild(slot)}if(c.ausencia){const aviso=document.createElement('div');aviso.className='agenda-ausencia-label';aviso.textContent=rotuloMotivo(c.ausencia.motivo);hours.appendChild(aviso)}payload.agenda.filter(a=>Number(a.tecnico_id)===Number(c.tecnico)&&a.data.slice(0,10)===c.data).forEach(a=>{const card=document.createElement('article');card.className='agenda-card';card.draggable=true;card.dataset.id=a.id;card.style.top=(minutos(a.hora_inicio)/30*32)+'px';card.style.height=((minutos(a.hora_fim)-minutos(a.hora_inicio))/30*32)+'px';card.innerHTML=`<span class="agenda-time">${a.hora_inicio.slice(0,5)}–${a.hora_fim.slice(0,5)}</span><strong>${escaparHtml(a.os_tecnico?.ordem_servico||'OS')}</strong><span>${escaparHtml(a.os_tecnico?.titulo||'')}</span><div class="agenda-resize"></div>`;card.onclick=e=>e.stopPropagation();card.ondragstart=e=>e.dataTransfer.setData('text/plain',a.id);card.querySelector('.agenda-resize').onpointerdown=e=>redimensionar(e,card,a);hours.appendChild(card)});grid.appendChild(col)})}
  async function carregarPendentes(){pendentesList.innerHTML='<div class="agenda-pendentes-empty">Carregando OS…</div>';try{const q=new URLSearchParams({regiao:regiaoEl.value,ordem:pendentesOrdem.value,busca:pendentesBusca.value.trim()});const r=await api('agenda-ordens-disponiveis?'+q);pendentesCount.textContent=r.ordens.length;pendentesList.innerHTML='';if(!r.ordens.length){pendentesList.innerHTML=`<div class="agenda-pendentes-empty">${pendentesBusca.value.trim()?'Nenhuma OS encontrada.':'Nenhuma OS pendente.'}</div>`;return}r.ordens.forEach(os=>{const item=document.createElement('div');item.className='agenda-pendente-item';item.draggable=true;item.title='Arraste para um horário da agenda';item.ondragstart=e=>{e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('application/x-planner-os-pendente',String(os.id));item.classList.add('dragging')};item.ondragend=()=>item.classList.remove('dragging');const codigo=document.createElement('div');codigo.className='agenda-pendente-codigo';codigo.textContent=os.ordem_servico||os.task_code||'OS';const titulo=document.createElement('div');titulo.className='agenda-pendente-titulo';titulo.textContent=os.titulo||'Sem título';const meta=document.createElement('div');meta.className='agenda-pendente-meta';meta.textContent=(os.status||'Pendente')+' · arraste para programar';item.append(codigo,titulo,meta);pendentesList.appendChild(item)})}catch(e){pendentesCount.textContent='!';pendentesList.innerHTML='<div class="agenda-pendentes-empty">Não foi possível carregar as OS.</div>'}}
  function renderDisponibilidade(){const lista=document.getElementById('agenda-disponibilidade-list');lista.innerHTML='';const tecnicos=payload.tecnicos_regiao||[];if(!tecnicos.length){lista.innerHTML='<div class="agenda-pendentes-empty">Nenhum técnico nesta região.</div>';return}tecnicos.forEach(tecnico=>{const ausencia=indisponibilidadeEm(tecnico.id,dataEl.value);const item=document.createElement('div');item.className='agenda-disponibilidade-item';const dot=document.createElement('span');dot.className='agenda-status-dot'+(ausencia?' off':'');const info=document.createElement('div');info.className='agenda-disponibilidade-info';const nome=document.createElement('strong');nome.textContent=tecnico.nome;const status=document.createElement('small');status.textContent=ausencia?`${rotuloMotivo(ausencia.motivo)} · ${ausencia.data_inicio.slice(0,10).split('-').reverse().join('/')} a ${ausencia.data_fim.slice(0,10).split('-').reverse().join('/')}`:'Em atividade';info.append(nome,status);item.append(dot,info);if(ausencia){const remover=document.createElement('button');remover.className='agenda-remover-ausencia';remover.title='Remover ausência';remover.innerHTML='<i class="ti ti-trash"></i>';remover.onclick=()=>removerAusencia(ausencia.id);item.appendChild(remover)}lista.appendChild(item)})}
  async function carregar(){const listaDisp=document.getElementById('agenda-disponibilidade-list');try{const semanal=visaoEl.value==='semanal';const q=new URLSearchParams({data:dataEl.value,regiao:regiaoEl.value,visao:visaoEl.value});if(semanal&&(tecnicoEl.value||tecnicoPreferido))q.set('tecnico_id',tecnicoEl.value||tecnicoPreferido);payload=await api('agenda?'+q);if(semanal){tecnicoEl.innerHTML=payload.tecnicos.map(t=>`<option value="${Number(t.id)}">${escaparHtml(t.nome)}</option>`).join('');tecnicoEl.value=payload.tecnicoId||'';tecnicoPreferido=tecnicoEl.value;tecnicoEl.disabled=false}else{tecnicoEl.innerHTML='<option value="">Todos os técnicos</option>';tecnicoEl.disabled=true}tecnicoWrap.hidden=false;salvarFiltros();renderDisponibilidade();render();await carregarPendentes()}catch(e){payload=null;grid.innerHTML=`<div class="agenda-empty">${escaparHtml(e.message)}</div>`;if(listaDisp)listaDisp.innerHTML=`<div class="agenda-pendentes-empty">${escaparHtml(e.message)}</div>`;pendentesCount.textContent='!';pendentesList.innerHTML=`<div class="agenda-pendentes-empty">${escaparHtml(e.message)}</div>`}}
  async function soltarNoSlot(e,slot){e.preventDefault();slot.classList.remove('over');try{const osPendente=e.dataTransfer.getData('application/x-planner-os-pendente');if(osPendente){const inicio=minutos(slot.dataset.hora);if(inicio>=1410)throw new Error('Não é possível iniciar uma OS às 23:30.');const fim=Math.min(inicio+60,1410);const horaFim=String(Math.floor(fim/60)).padStart(2,'0')+':'+String(fim%60).padStart(2,'0');await api('agenda',{method:'POST',body:JSON.stringify({os_tecnico_id:Number(osPendente),tecnico_id:Number(slot.dataset.tecnico),data:slot.dataset.data,hora_inicio:slot.dataset.hora,hora_fim:horaFim})})}else{const agendaId=e.dataTransfer.getData('text/plain');if(!agendaId)return;await api(`agenda/${agendaId}/mover`,{method:'PUT',body:JSON.stringify({tecnico_id:Number(slot.dataset.tecnico),data:slot.dataset.data,hora_inicio:slot.dataset.hora})})}await carregar()}catch(err){alert(err.message)}}
  function redimensionar(e,card,a){e.preventDefault();e.stopPropagation();card.draggable=false;const y=e.clientY,h=card.offsetHeight;const move=ev=>card.style.height=Math.max(32,Math.round((h+ev.clientY-y)/32)*32)+'px';const up=async()=>{removeEventListener('pointermove',move);removeEventListener('pointerup',up);const fim=minutos(a.hora_inicio)+Math.round(card.offsetHeight/32)*30;const hora=String(Math.floor(fim/60)).padStart(2,'0')+':'+String(fim%60).padStart(2,'0');try{await api(`agenda/${a.id}/duracao`,{method:'PUT',body:JSON.stringify({hora_fim:hora})})}catch(err){alert(err.message)}card.draggable=true;await carregar()};addEventListener('pointermove',move);addEventListener('pointerup',up)}
  const modal=document.getElementById('agenda-modal'),osBusca=document.getElementById('agenda-os-busca'),osList=document.getElementById('agenda-os-list'),osId=document.getElementById('agenda-os-id');
  function abrirProgramacao(slot){slotProgramacao=slot;osId.value='';osBusca.value='';document.getElementById('agenda-inicio').value=slot.dataset.hora;const fim=Math.min(minutos(slot.dataset.hora)+60,1410);document.getElementById('agenda-fim').value=String(Math.floor(fim/60)).padStart(2,'0')+':'+String(fim%60).padStart(2,'0');document.getElementById('agenda-modal-contexto').textContent=`${slot.dataset.data} · ${slot.dataset.hora}`;modal.classList.add('open');carregarOrdens()}
  function fecharModal(){modal.classList.remove('open');slotProgramacao=null}document.getElementById('agenda-modal-fechar').onclick=fecharModal;document.getElementById('agenda-modal-cancelar').onclick=fecharModal;
  async function carregarOrdens(){osList.innerHTML='<div class="agenda-empty">Carregando OS…</div>';try{const q=new URLSearchParams({regiao:regiaoEl.value,busca:osBusca.value});const r=await api('agenda-ordens-disponiveis?'+q);osList.innerHTML=r.ordens.length?'':'<div class="agenda-empty">Nenhuma OS disponível.</div>';r.ordens.forEach(os=>{const item=document.createElement('label');item.className='agenda-os-option';item.innerHTML=`<input type="radio" name="agenda_os" value="${Number(os.id)}"> <strong>${escaparHtml(os.ordem_servico||os.task_code||'OS')}</strong><small>${escaparHtml(os.titulo||'')}</small>`;item.querySelector('input').onchange=()=>osId.value=os.id;osList.appendChild(item)})}catch(e){osList.innerHTML=`<div class="agenda-empty">${escaparHtml(e.message)}</div>`}}
  osBusca.oninput=()=>{clearTimeout(buscaTimer);buscaTimer=setTimeout(carregarOrdens,300)};document.getElementById('agenda-form').onsubmit=async e=>{e.preventDefault();if(!osId.value)return alert('Selecione uma OS.');try{await api('agenda',{method:'POST',body:JSON.stringify({os_tecnico_id:Number(osId.value),tecnico_id:Number(slotProgramacao.dataset.tecnico),data:slotProgramacao.dataset.data,hora_inicio:document.getElementById('agenda-inicio').value,hora_fim:document.getElementById('agenda-fim').value})});fecharModal();await carregar()}catch(err){alert(err.message)}};
  const ausenciaModal=document.getElementById('agenda-ausencia-modal'),ausenciaTecnico=document.getElementById('agenda-ausencia-tecnico'),ausenciaInicio=document.getElementById('agenda-ausencia-inicio'),ausenciaFim=document.getElementById('agenda-ausencia-fim');
  function abrirAusencia(){ausenciaTecnico.innerHTML=(payload.tecnicos_regiao||[]).map(t=>`<option value="${Number(t.id)}">${escaparHtml(t.nome)}</option>`).join('');ausenciaInicio.value=dataEl.value;ausenciaFim.value=dataEl.value;document.getElementById('agenda-ausencia-observacao').value='';ausenciaModal.classList.add('open')}
  function fecharAusencia(){ausenciaModal.classList.remove('open')}document.getElementById('agenda-registrar-ausencia').onclick=abrirAusencia;document.getElementById('agenda-ausencia-fechar').onclick=fecharAusencia;document.getElementById('agenda-ausencia-cancelar').onclick=fecharAusencia;
  document.getElementById('agenda-ausencia-form').onsubmit=async e=>{e.preventDefault();try{const resultado=await api('agenda-indisponibilidades',{method:'POST',body:JSON.stringify({tecnico_id:Number(ausenciaTecnico.value),motivo:document.getElementById('agenda-ausencia-motivo').value,data_inicio:ausenciaInicio.value,data_fim:ausenciaFim.value,observacao:document.getElementById('agenda-ausencia-observacao').value||null})});fecharAusencia();await carregar();if(resultado.agendamentos_no_periodo>0)alert(`Ausência salva. Existem ${resultado.agendamentos_no_periodo} agendamento(s) no período que precisam ser reorganizados.`)}catch(err){alert(err.message)}};
  async function removerAusencia(id){if(!confirm('Remover esta ausência?'))return;try{await api(`agenda-indisponibilidades/${id}`,{method:'DELETE'});await carregar()}catch(err){alert(err.message)}}
  [dataEl,regiaoEl,visaoEl].forEach(el=>el.addEventListener('change',()=>{salvarFiltros();carregar()}));tecnicoEl.addEventListener('change',()=>{tecnicoPreferido=tecnicoEl.value;salvarFiltros();carregar()});pendentesBusca.addEventListener('input',()=>{clearTimeout(pendentesBuscaTimer);pendentesBuscaTimer=setTimeout(carregarPendentes,300)});pendentesOrdem.addEventListener('change',()=>{salvarFiltros();carregarPendentes()});document.getElementById('agenda-hoje').onclick=()=>{dataEl.value=iso(new Date());salvarFiltros();carregar()};document.getElementById('agenda-anterior').onclick=()=>navegar(-1);document.getElementById('agenda-proximo').onclick=()=>navegar(1);function navegar(dir){const d=localDate(dataEl.value);d.setDate(d.getDate()+dir*(visaoEl.value==='semanal'?7:1));dataEl.value=iso(d);salvarFiltros();carregar()}carregar();
})();
</script>
@endsection
