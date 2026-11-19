<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.HELPER', 'HELPER') }} | {{ $document->title }}</title>
  <link rel="icon" type="image/png" href="{{ asset('backend/assets/images/favicon/faviconHelper.png') }}">
  <link rel="preload" href="{{ asset('backend/assets/vendors/bootstrap-icons/fonts/bootstrap-icons.woff2') }}" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="{{ asset('backend/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    .editor-stage { display: flex; height: 100%; min-height: 0; position: relative; background: #e1dfe9; }
    .editor-main { display: flex; min-width: 0; flex: 1; flex-direction: column; overflow: hidden; border-radius: 0 32px 32px 0; }
    .editor-canvas { min-width: 0; min-height: 0; flex: 1; position: relative; }
    .editor-side.hp-sidebar { position: relative; inset: auto; top: 0; right: 0; transform: none; align-self: stretch;
      height: 100%; width: 320px; flex: 0 0 320px; margin: 0; z-index: 1;
      gap: 0; padding: 20px 16px; overflow: hidden; box-shadow: none;
      background: #e1dfe9; color: #3f4157;
      transition: flex-basis .2s ease, width .2s ease, padding .2s ease; }
    .editor-side .hp-brand-title { color: #1f2033; }
    .editor-side .hp-brand-sub { color: #6b6d85; }
    .editor-side-head { display: flex; align-items: center; justify-content: space-between; gap: 8px;
      margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #dcd6ee; }
    .helper-tools-tabs { display:grid; grid-template-columns:repeat(2,1fr); gap:4px; padding:4px;
      background:#f7f5fd; border:1px solid #dcd6ee; border-radius:12px; margin-bottom:10px; }
    .helper-tools-tab { color:#6b6d85; font-size:11px; font-weight:700; padding:10px 2px; border-radius:9px;
      text-align:center; border:0; cursor:pointer; background:transparent; white-space:nowrap; min-width:0;
      transition: background .15s, color .15s; }
    .helper-tools-tab:hover { color:#6d3ce8; }
    .helper-tools-tab[aria-selected="true"] { background:#fff; color:#6d3ce8; box-shadow: 0 1px 2px rgb(34 34 47 / .08); }
    .helper-tool-panel { flex:1; min-height:0; overflow-y:auto; overscroll-behavior:contain; scrollbar-width:thin; padding:3px 2px 12px; }
    .helper-tool-panel::-webkit-scrollbar { width: 8px; }
    .helper-tool-panel::-webkit-scrollbar-thumb { background: rgb(109 60 232 / .25); border-radius: 999px; }
    .helper-tool-panel[hidden] { display:none; }
    .helper-tool-label { font-size:12px; font-weight:600; color:#3f4157; display:block; margin:11px 0 5px; }
    .helper-tool-field { width:100%; background:#fff; border:1px solid #dcd6ee; color:#1f2033; border-radius:10px;
      padding:10px; outline-offset:2px; font-size:13px; box-shadow: 0 1px 2px rgb(34 34 47 / .04); }
    .helper-tool-field:focus { border-color:#6d3ce8; outline:none; box-shadow: 0 0 0 3px rgb(109 60 232 / .12); }
    .helper-tool-field::placeholder { color:#9a9cb3; }
    .helper-tool-btn { width:100%; min-width:0; border:1px solid #dcd6ee; background:#fff; color:#55576f;
      padding:10px 12px; border-radius:10px; font-weight:600; margin-top:11px; font-size:12px; cursor:pointer;
      transition: border-color .15s ease, background-color .15s ease, color .15s ease; }
    .helper-tool-btn:hover:not(:disabled) { border-color:#6d3ce8; background:#f0eaff; color:#6d3ce8; }
    .helper-tool-btn:disabled { opacity:.45; cursor:not-allowed; }
    .helper-snippet { background:#fff; border:1px solid #dcd6ee; border-radius:12px; padding:11px; margin:10px 0;
      box-shadow: 0 1px 2px rgb(34 34 47 / .05), 0 4px 14px -8px rgb(34 34 47 / .12); }
    .helper-snippet-title { font-size:13px; font-weight:700; color:#1f2033; }
    .helper-snippet-text { font-size:12px; color:#6b6d85; white-space:pre-wrap; overflow-wrap:anywhere; max-height:74px; overflow:hidden; margin:6px 0; }
    .helper-snippet-actions { display:flex; flex-wrap:wrap; gap:6px; }
    .helper-snippet-actions button { min-width:0; background:#fff; color:#55576f; border:1px solid #dcd6ee; border-radius:8px; padding:7px 9px; font-size:11px; cursor:pointer;
      transition: border-color .15s ease, background-color .15s ease, color .15s ease; }
    .helper-snippet-actions button:hover { border-color:#6d3ce8; background:#f0eaff; color:#6d3ce8; }
    .helper-snippet-actions button:first-child { flex:1; }
    .helper-tool-hint { font-size:11px; line-height:1.5; color:#6b6d85; margin:7px 0; }
    .helper-tool-status { font-size:11px; color:#6b6d85; min-height:26px; line-height:1.5; margin:10px 2px 6px; }
    .helper-tools-title { color:#1f2033; font-size:14px; font-weight:800; margin:4px 0 7px; }
    .helper-photo-preview { width:100%; max-height:160px; object-fit:contain; background:#fff; border:1px solid #dcd6ee; border-radius:10px; margin-top:9px; }
    .helper-photo-preview[hidden] { display:none; }
    .helper-image-drop { border:1px dashed #b9a9e6; border-radius:11px; background:#fff; padding:13px; text-align:center; margin:8px 0; font-size:12px; color:#6b6d85; cursor:pointer; }
    .helper-image-drop.is-dragover { border-color:#6d3ce8; background:#f0eaff; }
    .helper-image-list { display:grid; gap:9px; margin-top:12px; }
    .helper-image-item { display:flex; align-items:center; gap:9px; min-width:0; border:1px solid #dcd6ee; border-radius:10px; padding:7px; background:#fff; }
    .helper-image-item img { flex:0 0 64px; width:64px; height:50px; object-fit:contain; background:#f7f5fd; border:1px solid #eeebf7; border-radius:6px; }
    .helper-image-item-info { flex:1; min-width:0; font-size:11px; color:#6b6d85; }
    .helper-image-item-info strong { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#1f2033; }
    .helper-image-item-actions { display:flex; flex:0 0 auto; gap:3px; }
    .helper-image-item-actions button { border:1px solid #dcd6ee; border-radius:6px; color:#55576f; background:#fff; padding:4px 6px; font-size:11px; cursor:pointer; }
    .helper-image-item-actions button:hover { border-color:#6d3ce8; background:#f0eaff; color:#6d3ce8; }
    .helper-image-note { color:#6b6d85; font-size:11px; line-height:1.4; }
    .helper-image-layout-preview { display:grid; gap:6px; padding:10px; border:1px solid #dcd6ee; border-radius:10px; margin:9px 0; background:#fff; }
    .helper-image-layout-preview.is-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .helper-image-layout-preview.is-vertical { grid-template-columns:1fr; }
    .helper-image-preview-cell { min-width:0; height:115px; background:#f7f5fd; border:1px solid #eeebf7; border-radius:8px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; overflow:hidden; padding:6px; }
    .helper-image-preview-cell img { display:block; object-fit:contain; max-width:100%; max-height:calc(100% - 20px); }
    .helper-image-preview-cell small { flex:0 0 auto; font-size:10px; color:#6b6d85; }
    .helper-image-preview-empty { font-size:11px; color:#6b6d85; text-align:center; padding:12px; }
    .editor-stage:not(.side-visible) .editor-side.hp-sidebar { position:relative; inset:auto; transform:none; width:0; flex-basis:0; padding:0; border:0; visibility:hidden; }
    .editor-side-backdrop { display: none; }
    @media (max-width: 900px) {
      .editor-side.hp-sidebar { position: fixed; z-index: 60; top: 0; bottom: 0; right: 0;
        width: min(86vw, 320px); flex-basis: auto; box-shadow: -12px 0 30px #0004;
        transform: translateX(100%); transition: transform .2s ease; }
      .editor-stage.side-visible .editor-side.hp-sidebar { transform: translateX(0); }
      .editor-stage:not(.side-visible) .editor-side.hp-sidebar { position:fixed; inset:0 0 0 auto; width:min(86vw,320px); flex-basis:auto; transform:translateX(100%); visibility:hidden; }
      .editor-stage.side-visible .editor-side-backdrop { display:block; position:fixed; z-index:59; inset:0; background:rgba(0,0,0,.4); }
      .editor-side.hp-sidebar { padding:16px 14px; }
      .editor-side-head { margin-bottom:12px; padding-bottom:12px; }
      .helper-tools-tab { font-size:10px; padding:9px 0; }
      .helper-tool-label { font-size:11px; }
      .helper-tool-btn { font-size:11px; padding:9px 10px; }
    }
    @media (max-width: 380px) {
      .editor-side.hp-sidebar { padding:14px 12px; }
      .helper-tools-tabs { gap:3px; padding:3px; }
      .helper-tools-tab { font-size:10px; padding:8px 0; }
      .helper-snippet-actions { gap:4px; }
      .helper-snippet-actions button { padding:6px 7px; font-size:10px; }
      .helper-image-item img { flex-basis:48px; width:48px; height:42px; }
      .helper-image-item-actions { flex-direction:column; }
    }
    @media (prefers-reduced-motion: reduce) { .editor-side { transition: none; } }
  </style>
</head>

<body class="hp-body h-full overflow-hidden">
  <main id="editorStage" class="editor-stage{{ $mode === 'edit' ? ' side-visible' : '' }}">
    <div class="editor-main">

    <header class="flex h-14 shrink-0 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4">
      <div class="flex min-w-0 items-center gap-3">
        <a href="{{ route('documents.index') }}" class="hp-btn-outline flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
          Documentos
        </a>
        <div class="min-w-0 leading-tight">
          <p class="truncate text-xs text-gray-500">{{ $document->code }} · Creado por <span class="font-medium text-gray-700">{{ $document->user?->name ?? '—' }}</span></p>
        </div>
      </div>
      <div class="flex items-center gap-3">
      @if ($mode === 'edit')
        <p class="hidden text-xs text-gray-500 md:block">Ctrl+S guarda al instante. Al cerrar el editor, los cambios se guardan solos en unos segundos.</p>
      @elseif ($mode === 'review')
        <p class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">Modo revisión · tus cambios quedan como sugerencias para {{ $document->user?->name ?? 'su creador' }}</p>
      @else
        <p class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Solo lectura · documento completado</p>
      @endif
      @if ($mode === 'edit')
      <button id="editorSideToggle" type="button" aria-controls="editorRightSidebar" aria-expanded="true"
              class="hp-btn-outline flex shrink-0 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
        <i class="bi bi-layout-sidebar-reverse" aria-hidden="true"></i><span class="hidden sm:inline">Herramientas</span>
      </button>
      @endif
      </div>
    </header>

      <section class="editor-canvas" aria-label="Editor del documento">
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
      </section>
    </div>
    @if ($mode === 'edit')
    <div id="editorSideBackdrop" class="editor-side-backdrop" aria-hidden="true"></div>
      <aside id="editorRightSidebar" class="editor-side hp-sidebar" aria-label="Herramientas del editor">
        <div class="editor-side-head">
          <a href="{{ route('dashboard') }}" class="hp-brand" aria-label="Ir al panel de HELPER">
            <img class="hp-brand-logo" src="{{ asset('backend/assets/images/avatar/lt_logo-removebg-preview.png') }}" alt="HELPER" />
            <span class="hp-brand-text"><span class="hp-brand-title">HELPER</span><span class="hp-brand-sub">Docs maker</span></span>
          </a>
        </div>
        <div class="helper-tools-tabs" role="tablist" aria-label="Herramientas del editor">
          <button id="helperTabReusable" class="helper-tools-tab" role="tab" aria-selected="true" aria-controls="helperPaneReusable" data-pane="helperPaneReusable" type="button"><i class="bi bi-bookmark-plus" aria-hidden="true"></i> Textos</button>
          <button id="helperTabImages" class="helper-tools-tab" role="tab" aria-selected="false" aria-controls="helperPaneImages" data-pane="helperPaneImages" type="button"><i class="bi bi-image" aria-hidden="true"></i> Imágenes</button>
        </div>
        <div id="helperBridgeStatus" class="helper-tool-status" role="status" aria-live="polite">Conectando con OnlyOffice…</div>
        <section id="helperPaneReusable" class="helper-tool-panel" role="tabpanel" aria-labelledby="helperTabReusable">
          <h2 class="helper-tools-title">Contenido reutilizable</h2>
          <p class="helper-tool-hint">Selecciona un texto dentro de OnlyOffice y pulsa «Capturar selección». Guárdalo con un nombre para insertarlo en otros documentos.</p>
          <button id="helperCapture" type="button" class="helper-tool-btn helper-tool-secondary" @if($mode === 'view') disabled @endif><i class="bi bi-text-paragraph" aria-hidden="true"></i> Capturar selección</button>
          <label class="helper-tool-label" for="helperSnippetTitle">Nombre del contenido</label>
          <input class="helper-tool-field" id="helperSnippetTitle" maxlength="100" placeholder="Ej. Introducción de informe" @if($mode === 'view') disabled @endif>
          <label class="helper-tool-label" for="helperSnippetContent">Texto seleccionado</label>
          <textarea class="helper-tool-field" id="helperSnippetContent" rows="5" maxlength="20000" placeholder="Primero captura una selección…" @if($mode === 'view') disabled @endif></textarea>
          <button class="helper-tool-btn" id="helperSnippetSave" type="button" @if($mode === 'view') disabled @endif><i class="bi bi-plus-circle" aria-hidden="true"></i> Guardar fragmento</button>
          <label class="helper-tool-label" for="helperSnippetSearch">Mis fragmentos</label>
          <input class="helper-tool-field" id="helperSnippetSearch" type="search" placeholder="Buscar contenido…">
          <div id="helperSnippetList" aria-live="polite"><p class="helper-tool-hint">Cargando fragmentos…</p></div>
        </section>
        <section id="helperPaneImages" class="helper-tool-panel" role="tabpanel" aria-labelledby="helperTabImages" hidden>
          <h2 class="helper-tools-title">Organizador de evidencias</h2>
          <p class="helper-tool-hint">Sube varias fotos o pega una captura. HELPER mantiene su proporción y calcula un tamaño seguro para la página.</p>
          <div class="helper-image-drop" id="helperImageDrop" tabindex="0">Arrastra imágenes aquí o usa «Seleccionar imágenes».</div>
          <label class="helper-tool-label" for="helperImageFile">Imágenes PNG, JPG o WebP (hasta 4 MB cada una, máximo 8)</label>
          <input class="helper-tool-field" id="helperImageFile" type="file" accept="image/png,image/jpeg,image/webp" multiple @if($mode === 'view') disabled @endif>
          <button id="helperPasteScreenshot" class="helper-tool-btn helper-tool-secondary" type="button" @if($mode === 'view') disabled @endif><i class="bi bi-clipboard" aria-hidden="true"></i> Pegar captura del portapapeles</button>
          <p class="helper-image-note">Toma una captura con Windows + Shift + S y pulsa este botón. Chrome puede solicitar permiso. El Ctrl+V normal de OnlyOffice no cambia.</p>
          <div id="helperImageList" class="helper-image-list" aria-live="polite"></div>
          <label class="helper-tool-label" for="helperImageLayout">Distribución</label>
          <select class="helper-tool-field" id="helperImageLayout">
            <option value="auto" selected>Automática según las proporciones</option>
            <option value="grid">Cuadrícula 2 × 2 — máximo 4 por página</option>
            <option value="vertical">Una debajo de otra</option>
          </select>
          <div id="helperImageLayoutPreview" class="helper-image-layout-preview" aria-label="Vista previa de distribución"></div>
          <p class="helper-image-note" id="helperImageSizingInfo" aria-live="polite"></p>
          <label class="helper-tool-label" for="helperImageSpace">Tamaño de imágenes dentro del espacio disponible</label>
          <select class="helper-tool-field" id="helperImageSpace">
            <option value="full" selected>Grande — llenar al máximo el área disponible</option>
            <option value="medium">Mediano — 68% del tamaño máximo</option>
            <option value="small">Pequeño — 38% del tamaño máximo</option>
          </select>
          <p class="helper-image-note" id="helperPageDimensions">Calculando el tamaño útil de la hoja con OnlyOffice…</p>
          <p class="helper-image-note">OnlyOffice permite leer los márgenes de la página, pero no el espacio vertical exacto que queda bajo el cursor. Elige Pequeño, Mediano o Grande para modificar realmente el ancho y el alto. Para garantizar una cuadrícula de cuatro imágenes por página, se inicia en una página nueva.</p>
          <label class="helper-tool-label" for="helperImageAlign">Alineación (imágenes individuales)</label>
          <select class="helper-tool-field" id="helperImageAlign"><option value="left">Izquierda</option><option value="center" selected>Centro</option><option value="right">Derecha</option></select>
          <label class="helper-tool-label" for="helperImageCaption">Descripción del conjunto (opcional)</label>
          <input class="helper-tool-field" id="helperImageCaption" maxlength="180" placeholder="Ej. Evidencia de verificación">
          <button id="helperInsertImage" class="helper-tool-btn" type="button" @if($mode === 'view') disabled @endif><i class="bi bi-images" aria-hidden="true"></i> Insertar imágenes organizadas</button>
          <button id="helperImageClear" class="helper-tool-btn helper-tool-secondary" type="button">Vaciar lista</button>
          <p class="helper-tool-hint">Se inserta un bloque en la posición del cursor. Puedes ordenar las imágenes antes de insertarlas. Las cuadrículas comienzan en una página nueva y se agrupan hasta cuatro fotos por página; el tamaño respeta la proporción de cada imagen.</p>
        </section>
      </aside>
    @endif
  </main>

  <x-toast />
  <x-idle-timeout />

  <script>
    (function () {
      var stage = document.getElementById('editorStage');
      var toggle = document.getElementById('editorSideToggle');
      var backdrop = document.getElementById('editorSideBackdrop');
      var sidebar = document.getElementById('editorRightSidebar');
      var mobile = window.matchMedia('(max-width: 900px)');
      function setOpen(open) {
        stage.classList.toggle('side-visible', open);
        if (toggle) toggle.setAttribute('aria-expanded', String(open));
        var firstTab = document.getElementById('helperTabReusable');
        if (open && mobile.matches && firstTab) firstTab.focus();
        if (!open && mobile.matches && toggle && stage.contains(document.activeElement)) toggle.focus();
        // OnlyOffice se adapta al ancho disponible sin volver a crear el editor.
        window.dispatchEvent(new Event('resize'));
      }
      if (toggle) {
        if (mobile.matches) setOpen(false);
        toggle.addEventListener('click', function () { setOpen(!stage.classList.contains('side-visible')); });
      }
      if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && stage.classList.contains('side-visible')) setOpen(false); });
      mobile.addEventListener('change', function (e) { if (toggle) setOpen(!e.matches); });
      if (sidebar) sidebar.addEventListener('transitionend', function () { window.dispatchEvent(new Event('resize')); });
    })();
    window.__ooFailed = function () {
      var box = document.getElementById('editor-error');
      box.classList.remove('hidden');
      box.classList.add('flex');
    };
  </script>
  @if ($mode === 'edit')
  <script>
    window.helperEditorSettings = {
      enabled: @json($mode !== 'view'),
      listUrl: @json(route('editor.reusable.index')),
      storeUrl: @json(route('editor.reusable.store')),
      deleteBaseUrl: @json(url('/editor/reusable')),
    };
  </script>
  <script src="{{ asset('helper-editor-plugin/sidebar.js') . '?v=5' }}" defer></script>
  @endif
  <script src="{{ $apiUrl }}" onerror="window.__ooFailed()"></script>
  <script>
    window.addEventListener('load', function () {
      if (!window.DocsAPI) { window.__ooFailed(); return; }

      var config = {{ Illuminate\Support\Js::from($config) }};
      config.width = '100%';
      config.height = '100%';
      config.events = {
        onDocumentStateChange: function () { if (window.HelperIdle) window.HelperIdle.mark(); },
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
