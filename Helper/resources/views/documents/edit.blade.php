<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ config('app.HELPER', 'HELPER') }} | {{ $document->title }}</title>
  <link rel="icon" type="image/png" href="{{ asset('backend/assets/images/favicon/faviconHelper.png') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full overflow-hidden bg-gray-100">
  <div class="flex h-full flex-col">

    <header class="flex h-14 shrink-0 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4">
      <div class="flex min-w-0 items-center gap-3">
        <a href="{{ route('documents.index') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
          Documentos
        </a>
        <div class="min-w-0 leading-tight">
          <p class="truncate text-sm font-semibold text-gray-900">{{ $document->title }}</p>
          <p class="truncate text-xs text-gray-500">{{ $document->code }} · Creado por <span class="font-medium text-gray-700">{{ $document->user?->name ?? '—' }}</span></p>
        </div>
      </div>
      @if ($mode === 'edit')
        <p class="hidden text-xs text-gray-500 md:block">Ctrl+S guarda al instante. Al cerrar el editor, los cambios se guardan solos en unos segundos.</p>
      @elseif ($mode === 'review')
        <p class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">Modo revisión · tus cambios quedan como sugerencias para {{ $document->user?->name ?? 'su creador' }}</p>
      @else
        <p class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Solo lectura · documento completado</p>
      @endif
    </header>

    <main class="relative min-h-0 flex-1">
      <div id="editor" class="h-full w-full"></div>

      <div id="editor-error" class="absolute inset-0 hidden items-center justify-center bg-gray-100 p-6">
        <div class="max-w-md rounded-xl border border-red-200 bg-white p-6 text-center shadow-sm">
          <p class="font-semibold text-gray-900">No se pudo cargar el editor</p>
          <p class="mt-2 text-sm text-gray-600">
            No hay respuesta de OnlyOffice en <span class="font-mono text-xs">{{ $serverUrl }}</span>.
            Comprueba que el contenedor de Docker esté en ejecución y que <span class="font-mono text-xs">ONLYOFFICE_URL</span> sea correcta.
          </p>
          <a href="{{ route('documents.index') }}" class="hp-btn-outline">Volver a documentos</a>
        </div>
      </div>
    </main>
  </div>

  <x-toast />

  <script>
    window.__ooFailed = function () {
      var box = document.getElementById('editor-error');
      box.classList.remove('hidden');
      box.classList.add('flex');
    };
  </script>
  <script src="{{ $apiUrl }}" onerror="window.__ooFailed()"></script>
  <script>
    window.addEventListener('load', function () {
      if (!window.DocsAPI) { window.__ooFailed(); return; }

      var config = {{ Illuminate\Support\Js::from($config) }};
      config.width = '100%';
      config.height = '100%';
      config.events = {
        onError: function (e) {
          var detail = e && e.data && (e.data.errorDescription || e.data.error);
          window.toast.error(detail ? 'OnlyOffice: ' + detail : 'El editor reportó un error.');
        }
      };

      new DocsAPI.DocEditor('editor', config);
    });
  </script>
</body>
</html>
