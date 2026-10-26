@php
  $me = auth()->user();
  $canRegulate = $me->hasRole('admin', 'mod');
@endphp
<x-panel-layout title="Documentos">
  <section class="flex flex-wrap items-center justify-between gap-4">
    <div>
      <h2 class="mb-1 text-xl font-bold text-gray-900">Documentos</h2>
      <p class="text-sm text-gray-600">
        @if ($me->isAdmin())
          Todos los documentos del sistema.
        @elseif ($canRegulate)
          Documentos de tu área ({{ $me->area?->name }}). Puedes revisarlos, editarlos y marcarlos como completados.
        @else
          Documentos del área {{ $me->area?->name }}.
        @endif
      </p>
    </div>
    <a href="{{ route('documents.create') }}" class="hp-btn-primary">
      <i class="bi bi-plus-lg" aria-hidden="true"></i><span>Nuevo documento</span>
    </a>
  </section>

  <form method="GET" action="{{ route('documents.index') }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por título o código"
           class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">

    <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
      <option value="">Todos los estados</option>
      <option value="draft" @selected(request('status') === 'draft')>Borrador</option>
      <option value="completed" @selected(request('status') === 'completed')>Completado</option>
    </select>

    @if ($canRegulate)
      <select name="author" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
        <option value="">Todos los autores</option>
        @foreach ($authors as $author)
          <option value="{{ $author->id }}" @selected(request('author') == $author->id)>{{ $author->name }}</option>
        @endforeach
      </select>
    @endif

    @if ($me->isAdmin())
      <select name="area" class="rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand">
        <option value="">Todas las áreas</option>
        @foreach ($areas as $area)
          <option value="{{ $area->id }}" @selected(request('area') == $area->id)>{{ $area->name }}</option>
        @endforeach
      </select>
    @endif

    <div class="flex gap-2">
      <button type="submit" class="flex-1 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filtrar</button>
      <a href="{{ route('documents.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Limpiar</a>
    </div>
  </form>

  <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
        <tr>
          <th class="px-4 py-3">Documento</th>
          <th class="px-4 py-3">Plantilla</th>
          <th class="px-4 py-3">Área</th>
          <th class="px-4 py-3">Estado</th>
          <th class="px-4 py-3">Última edición</th>
          <th class="px-4 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse ($documents as $doc)
          @php $done = $doc->status === 'completed'; @endphp
          <tr>
            <td class="px-4 py-3">
              <p class="font-semibold text-gray-900">{{ $doc->title ?? 'Sin título' }}</p>
              <p class="text-xs text-gray-500">{{ $doc->code }} · creado por {{ $doc->user?->name ?? '—' }}</p>
            </td>
            <td class="px-4 py-3 text-gray-700">{{ $doc->template?->name ?? '—' }}</td>
            <td class="px-4 py-3 text-gray-700">{{ $doc->area?->name ?? '—' }}</td>
            <td class="px-4 py-3">
              @if ($done)
                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Completado</span>
              @else
                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">Borrador</span>
              @endif
            </td>
            <td class="px-4 py-3 text-gray-700">
              {{ $doc->updated_at->format('d/m/Y H:i') }}
              @if ($doc->editor)
                <p class="text-xs text-gray-500">por {{ $doc->editor->name }}</p>
              @endif
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap items-center justify-end gap-2">
                @can('edit', $doc)
                  <a href="{{ route('documents.edit', $doc) }}" class="rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800">Abrir</a>
                @else
                  <a href="{{ route('documents.edit', $doc) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Ver</a>
                @endcan

                <a href="{{ route('documents.download', $doc) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Descargar</a>

                @can('manage', $doc)
                  <form method="POST" action="{{ route('documents.status', $doc) }}"
                        data-confirm="{{ $done ? 'Los usuarios podrán volver a editar «'.$doc->title.'».' : 'Los usuarios ya no podrán editar «'.$doc->title.'»; solo tú y el administrador. Podrás reabrirlo cuando quieras.' }}"
                        data-confirm-title="{{ $done ? 'Reabrir documento' : 'Marcar como completado' }}"
                        data-confirm-text="{{ $done ? 'Reabrir' : 'Completar' }}"
                        data-confirm-variant="{{ $done ? 'primary' : 'warning' }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-lg border px-3 py-1.5 text-xs font-medium {{ $done ? 'border-indigo-300 text-indigo-700 hover:bg-indigo-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                      {{ $done ? 'Reabrir' : 'Completar' }}
                    </button>
                  </form>
                @endcan
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-4 py-12 text-center text-gray-500">
              No hay documentos que mostrar. <a href="{{ route('documents.create') }}" class="font-medium text-brand hover:underline">Crea uno</a> a partir de una plantilla.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $documents->links() }}
</x-panel-layout>
