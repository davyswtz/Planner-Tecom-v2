const GRUPOS_VARIAVEIS = [
  { titulo: 'Ordem de serviço', keys: ['os_tipo', 'os_sequencia', 'task_code', 'titulo', 'responsavel', 'descricao', 'regiao', 'status_novo'], osOnly: true },
  { titulo: 'Sequência de OS', keys: ['os_sequencia', 'os_lista', 'os_total'] },
  { titulo: 'Resumo de OS (finalização)', keys: ['os_total', 'os_finalizadas', 'os_resumo_tecnicos', 'os_resumo'] },
  { titulo: 'Tarefa pai', keys: ['parent_titulo', 'parent_task_code', 'parent_categoria_label', 'parent_categoria', 'parent_task_id'], osOnly: true },
  { titulo: 'Identificação', keys: ['task_code', 'titulo', 'id', 'categoria', 'categoria_label', 'os_tipo', 'numero_os', 'ordem_servico', 'etiquetas'] },
  { titulo: 'Etiquetas', keys: ['etiquetas', 'etiquetas_localizacao', 'etiquetas_coordenadas'] },
  { titulo: 'Status', keys: ['status', 'status_anterior', 'status_novo', 'historico'] },
  { titulo: 'Localização (Telegram: coords clicáveis)', keys: ['regiao', 'setor', 'elemento', 'cto', 'coordenadas', 'localizacao', 'localizacao_texto'] },
  { titulo: 'Operação', keys: ['responsavel', 'prioridade', 'prazo', 'clientes_afetados', 'descricao', 'sub_processo'] },
  { titulo: 'Cliente', keys: ['nome_cliente', 'protocolo'] },
  { titulo: 'Datas', keys: ['criada_em', 'atualizada_em', 'data_entrada', 'data_instalacao', 'assinada_em', 'assinada_por'] },
  { titulo: 'Outros', keys: ['duracao_ativa', 'parent_task_id', 'parent_task_code', 'parent_titulo', 'parent_categoria', 'parent_categoria_label', 'is_parent_task'] },
];

const ICONES_CATEGORIA = {
  rompimentos: 'ti-bolt',
  'troca-poste': 'ti-building-community',
  'troca-etiqueta': 'ti-tag',
  'otimizacao-rede': 'ti-network',
  'atendimento-cliente': 'ti-headset',
  'correcao-atenuacao': 'ti-antenna-bars-5',
  'manutencao-corretiva': 'ti-tool',
  'certificacao-cemig': 'ti-certificate',
  'ordem-servico': 'ti-clipboard-list',
};

const VARS_RAPIDAS_OS = ['parent_titulo', 'parent_task_code', 'os_tipo', 'responsavel', 'descricao', 'task_code', 'regiao'];
const VARS_RAPIDAS_OPERACIONAL = ['task_code', 'titulo', 'numero_os', 'setor', 'regiao', 'responsavel', 'coordenadas', 'localizacao', 'status_anterior', 'status_novo', 'os_resumo'];
const VARS_RAPIDAS_ETIQUETA = ['task_code', 'numero_os', 'regiao', 'responsavel', 'etiquetas_localizacao', 'etiquetas_coordenadas', 'status_novo'];

const STATUS_WEBHOOK = {
  'ordem-servico': { Aberta: false },
};

const GRUPOS_EMOJIS = [
  { label: 'Status', emojis: ['✅', '❌', '⚠️', '🚨', '🔧', '🔄', '⏳', '📋', '🛑', '🔴', '🟡', '🟢'] },
  { label: 'Alertas', emojis: ['📢', '📣', '🔔', '💥', '❗', '‼️', '🔥', '⚡', '💡', '🎯'] },
  { label: 'Local', emojis: ['📍', '🗺️', '🌐', '🏠', '🏢', '🧭'] },
  { label: 'Pessoas', emojis: ['👤', '👥', '🧑‍🔧', '👷', '🤝'] },
  { label: 'Docs', emojis: ['🧾', '📌', '📝', '💻', '🔑', '🆔', '📄', '📎'] },
  { label: 'Rede', emojis: ['📡', '🛜', '🔌', '📶', '📞', '🔗'] },
  { label: 'Símbolos', emojis: ['⭐', '✨', '➡️', '━', '•', '—'] },
];

let catalogo = [];
let grupos = {};
let placeholders = [];
let placeholdersPorChave = {};
let templates = {};
let padroes = {};
let customizados = {};
let categoriaAtiva = null;
let alterado = false;
let campoAtivoId = null;
let campoAtivoRotulo = '';
let emojiTabAtiva = 0;
let variavelFiltro = '';
const statusExpandidos = {};

function iconeCategoria(key) {
  return ICONES_CATEGORIA[key] || 'ti-folder';
}

function isOsCategoria(key) {
  return key === 'ordem-servico';
}

function statusDisparaWebhook(categoria, status) {
  if (STATUS_WEBHOOK[categoria]?.[status] === false) return false;
  return true;
}

function gruposCatalogoOrdenados() {
  const ordem = ['operacional', 'ordem-servico'];
  const chaves = [...new Set([...ordem, ...Object.keys(grupos)])];
  return chaves
    .map((key) => ({ key, meta: grupos[key] }))
    .filter((item) => item.meta && catalogo.some((cat) => cat.grupo === item.key));
}

function atualizarContextoCategoria(cat) {
  const banner = document.getElementById('msg-context-banner');
  const varsDestaque = document.getElementById('msg-vars-destaque');

  if (!banner) return;

  if (!cat) {
    banner.className = 'msg-context-banner';
    banner.innerHTML = '';
    varsDestaque?.classList.remove('visible');
    if (varsDestaque) varsDestaque.innerHTML = '';
    return;
  }

  const grupoMeta = grupos[cat.grupo] || {};
  const descricao = cat.descricao || grupoMeta.descricao || '';
  const isOs = isOsCategoria(cat.key);

  if (isOs || descricao) {
    banner.className = `msg-context-banner${isOs ? ' os' : ''}`;
    banner.innerHTML = `
      <i class="ti ${isOs ? 'ti-clipboard-list' : 'ti-info-circle'}"></i>
      <div>
        <strong>${esc(isOs ? 'Mensagens de Ordem de Serviço' : cat.label)}</strong>
        ${esc(descricao || 'Templates enviados ao canal do Telegram quando o status muda. Use {variaveis} — o sistema adapta negrito, coords e menções.')}
      </div>
    `;
  } else {
    banner.className = 'msg-context-banner';
    banner.innerHTML = '';
  }

  const rapidas = isOs
    ? VARS_RAPIDAS_OS
    : (cat.key === 'troca-etiqueta' ? VARS_RAPIDAS_ETIQUETA : VARS_RAPIDAS_OPERACIONAL);
  const tituloRapidas = isOs
    ? 'Variáveis mais usadas na OS'
    : 'Variáveis mais usadas (Telegram)';

  if (varsDestaque) {
    varsDestaque.classList.add('visible');
    varsDestaque.innerHTML = `
      <div class="msg-vars-destaque-titulo">${tituloRapidas}</div>
      <div class="msg-vars-chips">
        ${rapidas.map((key) => `
          <button type="button" class="msg-var-chip" data-var-rapida="${esc(key)}" title="${esc(placeholdersPorChave[key]?.hint || placeholdersPorChave[key]?.label || key)}">{${esc(key)}}</button>
        `).join('')}
      </div>
    `;
  }
}

function gruposVariaveisAtivos() {
  const isOs = isOsCategoria(categoriaAtiva);
  return GRUPOS_VARIAVEIS.filter((grupo) => !grupo.osOnly || isOs);
}

function token() {
  return localStorage.getItem('planner_token');
}

function esc(valor) {
  if (valor == null) return '';
  return String(valor)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function idCampo(categoria, status) {
  return `msg-${categoria}-${status}`.replace(/[^a-zA-Z0-9_-]/g, '_');
}

function chaveStatusColapsado(categoria, status) {
  return `${categoria}|${status}`;
}

function isCustomizado(categoria, status) {
  return Boolean(customizados[categoria]?.[status]);
}

function getCampoAtivo() {
  return campoAtivoId ? document.getElementById(campoAtivoId) : null;
}

function mostrarFeedback(mensagem, tipo = 'ok') {
  const el = document.getElementById('mensagens-feedback');
  if (!el) return;
  el.textContent = mensagem;
  el.className = `mensagens-feedback ${tipo}`;
  clearTimeout(mostrarFeedback._timer);
  mostrarFeedback._timer = setTimeout(() => {
    el.className = 'mensagens-feedback';
  }, 4500);
}

function marcarAlterado() {
  alterado = true;
  const btn = document.getElementById('btn-salvar-mensagens');
  if (btn) btn.disabled = false;
  document.getElementById('msg-save-mobile')?.classList.add('is-visible');
}

function limparAlterado() {
  alterado = false;
  const btn = document.getElementById('btn-salvar-mensagens');
  if (btn) btn.disabled = true;
  document.getElementById('msg-save-mobile')?.classList.remove('is-visible');
}

function garantirStatusVisivel(cat) {
  if (!cat?.statuses?.length) return;
  const algumAberto = cat.statuses.some(
    (status) => statusExpandidos[chaveStatusColapsado(cat.key, status)]
  );
  if (!algumAberto) {
    statusExpandidos[chaveStatusColapsado(cat.key, cat.statuses[0])] = true;
  }
}

function atualizarCabecalhoEditor(cat) {
  const titulo = document.getElementById('mensagens-editor-titulo');
  const sub = document.getElementById('mensagens-editor-sub');
  if (!cat) {
    if (titulo) titulo.textContent = 'Mensagens';
    if (sub) sub.textContent = 'Templates enviados ao canal do Telegram';
    return;
  }
  if (titulo) titulo.textContent = cat.label;
  const qtd = cat.statuses?.length || 0;
  const customQtd = (cat.statuses || []).filter((s) => isCustomizado(cat.key, s)).length;
  if (sub) {
    if (isOsCategoria(cat.key)) {
      sub.textContent = customQtd > 0
        ? `${qtd} status · ${customQtd} personalizada${customQtd === 1 ? '' : 's'} · comentário no post pai (Telegram)`
        : `${qtd} status · comentário no post da tarefa pai no Telegram`;
    } else {
      sub.textContent = customQtd > 0
        ? `${qtd} status · ${customQtd} personalizada${customQtd === 1 ? '' : 's'} · post no canal Telegram`
        : `${qtd} status · post no canal Telegram`;
    }
  }
}

async function requestJson(url, options = {}) {
  const response = await fetch(url, {
    ...options,
    headers: {
      Authorization: 'Bearer ' + token(),
      Accept: 'application/json',
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

function inserirNoCampo(campoId, texto) {
  const el = document.getElementById(campoId);
  if (!el) return false;

  const inicio = el.selectionStart ?? el.value.length;
  const fim = el.selectionEnd ?? el.value.length;
  el.value = el.value.slice(0, inicio) + texto + el.value.slice(fim);
  el.focus();
  el.selectionStart = el.selectionEnd = inicio + texto.length;
  el.dispatchEvent(new Event('input', { bubbles: true }));
  atualizarContador(el);
  return true;
}

function aplicarFormatacao(tipo) {
  const el = getCampoAtivo();
  if (!el) {
    mostrarFeedback('Clique no campo da mensagem antes de aplicar formatação.', 'err');
    return;
  }

  if (tipo === 'sep') {
    inserirNoCampo(el.id, '\n━━━━━━━━━━━━━━━━━━━━\n');
    return;
  }

  const mapa = {
    bold: ['*', '*', 'texto'],
    italic: ['_', '_', 'texto'],
    strike: ['~', '~', 'texto'],
    mono: ['`', '`', 'código'],
  };
  const [antes, depois, padrao] = mapa[tipo] || ['', '', ''];
  const inicio = el.selectionStart ?? 0;
  const fim = el.selectionEnd ?? 0;
  const selecionado = el.value.slice(inicio, fim) || padrao;
  const novo = antes + selecionado + depois;
  el.value = el.value.slice(0, inicio) + novo + el.value.slice(fim);
  el.focus();
  el.selectionStart = inicio + antes.length;
  el.selectionEnd = inicio + antes.length + selecionado.length;
  el.dispatchEvent(new Event('input', { bubbles: true }));
  atualizarContador(el);
}

function atualizarContador(el) {
  const contador = document.getElementById(`count-${el.id}`);
  if (!contador) return;
  const len = el.value.length;
  contador.textContent = `${len} caractere${len === 1 ? '' : 's'}`;
  contador.classList.toggle('warn', len > 3500);
}

/** Prévia visual igual ao Telegram (HTML: negrito, links, menções). */
function renderizarFormatoTelegram(textoHtml, textoBruto) {
  if (textoHtml) {
    return String(textoHtml).replace(/\n/g, '<br>');
  }

  const safe = esc(textoBruto);
  return safe
    .replace(/&lt;users\/([0-9]+)&gt;/g, '<span class="msg-chat-mention">@usuário</span>')
    .replace(/@([^\s&<,]+(?:\s+[^\s&<,]+){0,3})/g, '<span class="msg-chat-mention">@$1</span>')
    .replace(/&lt;(https?:\/\/[^|&]+)\|([^&]+)&gt;/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$2</a>')
    .replace(/\*([^*\n]+)\*/g, '<strong>$1</strong>')
    .replace(/_([^_\n]+)_/g, '<em>$1</em>')
    .replace(/~([^~\n]+)~/g, '<s>$1</s>')
    .replace(/`([^`\n]+)`/g, '<code>$1</code>');
}

function renderVariaveisPopover() {
  const body = document.getElementById('variaveis-popover-body');
  if (!body) return;

  const filtro = variavelFiltro.trim().toLowerCase();
  const chips = [];

  gruposVariaveisAtivos().forEach((grupo) => {
    const itens = grupo.keys
      .map((key) => placeholdersPorChave[key])
      .filter((ph) => ph && (!filtro
        || ph.key.includes(filtro)
        || ph.label.toLowerCase().includes(filtro)
        || (ph.hint || '').toLowerCase().includes(filtro)
        || (ph.exemplo || '').toLowerCase().includes(filtro)));

    if (!itens.length) return;

    chips.push(`<div class="msg-var-grupo-titulo">${esc(grupo.titulo)}</div><div class="msg-var-grupo-lista">`);
    itens.forEach((ph) => {
      const hint = ph.hint || ph.exemplo || '';
      chips.push(`
        <button type="button" class="msg-var-item" data-var-key="${esc(ph.key)}" title="${esc(hint)}">
          <span class="msg-var-item-key">{${esc(ph.key)}}</span>
          <span class="msg-var-item-label">${esc(ph.label)}</span>
          ${hint ? `<span class="msg-var-item-hint">${esc(hint)}</span>` : ''}
        </button>
      `);
    });
    chips.push('</div>');
  });

  body.innerHTML = chips.length
    ? chips.join('')
    : '<div class="msg-var-vazio">Nenhuma variável encontrada.</div>';
}

function renderEmojiPopover() {
  const tabs = document.getElementById('emoji-popover-tabs');
  const grid = document.getElementById('emoji-popover-grid');
  if (!tabs || !grid) return;

  tabs.innerHTML = GRUPOS_EMOJIS.map((grupo, index) => `
    <button type="button" class="emoji-popover-tab ${index === emojiTabAtiva ? 'active' : ''}"
      title="${esc(grupo.label)}" onclick="window.__msgSelecionarEmojiTab(${index})">
      ${grupo.emojis[0] || '😀'}
    </button>
  `).join('');

  const grupo = GRUPOS_EMOJIS[emojiTabAtiva] || GRUPOS_EMOJIS[0];
  grid.innerHTML = (grupo?.emojis || []).map((emoji) => `
    <button type="button" class="emoji-popover-btn" data-emoji="${emoji}">${emoji}</button>
  `).join('');
}

function posicionarPopover(popoverId, anchor) {
  const popover = document.getElementById(popoverId);
  if (!popover || !anchor) return;

  const rect = anchor.getBoundingClientRect();
  const margem = 12;
  const largura = popover.offsetWidth || 320;
  const altura = popover.offsetHeight || 360;

  let left = rect.left;
  let top = rect.bottom + 8;

  if (left + largura > window.innerWidth - margem) {
    left = window.innerWidth - largura - margem;
  }
  if (left < margem) left = margem;
  if (top + altura > window.innerHeight - margem) {
    top = rect.top - altura - 8;
  }
  if (top < margem) top = margem;

  popover.style.left = `${left}px`;
  popover.style.top = `${top}px`;
}

function fecharPopovers(exceto = null) {
  ['emoji-popover', 'variaveis-popover'].forEach((id) => {
    if (id === exceto) return;
    document.getElementById(id)?.classList.remove('open');
  });
}

function toolbarHtml(campoId, catLabel, status) {
  const rotulo = `${catLabel} · ${status}`;
  return `
    <div class="msg-toolbar" role="toolbar" aria-label="Formatação da mensagem">
      <button type="button" class="msg-tool-btn" title="Negrito (*texto*)" onclick="window.__msgFormatar('bold')"><i class="ti ti-bold"></i></button>
      <button type="button" class="msg-tool-btn" title="Itálico (_texto_)" onclick="window.__msgFormatar('italic')"><i class="ti ti-italic"></i></button>
      <button type="button" class="msg-tool-btn" title="Tachado (~texto~)" onclick="window.__msgFormatar('strike')"><i class="ti ti-strikethrough"></i></button>
      <button type="button" class="msg-tool-btn" title="Monoespaçado (\`texto\`)" onclick="window.__msgFormatar('mono')"><i class="ti ti-code"></i></button>
      <span class="msg-tool-sep"></span>
      <button type="button" class="msg-tool-btn" title="Linha separadora" onclick="window.__msgFormatar('sep')"><i class="ti ti-separator-horizontal"></i></button>
      <button type="button" class="msg-tool-btn" data-emoji-trigger title="Inserir emoji"
        onclick="window.__msgAbrirEmoji('${esc(campoId)}', '${esc(rotulo)}', this, event)">
        <i class="ti ti-mood-smile"></i>
      </button>
      <button type="button" class="msg-tool-btn" data-var-trigger title="Inserir variável"
        onclick="window.__msgAbrirVariaveis('${esc(campoId)}', '${esc(rotulo)}', this, event)">
        <i class="ti ti-braces"></i>
      </button>
      <span class="msg-tool-spacer"></span>
      <span class="msg-tool-hint">Telegram: *negrito* · _itálico_ · \`código\` · {variáveis}</span>
    </div>
  `;
}

function renderEditor() {
  const cat = catalogo.find((item) => item.key === categoriaAtiva);
  const wrap = document.getElementById('mensagens-editor-wrap');

  if (!cat || !wrap) {
    atualizarCabecalhoEditor(null);
    atualizarContextoCategoria(null);
    if (wrap) wrap.innerHTML = '<div class="mensagens-loading">Selecione uma categoria.</div>';
    return;
  }

  garantirStatusVisivel(cat);
  atualizarCabecalhoEditor(cat);
  atualizarContextoCategoria(cat);

  wrap.innerHTML = `
    <div class="mensagens-editor">
      ${cat.statuses.map((status) => {
        const campoId = idCampo(cat.key, status);
        const valor = templates[cat.key]?.[status] ?? '';
        const custom = isCustomizado(cat.key, status);
        const colapsado = !statusExpandidos[chaveStatusColapsado(cat.key, status)];
        const webhookAtivo = statusDisparaWebhook(cat.key, status);
        return `
          <div class="mensagens-status-card ${colapsado ? 'collapsed' : ''}" data-categoria="${esc(cat.key)}" data-status="${esc(status)}">
            <div class="mensagens-status-head"
              onclick="window.__msgToggleStatus('${esc(cat.key)}', '${esc(status)}', this.querySelector('.mensagens-status-collapse-btn'), event)">
              <div class="mensagens-status-head-left">
                <button type="button" class="mensagens-status-collapse-btn"
                  aria-expanded="${colapsado ? 'false' : 'true'}"
                  onclick="window.__msgToggleStatus('${esc(cat.key)}', '${esc(status)}', this, event)">
                  <i class="ti ti-chevron-down"></i>
                </button>
                <span class="mensagens-status-title">${esc(status)}</span>
              </div>
              <div class="msg-status-meta">
                <span class="msg-webhook-tag ${webhookAtivo ? 'on' : 'off'}">${webhookAtivo ? 'Webhook' : 'Sem webhook'}</span>
                <span class="mensagens-status-badge ${custom ? 'custom' : ''}">${custom ? 'Personalizada' : 'Padrão'}</span>
              </div>
            </div>
            <div class="mensagens-status-content">
              <div class="mensagens-status-body">
                ${toolbarHtml(campoId, cat.label, status)}
                <textarea id="${campoId}" class="mensagens-textarea" rows="8" spellcheck="false"
                  placeholder="Escreva a mensagem enviada ao mudar para &quot;${esc(status)}&quot;..."
                  oninput="window.__msgOnInput('${esc(cat.key)}', '${esc(status)}')"
                  onfocus="window.__msgDefinirCampo('${campoId}', '${esc(cat.label)} · ${esc(status)}')"
                >${esc(valor)}</textarea>
                <div class="mensagens-status-foot">
                  <span class="mensagens-char-count" id="count-${campoId}">${valor.length} caracteres</span>
                  <div class="mensagens-status-actions">
                    <button type="button" class="mensagens-btn" onclick="window.__msgPreview('${esc(cat.key)}', '${esc(status)}')">
                      <i class="ti ti-eye"></i> Pré-visualizar
                    </button>
                    <button type="button" class="mensagens-btn" onclick="window.__msgRestaurar('${esc(cat.key)}', '${esc(status)}')">
                      <i class="ti ti-refresh"></i> Restaurar padrão
                    </button>
                  </div>
                </div>
                <div class="mensagens-preview-wrap" id="wrap-preview-${campoId}">
                  <div class="mensagens-preview-label">Pré-visualização Telegram (dados de exemplo)</div>
                  <div class="mensagens-preview" id="preview-${campoId}"></div>
                </div>
              </div>
            </div>
          </div>
        `;
      }).join('')}
    </div>
  `;

  wrap.querySelectorAll('.mensagens-textarea').forEach((el) => atualizarContador(el));
}

function renderBotaoCategoria(cat) {
  const ativo = cat.key === categoriaAtiva;
  const osClass = isOsCategoria(cat.key) ? ' os-cat' : '';
  return `
    <button type="button" class="mensagens-cat-btn${osClass} ${ativo ? 'active' : ''}"
      onclick="window.__msgSelecionarCategoria('${esc(cat.key)}')">
      <i class="ti ${iconeCategoria(cat.key)}"></i> ${esc(cat.label)}
    </button>
  `;
}

function renderCategorias() {
  const sidebar = document.getElementById('mensagens-categorias');
  const mobile = document.getElementById('mensagens-categorias-mobile-inner');

  const htmlSidebar = gruposCatalogoOrdenados().map(({ key, meta }) => {
    const itens = catalogo.filter((cat) => cat.grupo === key);
    if (!itens.length) return '';
    return `
      <div class="msg-sidebar-grupo">
        <p class="msg-sidebar-grupo-label">${esc(meta.label || key)}</p>
        <div class="msg-sidebar-grupo-lista">
          ${itens.map((cat) => renderBotaoCategoria(cat)).join('')}
        </div>
      </div>
    `;
  }).join('');

  const htmlMobile = gruposCatalogoOrdenados().flatMap(({ key, meta }) => {
    const itens = catalogo.filter((cat) => cat.grupo === key);
    if (!itens.length) return [];
    return [
      `<span class="msg-cat-pill msg-cat-sep" aria-hidden="true">${esc(meta.label || key)}</span>`,
      ...itens.map((cat) => {
        const ativo = cat.key === categoriaAtiva;
        const osClass = isOsCategoria(cat.key) ? ' os-pill' : '';
        return `
          <button type="button" class="msg-cat-pill${osClass} ${ativo ? 'active' : ''}"
            onclick="window.__msgSelecionarCategoria('${esc(cat.key)}')">
            ${esc(cat.label)}
          </button>
        `;
      }),
    ];
  }).join('');

  if (sidebar) sidebar.innerHTML = htmlSidebar || '<div class="mensagens-loading">Nenhuma categoria.</div>';
  if (mobile) {
    mobile.innerHTML = htmlMobile;
    mobile.querySelector('.msg-cat-pill.active')?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
  }
}

function registrarGlobais() {
  window.__msgFormatar = aplicarFormatacao;
  window.__msgDefinirCampo = (campoId, rotulo) => {
    campoAtivoId = campoId;
    campoAtivoRotulo = rotulo || '';
  };
  window.__msgSelecionarCategoria = (key) => {
    categoriaAtiva = key;
    fecharPopovers();
    const cat = catalogo.find((item) => item.key === key);
    if (cat) garantirStatusVisivel(cat);
    renderCategorias();
    renderEditor();
  };
  window.__msgToggleStatus = (categoria, status, btn, event) => {
    event?.stopPropagation();
    const card = btn?.closest('.mensagens-status-card')
      || document.querySelector(`.mensagens-status-card[data-categoria="${categoria}"][data-status="${status}"]`);
    if (!card) return;
    const chave = chaveStatusColapsado(categoria, status);
    const vaiColapsar = !card.classList.contains('collapsed');
    if (vaiColapsar) delete statusExpandidos[chave];
    else statusExpandidos[chave] = true;
    card.classList.toggle('collapsed', vaiColapsar);
  };
  window.__msgOnInput = (categoria, status) => {
    const campoId = idCampo(categoria, status);
    const el = document.getElementById(campoId);
    const valor = el?.value ?? '';
    if (!templates[categoria]) templates[categoria] = {};
    templates[categoria][status] = valor;

    const padrao = padroes[categoria]?.[status] ?? '';
    if (valor.trim() === padrao.trim()) {
      if (customizados[categoria]) delete customizados[categoria][status];
    } else {
      if (!customizados[categoria]) customizados[categoria] = {};
      customizados[categoria][status] = valor;
    }

    const badge = document.querySelector(
      `.mensagens-status-card[data-categoria="${categoria}"][data-status="${status}"] .mensagens-status-badge`
    );
    if (badge) {
      const custom = isCustomizado(categoria, status);
      badge.textContent = custom ? 'Personalizada' : 'Padrão';
      badge.classList.toggle('custom', custom);
    }

    const cat = catalogo.find((item) => item.key === categoria);
    if (cat) atualizarCabecalhoEditor(cat);
    if (el) atualizarContador(el);
    marcarAlterado();
  };
  window.__msgRestaurar = (categoria, status) => {
    const padrao = padroes[categoria]?.[status] ?? '';
    const campoId = idCampo(categoria, status);
    const el = document.getElementById(campoId);
    if (el) {
      el.value = padrao;
      el.dispatchEvent(new Event('input'));
    }
    document.getElementById(`wrap-preview-${campoId}`)?.classList.remove('open');
  };
  window.__msgPreview = async (categoria, status) => {
    const campoId = idCampo(categoria, status);
    const preview = document.getElementById(`preview-${campoId}`);
    const wrap = document.getElementById(`wrap-preview-${campoId}`);
    const template = document.getElementById(campoId)?.value ?? '';
    if (!preview || !wrap) return;

    wrap.classList.add('open');
    preview.innerHTML = '<span class="mensagens-preview-loading">Gerando pré-visualização...</span>';

    try {
      const data = await requestJson('/api/mensagens-templates/preview', {
        method: 'POST',
        body: JSON.stringify({ categoria, status, template }),
      });
      preview.innerHTML = renderizarFormatoTelegram(data.texto_telegram, data.texto || '—');
    } catch (error) {
      preview.textContent = error.message || 'Falha na pré-visualização.';
    }
  };
  window.__msgAbrirEmoji = (campoId, rotulo, btn, event) => {
    event?.stopPropagation();
    window.__msgDefinirCampo(campoId, rotulo);
    const popover = document.getElementById('emoji-popover');
    if (!popover) return;
    const aberto = popover.classList.contains('open');
    fecharPopovers('emoji-popover');
    if (aberto) return;
    emojiTabAtiva = 0;
    renderEmojiPopover();
    popover.classList.add('open');
    requestAnimationFrame(() => posicionarPopover('emoji-popover', btn));
  };
  window.__msgAbrirVariaveis = (campoId, rotulo, btn, event) => {
    event?.stopPropagation();
    window.__msgDefinirCampo(campoId, rotulo);
    const popover = document.getElementById('variaveis-popover');
    if (!popover) return;
    const aberto = popover.classList.contains('open');
    fecharPopovers('variaveis-popover');
    if (aberto) return;
    variavelFiltro = '';
    const input = document.getElementById('variaveis-popover-search');
    if (input) input.value = '';
    renderVariaveisPopover();
    popover.classList.add('open');
    requestAnimationFrame(() => posicionarPopover('variaveis-popover', btn));
  };
  window.__msgSelecionarEmojiTab = (index) => {
    emojiTabAtiva = index;
    renderEmojiPopover();
  };
  window.salvarMensagens = async () => {
    const btn = document.getElementById('btn-salvar-mensagens');
    if (btn) btn.disabled = true;
    try {
      const data = await requestJson('/api/mensagens-templates', {
        method: 'PUT',
        body: JSON.stringify({ templates }),
      });
      templates = data.templates || templates;
      customizados = data.customizados || {};
      limparAlterado();
      renderEditor();
      mostrarFeedback(data.message || 'Mensagens salvas com sucesso.');
    } catch (error) {
      mostrarFeedback(error.message || 'Não foi possível salvar.', 'err');
      if (btn) btn.disabled = false;
    }
  };
}

function vincularPopovers() {
  document.getElementById('emoji-popover-grid')?.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-emoji]');
    if (!btn) return;
    const el = getCampoAtivo();
    if (!el) {
      mostrarFeedback('Selecione um campo de mensagem.', 'err');
      return;
    }
    inserirNoCampo(el.id, btn.dataset.emoji || '');
    fecharPopovers();
  });

  document.getElementById('variaveis-popover-body')?.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-var-key]');
    if (!btn) return;
    const el = getCampoAtivo();
    if (!el) {
      mostrarFeedback('Selecione um campo de mensagem.', 'err');
      return;
    }
    inserirNoCampo(el.id, `{${btn.dataset.varKey}}`);
    fecharPopovers();
  });

  document.getElementById('variaveis-popover-search')?.addEventListener('input', (event) => {
    variavelFiltro = event.target.value || '';
    renderVariaveisPopover();
  });

  document.getElementById('msg-vars-destaque')?.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-var-rapida]');
    if (!btn) return;
    const el = getCampoAtivo();
    if (!el) {
      mostrarFeedback('Clique no campo da mensagem antes de inserir a variável.', 'err');
      return;
    }
    inserirNoCampo(el.id, `{${btn.dataset.varRapida}}`);
  });

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-emoji-trigger], [data-var-trigger], #emoji-popover, #variaveis-popover')) return;
    fecharPopovers();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') fecharPopovers();
  });
}

async function carregar() {
  try {
    const data = await requestJson('/api/mensagens-templates');
    catalogo = data.catalogo || [];
    grupos = data.grupos || {};
    placeholders = data.placeholders || [];
    placeholdersPorChave = Object.fromEntries(placeholders.map((ph) => [ph.key, ph]));
    templates = data.templates || {};
    customizados = data.customizados || {};
    padroes = {};
    catalogo.forEach((cat) => {
      padroes[cat.key] = cat.padroes || {};
    });
    categoriaAtiva = catalogo[0]?.key || null;
    if (categoriaAtiva) {
      const cat = catalogo.find((c) => c.key === categoriaAtiva);
      if (cat) garantirStatusVisivel(cat);
    }
    renderCategorias();
    renderEditor();
  } catch (error) {
    const wrap = document.getElementById('mensagens-editor-wrap');
    if (wrap) {
      wrap.innerHTML = `<div class="mensagens-loading" style="color:#b91c1c">${esc(error.message || 'Falha ao carregar.')}</div>`;
    }
  }
}

export function initMensagensPage() {
  if (typeof window.plannerPossuiPermissao === 'function' && !window.plannerPossuiPermissao('visualizar_tela_mensagens')) {
    window.location.replace('/dashboard');
    return;
  }

  registrarGlobais();
  vincularPopovers();

  window.addEventListener('beforeunload', (event) => {
    if (alterado) {
      event.preventDefault();
      event.returnValue = '';
    }
  });

  carregar();
}
