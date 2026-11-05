// resources/js/ui.js
// Filtros: los formularios con data-autosubmit se envían solos al cambiar una lista desplegable
// o una fecha, y al vaciar el buscador con la "x". El texto del buscador se envía con Enter.

document.addEventListener('change', (event) => {
  const el = event.target;
  if (!(el instanceof HTMLElement)) return;

  const form = el.closest('form[data-autosubmit]');
  if (form && el.matches('select, input[type="date"]')) form.requestSubmit();
});

document.addEventListener('search', (event) => {
  const input = event.target;
  if (!(input instanceof HTMLInputElement) || input.value !== '') return;

  const form = input.closest('form[data-autosubmit]');
  if (form) form.requestSubmit();
});

function initSearchFields() {
  document.querySelectorAll('.hp-search').forEach((wrapper) => {
    if (!(wrapper instanceof HTMLElement)) return;

    const input = wrapper.querySelector('input[type="search"]');
    if (!(input instanceof HTMLInputElement)) return;

    let clear = wrapper.querySelector('[data-search-clear]');
    if (!clear) {
      clear = document.createElement('button');
      clear.type = 'button';
      clear.className = 'hp-search-clear';
      clear.dataset.searchClear = '';
      clear.setAttribute('aria-label', 'Borrar búsqueda');
      clear.title = 'Borrar búsqueda';
      clear.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
      wrapper.appendChild(clear);
    }

    const sync = () => { clear.hidden = input.value === ''; };

    clear.addEventListener('click', () => {
      input.value = '';
      input.dispatchEvent(new Event('input', { bubbles: true }));
      sync();

      const form = input.closest('form');
      if (form && input.name) form.requestSubmit();

      input.focus();
    });

    input.addEventListener('input', sync);
    sync();
  });
}

initSearchFields();
