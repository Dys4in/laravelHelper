{{-- Documentos creados por mes. Variable: $months (lista de label/count) --}}
@php $max = max(1, collect($months)->max('count')); @endphp
<div class="hp-chart" role="img" aria-label="Documentos creados por mes">
  @foreach ($months as $m)
    <div class="hp-chart-col">
      <span class="hp-chart-value">{{ $m['count'] }}</span>
      <div class="hp-chart-bar {{ $m['count'] === 0 ? 'hp-chart-bar--zero' : '' }}" style="height: {{ (int) round($m['count'] / $max * 100) }}%"></div>
      <span class="hp-chart-label">{{ $m['label'] }}</span>
    </div>
  @endforeach
</div>
