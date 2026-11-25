{{--
  Ventana de confirmación con el diseño de la app. Incluir UNA vez por layout: <x-confirm />

  Uso declarativo (en cualquier <form>):
    <form method="POST" action="..."
          data-confirm="Texto de la pregunta"
          data-confirm-title="Título"            (opcional)
          data-confirm-text="Texto del botón"    (opcional, por defecto "Confirmar")
          data-confirm-variant="danger|warning|primary|primary-outline"   (opcional, por defecto primary)
          data-confirm-phrase="eliminar">        (opcional: exige escribir esa palabra)

  Uso desde JavaScript (devuelve una promesa con true/false):
    confirmDialog({ title: '...', message: '...', confirmText: 'Eliminar', variant: 'danger' })
        .then(ok => { if (ok) { ... } });
--}}
<script>
(function () {
  if (window.confirmDialog) return;

  // Las clases viven en styles.css (.hp-confirm, .hp-btn-confirm) para compartir el diseño de la app.
  var VARIANTS = {
    danger:  { icon: 'hp-confirm__icon--danger',  btn: 'hp-btn-confirm--danger',
      path: 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0' },
    warning: { icon: 'hp-confirm__icon--warning', btn: 'hp-btn-confirm--warning',
      path: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866 1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z' },
    primary: { icon: 'hp-confirm__icon--primary', btn: 'hp-btn-confirm--primary',
      path: 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z' },
    'primary-outline': { icon: 'hp-confirm__icon--primary', btn: 'hp-btn-confirm--outline',
      path: 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z' }
  };
  var BTN = 'hp-btn-confirm';
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
      var panel = el('div', 'hp-confirm scale-95 opacity-0 transition duration-200 motion-reduce:transition-none');
      panel.setAttribute('role', 'alertdialog');
      panel.setAttribute('aria-modal', 'true');
      panel.setAttribute('aria-labelledby', 'confirm-title');
      panel.setAttribute('aria-describedby', 'confirm-desc');

      var head = el('div', 'hp-confirm__head');

      var wrap = el('div', 'hp-confirm__icon ' + v.icon);
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
      var title = el('h2', 'hp-confirm__title', opts.title || '¿Estás seguro?');
      title.id = 'confirm-title';
      var desc = el('p', 'hp-confirm__text', opts.message || '');
      desc.id = 'confirm-desc';
      text.appendChild(title);
      text.appendChild(desc);
      head.appendChild(text);
      panel.appendChild(head);

      var phraseInput = null;
      if (opts.phrase) {
        var phraseBox = el('div', 'hp-confirm__field');
        var phraseLabel = el('label', 'hp-label', 'Escribe «' + opts.phrase + '» para confirmar');
        phraseLabel.setAttribute('for', 'confirm-phrase');
        phraseInput = el('input', 'hp-input mt-2');
        phraseInput.type = 'text';
        phraseInput.id = 'confirm-phrase';
        phraseInput.autocomplete = 'off';
        phraseInput.spellcheck = false;
        phraseBox.appendChild(phraseLabel);
        phraseBox.appendChild(phraseInput);
        panel.appendChild(phraseBox);
      }

      var foot = el('div', 'hp-confirm__foot');
      var cancel = el('button', BTN + ' hp-btn-confirm--outline', opts.cancelText || 'Cancelar');
      cancel.type = 'button';
      var ok = el('button', BTN + ' ' + v.btn, opts.confirmText || 'Confirmar');
      ok.type = 'button';
      if (opts.phrase) ok.disabled = true;
      if (!opts.alert) foot.appendChild(cancel);
      foot.appendChild(ok);
      panel.appendChild(foot);

      overlay.appendChild(panel);
      document.body.appendChild(overlay);

      requestAnimationFrame(function () {
        overlay.classList.remove('opacity-0');
        panel.classList.remove('scale-95', 'opacity-0');
      });
      (phraseInput || (opts.alert ? ok : cancel)).focus(); // en alertas solo hay un botón

      if (phraseInput) {
        phraseInput.addEventListener('input', function () {
          var expected = String(opts.phrase).trim().toLocaleLowerCase();
          ok.disabled = phraseInput.value.trim().toLocaleLowerCase() !== expected;
        });
        phraseInput.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            if (!ok.disabled) finish(true);
          }
        });
      }

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
          var focusables = Array.prototype.slice.call(panel.querySelectorAll('input, button')).filter(function (n) { return !n.disabled; });
          if (!focusables.length) return;
          var first = focusables[0];
          var last = focusables[focusables.length - 1];
          if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
          else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
          else if (!panel.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
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
      variant: form.getAttribute('data-confirm-variant') || undefined,
      phrase: form.getAttribute('data-confirm-phrase') || undefined
    }).then(function (ok) {
      if (ok) {
        if (window.HelperClickGuard) window.HelperClickGuard.lockForm(form);
        HTMLFormElement.prototype.submit.call(form); // submit() no vuelve a disparar este evento
      }
    });
  }, true);
})();
</script>
