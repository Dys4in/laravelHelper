// resources/js/dashboard.js
// En resources/js/app.js agrega:   import './dashboard';
//
// HELPER · Dashboard: sidebar móvil, buscador, contadores y Personal Workspace.
// El status es obligatorio: toda card es 'draft' o 'completed' (si no es 'completed', cuenta como 'draft').

function initSidebar() {
  const sidebar = document.getElementById('hpSidebar');
  if (!sidebar) return;

  const backdrop = document.querySelector('[data-sidebar-close]');
  const toggle = document.querySelector('[data-sidebar-toggle]');

  const setOpen = (open) => {
    sidebar.classList.toggle('is-open', open);
    backdrop?.classList.toggle('is-open', open);
  };

  toggle?.addEventListener('click', () => setOpen(!sidebar.classList.contains('is-open')));
  backdrop?.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
}

function initDocuments() {
  const grid = document.getElementById('docsGrid');
  if (!grid) return;

  const input = document.getElementById('docSearch');
  const noResults = grid.querySelector('[data-no-results]');
  const emptyState = grid.querySelector('[data-empty]');
  const statButtons = document.querySelectorAll('[data-filter]');
  const workspace = document.querySelector('[data-workspace]');

  let activeStatus = 'all';

  // Siempre consulta el DOM: así también cuenta las cards agregadas después.
  const getCards = () => Array.from(grid.querySelectorAll('[data-doc]'));

  // "VIL-PLNT-007" -> "vilplnt007" (ignora mayúsculas, guiones y espacios)
  const normalize = (text) => String(text ?? '').toLowerCase().replace(/[^a-z0-9]/g, '');

  // Status obligatorio: solo 'completed' o 'draft'
  const statusOf = (card) => ((card.dataset.status || '').trim().toLowerCase() === 'completed' ? 'completed' : 'draft');

  // Cada palabra escrita debe aparecer en el código o en el id de la card.
  const matchesSearch = (card, tokens) => {
    if (!tokens.length) return true;
    const haystack = `${normalize(card.dataset.code)}|${normalize(card.dataset.id)}`;
    return tokens.every((t) => haystack.includes(t));
  };

  const syncBadge = (card) => {
    const badge = card.querySelector('[data-badge]');
    if (!badge) return;
    const status = statusOf(card);
    badge.classList.remove('hp-badge--draft', 'hp-badge--completed');
    badge.classList.add(`hp-badge--${status}`);
    if (badge.textContent !== status) badge.textContent = status;
  };

  // Contadores y Personal Workspace (no dependen de la búsqueda)
  const updateCounters = () => {
    const cards = getCards();
    const counts = { all: cards.length, draft: 0, completed: 0 };
    cards.forEach((c) => { counts[statusOf(c)]++; });

    Object.entries(counts).forEach(([key, value]) => {
      const el = document.querySelector(`[data-count="${key}"]`);
      if (el) el.textContent = value;
    });

    if (workspace) {
      const limit = parseInt(workspace.dataset.limit, 10) || 0;
      const pct = limit > 0 ? Math.min(100, Math.round((counts.all / limit) * 100)) : 0;
      const countEl = workspace.querySelector('[data-workspace-count]');
      const bar = workspace.querySelector('[data-workspace-bar]');
      const progress = workspace.querySelector('[data-workspace-progress]');
      if (countEl) countEl.textContent = counts.all;
      if (bar) bar.style.width = `${pct}%`;
      if (progress) progress.setAttribute('aria-valuenow', pct);
    }
  };

  // Búsqueda + filtro por estado
  const applyFilters = () => {
    const query = input ? input.value.trim() : '';
    const tokens = query ? query.split(/\s+/).map(normalize).filter(Boolean) : [];
    const cards = getCards();
    let visible = 0;

    cards.forEach((card) => {
      const show = (activeStatus === 'all' || statusOf(card) === activeStatus) && matchesSearch(card, tokens);
      card.hidden = !show;
      if (show) visible++;
    });

    if (noResults) noResults.hidden = !(cards.length > 0 && visible === 0);
    if (emptyState) emptyState.hidden = cards.length > 0;

    statButtons.forEach((btn) => {
      const pressed = activeStatus !== 'all' && btn.dataset.filter === activeStatus;
      btn.setAttribute('aria-pressed', pressed ? 'true' : 'false');
    });
  };

  const refresh = () => {
    getCards().forEach(syncBadge);
    updateCounters();
    applyFilters();
  };

  input?.addEventListener('input', applyFilters);

  // Clic en un contador = filtrar; otro clic (o en "All") = quitar filtro
  statButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const f = btn.dataset.filter;
      activeStatus = f === activeStatus ? 'all' : f;
      applyFilters();
    });
  });

  // Si cambia el data-status de una card o se agregan/quitan cards, todo se actualiza solo.
  if ('MutationObserver' in window) {
    new MutationObserver(refresh).observe(grid, { attributes: true, attributeFilter: ['data-status'], subtree: true });
    new MutationObserver(refresh).observe(grid, { childList: true }); // solo hijos directos (evita bucles con los badges)
  }

  // Ej.: HelperDocs.setStatus('VIL-PLNT-010', 'completed')
  window.HelperDocs = {
    refresh,
    setStatus(code, status) {
      getCards().forEach((c) => { if (c.dataset.code === code) c.dataset.status = status; });
      refresh();
    },
  };

  refresh();
}

initSidebar();
initDocuments();