@props(['title' => 'Panel'])

@php
  $authUser = auth()->user();
  $isAdmin = $authUser->isAdmin();
  $ownedDocuments = \App\Models\Document::countOwnedBy($authUser);
  $documentLimit = \App\Models\Document::MAX_PER_USER;
  $limitReached = $ownedDocuments >= $documentLimit;
  $usedPercent = min(100, (int) round($ownedDocuments / $documentLimit * 100));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.HELPER', 'HELPER') }} | {{ $title ?? 'Panel' }}</title>

  {{-- Fuente Inter sin bloquear el primer pintado (antes la hoja de Google detenía la página hasta descargarse). --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"></noscript>

  {{-- Los iconos usan font-display:block: se precarga su fuente para que no aparezcan vacíos al cambiar de pantalla. --}}
  <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ asset('backend/assets/vendors/bootstrap-icons/fonts/bootstrap-icons.woff2') }}?e34853135f9e39acf64315236852cd5a">
  <link rel="stylesheet" href="{{ asset('backend/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
  <x-fast-nav />
  <link rel="icon" type="image/png" href="{{ asset('backend/assets/images/favicon/faviconHelper.png') }}">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="hp-body">
  <div class="hp-shell">

    <div class="hp-backdrop" data-sidebar-close></div>

    {{-- ============ SIDEBAR ============ --}}
    <aside class="hp-sidebar" id="hpSidebar" aria-label="Navegación principal">
      <a class="hp-brand" href="{{ route('dashboard') }}" aria-label="HELPER Docs Maker">
        <img class="hp-brand-logo" src="{{ asset('backend/assets/images/avatar/lt_logo-removebg-preview.png') }}" alt="HELPER">
        <span class="hp-brand-text">
          <span class="hp-brand-title">HELPER</span>
          <span class="hp-brand-sub">Docs maker</span>
        </span>
      </a>

      <nav class="hp-nav">
        @if ($isAdmin)
          <a class="hp-nav-item {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2" aria-hidden="true"></i><span>Dashboard</span>
          </a>
        @endif

        <a class="hp-nav-item {{ request()->routeIs('documents.*') ? 'is-active' : '' }}" href="{{ route('documents.index') }}">
          <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>Mis documentos</span>
        </a>
        <a class="hp-nav-item {{ request()->routeIs('templates.*') ? 'is-active' : '' }}" href="{{ route('templates.index') }}">
          <i class="bi bi-grid" aria-hidden="true"></i><span>Plantillas</span>
        </a>

        @if ($isAdmin || $authUser->hasRole('mod'))
          <a class="hp-nav-item {{ request()->routeIs('supervision.*') ? 'is-active' : '' }}" href="{{ route('supervision.index') }}">
            <i class="bi bi-clipboard-check" aria-hidden="true"></i><span>{{ $isAdmin ? 'Todos los documentos' : 'Documentos del área' }}</span>
          </a>
        @endif

        <a class="hp-nav-item {{ request()->routeIs('audit.*') ? 'is-active' : '' }}" href="{{ route('audit.index') }}">
          <i class="bi bi-shield-check" aria-hidden="true"></i><span>Audit trail</span>
        </a>

        @if ($isAdmin || $authUser->hasRole('mod'))
          <p class="mt-4 mb-1 px-4 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Administración</p>
          <a class="hp-nav-item {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">
            <i class="bi bi-people" aria-hidden="true"></i><span>Usuarios</span>
          </a>
        @endif

        @if ($isAdmin)
          <a class="hp-nav-item {{ request()->routeIs('admin.areas.*') ? 'is-active' : '' }}" href="{{ route('admin.areas.index') }}">
            <i class="bi bi-diagram-3" aria-hidden="true"></i><span>Áreas</span>
          </a>
        @endif

        <p class="mt-4 mb-1 px-4 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Cuenta</p>
        <a class="hp-nav-item {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ route('profile.edit') }}">
          <i class="bi bi-person" aria-hidden="true"></i><span>Perfil</span>
        </a>
      </nav>

      <div class="hp-workspace">
        <div class="flex items-baseline justify-between gap-2">
          <p class="hp-workspace-title">Espacio personal</p>
          <span class="hp-workspace-meta">{{ $ownedDocuments }}/{{ $documentLimit }}</span>
        </div>

        <div class="hp-progress" role="progressbar"
             aria-label="Documentos usados"
             aria-valuemin="0" aria-valuemax="{{ $documentLimit }}" aria-valuenow="{{ $ownedDocuments }}">
          <div class="hp-progress-bar {{ $limitReached ? 'is-full' : '' }}" style="width: {{ $usedPercent }}%"></div>
        </div>

        <p class="hp-workspace-meta {{ $limitReached ? 'text-red-300' : '' }}">
          @if ($limitReached)
            Llegaste al límite. Elimina un documento para crear otro.
          @else
            {{ $documentLimit - $ownedDocuments }} documentos disponibles.
          @endif
        </p>
      </div>
    </aside>

    {{-- ============ MAIN ============ --}}
    <div class="hp-main">
      <header class="hp-topbar">
        <div class="hp-topbar-left">
          <button class="hp-sidebar-toggle" type="button" data-sidebar-toggle aria-controls="hpSidebar" aria-label="Abrir menú">
            <i class="bi bi-list" aria-hidden="true"></i>
          </button>
          <h1 class="hp-page-title">{{ $title }}</h1>
        </div>

        <div class="hp-topbar-actions">
          <div class="hidden text-right leading-tight sm:block">
            <p class="text-sm font-semibold text-gray-900">{{ $authUser->name }}</p>
            <p class="text-xs text-gray-500">
              {{ $authUser->role->label() }}@php($areaLabel = $authUser->assignedAreas()->pluck('name')->join(', '))@if ($areaLabel !== '') · {{ $areaLabel }}@endif
            </p>
          </div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="hp-btn-outline">
              <i class="bi bi-box-arrow-right" aria-hidden="true" style="margin-right: 0.2rem;"></i><span class="hidden sm:inline">Salir</span>
            </button>
          </form>
        </div>
      </header>

      <main class="hp-content">
        {{ $slot }}
      </main>
    </div>
  </div>

  <x-select-ui />
  <x-confirm />
  <x-toast />
  <x-idle-timeout />
</body>
</html>
