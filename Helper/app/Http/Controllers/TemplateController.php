<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\TemplateRequest;
use App\Models\Area;
use App\Models\Template;
use App\Support\Audit;
use App\Support\OnlyOfficeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gestión de plantillas .docx.
 * Moderador: las de su área. Administrador: todas.
 */
class TemplateController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Template::class);

        $user = $request->user();

        return view('templates.index', [
            'templates' => Template::query()
                ->with(['area', 'creator'])
                ->withCount('documents')
                ->when(! $user->isAdmin(), fn ($q) => $q->where('area_id', $user->area_id ?? 0))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Template::class);

        return view('templates.create', [
            'template' => new Template(['status' => Template::ACTIVE]),
            'areas' => Area::active()->orderBy('name')->get(),
        ]);
    }

    public function store(TemplateRequest $request): RedirectResponse
    {
        Gate::authorize('create', Template::class);

        $user = $request->user();
        $data = $request->validated();
        $file = $request->file('file');

        $size = $file->getSize();
        $originalName = $file->getClientOriginalName();
        $path = $file->storeAs('templates', Str::uuid().'.docx', ['disk' => config('onlyoffice.disk')]);

        Template::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            // El moderador siempre sube a su área, aunque manipule el formulario.
            'area_id' => $user->isAdmin() ? ($data['area_id'] ?? null) : $user->area_id,
            'status' => $data['status'] ?? Template::ACTIVE,
            'file_path' => $path,
            'original_name' => Str::limit($originalName, 250, ''),
            'file_size' => $size,
            'created_by' => $user->id,
        ]);

        return redirect()->route('templates.index')->with('status', 'Plantilla cargada correctamente.');
    }

    public function edit(Request $request, Template $template): View
    {
        Gate::authorize('manage', $template);

        return view('templates.edit', [
            'template' => $template,
            // áreas activas + la actual de la plantilla (aunque esté suspendida)
            'areas' => Area::query()
                ->where(fn ($q) => $q->where('status', Area::ACTIVE)->orWhere('id', $template->area_id))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(TemplateRequest $request, Template $template, OnlyOfficeService $oo): RedirectResponse
    {
        Gate::authorize('manage', $template);

        $user = $request->user();
        $data = $request->validated();

        $attributes = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            // Solo el admin puede mover una plantilla de área o hacerla general.
            'area_id' => $user->isAdmin() ? ($data['area_id'] ?? null) : $template->area_id,
            'status' => $data['status'] ?? $template->status,
        ];

        $oldPath = null;

        if ($file = $request->file('file')) {
            $oldPath = $template->file_path;

            $attributes['file_size'] = $file->getSize();
            $attributes['original_name'] = Str::limit($file->getClientOriginalName(), 250, '');
            $attributes['file_path'] = $file->storeAs('templates', Str::uuid().'.docx', ['disk' => config('onlyoffice.disk')]);
        }

        $template->update($attributes);

        // Los documentos ya creados son copias, así que el archivo anterior ya no se necesita.
        if ($oldPath) {
            $oo->disk()->delete($oldPath);
        }

        return redirect()->route('templates.index')->with('status', 'Plantilla actualizada correctamente.');
    }

    public function toggleStatus(Template $template): RedirectResponse
    {
        Gate::authorize('manage', $template);

        $template->update(['status' => $template->isActive() ? Template::INACTIVE : Template::ACTIVE]);

        return back()->with('status', $template->isActive()
            ? "La plantilla «{$template->name}» está activa y los usuarios pueden elegirla."
            : "La plantilla «{$template->name}» fue desactivada: ya no aparece al crear documentos.");
    }

    public function destroy(Template $template): RedirectResponse
    {
        Gate::authorize('manage', $template);

        // Soft delete: los documentos creados con ella conservan su historial.
        $template->delete();

        return back()->with('status', "La plantilla «{$template->name}» fue eliminada.");
    }

    public function download(Template $template, OnlyOfficeService $oo): StreamedResponse
    {
        Gate::authorize('manage', $template);

        abort_unless($oo->disk()->exists($template->file_path), 404, 'El archivo de la plantilla no existe en el servidor.');

        Audit::log('template.downloaded', "Descargó la plantilla «{$template->name}»", $template);

        return $oo->disk()->download($template->file_path, (Str::slug($template->name) ?: 'plantilla').'.docx');
    }
}
