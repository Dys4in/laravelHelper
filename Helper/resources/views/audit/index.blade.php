<x-panel-layout title="Audit trail">
  <section class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <p class="text-sm text-gray-600">
        Registro de todos los movimientos de la aplicación. Es de solo lectura: nadie puede editar ni borrar estos registros.
      </p>
    </div>

    {{-- Interruptor "En vivo" --}}
    <label class="inline-flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-2.5">
      <span id="audit-dot" class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
      <span class="text-sm font-medium text-gray-800" id="audit-live-text">En vivo</span>
      <input id="audit-live" type="checkbox" class="peer sr-only" checked>
      <span class="relative h-5 w-9 rounded-full bg-gray-300 transition peer-checked:bg-green-500 peer-focus-visible:ring-2 peer-focus-visible:ring-gray-400 after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4"></span>
    </label>
  </section>

  {{-- Filtros --}}
  <form method="GET" action="{{ route('audit.index') }}" data-autosubmit class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por el usuario, detalle o area..."
           class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand sm:col-span-2">

    <select name="module" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
      <option value="">Todos los módulos</option>
      @foreach ($modules as $key => $label)
        <option value="{{ $key }}" @selected($filters['module'] === $key)>{{ $label }}</option>
      @endforeach
    </select>

    <select name="user" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
      <option value="">Todos los usuarios</option>
      @foreach ($actors as $actor)
        <option value="{{ $actor->id }}" @selected((string) $filters['user'] === (string) $actor->id)>{{ $actor->name }}</option>
      @endforeach
    </select>

    <select name="area" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
      <option value="">Todas las áreas</option>
      @foreach ($areas as $area)
        <option value="{{ $area->id }}" @selected((string) $filters['area'] === (string) $area->id)>{{ $area->name }}</option>
      @endforeach
    </select>

    <div class="flex items-center gap-2">
      <label for="from" class="text-xs font-medium text-gray-500">Desde</label>
      <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
    </div>
    <div class="flex items-center gap-2">
      <label for="to" class="text-xs font-medium text-gray-500">Hasta</label>
      <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
    </div>

    <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
      <button type="submit" class="hp-btn-outline flex-1">Filtrar</button>
      <a href="{{ route('audit.index') }}" class="hp-btn-outline flex-1">Limpiar</a>
    </div>
  </form>

  @unless ($firstPage)
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
      Estás en una página anterior: la vista en vivo solo se actualiza en la primera página.
      <a href="{{ route('audit.index', request()->except('page')) }}" class="font-semibold underline">Ir a lo más reciente</a>
    </div>
  @endunless

  <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        <tr>
          <th class="px-4 py-3">Fecha</th>
          <th class="px-4 py-3">Usuario</th>
          <th class="px-4 py-3">Evento</th>
          <th class="px-4 py-3">Detalle</th>
          <th class="px-4 py-3">Área</th>
        </tr>
      </thead>
      <tbody id="audit-body" class="divide-y divide-gray-100"
             data-feed-url="{{ route('audit.feed') }}"
             data-query="{{ http_build_query(array_filter($filters)) }}"
             data-last-id="{{ $lastId }}"
             data-first-page="{{ $firstPage ? '1' : '0' }}">
        @forelse ($logs as $log)
          @include('audit._row', ['log' => $log])
        @empty
          <tr id="audit-empty"><td colspan="5" class="px-4 py-12 text-center text-gray-500">No hay movimientos que coincidan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $logs->links() }}

  <script>
    (function () {
      var body = document.getElementById('audit-body');
      var toggle = document.getElementById('audit-live');
      var dot = document.getElementById('audit-dot');
      var text = document.getElementById('audit-live-text');

      var feedUrl = body.dataset.feedUrl;
      var query = body.dataset.query;
      var lastId = parseInt(body.dataset.lastId, 10) || 0;
      var enabled = body.dataset.firstPage === '1';
      var busy = false;
      var MAX_ROWS = 100;

      function status(state) {
        // ok = verde, paused = gris, error = rojo
        dot.className = 'h-2.5 w-2.5 rounded-full ' + (state === 'ok' ? 'bg-green-500' : state === 'error' ? 'bg-red-500' : 'bg-gray-400');
        text.textContent = state === 'error' ? 'Sin conexión' : state === 'paused' ? 'Pausado' : 'En vivo';
      }

      if (!enabled) { toggle.checked = false; toggle.disabled = true; status('paused'); return; }

      toggle.addEventListener('change', function () { status(toggle.checked ? 'ok' : 'paused'); if (toggle.checked) poll(); });

      function poll() {
        if (busy || !toggle.checked || document.hidden) return;
        busy = true;

        var url = feedUrl + '?' + (query ? query + '&' : '') + 'after=' + lastId;

        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
          .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
          .then(function (data) {
            status('ok');
            if (!data.count) return;

            var empty = document.getElementById('audit-empty');
            if (empty) empty.remove();

            body.insertAdjacentHTML('afterbegin', data.html);
            lastId = data.last_id;

            // Resalta un momento las filas nuevas
            var rows = body.querySelectorAll('tr[data-log-id]');
            for (var i = 0; i < data.count && i < rows.length; i++) {
              (function (row) {
                row.classList.add('bg-amber-50');
                setTimeout(function () { row.classList.remove('bg-amber-50'); }, 2500);
              })(rows[i]);
            }

            // No dejar crecer la tabla sin límite
            while (rows.length > MAX_ROWS) { rows[rows.length - 1].remove(); rows = body.querySelectorAll('tr[data-log-id]'); }
          })
          .catch(function () { status('error'); })
          .finally(function () { busy = false; });
      }

      setInterval(poll, 5000);
      document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
    })();
  </script>
</x-panel-layout>
