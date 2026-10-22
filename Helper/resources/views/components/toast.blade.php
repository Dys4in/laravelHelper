{{--
  Avisos flotantes (toasts) globales. Incluir UNA vez por layout: <x-toast />

  Se muestran solos:
    - redirect()->with('status',  '...')  → verde  (éxito)
    - redirect()->with('error',   '...')  → rojo
    - redirect()->with('warning', '...')  → ámbar
    - redirect()->with('info',    '...')  → azul
    - Errores de validación ($errors)     → un solo aviso rojo con la lista

  Desde JavaScript (cualquier vista):
    toast.success('Guardado');  toast.error('Algo falló');
    toast.warning('...');       toast.info('...');
    toast({ type: 'error', title: 'Título', message: 'Texto', items: ['a', 'b'], duration: 6000 });
--}}
@php
    // Estados que Breeze ya muestra en línea en la página de perfil: no duplicarlos.
    $skip = ['profile-updated', 'password-updated', 'verification-link-sent'];
    $queue = [];

    $status = session('status');
    if (is_string($status) && $status !== '' && ! in_array($status, $skip, true)) {
        $queue[] = ['type' => 'success', 'message' => $status];
    }
    foreach (['error' => 'error', 'warning' => 'warning', 'info' => 'info'] as $key => $type) {
        if (is_string(session($key)) && session($key) !== '') {
            $queue[] = ['type' => $type, 'message' => session($key)];
        }
    }
    if ($errors->any()) {
        $all = array_values(array_unique($errors->all()));
        // Un solo error: se muestra como mensaje. Varios: lista con título.
        $queue[] = count($all) === 1
            ? ['type' => 'error', 'message' => $all[0]]
            : ['type' => 'error', 'title' => 'Revisa los datos', 'items' => $all];
    }
@endphp

<div id="toast-root"
     class="pointer-events-none fixed right-4 top-4 z-[100] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3"
     role="region" aria-label="Notificaciones" aria-live="polite"></div>

<script>
(function () {
  if (window.toast) return;

  // Clases completas (no concatenar) para que Tailwind las detecte.
  var TYPES = {
    success: { bar: 'bg-green-500', icon: 'text-green-600', title: 'Listo',
      path: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    error:   { bar: 'bg-red-500',   icon: 'text-red-600',   title: 'Error',
      path: 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    warning: { bar: 'bg-amber-500', icon: 'text-amber-600', title: 'Atención',
      path: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z' },
    info:    { bar: 'bg-brand',     icon: 'text-brand',     title: 'Información',
      path: 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z' }
  };
  var DURATION = { success: 5000, info: 5000, warning: 6500, error: 8000 };
  var SVG = 'http://www.w3.org/2000/svg';

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text) n.textContent = text;       // textContent: sin riesgo de inyectar HTML
    return n;
  }

  function show(opts) {
    var root = document.getElementById('toast-root');
    if (!root) return;
    opts = opts || {};
    var type = TYPES[opts.type] ? opts.type : 'info';
    var t = TYPES[type];
    var duration = opts.duration || DURATION[type];

    var card = el('div', 'pointer-events-auto relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg ring-1 ring-black/5 transition duration-300 ease-out motion-reduce:transition-none opacity-0 translate-x-6');
    card.setAttribute('role', type === 'error' ? 'alert' : 'status');

    card.appendChild(el('span', 'absolute inset-y-0 left-0 w-1 ' + t.bar));

    var row = el('div', 'flex items-start gap-3 py-3 pl-5 pr-3');

    var svg = document.createElementNS(SVG, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.8');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('class', 'mt-0.5 h-5 w-5 shrink-0 ' + t.icon);
    svg.setAttribute('aria-hidden', 'true');
    var p = document.createElementNS(SVG, 'path');
    p.setAttribute('d', t.path);
    svg.appendChild(p);
    row.appendChild(svg);

    var body = el('div', 'min-w-0 flex-1');
    body.appendChild(el('p', 'text-sm font-semibold text-gray-900', opts.title || t.title));
    if (opts.message) body.appendChild(el('p', 'mt-0.5 break-words text-sm text-gray-600', opts.message));
    if (opts.items && opts.items.length) {
      var ul = el('ul', 'mt-1 list-disc space-y-0.5 pl-4 text-sm text-gray-600');
      opts.items.forEach(function (i) { ul.appendChild(el('li', '', i)); });
      body.appendChild(ul);
    }
    row.appendChild(body);

    var close = el('button', 'shrink-0 rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600');
    close.type = 'button';
    close.setAttribute('aria-label', 'Cerrar aviso');
    close.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
    row.appendChild(close);
    card.appendChild(row);

    var track = el('div', 'h-1 w-full bg-gray-100');
    var bar = el('div', 'h-full w-full origin-left ' + t.bar);
    track.appendChild(bar);
    card.appendChild(track);

    root.appendChild(card);
    requestAnimationFrame(function () {
      card.classList.remove('opacity-0', 'translate-x-6');
    });

    var closed = false;
    function dismiss() {
      if (closed) return;
      closed = true;
      card.classList.add('opacity-0', 'translate-x-6');
      setTimeout(function () { card.remove(); }, 300);
    }

    // La barra de progreso marca el tiempo; se pausa al pasar el cursor.
    var anim = bar.animate([{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }],
                           { duration: duration, easing: 'linear', fill: 'forwards' });
    anim.onfinish = dismiss;
    card.addEventListener('mouseenter', function () { anim.pause(); });
    card.addEventListener('mouseleave', function () { anim.play(); });
    close.addEventListener('click', dismiss);

    return { dismiss: dismiss };
  }

  window.toast = show;
  ['success', 'error', 'warning', 'info'].forEach(function (type) {
    show[type] = function (message, extra) {
      return show(Object.assign({ type: type, message: message }, extra || {}));
    };
  });
})();

// Avisos que llegan desde el servidor en esta carga de página
(function () {
  var queue = {{ Illuminate\Support\Js::from($queue) }};
  var run = function () { queue.forEach(function (q) { window.toast(q); }); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
  else run();
})();
</script>
