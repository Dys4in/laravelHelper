{{-- Menú lateral según el rol: Administrador, Moderador o Usuario. --}}
@php
  $me = auth()->user();
  $link = fn (string $name, array $params = []) => Route::has($name) ? route($name, $params) : '#';
  $label = 'mt-4 mb-1 px-4 text-[11px] font-semibold uppercase tracking-wide text-gray-400';
@endphp

<nav class="hp-nav">
  <a class="hp-nav-item {{ request()->routeIs($me->role->dashboardRoute(), 'documents.index', 'documents.edit') ? 'is-active' : '' }}"
     href="{{ $link($me->role->dashboardRoute()) }}">
    <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
    <span>{{ $me->isAdmin() ? 'Todos los documentos' : ($me->hasRole('mod') ? 'Documentos del área' : 'Mis documentos') }}</span>
  </a>

  @can('create', \App\Models\Document::class)
    <a class="hp-nav-item {{ request()->routeIs('documents.create') ? 'is-active' : '' }}" href="{{ $link('documents.create') }}">
      <i class="bi bi-file-earmark-plus" aria-hidden="true"></i><span>Nuevo documento</span>
    </a>
  @endcan

  @can('viewAny', \App\Models\Template::class)
    <a class="hp-nav-item {{ request()->routeIs('templates.*') ? 'is-active' : '' }}" href="{{ $link('templates.index') }}">
      <i class="bi bi-grid" aria-hidden="true"></i><span>Plantillas</span>
    </a>
  @endcan

  @if ($me->isAdmin())
    <p class="{{ $label }}">Administración</p>
    <a class="hp-nav-item {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ $link('admin.users.index') }}">
      <i class="bi bi-people" aria-hidden="true"></i><span>Usuarios</span>
    </a>
    <a class="hp-nav-item {{ request()->routeIs('admin.areas.*') ? 'is-active' : '' }}" href="{{ $link('admin.areas.index') }}">
      <i class="bi bi-diagram-3" aria-hidden="true"></i><span>Áreas</span>
    </a>
    <a class="hp-nav-item {{ request()->routeIs('admin.audit.*') ? 'is-active' : '' }}" href="{{ $link('admin.audit.index') }}">
      <i class="bi bi-activity" aria-hidden="true"></i><span>Auditoría</span>
    </a>
  @endif

  <p class="{{ $label }}">Cuenta</p>
  <a class="hp-nav-item {{ request()->routeIs('profile.*') ? 'is-active' : '' }}" href="{{ $link('profile.edit') }}">
    <i class="bi bi-person" aria-hidden="true"></i><span>Perfil</span>
  </a>
</nav>
