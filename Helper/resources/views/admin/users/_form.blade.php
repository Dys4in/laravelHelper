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
  $selectedAreas = array_map('intval', (array) $val('areas', $user->exists ? ($user->areas->pluck('id')->all() ?: ($user->area_id ? [$user->area_id] : [])) : []));
  $areaNames = $areas->pluck('name', 'id');
  $areaSummary = collect($selectedAreas)->map(fn ($id) => $areaNames->get($id))->filter()->implode(', ');
  $isMod = $currentRole === 'mod';
  $noAreas = $currentRole === 'jefe_de_area';
  $formRoles = array_values(array_filter($roles, fn ($role) => $role->value !== 'admin' || $role->value === $currentRole));
@endphp

@php
  $lockedSeconds = $isEdit ? \App\Support\LoginThrottle::lockedFor($user->email) : 0;
  $tempPassword = $isEdit && $user->must_change_password ? $user->temporary_password : null;
@endphp

<div class="hp-modal-grid" data-user-form="{{ $p }}form">
  @if ($lockedSeconds > 0)
    <div class="sm:col-span-2 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">
      <p class="font-semibold">Acceso bloqueado por intentos fallidos</p>
      <p class="mt-0.5">Faltan {{ \App\Support\LoginThrottle::humanize($lockedSeconds) }} para que pueda volver a intentarlo.</p>
      <button type="submit" formaction="{{ route('admin.users.unlock', $user) }}" formmethod="post" formnovalidate
              class="hp-btn-outline mt-2 px-3 py-1.5 text-xs font-medium">Desbloquear ahora</button>
    </div>
  @endif

  @if ($tempPassword)
    <div class="sm:col-span-2 hp-temp-notice hp-temp-notice--value">
      <div class="hp-temp-notice__head">
        <span class="hp-temp-notice__icon"><i class="bi bi-key" aria-hidden="true"></i></span>
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <p class="hp-temp-notice__title">Contraseña temporal</p>
            <span class="hp-temp-notice__badge">Pendiente de cambio</span>
          </div>
          <p class="hp-temp-notice__text">Entrégala a {{ $user->name }} por un canal seguro. Dejará de mostrarse cuando la persona ingrese y la cambie.</p>
        </div>
      </div>
      <div class="hp-temp-notice__value">
        <code class="hp-temp-notice__code" data-temp-value>{{ $tempPassword }}</code>
        <button type="button" data-temp-copy data-click-guard-ignore class="hp-temp-notice__copy" aria-label="Copiar contraseña temporal">
          <i class="bi bi-copy" aria-hidden="true"></i><span data-temp-copy-label>Copiar</span>
        </button>
      </div>
    </div>
  @endif

  <div>
    <label for="{{ $p }}name" class="hp-label">Nombre completo *</label>
    <input id="{{ $p }}name" name="name" type="text" value="{{ $val('name', $user->name) }}" required class="hp-input" {{ $invalid('name') }}>
    @if ($e = $err('name'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  @if ($isEdit)
  <div>
    <span class="hp-label">Firma GMP</span>
    <p class="hp-input">{{ $user->signature ?: '—' }}</p>
    <p class="hp-help">La firma GMP no se puede editar.</p>
  </div>
  @else
  <div>
    <label for="{{ $p }}signature" class="hp-label">Firma GMP *</label>
    <input id="{{ $p }}signature" name="signature" type="text" value="{{ $val('signature', $user->signature) }}" maxlength="50" class="hp-input" required {{ $invalid('signature') }}>
    @if ($e = $err('signature'))<p class="hp-error">{{ $e }}</p>@endif
    <p class="hp-help">Firma que el usuario deberá repetir al entrar por primera vez.</p>
  </div>
  @endif

  <div>
    <label for="{{ $p }}email" class="hp-label">Correo electrónico *</label>
    <input id="{{ $p }}email" name="email" type="email" value="{{ $val('email', $user->email) }}" required autocomplete="off" class="hp-input" {{ $invalid('email') }}>
    @if ($e = $err('email'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div>
    <label for="{{ $p }}role" class="hp-label">Rol *</label>
    <select id="{{ $p }}role" name="role" required class="hp-select" data-role-select {{ $invalid('role') }}> 
      @foreach ($formRoles as $role)
        <option value="{{ $role->value }}" @selected($currentRole === $role->value)>{{ $role->label() }}</option>
      @endforeach
    </select>
    @if ($e = $err('role'))<p class="hp-error">{{ $e }}</p>@endif
  </div>

  <div>
    <label for="{{ $p }}status" class="hp-label">Estado *</label>
    <select id="{{ $p }}status" name="status" required class="hp-select" {{ $invalid('status') }}>
      <option value="active" @selected($val('status', $user->status) === 'active')>Activo</option>
      <option value="inactive" @selected($val('status', $user->status) === 'inactive')>Inactivo</option>
    </select>
  </div>

  <div class="sm:col-span-2" data-area-single @if ($isMod || $noAreas) hidden @endif>
    <label for="{{ $p }}area_id" class="hp-label">Área *</label>
    <select id="{{ $p }}area_id" name="area_id" class="hp-select" data-area-single-input @if ($isMod || $noAreas) disabled @endif {{ $invalid('area_id') }}>
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
    <label for="{{ $p }}areas" class="hp-label">Áreas del coordinador *</label>
    <div class="relative">
      <button id="{{ $p }}areas" type="button" data-area-multi-toggle data-click-guard-ignore
              aria-haspopup="true" aria-expanded="false" {{ $invalid('areas') }}
              class="flex w-full items-center justify-between gap-2 rounded-xl border bg-white px-3.5 py-2.5 text-left text-[13px] transition focus:outline-none focus:ring-2 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400 {{ $err('areas') ? 'border-red-400 text-gray-900 focus:border-red-500 focus:ring-red-200' : 'border-line text-gray-900 focus:border-brand focus:ring-brand/20' }}"
              @if (! $isMod) disabled @endif>
        <span class="truncate {{ $areaSummary === '' ? 'text-gray-500' : '' }}" data-area-multi-summary data-empty="Selecciona una o varias áreas">{{ $areaSummary !== '' ? $areaSummary : 'Selecciona una o varias áreas' }}</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0 text-gray-400" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
      </button>

      <div class="hidden fixed z-[140] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg ring-1 ring-black/5" data-area-multi-popup>
        <div class="border-b border-gray-100 p-2">
          <input type="text" data-area-multi-search aria-label="Buscar área" placeholder="Buscar área..."
                 class="w-full rounded-lg border border-gray-200 px-3 py-1.5 text-[13px] text-gray-900 placeholder-gray-400 focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand">
        </div>
        <div class="max-h-60 overflow-y-auto p-1" role="group" aria-label="Áreas del coordinador">
          @foreach ($areas as $area)
            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] text-gray-700 transition hover:bg-gray-100" data-area-option>
              <input type="checkbox" name="areas[]" value="{{ $area->id }}" data-area-checkbox
                     class="h-4 w-4 shrink-0 rounded border-gray-300 text-brand focus:ring-brand"
                     @checked(in_array($area->id, $selectedAreas, true)) @if (! $isMod) disabled @endif>
              <span class="truncate"><span data-area-name>{{ $area->name }}</span>@if (! $area->isActive()) <span class="text-amber-600">(suspendida)</span>@endif</span>
            </label>
          @endforeach
        </div>
        <p class="border-t border-gray-100 px-3 py-2 text-[11px] text-gray-500">La primera área marcada será la principal.</p>
      </div>
    </div>
    @if ($e = $err('areas'))<p class="hp-error">{{ $e }}</p>@elseif ($e = $err('areas.0'))<p class="hp-error">{{ $e }}</p>@else<p class="hp-help">Marca una o varias áreas para este coordinador.</p>@endif
  </div>

  @if (! $isEdit)
    <div class="sm:col-span-2 hp-temp-notice">
      <div class="hp-temp-notice__head">
        <span class="hp-temp-notice__icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
        <div>
          <p class="hp-temp-notice__title">La contraseña se genera al guardar</p>
          <p class="hp-temp-notice__text">El sistema creará una contraseña temporal aleatoria. Al iniciar sesión, el usuario deberá escribir la misma firma GMP registrada y elegir su contraseña. Podrás consultarla y copiarla desde la edición del usuario hasta que la cambie.</p>
        </div>
      </div>
    </div>
  @else
    <p class="sm:col-span-2 hp-help">La contraseña no se edita aquí. Utiliza el botón «Recuperar contraseña» en la lista de usuarios.</p>
  @endif
</div>

<script>
(function () {
  var form = document.querySelector('[data-user-form="{{ $p }}form"]');
  if (!form || form.dataset.bound === '1') return;
  form.dataset.bound = '1';

  var copyButton = form.querySelector('[data-temp-copy]');
  if (copyButton) {
    var copyValue = form.querySelector('[data-temp-value]');
    var copyLabel = copyButton.querySelector('[data-temp-copy-label]');
    var copyTimer;
    var markCopied = function () {
      copyLabel.textContent = 'Copiada';
      clearTimeout(copyTimer);
      copyTimer = setTimeout(function () { copyLabel.textContent = 'Copiar'; }, 1800);
    };
    var copyFallback = function (text) {
      var area = document.createElement('textarea');
      area.value = text;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.select();
      try { document.execCommand('copy'); } catch (e) {}
      document.body.removeChild(area);
      markCopied();
    };
    copyButton.addEventListener('click', function () {
      var text = copyValue ? copyValue.textContent.trim() : '';
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(markCopied, function () { copyFallback(text); });
      } else {
        copyFallback(text);
      }
    });
  }

  var role = form.querySelector('[data-role-select]');
  var singleWrap = form.querySelector('[data-area-single]');
  var multiWrap = form.querySelector('[data-area-multi]');
  var single = form.querySelector('[data-area-single-input]');
  var toggle = form.querySelector('[data-area-multi-toggle]');
  var popup = form.querySelector('[data-area-multi-popup]');
  var summary = form.querySelector('[data-area-multi-summary]');
  var search = form.querySelector('[data-area-multi-search]');
  var checks = form.querySelectorAll('[data-area-checkbox]');
  var options = form.querySelectorAll('[data-area-option]');
  var fields = form.querySelectorAll('input[name], select[name], textarea[name]');
  var initialValues = Array.prototype.map.call(fields, function (f) {
    return f.type === 'checkbox' ? f.checked : f.value;
  });

  function refresh() {
    var names = [];
    checks.forEach(function (c) {
      var option = c.closest('[data-area-option]');
      var name = option ? option.querySelector('[data-area-name]') : null;
      if (c.checked && name) names.push(name.textContent.trim());
    });
    summary.textContent = names.length ? names.join(', ') : (summary.dataset.empty || '');
    summary.classList.toggle('text-gray-500', !names.length);
  }

  function resetForm() {
    close();
    fields.forEach(function (f, i) {
      if (f.type === 'checkbox') f.checked = initialValues[i];
      else f.value = initialValues[i];
    });
    refresh();
    sync();
  }

  function place() {
    var r = toggle.getBoundingClientRect();
    popup.style.left = r.left + 'px';
    popup.style.width = r.width + 'px';
    var room = window.innerHeight - r.bottom;
    if (room < 240 && r.top > room) {
      popup.style.top = '';
      popup.style.bottom = (window.innerHeight - r.top + 4) + 'px';
    } else {
      popup.style.bottom = '';
      popup.style.top = (r.bottom + 4) + 'px';
    }
  }

  function filter() {
    var q = search.value.trim().toLowerCase();
    options.forEach(function (o) { o.hidden = q !== '' && o.textContent.toLowerCase().indexOf(q) === -1; });
  }

  function close() {
    if (popup.classList.contains('hidden')) return;
    popup.classList.add('hidden');
    popup.removeAttribute('data-ui-select-popup');
    toggle.setAttribute('aria-expanded', 'false');
    document.removeEventListener('mousedown', onOutside, true);
    window.removeEventListener('scroll', onScroll, true);
    window.removeEventListener('resize', place);
    document.removeEventListener('keydown', onKey);
    if (window.__areaMultiClose === close) window.__areaMultiClose = null;
  }

  function onOutside(e) { if (! popup.contains(e.target) && ! toggle.contains(e.target)) close(); }
  function onScroll(e) { if (! popup.contains(e.target)) close(); }
  function onKey(e) {
    if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); toggle.focus(); }
  }

  function open() {
    if (toggle.disabled) return;
    if (window.__areaMultiClose) window.__areaMultiClose();
    popup.classList.remove('hidden');
    popup.setAttribute('data-ui-select-popup', '');
    toggle.setAttribute('aria-expanded', 'true');
    place();
    search.value = '';
    filter();
    search.focus();
    document.addEventListener('mousedown', onOutside, true);
    window.addEventListener('scroll', onScroll, true);
    window.addEventListener('resize', place);
    document.addEventListener('keydown', onKey);
    window.__areaMultiClose = close;
  }

  function sync() {
    var isMod = role && role.value === 'mod';
    var noAreas = role && role.value === 'jefe_de_area';
    if (singleWrap) singleWrap.hidden = isMod || noAreas;
    if (multiWrap) multiWrap.hidden = !isMod;
    if (single) single.disabled = isMod || noAreas;
    checks.forEach(function (c) { c.disabled = !isMod; });
    toggle.disabled = !isMod;
    if (! isMod) close();
  }

  checks.forEach(function (c) { c.addEventListener('change', refresh); });
  toggle.addEventListener('click', function () {
    if (popup.classList.contains('hidden')) open(); else close();
  });
  search.addEventListener('input', filter);
  if (role) role.addEventListener('change', sync);
  var modalBox = form.closest('[data-app-modal]');
  if (modalBox) modalBox.addEventListener('modal:close', resetForm);
  refresh();
  sync();
})();
</script>
