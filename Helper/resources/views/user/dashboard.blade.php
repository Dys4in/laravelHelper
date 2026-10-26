<x-panel-layout title="Inicio">
  <section class="rounded-xl border border-gray-200 bg-white p-6">
    <h2 class="text-xl font-bold text-gray-900">Hola, {{ auth()->user()->name }}</h2>
    <p class="mt-1 text-sm text-gray-600">
      {{ auth()->user()->role->label() }} · Área {{ auth()->user()->area?->name ?? '—' }}
    </p>

    <div class="mt-5 flex flex-wrap gap-3">
      <a href="{{ route('documents.create') }}" class="hp-btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Nuevo documento</span></a>
      <a href="{{ route('documents.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Ver documentos</a>
    </div>
  </section>
</x-panel-layout>
