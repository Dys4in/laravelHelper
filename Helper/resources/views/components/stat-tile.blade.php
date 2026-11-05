{{-- Indicador del dashboard: <x-stat-tile label="Borradores" :value="4" icon="bi-pencil" tone="gray" hint="..." /> --}}
@props(['label', 'value', 'icon' => 'bi-file-earmark-text', 'hint' => null, 'tone' => 'brand'])

@php
  // Clases completas (no concatenar) para que Tailwind las detecte.
  $tones = [
    'brand' => 'bg-brand-soft text-brand',
    'green' => 'bg-success-bg text-success-text',
    'gray' => 'bg-gray-100 text-gray-600',
    'amber' => 'bg-amber-100 text-amber-700',
  ];
@endphp

<div class="hp-tile">
  <span class="hp-tile-icon {{ $tones[$tone] ?? $tones['brand'] }}" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
  <div class="min-w-0">
    <p class="hp-tile-label">{{ $label }}</p>
    <p class="hp-tile-value">{{ $value }}</p>
    @if ($hint)
      <p class="hp-tile-hint">{{ $hint }}</p>
    @endif
  </div>
</div>
