<x-panel-layout title="Inicio">
  <section class="rounded-xl border border-gray-200 bg-white p-6">
    <h2 class="text-xl font-bold text-gray-900">Hola, {{ auth()->user()->name }}</h2>
    <p class="mt-1 text-sm text-gray-600">
      {{ auth()->user()->role->label() }} · Área {{ auth()->user()->area?->name ?? '—' }}
    </p>
    <p class="mt-3 max-w-2xl text-sm text-gray-600">
      Desde aquí cargas las plantillas de tu área, revisas y editas los documentos de tu equipo y sigues su actividad.
    </p>

    <div class="mt-5 flex flex-wrap gap-3">
      <a href="{{ route('templates.create') }}" class="hp-btn-primary"><i class="bi bi-upload" aria-hidden="true"></i><span>Cargar plantilla</span></a>
      <a href="{{ route('documents.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Documentos del área</a>
      <a href="{{ route('audit.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Actividad</a>
      <a href="{{ route('documents.create') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Nuevo documento</a>
    </div>
  </section>
</x-panel-layout>
