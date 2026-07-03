(function () {
  let estadoAtual = null;

  function getOverlay() {
    return document.getElementById('modal-anexo-viewer-overlay');
  }

  function getToken() {
    return localStorage.getItem('planner_token') || '';
  }

  function fecharVisualizadorAnexo() {
    const overlay = getOverlay();
    if (!overlay) return;
    overlay.classList.remove('open');
    estadoAtual = null;
    const img = document.getElementById('modal-anexo-viewer-img');
    if (img) {
      img.removeAttribute('src');
      img.alt = '';
    }
  }

  async function baixarArquivo(url, nome) {
    const response = await fetch(url, {
      headers: { Authorization: 'Bearer ' + getToken() },
      cache: 'no-store',
    });
    if (!response.ok) throw new Error('Não foi possível baixar o anexo.');
    return response.blob();
  }

  async function baixarAnexoAtual() {
    if (!estadoAtual) return;

    const btn = document.getElementById('modal-anexo-viewer-download');
    if (btn) btn.disabled = true;

    try {
      let blob;
      if (estadoAtual.blobUrl) {
        const response = await fetch(estadoAtual.blobUrl);
        blob = await response.blob();
      } else if (estadoAtual.downloadUrl) {
        blob = await baixarArquivo(estadoAtual.downloadUrl, estadoAtual.nome);
      } else {
        throw new Error('Anexo indisponível para download.');
      }

      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = estadoAtual.nome || 'anexo';
      link.click();
      URL.revokeObjectURL(link.href);
    } catch (error) {
      alert(error.message || 'Erro ao baixar o anexo.');
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  window.abrirVisualizadorAnexo = function ({ src, nome, downloadUrl } = {}) {
    const overlay = getOverlay();
    const img = document.getElementById('modal-anexo-viewer-img');
    const nomeEl = document.getElementById('modal-anexo-viewer-nome');
    if (!overlay || !img || !src) return;

    estadoAtual = {
      blobUrl: src.startsWith('blob:') ? src : null,
      src,
      nome: nome || 'anexo',
      downloadUrl: downloadUrl || '',
    };

    if (nomeEl) nomeEl.textContent = estadoAtual.nome;
    img.src = src;
    img.alt = estadoAtual.nome;
    overlay.classList.add('open');
  };

  window.fecharVisualizadorAnexo = fecharVisualizadorAnexo;
  window.baixarAnexoAtual = baixarAnexoAtual;

  document.getElementById('modal-anexo-viewer-overlay')?.addEventListener('click', (event) => {
    if (event.target.id === 'modal-anexo-viewer-overlay') fecharVisualizadorAnexo();
  });

  document.getElementById('modal-anexo-viewer-download')?.addEventListener('click', baixarAnexoAtual);

  document.addEventListener('click', (event) => {
    if (event.target.closest('.os-anexo-detalhe-remover')) return;

    const card = event.target.closest('.os-anexo-detalhe-card[data-anexo-src]');
    if (card) {
      event.preventDefault();
      window.abrirVisualizadorAnexo({
        src: card.dataset.anexoSrc,
        nome: card.dataset.anexoNome,
        downloadUrl: card.dataset.anexoDownload,
      });
      return;
    }

    const thumb = event.target.closest('.os-anexo-thumb');
    if (!thumb || event.target.closest('.os-anexo-remover')) return;

    const anexoCard = thumb.closest('.os-anexo-card');
    const img = thumb.querySelector('img');
    if (!anexoCard || !img?.src) return;

    window.abrirVisualizadorAnexo({
      src: img.src,
      nome: anexoCard.querySelector('.os-anexo-nome')?.textContent?.trim() || 'anexo',
      downloadUrl: anexoCard.dataset.anexoUrl || '',
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !getOverlay()?.classList.contains('open')) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    fecharVisualizadorAnexo();
  }, true);
})();
