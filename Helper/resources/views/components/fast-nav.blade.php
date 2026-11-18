{{--
  Cambio de pantalla más fluido. Incluir UNA vez en <head>: <x-fast-nav />
  1) Transición entre páginas (Chrome/Edge): el menú lateral y la barra superior se quedan quietos y solo el
     contenido hace un fundido corto, en vez de parpadear en blanco. Otros navegadores ignoran la regla.
  2) Precarga: al pasar el mouse (o tocar) un enlace del menú lateral, la página se pide por adelantado.
     Solo enlaces del menú (pantallas de solo lectura): nunca descargas ni "abrir documento", que registran movimientos.
--}}
<style>
  @view-transition { navigation: auto; }
  .hp-sidebar { view-transition-name: hp-sidebar; }
  .hp-topbar { view-transition-name: hp-topbar; }
  ::view-transition-group(hp-sidebar), ::view-transition-group(hp-topbar) { animation-duration: 0s; }
  ::view-transition-old(root), ::view-transition-new(root) { animation-duration: .14s; }
  @media (prefers-reduced-motion: reduce) {
    ::view-transition-old(root), ::view-transition-new(root) { animation-duration: 0s; }
  }
</style>
<script>
  (function () {
    var seen = {};
    var timer = null;

    function prefetch(link) {
      var url = link.href;
      if (!url || seen[url] || link.origin !== location.origin || url === location.href) return;
      seen[url] = true;
      var el = document.createElement('link');
      el.rel = 'prefetch';
      el.as = 'document';
      el.href = url;
      document.head.appendChild(el);
    }

    function target(event) {
      return event.target instanceof Element ? event.target.closest('a.hp-nav-item[href]') : null;
    }

    document.addEventListener('mouseover', function (e) {
      var a = target(e);
      if (!a) return;
      clearTimeout(timer);
      timer = setTimeout(function () { prefetch(a); }, 80); // evita pedir páginas al pasar el mouse de largo
    }, { passive: true });

    document.addEventListener('mouseout', function (e) { if (target(e)) clearTimeout(timer); }, { passive: true });
    document.addEventListener('touchstart', function (e) { var a = target(e); if (a) prefetch(a); }, { passive: true });
  })();
</script>
