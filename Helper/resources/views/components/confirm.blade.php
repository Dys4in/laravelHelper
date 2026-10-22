{{--
  Ventana de confirmación con el diseño de la app. Incluir UNA vez por layout: <x-confirm />

  Uso declarativo (en cualquier <form>):
    <form method="POST" action="..."
          data-confirm="Texto de la pregunta"
          data-confirm-title="Título"            (opcional)
          data-confirm-text="Texto del botón"    (opcional, por defecto "Confirmar")
          data-confirm-variant="danger|warning|primary"   (opcional, por defecto primary)>

  Uso desde JavaScript (devuelve una promesa con true/false):
    confirmDialog({ title: '...', message: '...', confirmText: 'Eliminar', variant: 'danger' })
        .then(ok => { if (ok) { ... } });
--}}
<script>
(function () {
  if (window.confirmDialog) return;

  // Clases completas (no concatenar) para que Tailwind las detecte.
  var VARIANTS = {
    danger:  { wrap: 'bg-red-100 text-red-600',     btn: 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-300',
      path: 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0' },
    warning: { wrap: 'bg-amber-100 text-amber-600', btn: 'bg-amber-600 hover:bg-amber-700 focus-visible:ring-amber-300',
      path: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z' },
    primary: { wrap: 'bg-brand-soft text-brand',    btn: 'bg-brand hover:brightness-110 focus-visible:ring-indigo-300',
      path: 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z' }
  };
  var SVG = 'http://www.w3.org/2000/svg';
  var open = false;

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text) n.textContent = text;       // textContent: sin riesgo de inyectar HTML
    return n;
  }

  function confirmDialog(opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      if (open) { resolve(false); return; }
      open = true;

      var v = VARIANTS[opts.variant] || VARIANTS.primary;
      var previous = document.activeElement;

      var overlay = el('div', 'fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/40 p-4 opacity-0 transition-opacity duration-200 motion-reduce:transition-none');
      var panel = el('div', 'w-full max-w-md scale-95 rounded-2xl bg-white p-6 opacity-0 shadow-xl ring-1 ring-black/5 transition duration-200 motion-reduce:transition-none');
      panel.setAttribute('role', 'alertdialog');
      panel.setAttribute('aria-modal', 'true');
      panel.setAttribute('aria-labelledby', 'confirm-title');
      panel.setAttribute('aria-describedby', 'confirm-desc');

      var head = el('div', 'flex items-start gap-4');

      var wrap = el('div', 'flex h-11 w-11 shrink-0 items-center justify-center rounded-full ' + v.wrap);
      var svg = document.createElementNS(SVG, 'svg');
      svg.setAttribute('viewBox', '0 0 24 24');
      svg.setAttribute('fill', 'none');
      svg.setAttribute('stroke', 'currentColor');
      svg.setAttribute('stroke-width', '1.8');
      svg.setAttribute('stroke-linecap', 'round');
      svg.setAttribute('stroke-linejoin', 'round');
      svg.setAttribute('class', 'h-6 w-6');
      svg.setAttribute('aria-hidden', 'true');
      var path = document.createElementNS(SVG, 'path');
      path.setAttribute('d', v.path);
      svg.appendChild(path);
      wrap.appendChild(svg);
      head.appendChild(wrap);

      var text = el('div', 'min-w-0 flex-1');
      var title = el('h2', 'text-base font-semibold text-gray-900', opts.title || '¿Estás seguro?');
      title.id = 'confirm-title';
      var desc = el('p', 'mt-1 break-words text-sm text-gray-600', opts.message || '');
      desc.id = 'confirm-desc';
      text.appendChild(title);
      text.appendChild(desc);
      head.appendChild(text);
      panel.appendChild(head);

      var foot = el('div', 'mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end');
      var cancel = el('button', 'rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-300', opts.cancelText || 'Cancelar');
      cancel.type = 'button';
      var ok = el('button', 'rounded-lg px-4 py-2 text-sm font-semibold text-white transition focus:outline-none focus-visible:ring-2 ' + v.btn, opts.confirmText || 'Confirmar');
      ok.type = 'button';
      foot.appendChild(cancel);
      foot.appendChild(ok);
      panel.appendChild(foot);

      overlay.appendChild(panel);
      document.body.appendChild(overlay);

      requestAnimationFrame(function () {
        overlay.classList.remove('opacity-0');
        panel.classList.remove('scale-95', 'opacity-0');
      });
      cancel.focus(); // foco en "Cancelar": la opción segura

      var done = false;
      function finish(result) {
        if (done) return;
        done = true;
        document.removeEventListener('keydown', onKey, true);
        overlay.classList.add('opacity-0');
        panel.classList.add('scale-95', 'opacity-0');
        setTimeout(function () {
          overlay.remove();
          open = false;
          if (previous && previous.focus) previous.focus();
        }, 200);
        resolve(result);
      }

      function onKey(e) {
        if (e.key === 'Escape') { e.preventDefault(); finish(false); return; }
        if (e.key === 'Tab') {            // el foco se queda dentro de la ventana
          if (e.shiftKey && document.activeElement === cancel) { e.preventDefault(); ok.focus(); }
          else if (!e.shiftKey && document.activeElement === ok) { e.preventDefault(); cancel.focus(); }
          else if (!panel.contains(document.activeElement)) { e.preventDefault(); cancel.focus(); }
        }
      }

      document.addEventListener('keydown', onKey, true);
      cancel.addEventListener('click', function () { finish(false); });
      ok.addEventListener('click', function () { finish(true); });
      overlay.addEventListener('mousedown', function (e) { if (e.target === overlay) finish(false); });
    });
  }

  window.confirmDialog = confirmDialog;

  // Cualquier <form data-confirm="..."> pide confirmación antes de enviarse.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
    e.preventDefault();
    e.stopPropagation();
    confirmDialog({
      message: form.getAttribute('data-confirm'),
      title: form.getAttribute('data-confirm-title') || undefined,
      confirmText: form.getAttribute('data-confirm-text') || undefined,
      cancelText: form.getAttribute('data-confirm-cancel') || undefined,
      variant: form.getAttribute('data-confirm-variant') || undefined
    }).then(function (ok) {
      if (ok) HTMLFormElement.prototype.submit.call(form); // submit() no vuelve a disparar este evento
    });
  }, true);
})();
</script>
