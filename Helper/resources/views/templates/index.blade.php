<x-panel-layout title="Plantillas">
  <section class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h2 class="mb-1 text-xl font-bold text-gray-900">Plantillas</h2>
      <p class="text-sm text-gray-600">
        @if (auth()->user()->isAdmin())
          Plantillas de todas las áreas. Cada documento es una copia: editarlo no modifica la plantilla.
        @else
          Plantillas del área {{ auth()->user()->area?->name }}: las que subas aquí son las que tu equipo elige para crear documentos.
        @endif
      </p>
    </div>
    <a href="{{ route('templates.create') }}" class="hp-btn-primary">
      <i class="bi bi-upload" aria-hidden="true"></i><span>Cargar plantilla</span>
    </a>
  </section>

  <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        <tr>
          <th class="px-4 py-3">Plantilla</th>
          <th class="px-4 py-3">Disponible para</th>
          <th class="px-4 py-3">Subida por</th>
          <th class="px-4 py-3">Usos</th>
          <th class="px-4 py-3">Estado</th>
          <th class="px-4 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse ($templates as $t)
          <tr>
            <td class="px-4 py-3">
              <p class="font-semibold text-gray-900">{{ $t->name }}</p>
              <p class="text-xs text-gray-500">
                {{ $t->original_name }} · {{ \Illuminate\Support\Number::fileSize($t->file_size, precision: 1) }}
              </p>
              @if ($t->description)
                <p class="mt-0.5 text-xs text-gray-500">{{ $t->description }}</p>
              @endif
            </td>
            <td class="px-4 py-3 text-gray-700">
              @if ($t->area)
                {{ $t->area->name }}
                @if ($t->area->trashed())<span class="text-xs text-red-600">(eliminada)</span>@endif
              @else
                <span class="text-gray-500">Todas las áreas</span>
              @endif
            </td>
            <td class="px-4 py-3 text-gray-700">{{ $t->creator?->name ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-700">{{ $t->documents_count }}</td>
            <td class="px-4 py-3">
              @if ($t->isActive())
                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Activa</span>
              @else
                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">Inactiva</span>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('templates.download', $t) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Descargar</a>
                <a href="{{ route('templates.edit', $t) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Editar</a>

                <form method="POST" action="{{ route('templates.status', $t) }}"
                      data-confirm="{{ $t->isActive() ? 'Los usuarios dejarán de ver «'.$t->name.'» al crear documentos. Los documentos ya creados no se afectan.' : 'Los usuarios podrán volver a elegir «'.$t->name.'».' }}"
                      data-confirm-title="{{ $t->isActive() ? 'Desactivar plantilla' : 'Activar plantilla' }}"
                      data-confirm-text="{{ $t->isActive() ? 'Desactivar' : 'Activar' }}"
                      data-confirm-variant="{{ $t->isActive() ? 'warning' : 'primary' }}">
                  @csrf
                  @method('PATCH')
                  <button type="submit" class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $t->isActive() ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                    {{ $t->isActive() ? 'Desactivar' : 'Activar' }}
                  </button>
                </form>

                <form method="POST" action="{{ route('templates.destroy', $t) }}"
                      data-confirm="«{{ $t->name }}» dejará de aparecer en la lista. Los documentos creados con ella se conservan."
                      data-confirm-title="Eliminar plantilla"
                      data-confirm-text="Eliminar"
                      data-confirm-variant="danger">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-4 py-12 text-center text-gray-500">
              Aún no hay plantillas. <a href="{{ route('templates.create') }}" class="font-medium text-brand hover:underline">Carga la primera</a>.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $templates->links() }}
</x-panel-layout>
