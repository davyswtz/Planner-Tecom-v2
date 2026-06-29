/**
 * Tempo real sem WebSocket e sem limites externos (long polling no seu servidor).
 */
(function () {
  const config = window.PLANNER_POLLING;
  if (!config?.enabled) return;

  const timeoutSeg = config.timeoutSec || 25;
  const intervaloFallbackMs = config.fallbackIntervalMs || 5000;
  const debounceFocusMs = config.debounceFocusMs || 2500;

  let ultimaVersao = null;
  let recarregando = false;
  let ativo = false;
  let abortController = null;
  let fallbackTimer = null;
  let pausadoAte = 0;
  let focusDebounceTimer = null;

  function fingerprintDe(data) {
    if (data?.fingerprint) return data.fingerprint;
    return (data?.version ?? '0') + ':' + (data?.total ?? 0);
  }

  function temModalAberto() {
    return !!document.querySelector(
      '.modal-overlay.open, .confirm-excluir-overlay.open, .modal-os-overlay.open, #detalhe-overlay.open'
    );
  }

  function devePausar() {
    return document.hidden || temModalAberto() || recarregando || Date.now() < pausadoAte;
  }

  function montarUrl(longPoll) {
    const params = new URLSearchParams();
    (config.categorias || []).forEach((cat) => params.append('categorias[]', cat));
    if (longPoll) {
      if (ultimaVersao) params.set('since', ultimaVersao);
      params.set('timeout', String(timeoutSeg));
    }
    const base = longPoll ? '/api/planner/changes/wait' : '/api/planner/changes';
    return base + '?' + params.toString();
  }

  async function buscarMudancas(longPoll, { abortarAnterior = true } = {}) {
    const token = localStorage.getItem('planner_token');
    if (!token) return null;

    if (abortarAnterior && abortController) abortController.abort();
    const controller = new AbortController();
    if (abortarAnterior) abortController = controller;

    try {
      const response = await fetch(montarUrl(longPoll), {
        headers: {
          Authorization: 'Bearer ' + token,
          Accept: 'application/json',
        },
        cache: 'no-store',
        signal: controller.signal,
      });

      if (!response.ok) return null;
      return response.json();
    } catch (err) {
      if (err?.name === 'AbortError') return null;
      throw err;
    }
  }

  function pararFallback() {
    if (fallbackTimer) {
      clearInterval(fallbackTimer);
      fallbackTimer = null;
    }
  }

  function iniciarFallback() {
    if (fallbackTimer) return;
    fallbackTimer = setInterval(() => {
      if (!devePausar()) window.plannerPollingTick();
    }, intervaloFallbackMs);
  }

  async function aplicarMudanca(data) {
    const fingerprint = fingerprintDe(data);
    const mudou = data.changed !== false && fingerprint !== ultimaVersao;

    if (ultimaVersao === null) {
      ultimaVersao = fingerprint;
      return;
    }

    if (!mudou) return;

    ultimaVersao = fingerprint;
    if (typeof window.plannerRealtimeReload !== 'function') return;

    recarregando = true;
    try {
      await window.plannerRealtimeReload();
      window.plannerPurgarCardsExcluidos?.();
    } finally {
      recarregando = false;
    }
  }

  window.plannerPausarPolling = function (duracaoMs = 20000) {
    pausadoAte = Date.now() + Math.max(0, duracaoMs);
    if (abortController) abortController.abort();
  };

  window.plannerNotifyLocalMutation = async function () {
    window.plannerPausarPolling(20000);
    recarregando = true;
    if (abortController) abortController.abort();

    try {
      const data = await buscarMudancas(false, { abortarAnterior: false });
      if (data) ultimaVersao = fingerprintDe(data);
    } catch (err) {
      if (err?.name !== 'AbortError') console.warn('[Planner] Falha ao sincronizar estado local.', err);
    } finally {
      recarregando = false;
    }
  };

  async function cicloLongPoll() {
    while (ativo) {
      if (devePausar()) {
        if (abortController) abortController.abort();
        await new Promise((r) => setTimeout(r, 1500));
        continue;
      }

      try {
        const data = await buscarMudancas(true);
        if (!data) {
          iniciarFallback();
          await new Promise((r) => setTimeout(r, 3000));
          continue;
        }

        pararFallback();
        await aplicarMudanca(data);
      } catch (err) {
        if (err?.name === 'AbortError') continue;
        iniciarFallback();
        await new Promise((r) => setTimeout(r, 2000));
      }
    }
  }

  async function verificarMudancas() {
    if (devePausar()) return;
    try {
      const data = await buscarMudancas(false);
      if (!data) return;
      await aplicarMudanca(data);
    } catch (err) {
      if (err?.name !== 'AbortError') console.warn('[Planner] Falha no fallback de polling.', err);
    }
  }

  window.plannerPollingTick = verificarMudancas;

  function agendarVerificacaoPorFoco() {
    clearTimeout(focusDebounceTimer);
    focusDebounceTimer = setTimeout(() => {
      if (!devePausar()) verificarMudancas();
    }, debounceFocusMs);
  }

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) agendarVerificacaoPorFoco();
    if (document.hidden && abortController) abortController.abort();
  });

  window.addEventListener('focus', agendarVerificacaoPorFoco);

  function iniciarPolling() {
    if (ativo) return;
    ativo = true;
    cicloLongPoll();
    console.info('[Planner] Atualização em tempo real ativa (long polling).');
  }

  window.plannerAtivarPollingFallback = function () {
    if (ativo || !config?.enabled) return;
    console.warn('[Planner] Tempo real indisponível — usando polling como fallback.');
    iniciarPolling();
  };

  if (!config.defer) {
    iniciarPolling();
  }
})();
