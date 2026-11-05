{{-- Distribución por estado. Variable: $counts (total, draft, completed) --}}
@php
  $total = $counts['total'];
  $donePct = $total > 0 ? (int) round($counts['completed'] / $total * 100) : 0;
  $draftPct = $total > 0 ? 100 - $donePct : 0;
@endphp
<div class="hp-bar" role="img" aria-label="{{ $counts['completed'] }} completados y {{ $counts['draft'] }} borradores de {{ $total }}">
  <span class="hp-bar-seg--completed" style="width: {{ $donePct }}%"></span>
  <span class="hp-bar-seg--draft" style="width: {{ $draftPct }}%"></span>
</div>
<div class="hp-legend">
  <span><i class="hp-dot hp-dot--completed"></i>Completados · {{ $counts['completed'] }} ({{ $donePct }}%)</span>
  <span><i class="hp-dot hp-dot--draft"></i>Borradores · {{ $counts['draft'] }} ({{ $draftPct }}%)</span>
</div>
