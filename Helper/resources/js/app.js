// resources/js/app.js
// Mantén lo que ya tengas (Breeze). Solo necesitas la línea marcada con ◄◄.
import './bootstrap';

import Alpine from 'alpinejs';   // quítalo si tu app.js original no lo tenía
window.Alpine = Alpine;
Alpine.start();

import './dashboard';            // ◄◄ buscador, contadores, workspace y menú móvil
import './click-guard';          // evita dobles clics y envíos duplicados
import './modals';               // ventanas modales (<dialog>): crear usuario, usar/subir plantilla...
import './ui';                   // filtros que se aplican al cambiar una lista desplegable

// El selector multiarea solo aplica a moderadores.
function syncModeratorAreas(scope) {
  const role = scope.querySelector('select[name="role"]');
  const multi = scope.querySelector('[data-mod-areas]');
  const primary = scope.querySelector('select[name="area_id"]');
  if (!role || !multi) return;
  const moderator = role.value === 'mod';
  multi.hidden = !moderator;
  multi.querySelectorAll('select[name="area_ids[]"]').forEach((field) => {
    field.disabled = !moderator;
    field.dispatchEvent(new Event('change', { bubbles: true }));
  });
  if (primary) primary.required = role.value === 'user';
}
document.querySelectorAll('form:has([data-mod-areas])').forEach(syncModeratorAreas);
document.addEventListener('change', (event) => {
  if (event.target?.matches('select[name="role"]')) {
    syncModeratorAreas(event.target.closest('form'));
  }
});
