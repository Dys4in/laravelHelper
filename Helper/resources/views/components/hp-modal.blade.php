
{{--
  Componente modal de Helper.
  Utiliza <dialog> y resources/js/modals.js.
--}}

@props([
    'id',
    'title',
    'subtitle' => null,
    'icon' => 'bi-plus-lg',
    'size' => 'lg',
    'open' => false,
])

@php
    $widths = [
        'md' => 'max-w-md',
        'lg' => 'max-w-xl',
        'xl' => 'max-w-2xl',
    ];
@endphp

<dialog
    id="{{ $id }}"
    class="hp-modal {{ $widths[$size] ?? $widths['lg'] }}"
    aria-labelledby="{{ $id }}-title"
    {{ $open ? 'data-modal-autoopen' : '' }}
>
    <header class="hp-modal-head">
        <span class="hp-modal-icon" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>

        <div class="min-w-0">
            <h2 id="{{ $id }}-title" class="hp-modal-title">
                {{ $title }}
            </h2>

            @if ($subtitle)
                <p class="hp-modal-sub">{{ $subtitle }}</p>
            @endif
        </div>

        <button
            type="button"
            class="hp-modal-close"
            data-modal-close
            aria-label="Cerrar"
        >
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </header>

    {{ $slot }}
</dialog>
