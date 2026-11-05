{{--
  Ventana (modal) reutilizable, con el diseño de la app.

  Definir:
    <x-app-modal id="create-user" title="Nuevo usuario" description="Texto opcional" size="lg">
      <form method="POST" ...>
        @csrf
        <input type="hidden" name="_modal" value="create-user">   (así se reabre sola si el formulario trae errores)
        ...
      </form>
    </x-app-modal>

  Abrir (cualquier botón):
    <button type="button" data-modal-open="create-user">Nuevo usuario</button>

  Rellenar campos al abrir (opcional): data-fill-NOMBRE="valor"  →  [data-fill="NOMBRE"] dentro de la ventana
    <button data-modal-open="use-template" data-fill-template_id="5" data-fill-template_name="Informe">
    <input type="hidden" name="template_id" data-fill="template_id">   <span data-fill="template_name"></span>

  Cerrar: botón/enlace con data-modal-close, tecla Esc, clic fuera o la X.
  JS:  modal.open('id')  ·  modal.close('id')
--}}
@props(['id', 'title', 'description' => null, 'size' => 'lg'])

@php
    $width = match ($size) {
        'sm' => 'max-w-md',
        'md' => 'max-w-lg',
        'xl' => 'max-w-3xl',
        default => 'max-w-2xl',
    };
@endphp

<div id="modal-{{ $id }}" class="fixed inset-0 z-[90] hidden" role="dialog" aria-modal="true" aria-labelledby="modal-{{ $id }}-title" data-app-modal>
  <div class="absolute inset-0 bg-gray-900/40 opacity-0 transition-opacity duration-200 motion-reduce:transition-none" data-modal-backdrop></div>

  <div class="relative flex min-h-full items-center justify-center p-4">
    <div class="w-full {{ $width }} translate-y-2 scale-95 rounded-2xl bg-white opacity-0 shadow-xl ring-1 ring-black/5 transition duration-200 motion-reduce:transition-none" data-modal-panel>
      <div class="flex items-start justify-between gap-4 px-6 pb-2 pt-6">
        <div class="min-w-0">
          <h2 id="modal-{{ $id }}-title" class="text-lg font-bold text-gray-900">{{ $title }}</h2>
          @if ($description)
            <p class="mt-1 text-sm text-gray-600">{{ $description }}</p>
          @endif
        </div>
        <button type="button" data-modal-close class="shrink-0 rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Cerrar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-5 w-5" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
      </div>

      <div class="max-h-[calc(100vh-9rem)] overflow-y-auto px-6 pb-6 pt-4">
        {{ $slot }}
      </div>
    </div>
  </div>
</div>

@once
<script>
(function () {
  if (window.modal) return;

  var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
  var stack = [];

  function get(id) { return document.getElementById('modal-' + id); }

  function fill(box, trigger) {
    if (!trigger) return;
    Object.keys(trigger.dataset).forEach(function (key) {
      if (key.indexOf('fill') !== 0 || key.length < 5) return;
      var name = key.charAt(4).toLowerCase() + key.slice(5);
      box.querySelectorAll('[data-fill="' + name + '"]').forEach(function (target) {
        if ('value' in target) {
          target.value = trigger.dataset[key];
          target.dispatchEvent(new Event('change', { bubbles: true })); // refresca los desplegables personalizados
        } else {
          target.textContent = trigger.dataset[key];
        }
      });
    });
  }

  function open(id, trigger) {
    var box = get(id);
    if (!box || stack.indexOf(id) !== -1) return;

    fill(box, trigger);
    box.__previous = document.activeElement;
    box.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    stack.push(id);

    requestAnimationFrame(function () {
      box.querySelector('[data-modal-backdrop]').classList.remove('opacity-0');
      box.querySelector('[data-modal-panel]').classList.remove('translate-y-2', 'scale-95', 'opacity-0');

      var first = box.querySelector('input:not([type="hidden"]):not([type="file"]), select, textarea');
      (first || box.querySelector('[data-modal-close]')).focus();
    });
  }

  function close(id) {
    var box = get(id);
    var at = stack.indexOf(id);
    if (!box || at === -1) return;

    stack.splice(at, 1);
    box.querySelector('[data-modal-backdrop]').classList.add('opacity-0');
    box.querySelector('[data-modal-panel]').classList.add('translate-y-2', 'scale-95', 'opacity-0');

    setTimeout(function () {
      box.classList.add('hidden');
      if (!stack.length) document.body.classList.remove('overflow-hidden');
      if (box.__previous && box.__previous.focus) box.__previous.focus();
    }, 200);
  }

  window.modal = { open: open, close: close };

  document.addEventListener('click', function (e) {
    var opener = e.target.closest('[data-modal-open]');
    if (opener) { e.preventDefault(); open(opener.getAttribute('data-modal-open'), opener); return; }

    var closer = e.target.closest('[data-modal-close]');
    if (closer) {
      var box = closer.closest('[data-app-modal]');
      if (box) { e.preventDefault(); close(box.id.replace('modal-', '')); }
    }
  });

  // Clic en el fondo oscuro = cerrar
  document.addEventListener('mousedown', function (e) {
    if (e.target.hasAttribute && e.target.hasAttribute('data-modal-backdrop')) {
      close(e.target.parentElement.id.replace('modal-', ''));
    }
  });

  document.addEventListener('keydown', function (e) {
    if (!stack.length) return;
    var box = get(stack[stack.length - 1]);

    if (e.key === 'Escape') {
      // Si hay una confirmación o un desplegable abierto, Esc es para ellos.
      if (document.querySelector('[role="alertdialog"]') || document.querySelector('[data-ui-select-popup]')) return;
      e.preventDefault();
      close(stack[stack.length - 1]);
      return;
    }

    if (e.key === 'Tab' && box) {                       // el foco no se escapa de la ventana
      var items = Array.prototype.filter.call(box.querySelectorAll(FOCUSABLE), function (n) { return n.offsetParent !== null; });
      if (!items.length) return;
      var first = items[0], last = items[items.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  // Un formulario con errores de validación vuelve a abrir su ventana (campo oculto _modal)
  var reopen = {{ Illuminate\Support\Js::from(old('_modal')) }};
  if (reopen) {
    var go = function () { open(reopen); };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', go); else go();
  }
})();
</script>
@endonce
