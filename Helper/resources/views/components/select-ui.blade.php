{{--
  Desplegables con el diseño de la app. Incluir UNA vez por layout: <x-select-ui />

  Convierte automáticamente todos los <select> de la página (excepto los de selección múltiple o con data-native).
  El <select> original sigue en el formulario, así que no hay que cambiar nada en las vistas.
  Teclado: flechas, Enter, Esc, Inicio/Fin. Con más de 7 opciones aparece un buscador.
--}}
<script>
(function () {
  if (window.__uiSelect) return;
  window.__uiSelect = true;

  var NS = 'http://www.w3.org/2000/svg';
  var openState = null; // { popup, button, select, close }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text) n.textContent = text;
    return n;
  }

  function icon(path, cls) {
    var svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '2');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('class', cls);
    svg.setAttribute('aria-hidden', 'true');
    var p = document.createElementNS(NS, 'path');
    p.setAttribute('d', path);
    svg.appendChild(p);
    return svg;
  }

  var CHEVRON = 'M6 9l6 6 6-6';
  var CHECK = 'M5 13l4 4L19 7';
  var SEARCH = 'M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z';

  function optionsOf(select) {
    return Array.prototype.map.call(select.options, function (o) {
      return { value: o.value, label: o.textContent.trim(), disabled: o.disabled };
    });
  }

  function closeOpen() { if (openState) openState.close(); }

  function enhance(select) {
    if (select.dataset.uiSelect || select.multiple || select.hasAttribute('data-native')) return;
    select.dataset.uiSelect = '1';

    var hasError = select.className.indexOf('!border-red-400') !== -1;

    var wrap = el('div', 'relative w-full' + (select.classList.contains('mt-1') ? ' mt-1' : ''));
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);

    // El select real queda en el formulario, invisible y fuera del orden de tabulación.
    select.style.cssText = 'position:absolute;left:0;bottom:0;width:1px;height:1px;opacity:0;pointer-events:none;';
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    var button = el('button', 'flex h-11 w-full items-center justify-between gap-2 rounded-xl border bg-white px-3 text-left text-sm transition focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400'
      + (hasError ? 'border-red-400 focus:border-red-400 focus:ring-red-300' : 'border-gray-300 hover:border-gray-400 focus:border-brand focus:ring-brand'));
    button.type = 'button';
    button.setAttribute('data-click-guard-ignore', ''); // abrir/cerrar rápido no debe bloquearse
    button.setAttribute('aria-haspopup', 'listbox');
    button.setAttribute('aria-expanded', 'false');
    var label = el('span', 'truncate');
    button.appendChild(label);
    button.appendChild(icon(CHEVRON, 'h-4 w-4 shrink-0 text-gray-400'));
    wrap.appendChild(button);

    function refresh() {
      var opt = select.options[select.selectedIndex];
      label.textContent = opt ? opt.textContent.trim() : '';
      label.className = 'truncate ' + (select.value === '' ? 'text-gray-500' : 'text-gray-900');
      button.disabled = select.disabled;
    }
    refresh();
    select.addEventListener('change', refresh);

    if (select.id) {
      document.querySelectorAll('label[for="' + select.id + '"]').forEach(function (l) {
        l.addEventListener('click', function (e) { e.preventDefault(); button.focus(); });
      });
    }

    function openPopup() {
      closeOpen();
      var items = optionsOf(select);
      var searchable = items.length > 7;
      var query = '';
      var active = Math.max(0, select.selectedIndex);

      var popup = el('div', 'fixed z-[130] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg ring-1 ring-black/5');
      popup.setAttribute('data-ui-select-popup', '1');

      var search = null;
      if (searchable) {
        var bar = el('div', 'flex items-center gap-2 border-b border-gray-100 px-3 py-2');
        bar.appendChild(icon(SEARCH, 'h-4 w-4 shrink-0 text-gray-400'));
        search = el('input', 'w-full border-0 bg-transparent p-1 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0');
        search.type = 'text';
        search.placeholder = 'Buscar...';
        search.setAttribute('aria-label', 'Buscar en la lista');
        bar.appendChild(search);
        popup.appendChild(bar);
      }

      var list = el('ul', 'max-h-60 overflow-y-auto p-1');
      list.setAttribute('role', 'listbox');
      popup.appendChild(list);

      function visible() {
        return items.filter(function (it) { return !query || it.label.toLowerCase().indexOf(query) !== -1; });
      }

      function render() {
        list.innerHTML = '';
        var shown = visible();
        if (!shown.length) {
          list.appendChild(el('li', 'px-3 py-2 text-sm text-gray-500', 'Sin resultados'));
          return;
        }
        if (active >= shown.length) active = shown.length - 1;
        shown.forEach(function (it, i) {
          var selected = it.value === select.value;
          var row = el('li', 'flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm '
            + (it.disabled ? 'cursor-not-allowed text-gray-300 ' : 'cursor-pointer ')
            + (i === active ? 'bg-gray-100 ' : '')
            + (selected ? 'font-semibold text-brand' : (it.disabled ? '' : 'text-gray-700')));
          row.setAttribute('role', 'option');
          row.setAttribute('aria-selected', selected ? 'true' : 'false');
          row.appendChild(el('span', 'truncate', it.label));
          if (selected) row.appendChild(icon(CHECK, 'h-4 w-4 shrink-0'));
          if (!it.disabled) {
            row.addEventListener('mouseenter', function () {
              active = i;
              Array.prototype.forEach.call(list.children, function (child, j) {
                if (child.getAttribute('role') === 'option') child.classList.toggle('bg-gray-100', j === i);
              });
            });
            row.addEventListener('mousedown', function (e) { e.preventDefault(); choose(it); });
          }
          list.appendChild(row);
        });
        var current = list.children[active];
        if (current && current.scrollIntoView) current.scrollIntoView({ block: 'nearest' });
      }

      function choose(it) {
        if (it.disabled) return;
        select.value = it.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        close();
        button.focus();
      }

      function place() {
        var r = button.getBoundingClientRect();
        popup.style.left = r.left + 'px';
        popup.style.width = r.width + 'px';
        var room = window.innerHeight - r.bottom;
        if (room < 260 && r.top > room) {          // poco espacio abajo: se abre hacia arriba
          popup.style.top = '';
          popup.style.bottom = (window.innerHeight - r.top + 4) + 'px';
        } else {
          popup.style.bottom = '';
          popup.style.top = (r.bottom + 4) + 'px';
        }
      }

      function onKey(e) {
        var shown = visible();
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); button.focus(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(shown.length - 1, active + 1); render(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(0, active - 1); render(); }
        else if (e.key === 'Home') { e.preventDefault(); active = 0; render(); }
        else if (e.key === 'End') { e.preventDefault(); active = shown.length - 1; render(); }
        else if (e.key === 'Enter') { e.preventDefault(); if (shown[active]) choose(shown[active]); }
        else if (e.key === 'Tab') { close(); }
      }

      function onOutside(e) { if (!popup.contains(e.target) && !button.contains(e.target)) close(); }
      function onScroll(e) { if (!popup.contains(e.target)) close(); }

      function close() {
        document.removeEventListener('mousedown', onOutside, true);
        window.removeEventListener('scroll', onScroll, true);
        window.removeEventListener('resize', close);
        button.removeEventListener('keydown', onKey);
        button.setAttribute('aria-expanded', 'false');
        popup.remove();
        if (openState && openState.popup === popup) openState = null;
      }

      document.body.appendChild(popup);
      place();
      render();
      button.setAttribute('aria-expanded', 'true');
      openState = { popup: popup, button: button, select: select, close: close };

      document.addEventListener('mousedown', onOutside, true);
      window.addEventListener('scroll', onScroll, true);
      window.addEventListener('resize', close);
      button.addEventListener('keydown', onKey);

      if (search) {
        search.addEventListener('keydown', onKey);
        search.addEventListener('input', function () { query = search.value.trim().toLowerCase(); active = 0; render(); });
        search.focus();
      }
    }

    button.addEventListener('click', function () {
      if (openState && openState.button === button) closeOpen(); else openPopup();
    });
    button.addEventListener('keydown', function (e) {
      if (['ArrowDown', 'ArrowUp', 'Enter', ' '].indexOf(e.key) !== -1 && !(openState && openState.button === button)) {
        e.preventDefault();
        openPopup();
      }
    });
  }

  function init() { document.querySelectorAll('select').forEach(enhance); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
