{{-- Documentos recientes. Variables: $recent · $showOwner --}}
@php $showOwner = $showOwner ?? false; @endphp
<div class="hp-rows">
  @forelse ($recent as $doc)
    <a class="hp-row no-underline" href="{{ route('documents.edit', $doc) }}">
      <span class="hp-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-word"></i></span>
      <div class="hp-row-main">
        <p class="hp-row-title">{{ $doc->title }}</p>
        <p class="hp-row-sub">
          {{ $doc->code }} · Creado {{ $doc->created_at->format('d/m/Y') }}@if ($showOwner) · {{ $doc->user?->name ?? 'Usuario eliminado' }}@if ($doc->area) · {{ $doc->area->name }}@endif @endif
        </p>
      </div>
      <span class="hp-badge hp-badge--{{ $doc->status }}">{{ $doc->status }}</span>
    </a>
  @empty
    <p class="py-6 text-center text-sm text-gray-500">Aún no hay documentos.</p>
  @endforelse
</div>
