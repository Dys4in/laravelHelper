<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\TemplateRequest;
use App\Models\Area;
use App\Models\Template;
use App\Services\AuditLogger;
use App\Support\OnlyOfficeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plantillas .docx.
 *  - Usuario:       las ve y las usa ("Usar plantilla") para crear documentos.
 *  - Moderador:     además sube y administra las de su área.
 *  - Administrador: controla las de todas las áreas.
 */
class TemplateController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Template::class);

        $user = $request->user();
        $canManage = $user->hasRole('admin', 'mod');
        $search = trim((string) $request->input('q'));

        $base = Template::query()
            ->visibleTo($user)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($user->isAdmin() && $request->filled('area'), function ($q) use ($request) {
                $request->input('area') === 'general'
                    ? $q->whereNull('area_id')
                    : $q->where('area_id', $request->integer('area'));
            });

        $templates = (clone $base)
            ->with(['area', 'creator'])
            ->withCount('documents')
            ->when($canManage && in_array($request->input('status'), [Template::ACTIVE, Template::INACTIVE], true), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $counts = (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('templates.index', [
            'templates' => $templates,
            'canManage' => $canManage,
            'counts' => [
                'active' => (int) ($counts[Template::ACTIVE] ?? 0),
                'inactive' => (int) ($counts[Template::INACTIVE] ?? 0),
            ],
            // Áreas donde se puede subir una plantilla / crear un documento
            'formAreas' => $user->isAdmin() ? Area::active()->orderBy('name')->get() : collect(),
            'newTemplate' => new Template(['status' => Template::ACTIVE]),
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

    public function edit(Template $template): View
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

        AuditLogger::log('template.downloaded', "Descargó la plantilla «{$template->name}»", $template);

        return $oo->disk()->download($template->file_path, (Str::slug($template->name) ?: 'plantilla').'.docx');
    }
}
