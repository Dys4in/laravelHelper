{{-- Campos de usuario. Se usa en la ventana «Nuevo usuario» ($modalId) y en la página de edición. --}}
@php
  $isEdit = $user->exists;
  $modalId = $modalId ?? null;
  $p = $modalId ? $modalId.'-' : '';
  $mine = $modalId ? old('_modal') === $modalId : true;
  $val = fn (string $key, $default = null) => $mine ? old($key, $default) : $default;
  $err = fn (string $key) => $mine ? $errors->first($key) : '';
  $invalid = fn (string $key) => $err($key) !== '' ? 'aria-invalid=true' : '';
@endphp

<div class="hp-modal-grid">
  <div>
    <label for="{{ $p }}name" class="hp-label">Nombre completo *</label>
    <input id="{{ $p }}name" name="name" type="text" value="{{ $val('name', $user->name) }}" required class="hp-input" {{ $invalid('name') }}>
    @if ($e = $err('name'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div>
    <label for="{{ $p }}username" class="hp-label">Nombre de usuario</label>
    <input id="{{ $p }}username" name="username" type="text" value="{{ $val('username', $user->username) }}" class="hp-input" {{ $invalid('username') }}>
    @if ($e = $err('username'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div>
    <label for="{{ $p }}email" class="hp-label">Correo electrónico *</label>
    <input id="{{ $p }}email" name="email" type="email" value="{{ $val('email', $user->email) }}" required autocomplete="off" class="hp-input" {{ $invalid('email') }}>
    @if ($e = $err('email'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div>
    <label for="{{ $p }}phone" class="hp-label">Teléfono</label>
    <input id="{{ $p }}phone" name="phone" type="text" value="{{ $val('phone', $user->phone) }}" class="hp-input" {{ $invalid('phone') }}>
    @if ($e = $err('phone'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div>
    <label for="{{ $p }}role" class="hp-label">Rol *</label>
    <select id="{{ $p }}role" name="role" required class="hp-select" {{ $invalid('role') }}>
      @foreach ($roles as $role)
        <option value="{{ $role->value }}" @selected($val('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
      @endforeach
    </select>
    @if ($e = $err('role'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div>
    <label for="{{ $p }}area_id" class="hp-label">Área</label>
    <select id="{{ $p }}area_id" name="area_id" class="hp-select" {{ $invalid('area_id') }}>
      <option value="">— Sin área —</option>
      @foreach ($areas as $area)
        <option value="{{ $area->id }}" @selected((string) $val('area_id', $user->area_id) === (string) $area->id)>
          {{ $area->name }}{{ $area->isActive() ? '' : ' (suspendida)' }}
        </option>
      @endforeach
    </select>
    @if ($e = $err('area_id'))<p class="hp-error">{{ $e }}</p>@else<p class="hp-help">Obligatoria para todos los roles excepto Administrador.</p>@endif
  </div>

  <div>
    <label for="{{ $p }}status" class="hp-label">Estado *</label>
    <select id="{{ $p }}status" name="status" required class="hp-select" {{ $invalid('status') }}>
      <option value="active" @selected($val('status', $user->status) === 'active')>Activo</option>
      <option value="inactive" @selected($val('status', $user->status) === 'inactive')>Inactivo</option>
    </select>
  </div>

  <div class="hidden sm:block"></div>

  <div>
    <label for="{{ $p }}password" class="hp-label">{{ $isEdit ? 'Nueva contraseña' : 'Contraseña *' }}</label>
    <input id="{{ $p }}password" name="password" type="password" autocomplete="new-password" {{ $isEdit ? '' : 'required' }} class="hp-input" {{ $invalid('password') }}>
    @if ($e = $err('password'))<p class="hp-error">{{ $e }}</p>@elseif ($isEdit)<p class="hp-help">Déjala vacía para conservar la actual.</p>@endif
  </div>

  <div>
    <label for="{{ $p }}password_confirmation" class="hp-label">Confirmar contraseña</label>
    <input id="{{ $p }}password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="hp-input">
  </div>
</div>
