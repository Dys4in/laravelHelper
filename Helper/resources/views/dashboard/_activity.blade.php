{{-- Actividad reciente (audit trail). Variable: $activity --}}
<div class="hp-rows">
  @forelse ($activity as $log)
    <div class="hp-row">
      <span class="hp-card-icon" aria-hidden="true"><i class="bi bi-activity"></i></span>
      <div class="hp-row-main">
        <p class="line-clamp-2 text-sm text-gray-800">{{ $log->description }}</p>
        <p class="hp-row-sub">{{ $log->user_name ?? 'Sistema' }} · {{ $log->created_at->locale('es')->diffForHumans() }}</p>
      </div>
    </div>
  @empty
    <p class="py-6 text-center text-sm text-gray-500">Sin actividad registrada.</p>
  @endforelse
</div>
