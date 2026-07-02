(function () {
  function esc(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function formatarData(valor) {
    if (!valor) return '—';
    const d = new Date(valor);
    if (Number.isNaN(d.getTime())) return esc(String(valor).slice(0, 10));
    return d.toLocaleDateString('pt-BR');
  }

  function formatarDataHora(valor) {
    if (!valor) return '—';
    const d = new Date(valor);
    if (Number.isNaN(d.getTime())) return esc(String(valor));
    return d.toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  function normalizarStatusOs(status) {
    const chave = String(status || '').toLowerCase().replace(/_/g, ' ').trim();
    if (chave === 'em andamento') return 'Em andamento';
    if (chave === 'finalizada' || chave === 'concluida' || chave === 'concluída') return 'Finalizada';
    if (chave === 'aberta' || chave === '') return 'Aberta';
    return status || '—';
  }

  function statusDot(status) {
    const normalizado = normalizarStatusOs(status);
    const cls = normalizado === 'Finalizada'
      ? 'status-dot--finalizada'
      : normalizado === 'Em andamento'
        ? 'status-dot--andamento'
        : 'status-dot--aberta';
    return `<span class="status-dot ${cls}">${esc(normalizado)}</span>`;
  }

  function preenchido(valor) {
    if (valor == null || valor === undefined) return false;
    if (typeof valor === 'number' && !Number.isNaN(valor)) return true;
    const texto = String(valor).trim();
    return texto !== '' && texto !== '—' && texto !== '-';
  }

  function dataValida(valor) {
    if (!preenchido(valor)) return false;
    const d = new Date(valor);
    return !Number.isNaN(d.getTime());
  }

  function campoDetalhe(label, valor, span) {
    if (!preenchido(valor)) return '';
    const spanClass = span === 3 ? ' span-3' : span === 2 ? ' span-2' : '';
    return `
      <div class="detail-field${spanClass}">
        <span class="detail-label">${label}</span>
        <div class="detail-value">${valor}</div>
      </div>`;
  }

  function renderDescricao(descricao) {
    const bruto = String(descricao || '').trim();
    if (!bruto) return '';
    const texto = /<[a-z][\s\S]*>/i.test(bruto)
      ? bruto.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
      : bruto;
    if (!texto) return '';
    return esc(texto).replace(/\r?\n/g, '<br>');
  }

  async function carregarBlobAutenticado(url) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(url, {
      headers: { Authorization: 'Bearer ' + token },
      cache: 'no-store',
    });
    if (!response.ok) return null;
    const blob = await response.blob();
    return URL.createObjectURL(blob);
  }

  async function montarGaleriaAnexosDetalhe(osId) {
    const token = localStorage.getItem('planner_token');
    const response = await fetch(`/api/op-tasks/${osId}/anexos`, {
      headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
      cache: 'no-store',
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || !Array.isArray(payload.anexos) || !payload.anexos.length) {
      return '<div class="os-anexos-vazio">Nenhum anexo vinculado a esta OS.</div>';
    }

    const cards = await Promise.all(payload.anexos.map(async (anexo) => {
      const blobUrl = await carregarBlobAutenticado(anexo.url);
      if (!blobUrl) return '';
      const nome = esc(anexo.nome_arquivo || 'Imagem');
      return `
        <div class="os-anexo-detalhe-item">
          <button type="button" class="os-anexo-detalhe-card"
            data-anexo-src="${blobUrl}"
            data-anexo-nome="${nome}"
            data-anexo-download="${anexo.url}"
            title="Clique para ampliar">
            <img src="${blobUrl}" alt="${nome}">
          </button>
          <button type="button" class="os-anexo-detalhe-remover" data-anexo-id="${anexo.id}" title="Excluir anexo">
            <i class="ti ti-trash"></i>
          </button>
        </div>`;
    }));

    const conteudo = cards.filter(Boolean).join('');
    return conteudo || '<div class="os-anexos-vazio">Nenhum anexo vinculado a esta OS.</div>';
  }

  async function montarAnexosDetalhe(osId) {
    const galeria = await montarGaleriaAnexosDetalhe(osId);

    return `
      <div class="detail-field span-3" id="os-detalhe-anexos-wrap" style="margin-top:16px">
        <span class="detail-label">Anexos</span>
        <div class="detail-value" style="min-height:auto;padding:10px">
          <div class="os-anexos-detalhe" id="os-detalhe-anexos-galeria">${galeria}</div>
        </div>
      </div>`;
  }

  async function atualizarAnexosDetalheOs(osId) {
    const galeria = document.getElementById('os-detalhe-anexos-galeria');
    if (!galeria) return;
    galeria.innerHTML = '<div class="os-anexos-vazio"><i class="ti ti-loader-2"></i> Atualizando anexos…</div>';
    galeria.innerHTML = await montarGaleriaAnexosDetalhe(osId);
  }

  function renderLoading() {
    document.getElementById('modal-os-detalhe-titulo').textContent = 'Ordem de serviço';
    document.getElementById('modal-os-detalhe-subtitulo').textContent = 'Carregando…';
    document.getElementById('modal-os-detalhe-conteudo').innerHTML =
      '<div class="os-detalhe-loading"><i class="ti ti-loader-2"></i> Carregando detalhes…</div>';
  }

  function renderErro(mensagem) {
    document.getElementById('modal-os-detalhe-titulo').textContent = 'Ordem de serviço';
    document.getElementById('modal-os-detalhe-subtitulo').textContent = 'Erro';
    document.getElementById('modal-os-detalhe-conteudo').innerHTML =
      `<div class="os-detalhe-erro">${esc(mensagem)}</div>`;
  }

  async function renderDetalhe(os) {
    const titulo = os.numero_os || os.ordem_servico || os.taskCode || `OS #${os.id}`;
    const setorCto = [os.setor, os.cto].filter((valor) => preenchido(valor)).join(' · ');
    const numeroOs = os.numero_os || os.ordem_servico || '';
    const descricaoHtml = renderDescricao(os.descricao);
    const campos = [
      campoDetalhe('Técnico', preenchido(os.responsavel) ? esc(os.responsavel) : ''),
      campoDetalhe('Status', preenchido(os.status) ? statusDot(os.status) : ''),
      campoDetalhe('Região', preenchido(os.regiao) ? esc(os.regiao) : ''),
      campoDetalhe('Número da OS', preenchido(numeroOs) ? esc(numeroOs) : ''),
      campoDetalhe('Código', preenchido(os.taskCode) ? esc(os.taskCode) : ''),
      campoDetalhe('Prioridade', preenchido(os.prioridade) ? esc(os.prioridade) : ''),
      campoDetalhe('Título', preenchido(os.titulo) ? esc(os.titulo) : '', 3),
      campoDetalhe('Protocolo', preenchido(os.protocolo) ? esc(os.protocolo) : ''),
      campoDetalhe('Cliente', preenchido(os.nome_cliente) ? esc(os.nome_cliente) : ''),
      campoDetalhe('Setor / CTO', preenchido(setorCto) ? esc(setorCto) : '', 2),
      campoDetalhe('Localização', preenchido(os.localizacao_texto) ? esc(os.localizacao_texto) : '', 2),
      campoDetalhe('Coordenadas', preenchido(os.coordenadas) ? esc(os.coordenadas) : ''),
      campoDetalhe('Criada em', dataValida(os.criadaEm) ? formatarDataHora(os.criadaEm) : ''),
      campoDetalhe('Concluída em', dataValida(os.assinada_em) ? formatarData(os.assinada_em) : ''),
      campoDetalhe('Assinada por', preenchido(os.assinada_por) ? esc(os.assinada_por) : ''),
      campoDetalhe('Descrição', descricaoHtml, 3),
    ].filter(Boolean).join('');

    const anexosHtml = await montarAnexosDetalhe(os.id);

    document.getElementById('modal-os-detalhe-titulo').textContent = titulo;
    document.getElementById('modal-os-detalhe-subtitulo').textContent = os.titulo || 'Ordem de serviço';
    document.getElementById('modal-os-detalhe-conteudo').innerHTML = campos
      ? `<div class="detail-grid">${campos}${anexosHtml}</div>`
      : `<div class="detail-grid">${anexosHtml}<div class="os-detalhe-loading">Nenhum detalhe adicional para esta OS.</div></div>`;
  }

  let osDetalheAtualId = null;

  function fecharDetalheOs() {
    document.getElementById('modal-os-detalhe-overlay')?.classList.remove('open');
    osDetalheAtualId = null;
    document.querySelectorAll('.os-card.is-active').forEach((card) => card.classList.remove('is-active'));
  }

  async function abrirDetalheOs(id) {
    const overlay = document.getElementById('modal-os-detalhe-overlay');
    if (!overlay || !id) return;

    osDetalheAtualId = String(id);
    document.querySelectorAll('.os-card.is-active').forEach((card) => {
      card.classList.toggle('is-active', card.dataset.osId === osDetalheAtualId);
    });

    overlay.classList.add('open');
    renderLoading();

    const token = localStorage.getItem('planner_token');
    try {
      const response = await fetch('/api/op-tasks/' + id, {
        headers: {
          Authorization: 'Bearer ' + token,
          Accept: 'application/json',
        },
        cache: 'no-store',
      });
      const payload = await response.json().catch(() => ({}));
      if (osDetalheAtualId !== String(id)) return;

      if (!response.ok) {
        renderErro(payload.message || 'Não foi possível carregar os detalhes da OS.');
        return;
      }

      await renderDetalhe(payload);
    } catch (error) {
      if (osDetalheAtualId !== String(id)) return;
      renderErro('Erro de conexão ao carregar a OS.');
    }
  }

  function deveIgnorarClique(target) {
    return Boolean(target.closest(
      '.os-card-actions, .btn-edit-os, .btn-delete-os, .os-status-wrap, .os-status-badge, .os-status-pills, .os-status-pill, .os-status-close'
    ));
  }

  document.addEventListener('click', (event) => {
    const card = event.target.closest('.os-card[data-os-id]');
    if (!card || deveIgnorarClique(event.target)) return;
    abrirDetalheOs(card.dataset.osId);
  });

  document.getElementById('modal-os-detalhe-overlay')?.addEventListener('click', (event) => {
    if (event.target.id === 'modal-os-detalhe-overlay') fecharDetalheOs();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.getElementById('modal-os-detalhe-overlay')?.classList.contains('open')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      fecharDetalheOs();
    }
  }, true);

  window.abrirDetalheOs = abrirDetalheOs;
  window.fecharDetalheOs = fecharDetalheOs;
  window.getOsDetalheAtualId = () => osDetalheAtualId;
  window.atualizarAnexosDetalheOs = atualizarAnexosDetalheOs;
})();
