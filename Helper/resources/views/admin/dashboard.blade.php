<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard · HELPER Docs Maker</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  {{-- Solo para los iconos (bi bi-*) --}}
  <link rel="stylesheet" href="{{ asset('backend/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
  /*
   * Desde el controlador: return view('dashboard', ['documents' => $documents, 'limit' => 20]);
   * Cada documento (array u objeto Eloquent) con:
   *   id      → identificador interno
   *   code    → nombre/ID visible, ej. "VIL-PLNT-007"  (lo usa el buscador)
   *   status  → OBLIGATORIO: 'draft' | 'completed'
   *   edited  → texto "Edited yesterday" (o updated_at->diffForHumans())
   */
  $documents = $documents ?? [
    ['id' => 7,  'code' => 'VIL-PLNT-007', 'status' => 'completed', 'edited' => 'Edited yesterday'],
    ['id' => 8,  'code' => 'VIL-PLNT-008', 'status' => 'draft',     'edited' => 'Edited 1/08/2026'],
    ['id' => 9,  'code' => 'VIL-PLNT-009', 'status' => 'draft',     'edited' => 'Edited 28/09/2026'],
    ['id' => 12, 'code' => 'VIL-PLNT-012', 'status' => 'completed', 'edited' => 'Edited today'],
    ['id' => 11, 'code' => 'VIL-PLNT-011', 'status' => 'completed', 'edited' => 'Edited 23/08/2026'],
    ['id' => 10, 'code' => 'VIL-PLNT-010', 'status' => 'draft',     'edited' => 'Edited 28/09/2026'],
  ];
  $limit = $limit ?? 20;

  // Enlaces: usa la ruta nombrada si existe; si no, '#'. Ajusta los nombres a tus rutas reales.
  $link = fn (string $name, array $params = []) => Route::has($name) ? route($name, $params) : '#';

  // Status obligatorio: si llega vacío o inválido se toma como 'draft'
  $docs = collect($documents)->map(function ($d) {
      $status = strtolower((string) data_get($d, 'status'));
      return [
        'id'     => data_get($d, 'id'),
        'code'   => data_get($d, 'code'),
        'status' => in_array($status, ['draft', 'completed'], true) ? $status : 'draft',
        'edited' => data_get($d, 'edited'),
      ];
  });

  $total     = $docs->count();
  $drafts    = $docs->where('status', 'draft')->count();
  $completed = $docs->where('status', 'completed')->count();
  $usedPct   = $limit > 0 ? min(100, round($total / $limit * 100)) : 0;
@endphp

<body class="hp-body">
  <div class="hp-shell">

    <div class="hp-backdrop" data-sidebar-close></div>

    {{-- ============ SIDEBAR ============ --}}
    <aside class="hp-sidebar" id="hpSidebar" aria-label="Navegación principal">
      <a class="hp-brand" href="{{ $link('admin.dashboard') }}" aria-label="HELPER Docs Maker">
        <img class="hp-brand-logo" src="{{ asset('backend/assets/images/avatar/lt_logo-removebg-preview.png') }}" alt="HELPER">
        <span class="hp-brand-text">
          <span class="hp-brand-title">HELPER</span>
          <span class="hp-brand-sub">Docs maker</span>
        </span>
      </a>

      <nav class="hp-nav">
        <a class="hp-nav-item {{ request()->routeIs('admin.dashboard', 'documents.*') ? 'is-active' : '' }}" href="{{ $link('documents.index') }}">
          <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>Documents</span>
        </a>
        <a class="hp-nav-item {{ request()->routeIs('templates.*') ? 'is-active' : '' }}" href="{{ $link('templates.index') }}">
          <i class="bi bi-grid" aria-hidden="true"></i><span>Templates</span>
        </a>
        <a class="hp-nav-item {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ $link('profile.edit') }}">
          <i class="bi bi-person" aria-hidden="true"></i><span>Profile</span>
        </a>
      </nav>

      {{-- Personal Workspace: el JS lo recalcula según las cards --}}
      <div class="hp-workspace" data-workspace data-limit="{{ $limit }}">
        <div>
          <p class="hp-workspace-title">Personal Workspace</p>
          <p class="hp-workspace-meta"><span data-workspace-count>{{ $total }}</span> / {{ $limit }} docs</p>
        </div>
        <div class="hp-progress" role="progressbar" aria-valuenow="{{ $usedPct }}" aria-valuemin="0" aria-valuemax="100" data-workspace-progress>
          <div class="hp-progress-bar" data-workspace-bar style="width: {{ $usedPct }}%"></div>
        </div>
        
      </div>
    </aside>

    {{-- ============ MAIN ============ --}}
    <div class="hp-main">

      <header class="hp-topbar">
        <div class="hp-topbar-left">
          <button class="hp-sidebar-toggle" type="button" data-sidebar-toggle aria-controls="hpSidebar" aria-label="Abrir menú">
            <i class="bi bi-list" aria-hidden="true"></i>
          </button>
          <h1 class="hp-page-title">Dashboard</h1>
        </div>

        <div class="hp-topbar-actions">
          <form class="hp-search" role="search" id="docSearchForm" onsubmit="return false">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input id="docSearch" type="search" placeholder="Search..." aria-label="Buscar documentos" autocomplete="off">
          </form>

          <a class="hp-btn-primary" href="{{ $link('documents.create') }}">
            <i class="bi bi-file-earmark-plus" aria-hidden="true"></i>
            <span>New Document</span>
          </a>

          <img class="hp-avatar" src="{{ asset('backend/assets/images/avatar/avatar.jpg') }}" alt="Perfil">
        </div>
      </header>

      <main class="hp-content">

        <section class="hp-overview">
          <div>
            <h2 class="hp-overview-title">My Documents</h2>
            <p class="hp-overview-sub">Manage, edit, and export your digital documents</p>
          </div>

          {{-- Contadores: también filtran al hacer clic --}}
          <div class="hp-stats" role="group" aria-label="Filtrar por estado">
            <button type="button" class="hp-stat" data-filter="all" aria-pressed="false">
              <span class="hp-stat-label">All:</span><span class="hp-stat-value" data-count="all">{{ $total }}</span>
            </button>
            <button type="button" class="hp-stat" data-filter="draft" aria-pressed="false">
              <span class="hp-stat-label">Drafts:</span><span class="hp-stat-value" data-count="draft">{{ $drafts }}</span>
            </button>
            <button type="button" class="hp-stat" data-filter="completed" aria-pressed="false">
              <span class="hp-stat-label">Completed:</span><span class="hp-stat-value" data-count="completed">{{ $completed }}</span>
            </button>
          </div>
        </section>

        <section class="hp-docs-grid" id="docsGrid" aria-label="Lista de documentos">
          @foreach ($docs as $doc)
            <article class="hp-card" data-doc data-id="{{ $doc['id'] }}" data-code="{{ $doc['code'] }}" data-status="{{ $doc['status'] }}">
              <div class="hp-card-thumb">
                <img src="{{ asset('backend/assets/images/documents/informePlantilla.png') }}" alt="Vista previa de {{ $doc['code'] }}">
              </div>

              <div class="hp-card-body">
                <div class="hp-card-row">
                  <span class="hp-badge hp-badge--{{ $doc['status'] }}" data-badge>{{ $doc['status'] }}</span>
                  <button class="hp-card-menu" type="button" aria-label="Opciones de {{ $doc['code'] }}">
                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                  </button>
                </div>

                <div class="hp-card-details">
                  <p class="hp-card-code">{{ $doc['code'] }}</p>
                  <p class="hp-card-date">{{ $doc['edited'] }}</p>
                </div>

                <a class="hp-btn-outline" href="{{ $link('documents.edit', ['document' => $doc['id']]) }}">Edit Document</a>
              </div>
            </article>
          @endforeach

          {{-- Sin documentos en absoluto --}}
          <div class="hp-empty" data-empty @if($total > 0) hidden @endif>
            Aún no tienes documentos. Crea el primero con “New Document”.
          </div>

          {{-- Búsqueda/filtro sin resultados --}}
          <div class="hp-empty" data-no-results hidden>
            No se encontraron documentos para tu búsqueda.
          </div>
        </section>
      </main>
    </div>
  </div>
</body>
</html>