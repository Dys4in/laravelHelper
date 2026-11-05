// resources/js/modals.js
// Ventanas modales sobre <dialog> nativo (foco atrapado, Esc y fondo incluidos).
//
//   <button data-modal-open="mi-modal">Abrir</button>
//   <x-hp-modal id="mi-modal" title="..."> ... </x-hp-modal>
//   Cualquier elemento con data-modal-close cierra la ventana en la que está.
//
// data-fill (opcional, en el botón que abre) rellena la ventana antes de mostrarla:
//   data-fill='{"values":{"template_id":5},"text":{"template-name":"Informe"}}'
//   - values: valor de los campos name="..." de la ventana
//   - text:   textContent de los elementos [data-fill-text="..."]
//
// Las ventanas con data-modal-autoopen se abren solas al cargar la página
// (así se vuelve a mostrar una ventana cuyo formulario tuvo errores de validación).

const root = document.documentElement;

const syncScrollLock = () => {
  root.classList.toggle('overflow-hidden', Boolean(document.querySelector('dialog.hp-modal[open]')));
};

function applyFill(dialog, trigger) {
  const raw = trigger?.dataset?.fill;
  if (!raw) return;

  let data;
  try { data = JSON.parse(raw); } catch { return; }

  Object.entries(data.values || {}).forEach(([name, value]) => {
    const field = dialog.querySelector(`[name="${CSS.escape(name)}"]`);
    if (field) field.value = value ?? '';
  });

  Object.entries(data.text || {}).forEach(([key, value]) => {
    dialog.querySelectorAll(`[data-fill-text="${CSS.escape(key)}"]`).forEach((node) => {
      node.textContent = value ?? '';
    });
  });
}

function openModal(dialog, trigger = null) {
  if (!(dialog instanceof HTMLDialogElement) || dialog.open) return;

  applyFill(dialog, trigger);
  dialog.showModal();
  syncScrollLock();

  const first = dialog.querySelector('[autofocus]')
    || dialog.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
  first?.focus();
}

function closeModal(dialog) {
  if (dialog instanceof HTMLDialogElement && dialog.open) dialog.close();
}

// Un clic cuenta como "fuera" solo si también empezó en el fondo
// (evita cerrar al soltar el ratón tras seleccionar texto dentro de un campo).
let pressedOnBackdrop = false;
const isBackdrop = (el) => el instanceof HTMLDialogElement && el.classList.contains('hp-modal');

document.addEventListener('mousedown', (event) => { pressedOnBackdrop = isBackdrop(event.target); });

document.addEventListener('click', (event) => {
  const target = event.target instanceof Element ? event.target : null;
  if (!target) return;

  const opener = target.closest('[data-modal-open]');
  if (opener) {
    event.preventDefault();
    openModal(document.getElementById(opener.dataset.modalOpen), opener);
    return;
  }

  const closer = target.closest('[data-modal-close]');
  if (closer) {
    closeModal(closer.closest('dialog'));
    return;
  }

  if (pressedOnBackdrop && isBackdrop(target)) closeModal(target);
});

// "close" no burbujea: se escucha en captura.
document.addEventListener('close', syncScrollLock, true);

const autoOpen = () => {
  document.querySelectorAll('dialog.hp-modal[data-modal-autoopen]').forEach((dialog) => openModal(dialog));
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', autoOpen);
else autoOpen();

window.HelperModal = {
  open: (id) => openModal(document.getElementById(id)),
  close: (id) => closeModal(document.getElementById(id)),
};
