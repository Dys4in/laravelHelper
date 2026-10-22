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
        return view('admin.areas.index', [
            'areas' => Area::withCount('users')->orderBy('name')->get(),
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
        $area->update($request->validated());

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
        $users = $area->users()->count();

        if ($users > 0) {
            return back()->with('error', "No se puede eliminar «{$area->name}»: tiene {$users} usuario(s) asignado(s). Reasígnalos o suspende el área.");
        }

        $area->delete(); // soft delete: se conserva el historial de documentos

        return back()->with('status', "El área {$area->name} fue eliminada.");
    }
}
