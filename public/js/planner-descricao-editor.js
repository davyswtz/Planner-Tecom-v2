/**
 * Editor de descrição com texto rico, imagens (anexo e Ctrl+V) e exibição segura.
 */
const ALLOWED_TAGS = new Set(['P', 'BR', 'DIV', 'IMG']);

function escHtml(texto) {
  return String(texto ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function pareceHtml(valor) {
  return /<img[\s>]/i.test(valor) || /<\/(p|div|br)\s*>/i.test(valor) || /<br\s*\/?>/i.test(valor);
}

export function sanitizeDescricaoHtml(html) {
  if (!html) return '';

  const doc = new DOMParser().parseFromString(String(html), 'text/html');

  function limparNo(no) {
    const filhos = [...no.childNodes];
    for (const filho of filhos) {
      if (filho.nodeType !== Node.ELEMENT_NODE) continue;

      if (!ALLOWED_TAGS.has(filho.tagName)) {
        while (filho.firstChild) no.insertBefore(filho.firstChild, filho);
        no.removeChild(filho);
        continue;
      }

      if (filho.tagName === 'IMG') {
        const src = filho.getAttribute('src') || '';
        if (!/^data:image\/(png|jpe?g|gif|webp);base64,/i.test(src)) {
          no.removeChild(filho);
          continue;
        }
        [...filho.attributes].forEach((attr) => {
          if (attr.name !== 'src') filho.removeAttribute(attr.name);
        });
        filho.setAttribute('alt', 'Imagem');
      } else {
        [...filho.attributes].forEach((attr) => filho.removeAttribute(attr.name));
      }

      limparNo(filho);
    }
  }

  limparNo(doc.body);
  return doc.body.innerHTML.trim();
}

export function renderDescricaoView(valor) {
  if (valor == null || String(valor).trim() === '') return '—';

  const bruto = String(valor).trim();
  const html = pareceHtml(bruto)
    ? sanitizeDescricaoHtml(bruto)
    : escHtml(bruto).replace(/\r?\n/g, '<br>');

  const soTexto = html.replace(/<[^>]+>/g, '').replace(/\u00a0/g, ' ').trim();
  if (!soTexto && !html.includes('<img')) return '—';

  return `<div class="descricao-view-shell"><div class="descricao-view">${html}</div></div>`;
}

export function createDescricaoEditorMarkup(placeholder = 'Detalhes da tarefa (opcional)', { compact = false } = {}) {
  const toolbar = compact ? '' : `
        <div class="descricao-editor-toolbar">
          <div class="descricao-toolbar-left">
            <button type="button" class="descricao-btn-anexo" title="Selecionar imagem do computador">
              <i class="ti ti-photo-plus"></i>
              <span>Anexar imagem</span>
            </button>
            <input type="file" class="descricao-input-anexo" accept="image/*" hidden />
          </div>
        </div>`;

  return `
    <div class="descricao-editor-wrap${compact ? ' descricao-editor-wrap--compact' : ''}" data-descricao-editor>
      <div class="descricao-editor-shell">
        ${toolbar}
        <div class="descricao-editor-body">
          <div
            class="descricao-editor"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            data-placeholder="${escHtml(placeholder)}"
          ></div>
        </div>
      </div>
    </div>`;
}

function lerArquivoComoDataUrl(arquivo) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = () => reject(new Error('Não foi possível ler a imagem.'));
    reader.readAsDataURL(arquivo);
  });
}

function redimensionarImagem(dataUrl, maxLado = 1280, qualidade = 0.82) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => {
      let { width, height } = img;
      const escala = Math.min(1, maxLado / Math.max(width, height));
      width = Math.max(1, Math.round(width * escala));
      height = Math.max(1, Math.round(height * escala));

      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      const ctx = canvas.getContext('2d');
      if (!ctx) {
        resolve(dataUrl);
        return;
      }
      ctx.drawImage(img, 0, 0, width, height);
      resolve(canvas.toDataURL('image/jpeg', qualidade));
    };
    img.onerror = () => reject(new Error('Imagem inválida.'));
    img.src = dataUrl;
  });
}

async function prepararImagem(arquivo) {
  if (!arquivo?.type?.startsWith('image/')) {
    throw new Error('Selecione um arquivo de imagem.');
  }
  if (arquivo.size > 12 * 1024 * 1024) {
    throw new Error('A imagem deve ter no máximo 12 MB.');
  }
  const dataUrl = await lerArquivoComoDataUrl(arquivo);
  return redimensionarImagem(dataUrl);
}

function inserirImagemNoEditor(editor, dataUrl) {
  editor.focus();
  const img = document.createElement('img');
  img.src = dataUrl;
  img.alt = 'Imagem';
  img.className = 'descricao-img-inline';

  const sel = window.getSelection();
  if (sel?.rangeCount) {
    const range = sel.getRangeAt(0);
    range.deleteContents();
    range.insertNode(img);
    range.setStartAfter(img);
    range.collapse(true);
    sel.removeAllRanges();
    sel.addRange(range);
  } else {
    editor.appendChild(img);
  }

  editor.appendChild(document.createElement('br'));
}

function vincularEditor(wrap) {
  if (!wrap || wrap.dataset.bound) return wrap;
  wrap.dataset.bound = '1';

  const shell = wrap.querySelector('.descricao-editor-shell');
  const editor = wrap.querySelector('.descricao-editor');
  const btnAnexo = wrap.querySelector('.descricao-btn-anexo');
  const inputAnexo = wrap.querySelector('.descricao-input-anexo');
  if (!editor) return wrap;

  async function adicionarImagem(arquivo) {
    try {
      const dataUrl = await prepararImagem(arquivo);
      inserirImagemNoEditor(editor, dataUrl);
    } catch (err) {
      alert(err.message || 'Erro ao adicionar imagem.');
    }
  }

  btnAnexo?.addEventListener('click', () => inputAnexo?.click());
  inputAnexo?.addEventListener('change', () => {
    const arquivo = inputAnexo.files?.[0];
    inputAnexo.value = '';
    if (arquivo) adicionarImagem(arquivo);
  });

  editor.addEventListener('focus', () => shell?.classList.add('is-focused'));
  editor.addEventListener('blur', () => shell?.classList.remove('is-focused'));

  editor.addEventListener('paste', (event) => {
    const itens = event.clipboardData?.items;
    if (!itens) return;

    for (const item of itens) {
      if (!item.type.startsWith('image/')) continue;
      event.preventDefault();
      const arquivo = item.getAsFile();
      if (arquivo) adicionarImagem(arquivo);
      return;
    }
  });

  shell?.addEventListener('dragenter', (event) => {
    if (![...event.dataTransfer?.types || []].includes('Files')) return;
    event.preventDefault();
  });

  shell?.addEventListener('dragleave', (event) => {
    if (![...event.dataTransfer?.types || []].includes('Files')) return;
  });

  shell?.addEventListener('dragover', (event) => {
    if ([...event.dataTransfer?.types || []].includes('Files')) {
      event.preventDefault();
    }
  });

  shell?.addEventListener('drop', (event) => {
    const arquivo = [...event.dataTransfer?.files || []].find((f) => f.type.startsWith('image/'));
    if (!arquivo) return;
    event.preventDefault();
    adicionarImagem(arquivo);
  });

  return wrap;
}

export function mountDescricaoEditor(container, { html = '', placeholder, compact = false } = {}) {
  if (!container) return null;

  container.innerHTML = createDescricaoEditorMarkup(placeholder, { compact });
  const wrap = container.querySelector('[data-descricao-editor]');
  vincularEditor(wrap);

  const editor = wrap?.querySelector('.descricao-editor');
  if (editor) {
    const conteudo = html ? (pareceHtml(html) ? sanitizeDescricaoHtml(html) : escHtml(html).replace(/\r?\n/g, '<br>')) : '';
    editor.innerHTML = conteudo;
  }

  return wrap;
}

export function getDescricaoEditorValue(wrapOrContainer) {
  const wrap = wrapOrContainer?.matches?.('[data-descricao-editor]')
    ? wrapOrContainer
    : wrapOrContainer?.querySelector?.('[data-descricao-editor]');

  const editor = wrap?.querySelector('.descricao-editor');
  if (!editor) return '';

  const html = sanitizeDescricaoHtml(editor.innerHTML);
  const texto = html.replace(/<[^>]+>/g, '').replace(/\u00a0/g, ' ').trim();
  if (!texto && !html.includes('<img')) return '';
  return html;
}

export async function prepararImagemArquivo(arquivo) {
  return prepararImagem(arquivo);
}

export function resetDescricaoEditor(container) {
  mountDescricaoEditor(container, { html: '', compact: container?.dataset?.compact === '1' });
}

function resolverWrapDescricao(wrapOrContainer) {
  return wrapOrContainer?.matches?.('[data-descricao-editor]')
    ? wrapOrContainer
    : wrapOrContainer?.querySelector?.('[data-descricao-editor]');
}

export async function appendImagemAoEditor(wrapOrContainer, arquivo) {
  const wrap = resolverWrapDescricao(wrapOrContainer);
  const editor = wrap?.querySelector('.descricao-editor');
  if (!editor) {
    throw new Error('Editor de descrição não encontrado.');
  }

  const dataUrl = await prepararImagem(arquivo);
  inserirImagemNoEditor(editor, dataUrl);
}
