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
