@props(['title' => 'Panel', 'active' => ''])

@php
  $link = fn (string $name, array $params = []) => Route::has($name) ? route($name, $params) : '#';
  $authUser = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title }} · HELPER Docs Maker</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('backend/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

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
        <a class="hp-nav-item {{ request()->routeIs('admin.dashboard', 'documents.*') ? 'is-active' : '' }}" href="{{ $link('admin.dashboard') }}">
          <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>Documents</span>
        </a>
        <a class="hp-nav-item {{ request()->routeIs('templates.*') ? 'is-active' : '' }}" href="{{ $link('templates.index') }}">
          <i class="bi bi-grid" aria-hidden="true"></i><span>Templates</span>
        </a>

        <p class="mt-4 mb-1 px-4 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Administración</p>
        <a class="hp-nav-item {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ $link('admin.users.index') }}">
          <i class="bi bi-people" aria-hidden="true"></i><span>Usuarios</span>
        </a>
        <a class="hp-nav-item {{ request()->routeIs('admin.areas.*') ? 'is-active' : '' }}" href="{{ $link('admin.areas.index') }}">
          <i class="bi bi-diagram-3" aria-hidden="true"></i><span>Áreas</span>
        </a>

        <p class="mt-4 mb-1 px-4 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Cuenta</p>
        <a class="hp-nav-item {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ $link('profile.edit') }}">
          <i class="bi bi-person" aria-hidden="true"></i><span>Profile</span>
        </a>
      </nav>
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
            <p class="text-xs text-gray-500">{{ $authUser->role->label() }}</p>
          </div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
              <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="hidden sm:inline">Salir</span>
            </button>
          </form>
        </div>
      </header>

      <main class="hp-content">
        {{ $slot }}
      </main>
    </div>
  </div>
  <x-confirm />
  <x-toast />
</body>
</html>
