@php
  $badge = match (\App\Models\AuditLog::tone($log->event)) {
      'green' => 'bg-green-100 text-green-700',
      'red' => 'bg-red-100 text-red-700',
      'amber' => 'bg-amber-100 text-amber-700',
      'indigo' => 'bg-indigo-50 text-indigo-700',
      default => 'bg-gray-100 text-gray-600',
  };
  $roleLabel = $log->user_role ? \App\Enums\UserRole::tryFrom($log->user_role)?->label() : null;
@endphp
<tr data-log-id="{{ $log->id }}" class="transition-colors duration-1000">
  <td class="whitespace-nowrap px-4 py-3 align-top text-gray-700">
    {{ $log->created_at->format('d/m/Y') }}
    <span class="block text-xs text-gray-500">{{ $log->created_at->format('H:i:s') }}</span>
  </td>

  <td class="px-4 py-3 align-top">
    @if ($log->user_name)
      <p class="font-medium text-gray-900">{{ $log->user_name }}</p>
      @if ($roleLabel)<p class="text-xs text-gray-500">{{ $roleLabel }}</p>@endif
    @else
      <p class="text-gray-500">Sin sesión</p>
    @endif
  </td>

  <td class="px-4 py-3 align-top">
    <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge }}">
      {{ \App\Models\AuditLog::eventLabel($log->event) }}
    </span>
  </td>

  <td class="px-4 py-3 align-top">
    <p class="text-gray-900">{{ $log->description }}</p>

    @php $changes = $log->changes(); @endphp
    @if (! empty($changes))
      <ul class="mt-1 space-y-0.5 text-xs text-gray-600">
        @foreach ($changes as $change)
          <li>
            <span class="font-medium">{{ $change['label'] }}:</span>
            <span class="text-gray-400 line-through">{{ $change['old'] }}</span>
            →
            <span class="text-gray-800">{{ $change['new'] }}</span>
          </li>
        @endforeach
      </ul>
    @endif
  </td>

  <td class="px-4 py-3 align-top text-gray-700">{{ $log->area?->name ?? '—' }}</td>
</tr>
