/**
 * Planner Telecom — tempo real via Laravel Echo.
 * Suporta Reverb (VPS Hostinger) e Pusher (hospedagem compartilhada).
 */
(function () {
  const config = window.PLANNER_REALTIME;
  if (!config?.enabled) return;

  const token = localStorage.getItem('planner_token');
  if (!token) return;

  if (typeof Pusher === 'undefined' || typeof Echo === 'undefined') {
    console.warn('[Planner] Pusher/Echo não carregados — tempo real desativado.');
    return;
  }

  window.Pusher = Pusher;

  const auth = {
    authEndpoint: config.authEndpoint,
    auth: {
      headers: {
        Authorization: 'Bearer ' + token,
        Accept: 'application/json',
      },
    },
  };

  const echoOptions = config.driver === 'pusher'
    ? {
        broadcaster: 'pusher',
        key: config.key,
        cluster: config.cluster,
        forceTLS: true,
        ...auth,
      }
    : {
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.host,
        wsPort: config.port,
        wssPort: config.port,
        forceTLS: config.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        ...auth,
      };

  const echo = new Echo(echoOptions);

  let reloadTimer = null;
  let lastEventId = null;

  function shouldHandle(payload) {
    const categorias = window.plannerRealtimeCategorias;
    if (!Array.isArray(categorias) || categorias.length === 0) {
      return typeof window.plannerRealtimeReload === 'function';
    }
    return categorias.includes(payload.categoria);
  }

  function scheduleReload(payload) {
    if (!shouldHandle(payload)) return;

    if (payload?.action === 'deleted' && payload?.id != null) {
      clearTimeout(reloadTimer);
      reloadTimer = null;
      window.plannerRemoverCardKanban?.(payload.id);
      return;
    }

    if (payload?.id != null && window.plannerEstaExcluida?.(payload.id)) {
      return;
    }

    if (typeof window.plannerRealtimeReload !== 'function') return;

    const eventKey = [payload.action, payload.id, payload.status, payload.categoria].join(':');
    if (eventKey === lastEventId) return;
    lastEventId = eventKey;

    clearTimeout(reloadTimer);
    reloadTimer = setTimeout(async () => {
      await window.plannerRealtimeReload();
      window.plannerPurgarCardsExcluidos?.();
      setTimeout(() => { lastEventId = null; }, 300);
    }, 350);
  }

  echo.private('planner.tasks')
    .listen('.OpTaskChanged', (payload) => {
      scheduleReload(payload);
    })
    .error((err) => {
      console.error('[Planner] Falha ao conectar tempo real:', err);
      window.plannerAtivarPollingFallback?.();
    });

  setTimeout(() => {
    const estado = echo.connector?.pusher?.connection?.state;
    if (estado && estado !== 'connected' && estado !== 'connecting') {
      window.plannerAtivarPollingFallback?.();
    }
  }, 8000);

  console.info(`[Planner] Tempo real ativo (${config.driver}) — canal planner.tasks`);
  window.plannerEcho = echo;
})();
