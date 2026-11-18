{{-- Campos de usuario. Se usa en la ventana "Nuevo usuario" ($modalId) y en la página de edición. --}}
@php
  $isEdit = $user->exists;
  $modalId = $modalId ?? null;
  $p = $modalId ? $modalId.'-' : '';
  $mine = $modalId ? old('_modal') === $modalId : true;
  $val = fn (string $key, $default = null) => $mine ? old($key, $default) : $default;
  $err = fn (string $key) => $mine ? $errors->first($key) : '';
  $invalid = fn (string $key) => $err($key) !== '' ? 'aria-invalid=true' : '';
  $currentRole = $val('role', $user->role?->value);
  $primaryArea = $val('area_id', $user->exists ? ($user->area_id ?? $user->areas->pluck('id')->first()) : null);
  $selectedAreas = array_map('intval', (array) $val('area_ids', $user->exists ? ($user->areas->pluck('id')->all() ?: ($user->area_id ? [$user->area_id] : [])) : []));
  $isMod = $currentRole === 'mod';
@endphp

<div class="hp-modal-grid" data-user-form="{{ $p }}form">
  <div>
    <label for="{{ $p }}name" class="hp-label">Nombre completo *</label>
    <input id="{{ $p }}name" name="name" type="text" value="{{ $val('name', $user->name) }}" required class="hp-input" {{ $invalid('name') }}>
    @if ($e = $err('name'))<p class="hp-error">{{ $e }}</p>@endif
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
    <select id="{{ $p }}role" name="role" required class="hp-select" data-role-select {{ $invalid('role') }}>
      @foreach ($roles as $role)
        <option value="{{ $role->value }}" @selected($currentRole === $role->value)>{{ $role->label() }}</option>
      @endforeach
    </select>
    @if ($e = $err('role'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div class="sm:col-span-2" data-area-single @if ($isMod) hidden @endif>
    <label for="{{ $p }}area_id" class="hp-label">Área *</label>
    <select id="{{ $p }}area_id" name="area_id" class="hp-select" data-area-single-input @if ($isMod) disabled @endif {{ $invalid('area_id') }}>
      <option value="">— Selecciona un área —</option>
      @foreach ($areas as $area)
        <option value="{{ $area->id }}" @selected((string) $primaryArea === (string) $area->id)>
          {{ $area->name }}{{ $area->isActive() ? '' : ' (suspendida)' }}
        </option>
      @endforeach
    </select>
    @if ($e = $err('area_id'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div class="sm:col-span-2" data-area-multi @if (! $isMod) hidden @endif>
    <label for="{{ $p }}area_ids" class="hp-label">Áreas (moderador) *</label>
    <select id="{{ $p }}area_ids" name="area_ids[]" multiple data-placeholder="Selecciona una o varias áreas" class="hp-select" data-area-multi-input @if (! $isMod) disabled @endif {{ $invalid('area_ids') }}>
      @foreach ($areas as $area)
        <option value="{{ $area->id }}" @selected(in_array($area->id, $selectedAreas, true))>
          {{ $area->name }}{{ $area->isActive() ? '' : ' (suspendida)' }}
        </option>
      @endforeach
    </select>
    @if ($e = $err('area_ids'))<p class="hp-error">{{ $e }}</p>@elseif ($e = $err('area_ids.0'))<p class="hp-error">{{ $e }}</p>@else<p class="hp-help">Solo los moderadores pueden tener más de un área. La primera será el área principal.</p>@endif
  </div>

  <div>
    <label for="{{ $p }}status" class="hp-label">Estado *</label>
    <select id="{{ $p }}status" name="status" required class="hp-select" {{ $invalid('status') }}>
      <option value="active" @selected($val('status', $user->status) === 'active')>Activo</option>
      <option value="inactive" @selected($val('status', $user->status) === 'inactive')>Inactivo</option>
    </select>
  </div>

  <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2">
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
</div>

<script>
(function () {
  var form = document.querySelector('[data-user-form="{{ $p }}form"]');
  if (!form || form.dataset.bound === '1') return;
  form.dataset.bound = '1';
  var role = form.querySelector('[data-role-select]');
  var singleWrap = form.querySelector('[data-area-single]');
  var multiWrap = form.querySelector('[data-area-multi]');
  var single = form.querySelector('[data-area-single-input]');
  var multi = form.querySelector('[data-area-multi-input]');
  function sync() {
    var isMod = role && role.value === 'mod';
    if (singleWrap) singleWrap.hidden = isMod;
    if (multiWrap) multiWrap.hidden = !isMod;
    if (single) single.disabled = isMod;
    if (multi) multi.disabled = !isMod;
  }
  if (role) role.addEventListener('change', sync);
  sync();
})();
</script>
