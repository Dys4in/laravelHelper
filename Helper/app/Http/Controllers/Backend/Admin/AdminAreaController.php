<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AreaRequest;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminAreaController extends Controller
{
    public function index(): View
    {
        $members = Area::memberCounts();

        return view('admin.areas.index', [
            // users_count incluye a los moderadores que tienen el área entre las suyas.
            'areas' => Area::orderBy('name')->get()->each(
                fn (Area $area) => $area->setAttribute('users_count', $members[$area->id] ?? 0)
            ),
        ]);
    }

    public function store(AreaRequest $request): RedirectResponse
    {
        Area::create($request->validated());

        return redirect()->route('admin.areas.index')->with('status', 'Área creada correctamente.');
    }

    public function edit(Area $area): View
    {
        return view('admin.areas.edit', ['area' => $area]);
    }

    public function update(AreaRequest $request, Area $area): RedirectResponse
    {
        $data = $request->validated();
        $description = isset($data['description']) ? trim((string) $data['description']) : null;
        $description = $description === '' ? null : $description;
        $currentDescription = $area->description === null ? null : trim((string) $area->description);

        $unchanged = (string) $data['name'] === (string) $area->name
            && $description === $currentDescription;

        if ($unchanged) {
            return back()->withInput()->with('status', 'No hay cambios para guardar.');
        }

        $area->update($data);

        return redirect()->route('admin.areas.index')->with('status', 'Área actualizada correctamente.');
    }

    public function toggleStatus(Area $area): RedirectResponse
    {
        $area->update([
            'status' => $area->isActive() ? Area::SUSPENDED : Area::ACTIVE,
        ]);

        return back()->with('status', $area->isActive()
            ? "El área {$area->name} fue reactivada."
            : "El área {$area->name} fue suspendida: sus usuarios ya no podrán iniciar sesión.");
    }

    public function destroy(Area $area): RedirectResponse
    {
        $users = $area->assignedUsersCount();

        if ($users > 0) {
            return back()->with('error', "No se puede eliminar «{$area->name}»: tiene {$users} usuario(s) asignado(s). Reasígnalos o suspende el área.");
        }

        $area->delete(); // soft delete: se conserva el historial de documentos

        return back()->with('status', "El área {$area->name} fue eliminada.");
    }
}
