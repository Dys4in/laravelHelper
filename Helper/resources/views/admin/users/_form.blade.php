@php
  $isEdit = $user->exists;
  $field = 'mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand';
  $label = 'block text-sm font-medium text-gray-700';
@endphp

<div class="grid gap-5 sm:grid-cols-2">
  <div>
    <label for="name" class="{{ $label }}">Nombre completo *</label>
    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="{{ $field }} @error('name') !border-red-400 @enderror">
  </div>

  <div>
    <label for="username" class="{{ $label }}">Nombre de usuario</label>
    <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" class="{{ $field }} @error('username') !border-red-400 @enderror">
  </div>

  <div>
    <label for="email" class="{{ $label }}">Correo electrónico *</label>
    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="off" class="{{ $field }} @error('email') !border-red-400 @enderror">
  </div>

  <div>
    <label for="phone" class="{{ $label }}">Teléfono</label>
    <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" class="{{ $field }} @error('phone') !border-red-400 @enderror">
  </div>

  <div>
    <label for="role" class="{{ $label }}">Rol *</label>
    <select id="role" name="role" required class="{{ $field }} @error('role') !border-red-400 @enderror">
      @foreach ($roles as $role)
        <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
      @endforeach
    </select>
  </div>

  <div>
    <label for="area_id" class="{{ $label }}">Área</label>
    <select id="area_id" name="area_id" class="{{ $field }} @error('area_id') !border-red-400 @enderror">
      <option value="">— Sin área —</option>
      @foreach ($areas as $area)
        <option value="{{ $area->id }}" @selected((string) old('area_id', $user->area_id) === (string) $area->id)>
          {{ $area->name }}{{ $area->isActive() ? '' : ' (suspendida)' }}
        </option>
      @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-500">Obligatoria para todos los roles excepto Administrador.</p>
  </div>

  <div>
    <label for="status" class="{{ $label }}">Estado *</label>
    <select id="status" name="status" required class="{{ $field }} @error('status') !border-red-400 @enderror">
      <option value="active" @selected(old('status', $user->status) === 'active')>Activo</option>
      <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactivo</option>
    </select>
  </div>

  <div class="hidden sm:block"></div>

  <div>
    <label for="password" class="{{ $label }}">{{ $isEdit ? 'Nueva contraseña' : 'Contraseña *' }}</label>
    <input id="password" name="password" type="password" autocomplete="new-password" {{ $isEdit ? '' : 'required' }} class="{{ $field }} @error('password') !border-red-400 @enderror">
    @if ($isEdit)
      <p class="mt-1 text-xs text-gray-500">Déjala vacía para conservar la actual.</p>
    @endif
  </div>

  <div>
    <label for="password_confirmation" class="{{ $label }}">Confirmar contraseña</label>
    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="{{ $field }} @error('password') !border-red-400 @enderror">
  </div>
</div>
