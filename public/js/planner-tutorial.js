(function () {
  'use strict';

  const VERSION = String(window.PLANNER_TUTORIAL_VERSION || '20260703');
  const TUTORIAL_ENABLED = window.PLANNER_TUTORIAL_ENABLED !== false;
  const STORAGE_PREFIX = 'planner_tutorial_' + VERSION;

  const STEPS = [
    {
      id: 'welcome',
      center: true,
      icon: 'ti-sparkles',
      title: 'Bem-vindo ao Planner',
      body: 'Este tour rápido mostra as partes principais do sistema. Leva menos de um minuto e você pode pular ou refazer quando quiser.',
    },
    {
      target: '#sidebar',
      placement: 'right',
      icon: 'ti-layout-sidebar',
      title: 'Menu lateral',
      body: 'Aqui você navega entre Dashboard, Tarefas, categorias de serviço (rompimentos, manutenção, OS…) e ferramentas de gestão.',
      expandSidebar: true,
    },
    {
      target: 'a[href="/dashboard"]',
      placement: 'right',
      icon: 'ti-layout-dashboard',
      title: 'Dashboard',
      body: 'Visão geral com métricas, mapa de calor e suas tarefas em andamento. É a página inicial após o login.',
      expandSidebar: true,
    },
    {
      target: '#tutorial-nav-categorias',
      placement: 'right',
      icon: 'ti-category',
      title: 'Categorias',
      body: 'Cada tipo de serviço tem sua própria tela em formato kanban. Arraste os cards entre colunas para atualizar o status.',
      expandSidebar: true,
    },
    {
      target: '#nav-item-tarefas',
      placement: 'right',
      icon: 'ti-checklist',
      title: 'Tarefas',
      body: 'Lista unificada de todas as suas tarefas, com filtros e busca. Disponível conforme sua permissão de acesso.',
      expandSidebar: true,
      optional: true,
    },
    {
      target: '#btn-notificacoes',
      placement: 'bottom',
      icon: 'ti-bell',
      title: 'Notificações',
      body: 'Acompanhe alertas e atualizações importantes. O badge indica quantas notificações ainda não foram lidas.',
    },
    {
      target: '#btn-topbar-atualizar',
      placement: 'bottom',
      icon: 'ti-refresh',
      title: 'Atualizar',
      body: 'Recarrega os dados da tela atual manualmente, útil quando você espera uma mudança recente.',
    },
    {
      target: '#btn-topbar-criar',
      placement: 'bottom',
      icon: 'ti-plus',
      title: 'Criar novo',
      body: 'Abre o formulário para registrar uma nova tarefa ou item, dependendo da tela em que você está.',
      optional: true,
    },
    {
      target: '#btn-theme-toggle',
      placement: 'right',
      placementWhenCollapsed: 'top',
      icon: 'ti-moon',
      title: 'Tema claro / escuro',
      body: 'Alterne entre modo claro e escuro. Sua preferência fica salva no navegador.',
      expandSidebar: true,
    },
    {
      target: '#sidebar-user-card',
      placement: 'right',
      placementWhenCollapsed: 'top',
      icon: 'ti-user',
      title: 'Seu perfil',
      body: 'Veja seu usuário e função. Clique no nome para sair do sistema com segurança.',
      expandSidebar: true,
    },
    {
      id: 'finish',
      center: true,
      icon: 'ti-circle-check',
      title: 'Pronto para começar!',
      body: 'Explore o menu e crie suas primeiras tarefas. Para rever este tour, clique no ícone de ajuda na barra superior.',
    },
  ];

  let root = null;
  let spotlight = null;
  let card = null;
  let activeIndex = 0;
  let running = false;
  let sidebarWasCollapsed = false;
  function getUsername() {
    try {
      const user = JSON.parse(localStorage.getItem('planner_user') || 'null');
      return user?.username || '';
    } catch {
      return '';
    }
  }

  function storageKey() {
    return STORAGE_PREFIX + '_' + (getUsername() || 'guest');
  }

  function isDone() {
    return localStorage.getItem(storageKey()) === '1';
  }

  function markDone() {
    localStorage.setItem(storageKey(), '1');
  }

  function cleanupOldTutorialKeys() {
    const current = storageKey();
    Object.keys(localStorage).forEach((key) => {
      if (key.startsWith('planner_tutorial_') && key !== current) {
        localStorage.removeItem(key);
      }
    });
  }

  function isVisible(el) {
    if (!el) return false;
    const style = window.getComputedStyle(el);
    if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') return false;
    const rect = el.getBoundingClientRect();
    return rect.width > 0 && rect.height > 0;
  }

  function resolveSteps() {
    const resolved = [];
    STEPS.forEach((step) => {
      if (step.center) {
        resolved.push(step);
        return;
      }
      const el = step.target ? document.querySelector(step.target) : null;
      if (!el || !isVisible(el)) return;
      resolved.push(step);
    });
    return resolved;
  }

  function ensureDom() {
    if (root) return;

    root = document.createElement('div');
    root.id = 'planner-tutorial-root';
    root.className = 'planner-tutorial-root';
    root.innerHTML = `
      <div class="planner-tutorial-backdrop" id="planner-tutorial-backdrop"></div>
      <div class="planner-tutorial-spotlight" id="planner-tutorial-spotlight" hidden></div>
      <div class="planner-tutorial-card" id="planner-tutorial-card" role="dialog" aria-modal="true" aria-labelledby="planner-tutorial-title">
        <div class="planner-tutorial-card-head">
          <div class="planner-tutorial-icon" id="planner-tutorial-icon"><i class="ti ti-help"></i></div>
          <button type="button" class="planner-tutorial-close" id="planner-tutorial-close" title="Fechar tour" aria-label="Fechar tour">
            <i class="ti ti-x"></i>
          </button>
        </div>
        <div class="planner-tutorial-progress" id="planner-tutorial-progress"></div>
        <h2 class="planner-tutorial-title" id="planner-tutorial-title"></h2>
        <p class="planner-tutorial-body" id="planner-tutorial-body"></p>
        <div class="planner-tutorial-foot">
          <button type="button" class="planner-tutorial-btn planner-tutorial-btn-ghost" id="planner-tutorial-skip">Pular tour</button>
          <div class="planner-tutorial-nav">
            <button type="button" class="planner-tutorial-btn planner-tutorial-btn-ghost" id="planner-tutorial-prev">Anterior</button>
            <button type="button" class="planner-tutorial-btn planner-tutorial-btn-primary" id="planner-tutorial-next">Próximo</button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(root);

    spotlight = document.getElementById('planner-tutorial-spotlight');
    card = document.getElementById('planner-tutorial-card');

    document.getElementById('planner-tutorial-close').addEventListener('click', finish);
    document.getElementById('planner-tutorial-skip').addEventListener('click', finish);
    document.getElementById('planner-tutorial-prev').addEventListener('click', prev);
    document.getElementById('planner-tutorial-next').addEventListener('click', next);

    window.addEventListener('resize', reposition, { passive: true });
    window.addEventListener('scroll', reposition, { passive: true, capture: true });
  }

  function expandSidebarIfNeeded(step) {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    if (step.expandSidebar) {
      sidebarWasCollapsed = sidebar.classList.contains('collapsed');
      sidebar.classList.remove('collapsed');
      sidebar.classList.add('open');
      const overlay = document.getElementById('sidebar-overlay');
      if (overlay && window.innerWidth <= 768) overlay.classList.add('active');
    }
  }

  function restoreSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    if (sidebarWasCollapsed) sidebar.classList.add('collapsed');
    if (window.innerWidth <= 768) {
      sidebar.classList.remove('open');
      document.getElementById('sidebar-overlay')?.classList.remove('active');
    }
    sidebarWasCollapsed = false;
  }

  function getTargetEl(step) {
    if (step.center) return null;
    return document.querySelector(step.target);
  }

  function getPlacement(step, target) {
    if (!target) return 'center';
    const sidebar = document.getElementById('sidebar');
    const collapsed = sidebar?.classList.contains('collapsed');
    if (collapsed && step.placementWhenCollapsed) return step.placementWhenCollapsed;
    return step.placement || 'bottom';
  }

  function positionSpotlight(target, padding) {
    const pad = padding ?? 10;
    const rect = target.getBoundingClientRect();
    spotlight.hidden = false;
    spotlight.style.top = Math.max(0, rect.top - pad) + 'px';
    spotlight.style.left = Math.max(0, rect.left - pad) + 'px';
    spotlight.style.width = rect.width + pad * 2 + 'px';
    spotlight.style.height = rect.height + pad * 2 + 'px';
  }

  function positionCard(step, target) {
    const margin = 16;
    const gap = 14;
    card.classList.remove('is-center');

    if (!target || step.center) {
      card.classList.add('is-center');
      card.style.top = '';
      card.style.left = '';
      card.style.right = '';
      card.style.bottom = '';
      card.style.transform = '';
      return;
    }

    const placement = getPlacement(step, target);
    const rect = target.getBoundingClientRect();
    const cardRect = card.getBoundingClientRect();
    let top = 0;
    let left = 0;

    if (placement === 'right') {
      top = rect.top + rect.height / 2 - cardRect.height / 2;
      left = rect.right + gap;
      if (left + cardRect.width > window.innerWidth - margin) {
        left = rect.left - cardRect.width - gap;
      }
    } else if (placement === 'left') {
      top = rect.top + rect.height / 2 - cardRect.height / 2;
      left = rect.left - cardRect.width - gap;
    } else if (placement === 'top') {
      top = rect.top - cardRect.height - gap;
      left = rect.left + rect.width / 2 - cardRect.width / 2;
    } else {
      top = rect.bottom + gap;
      left = rect.left + rect.width / 2 - cardRect.width / 2;
    }

    top = Math.min(Math.max(margin, top), window.innerHeight - cardRect.height - margin);
    left = Math.min(Math.max(margin, left), window.innerWidth - cardRect.width - margin);

    card.style.top = top + 'px';
    card.style.left = left + 'px';
    card.style.right = 'auto';
    card.style.bottom = 'auto';
    card.style.transform = 'none';
  }

  function reposition() {
    if (!running) return;
    const steps = resolveSteps();
    const step = steps[activeIndex];
    if (!step) return;
    const target = getTargetEl(step);
    if (target) positionSpotlight(target);
    else spotlight.hidden = true;
    positionCard(step, target);
  }

  function renderStep(index) {
    const steps = resolveSteps();
    if (index < 0 || index >= steps.length) return;
    activeIndex = index;
    const step = steps[index];
    const target = getTargetEl(step);

    expandSidebarIfNeeded(step);

    document.getElementById('planner-tutorial-title').textContent = step.title;
    document.getElementById('planner-tutorial-body').textContent = step.body;
    document.getElementById('planner-tutorial-icon').innerHTML = `<i class="ti ${step.icon || 'ti-help'}"></i>`;

    const total = steps.length;
    document.getElementById('planner-tutorial-progress').textContent = `Passo ${index + 1} de ${total}`;

    const prevBtn = document.getElementById('planner-tutorial-prev');
    const nextBtn = document.getElementById('planner-tutorial-next');
    const skipBtn = document.getElementById('planner-tutorial-skip');

    prevBtn.disabled = index === 0;
    prevBtn.style.visibility = index === 0 ? 'hidden' : 'visible';

    const isLast = index === total - 1;
    nextBtn.textContent = isLast ? 'Concluir' : 'Próximo';
    skipBtn.style.display = isLast ? 'none' : '';

    if (target) {
      target.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'instant' in window ? 'instant' : 'auto' });
      requestAnimationFrame(() => {
        positionSpotlight(target);
        positionCard(step, target);
      });
    } else {
      spotlight.hidden = true;
      requestAnimationFrame(() => positionCard(step, null));
    }
  }

  function start(force) {
    if (running) return;
    if (!TUTORIAL_ENABLED && !force) return;
    if (!force && isDone()) return;
    if (!getUsername() && !force) return;

    ensureDom();
    running = true;
    root.classList.add('is-active');
    document.body.classList.add('planner-tutorial-open');
    activeIndex = 0;
    renderStep(0);
  }

  function finish() {
    if (!running) return;
    running = false;
    markDone();
    restoreSidebar();
    root?.classList.remove('is-active');
    document.body.classList.remove('planner-tutorial-open');
    spotlight.hidden = true;
  }

  function next() {
    const steps = resolveSteps();
    if (activeIndex >= steps.length - 1) {
      finish();
      return;
    }
    renderStep(activeIndex + 1);
  }

  function prev() {
    if (activeIndex <= 0) return;
    renderStep(activeIndex - 1);
  }

  function maybeStart() {
    if (!TUTORIAL_ENABLED) return;
    if (!getUsername()) return;
    cleanupOldTutorialKeys();
    if (isDone()) return;
    setTimeout(() => start(false), 600);
  }

  function reset() {
    localStorage.removeItem(storageKey());
    start(true);
  }

  window.plannerTutorial = {
    start: () => start(true),
    maybeStart,
    reset,
    isDone,
  };

  function bindTutorialButton() {
    const btn = document.getElementById('btn-tutorial');
    btn?.addEventListener('click', () => start(true));
  }

  window.addEventListener('planner-user-ready', maybeStart);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      bindTutorialButton();
      if (window.plannerUserReady) maybeStart();
    });
  } else {
    bindTutorialButton();
    if (window.plannerUserReady) maybeStart();
  }
})();
