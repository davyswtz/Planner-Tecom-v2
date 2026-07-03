import { prepararImagemArquivo } from './planner-descricao-editor.js';

const DESCRICAO_ID = 'os-input-descricao';
const GALERIA_ID = 'os-anexos-galeria';

let osAnexoContextoId = null;
let anexosSalvos = [];
let anexosPendentes = [];

function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function textoPlano(valor) {
  const bruto = String(valor ?? '').trim();
  if (!bruto) return '';
  if (!/<[a-z][\s\S]*>/i.test(bruto)) return bruto;
  const tmp = document.createElement('div');
  tmp.innerHTML = bruto;
  return (tmp.textContent || '').replace(/\s+/g, ' ').trim();
}

function getToken() {
  return localStorage.getItem('planner_token') || '';
}

function getDescricaoEl() {
  return document.getElementById(DESCRICAO_ID);
}

function getGaleriaEl() {
  return document.getElementById(GALERIA_ID);
}

window.getOsDescricaoValor = function () {
  return getDescricaoEl()?.value.trim() || '';
};

window.setOsDescricaoValor = function (valor = '') {
  const el = getDescricaoEl();
  if (el) el.value = textoPlano(valor);
};

window.resetOsDescricaoEditor = function () {
  window.setOsDescricaoValor('');
};

window.plannerOsModalSetContexto = function (osId = null) {
  osAnexoContextoId = osId ? Number(osId) : null;
};

function revogarUrlsPendentes() {
  anexosPendentes.forEach((item) => {
    if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
  });
}

window.resetOsAnexosModal = function () {
  plannerOsModalSetContexto(null);
  revogarUrlsPendentes();
  anexosPendentes = [];
  anexosSalvos = [];
  renderGaleriaAnexos();
};

async function fetchAutenticado(url, options = {}) {
  const headers = {
    Authorization: 'Bearer ' + getToken(),
    Accept: 'application/json',
    ...(options.headers || {}),
  };

  return fetch(url, { ...options, headers, cache: 'no-store' });
}

async function carregarBlobAutenticado(url) {
  const response = await fetch(url, {
    headers: { Authorization: 'Bearer ' + getToken() },
    cache: 'no-store',
  });
  if (!response.ok) return null;
  const blob = await response.blob();
  return URL.createObjectURL(blob);
}

function renderGaleriaAnexos() {
  const galeria = getGaleriaEl();
  if (!galeria) return;

  const itens = [
    ...anexosSalvos.map((anexo) => ({ tipo: 'salvo', ...anexo })),
    ...anexosPendentes.map((anexo) => ({ tipo: 'pendente', ...anexo })),
  ];

  if (!itens.length) {
    galeria.innerHTML = '<div class="os-anexos-vazio">Nenhum anexo vinculado a esta OS.</div>';
    return;
  }

  galeria.innerHTML = itens.map((item) => {
    const src = item.previewUrl || item.blobUrl || '';
    const nome = esc(item.nome_arquivo || item.nome || 'Imagem');
    const remover = item.tipo === 'salvo'
      ? `onclick="removerAnexoSalvoOs(${item.id})"`
      : `onclick="removerAnexoPendenteOs('${item.tempId}')"`;

    return `
      <div class="os-anexo-card" data-anexo-tipo="${item.tipo}"${item.url ? ` data-anexo-url="${item.url}"` : ''}>
        <div class="os-anexo-thumb" title="Clique para ampliar">
          ${src ? `<img src="${src}" alt="${nome}">` : '<div class="os-anexo-thumb-loading"><i class="ti ti-loader-2"></i></div>'}
        </div>
        <div class="os-anexo-meta">
          <span class="os-anexo-nome" title="${nome}">${nome}</span>
          ${item.tipo === 'pendente' ? '<span class="os-anexo-badge">Novo</span>' : ''}
        </div>
        <button type="button" class="os-anexo-remover" title="Remover anexo" ${remover}>
          <i class="ti ti-x"></i>
        </button>
      </div>`;
  }).join('');
}

async function hidratarPreviewsSalvos() {
  for (const anexo of anexosSalvos) {
    if (anexo.blobUrl || !anexo.url) continue;
    anexo.blobUrl = await carregarBlobAutenticado(anexo.url);
  }
  renderGaleriaAnexos();
}

window.carregarAnexosOsModal = async function (osId) {
  if (!osId) {
    anexosSalvos = [];
    renderGaleriaAnexos();
    return;
  }

  plannerOsModalSetContexto(osId);

  try {
    const response = await fetchAutenticado(`/api/op-tasks/${osId}/anexos`);
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(payload.message || 'Erro ao carregar anexos.');

    anexosSalvos = (payload.anexos || []).map((anexo) => ({ ...anexo, blobUrl: null }));
    renderGaleriaAnexos();
    await hidratarPreviewsSalvos();
  } catch (error) {
    anexosSalvos = [];
    renderGaleriaAnexos();
    console.error(error);
  }
};

async function uploadAnexoApi(osId, arquivo, dataUrl) {
  const mimeMatch = String(dataUrl).match(/^data:(image\/[a-z0-9.+-]+);base64,/i);
  const mimeType = mimeMatch?.[1] || arquivo.type || 'image/jpeg';

  const response = await fetchAutenticado(`/api/op-tasks/${osId}/anexos`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      nome_arquivo: arquivo.name || 'anexo.jpg',
      mime_type: mimeType,
      conteudo_base64: dataUrl,
    }),
  });

  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(payload.message || 'Não foi possível salvar o anexo.');
  }

  return payload.anexo;
}

window.adicionarAnexosDiretoOs = async function (osId, arquivos) {
  if (!osId || !arquivos?.length) return 0;

  let enviados = 0;
  for (const arquivo of arquivos) {
    if (!arquivo?.type?.startsWith('image/')) continue;
    const dataUrl = await prepararImagemArquivo(arquivo);
    await uploadAnexoApi(Number(osId), arquivo, dataUrl);
    enviados += 1;
  }

  if (!enviados) {
    throw new Error('Selecione ao menos uma imagem válida.');
  }

  return enviados;
};

function vincularBotaoAnexoDetalhe({ btnId, inputId, getOsId, onUploaded }) {
  const btn = document.getElementById(btnId);
  const input = document.getElementById(inputId);
  if (!btn || !input || btn.dataset.boundDetalhe) return;

  btn.dataset.boundDetalhe = '1';
  btn.addEventListener('click', () => input.click());
  input.addEventListener('change', async () => {
    const osId = getOsId?.();
    const arquivos = [...(input.files || [])];
    input.value = '';
    if (!osId || !arquivos.length) return;

    btn.disabled = true;
    try {
      await window.adicionarAnexosDiretoOs(osId, arquivos);
      await onUploaded?.(osId);
    } catch (error) {
      alert(error.message || 'Erro ao anexar imagem.');
    } finally {
      btn.disabled = false;
    }
  });
}

function vincularAnexosDetalheOs() {
  vincularBotaoAnexoDetalhe({
    btnId: 'os-detalhe-btn-anexo',
    inputId: 'os-detalhe-input-anexo',
    getOsId: () => window.getOsDetalheAtualId?.(),
    onUploaded: (osId) => window.atualizarAnexosDetalheOs?.(osId),
  });

  vincularBotaoAnexoDetalhe({
    btnId: 'ordem-os-detalhe-btn-anexo',
    inputId: 'ordem-os-detalhe-input-anexo',
    getOsId: () => window.getOrdemOsDetalheAtualId?.(),
    onUploaded: (osId) => window.atualizarAnexosOrdemOs?.(osId),
  });
}

async function adicionarArquivos(arquivos) {
  if (!arquivos?.length) return;

  for (const arquivo of arquivos) {
    if (!arquivo?.type?.startsWith('image/')) continue;

    try {
      const dataUrl = await prepararImagemArquivo(arquivo);

      if (osAnexoContextoId) {
        const salvo = await uploadAnexoApi(osAnexoContextoId, arquivo, dataUrl);
        anexosSalvos.unshift({ ...salvo, blobUrl: null });
        const ultimo = anexosSalvos[0];
        ultimo.blobUrl = await carregarBlobAutenticado(ultimo.url);
        renderGaleriaAnexos();
      } else {
        const previewUrl = URL.createObjectURL(arquivo);
        anexosPendentes.push({
          tempId: crypto.randomUUID(),
          nome: arquivo.name || 'anexo.jpg',
          arquivo,
          dataUrl,
          previewUrl,
        });
        renderGaleriaAnexos();
      }
    } catch (error) {
      alert(error.message || 'Erro ao anexar imagem.');
    }
  }
}

window.enviarAnexosPendentesOs = async function (osId) {
  if (!osId || !anexosPendentes.length) return;

  for (const item of [...anexosPendentes]) {
    await uploadAnexoApi(osId, item.arquivo, item.dataUrl);
    if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
  }

  anexosPendentes = [];
  await carregarAnexosOsModal(osId);
};

window.removerAnexoPendenteOs = function (tempId) {
  const idx = anexosPendentes.findIndex((item) => item.tempId === tempId);
  if (idx < 0) return;
  const [removido] = anexosPendentes.splice(idx, 1);
  if (removido?.previewUrl) URL.revokeObjectURL(removido.previewUrl);
  renderGaleriaAnexos();
};

window.excluirAnexoOs = async function (osId, anexoId) {
  if (!osId || !anexoId) return false;
  if (!confirm('Remover este anexo da ordem de serviço?')) return false;

  const response = await fetchAutenticado(`/api/op-tasks/${osId}/anexos/${anexoId}`, {
    method: 'DELETE',
  });
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(payload.message || 'Não foi possível remover o anexo.');
  }

  return true;
};

window.removerAnexoSalvoOs = async function (anexoId) {
  if (!osAnexoContextoId) return;

  try {
    const removido = anexosSalvos.find((item) => item.id === anexoId);
    const ok = await window.excluirAnexoOs(osAnexoContextoId, anexoId);
    if (!ok) return;

    if (removido?.blobUrl) URL.revokeObjectURL(removido.blobUrl);
    anexosSalvos = anexosSalvos.filter((item) => item.id !== anexoId);
    renderGaleriaAnexos();
  } catch (error) {
    alert(error.message || 'Erro ao remover anexo.');
  }
};

function vincularExcluirAnexoDetalhe() {
  if (document.body.dataset.anexoExcluirDetalheBound) return;
  document.body.dataset.anexoExcluirDetalheBound = '1';

  document.addEventListener('click', async (event) => {
    const btn = event.target.closest('.os-anexo-detalhe-remover');
    if (!btn) return;

    event.preventDefault();
    event.stopPropagation();

    const anexoId = Number(btn.dataset.anexoId);
    const galeriaKanban = document.getElementById('os-detalhe-anexos-galeria');
    const galeriaOrdem = document.getElementById('ordem-os-detalhe-anexos-galeria');

    let osId = null;
    let atualizar = null;

    if (galeriaKanban?.contains(btn)) {
      osId = window.getOsDetalheAtualId?.();
      atualizar = () => window.atualizarAnexosDetalheOs?.(osId);
    } else if (galeriaOrdem?.contains(btn)) {
      osId = window.getOrdemOsDetalheAtualId?.();
      atualizar = () => window.atualizarAnexosOrdemOs?.(osId);
    }

    if (!osId || !anexoId) return;

    btn.disabled = true;
    try {
      const ok = await window.excluirAnexoOs(osId, anexoId);
      if (ok) await atualizar?.();
    } catch (error) {
      alert(error.message || 'Erro ao remover anexo.');
    } finally {
      btn.disabled = false;
    }
  });
};

function vincularAnexoOsModal() {
  const btnAnexo = document.getElementById('os-btn-anexo');
  const inputAnexo = document.getElementById('os-input-anexo');
  if (!btnAnexo || !inputAnexo || btnAnexo.dataset.bound) return;

  btnAnexo.dataset.bound = '1';
  btnAnexo.addEventListener('click', () => inputAnexo.click());
  inputAnexo.addEventListener('change', () => {
    const arquivos = [...(inputAnexo.files || [])];
    inputAnexo.value = '';
    adicionarArquivos(arquivos);
  });
}

let osTecnicosCatalogo = [];
let osTecnicosSelecionados = [];

function getOsTecnicosTagsEl() {
  return document.getElementById('os-tecnicos-tags');
}

function getOsTecnicosDropdownEl() {
  return document.getElementById('os-tecnicos-dropdown');
}

function renderOsTecnicosTags() {
  const tags = getOsTecnicosTagsEl();
  if (!tags) return;

  if (!osTecnicosSelecionados.length) {
    tags.innerHTML = '<span id="os-tecnicos-placeholder" style="color:var(--gray-400);font-size:13px">Selecione...</span>';
    return;
  }

  tags.innerHTML = osTecnicosSelecionados.map((tec) => `
    <span style="background:#e8f2fc;color:#0c447c;font-size:11px;font-weight:500;padding:3px 8px;border-radius:20px;display:inline-flex;align-items:center;gap:4px">
      ${esc(tec.nome)}
      <i class="ti ti-x" style="font-size:10px;cursor:pointer" data-os-tecnico-remover="${esc(String(tec.id))}"></i>
    </span>`).join('');
}

function renderOsTecnicosDropdown() {
  const dropdown = getOsTecnicosDropdownEl();
  if (!dropdown) return;

  if (!osTecnicosCatalogo.length) {
    dropdown.innerHTML = '<div style="padding:8px 12px;font-size:13px;color:var(--gray-400)">Nenhum técnico disponível</div>';
    return;
  }

  dropdown.innerHTML = osTecnicosCatalogo.map((tec) => {
    const jaSelecionado = osTecnicosSelecionados.some((item) => item.id === tec.id);
    const estilo = jaSelecionado
      ? 'padding:8px 12px;font-size:13px;color:var(--gray-400);cursor:default'
      : 'padding:8px 12px;cursor:pointer;font-size:13px;color:var(--gray-950)';
    const acao = jaSelecionado ? '' : `data-os-tecnico-add="${tec.id}"`;

    return `<div style="${estilo}" ${acao}>${esc(tec.nome)}</div>`;
  }).join('');
}

async function buscarTecnicosOsModal(regiao) {
  const url = regiao
    ? `/api/tecnicos?regiao=${encodeURIComponent(regiao)}`
    : '/api/tecnicos';
  const response = await fetchAutenticado(url);
  if (!response.ok) return [];
  const data = await response.json();
  return Array.isArray(data) ? data : [];
}

window.resetOsTecnicosModal = function () {
  osTecnicosSelecionados = [];
  renderOsTecnicosTags();
  const dropdown = getOsTecnicosDropdownEl();
  if (dropdown) dropdown.style.display = 'none';
};

window.getOsTecnicosValor = function () {
  return osTecnicosSelecionados.map((tec) => tec.nome).join(', ');
};

window.setOsTecnicosValor = function (valor = '') {
  const nomes = String(valor || '')
    .split(',')
    .map((nome) => nome.trim())
    .filter(Boolean);

  osTecnicosSelecionados = nomes.map((nome) => {
    const cadastrado = osTecnicosCatalogo.find((tec) => tec.nome === nome);
    return cadastrado
      ? { id: cadastrado.id, nome: cadastrado.nome }
      : { id: `legacy-${nome}`, nome };
  });

  renderOsTecnicosTags();
};

window.carregarTecnicosOsModal = async function (regiao = null) {
  let tecnicos = await buscarTecnicosOsModal(regiao);
  if (regiao && tecnicos.length === 0) {
    tecnicos = await buscarTecnicosOsModal(null);
  }

  osTecnicosCatalogo = tecnicos;
  renderOsTecnicosDropdown();
  renderOsTecnicosTags();
};

window.toggleOsTecnicosDropdown = function () {
  const dropdown = getOsTecnicosDropdownEl();
  if (!dropdown) return;
  dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
};

function vincularOsTecnicosModal() {
  const wrap = document.getElementById('os-tecnicos-wrap');
  if (!wrap || wrap.dataset.bound) return;

  wrap.dataset.bound = '1';

  wrap.addEventListener('click', (event) => {
    const addBtn = event.target.closest('[data-os-tecnico-add]');
    if (addBtn) {
      event.stopPropagation();
      const id = Number(addBtn.dataset.osTecnicoAdd);
      const cadastro = osTecnicosCatalogo.find((tec) => tec.id === id);
      const nome = cadastro?.nome || '';
      if (!nome || osTecnicosSelecionados.some((tec) => tec.id === id)) return;
      osTecnicosSelecionados.push({ id, nome });
      renderOsTecnicosTags();
      renderOsTecnicosDropdown();
      getOsTecnicosDropdownEl().style.display = 'none';
      return;
    }

    const removerBtn = event.target.closest('[data-os-tecnico-remover]');
    if (removerBtn) {
      event.stopPropagation();
      const id = removerBtn.dataset.osTecnicoRemover;
      osTecnicosSelecionados = osTecnicosSelecionados.filter((tec) => String(tec.id) !== String(id));
      renderOsTecnicosTags();
      renderOsTecnicosDropdown();
    }
  });

  document.addEventListener('click', (event) => {
    const dropdown = getOsTecnicosDropdownEl();
    if (!dropdown || dropdown.style.display === 'none') return;
    if (wrap.contains(event.target)) return;
    dropdown.style.display = 'none';
  });
}

function iniciar() {
  vincularAnexoOsModal();
  vincularOsTecnicosModal();
  vincularAnexosDetalheOs();
  vincularExcluirAnexoDetalhe();
  renderGaleriaAnexos();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', iniciar);
} else {
  iniciar();
}
