// resources/js/app.js
// Mantén lo que ya tengas (Breeze). Solo necesitas la línea marcada con ◄◄.
import './bootstrap';

import Alpine from 'alpinejs';   // quítalo si tu app.js original no lo tenía
window.Alpine = Alpine;
Alpine.start();

import './dashboard';            // ◄◄ buscador, contadores, workspace y menú móvil