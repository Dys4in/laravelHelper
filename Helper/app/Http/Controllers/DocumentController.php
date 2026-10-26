<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Document;
use App\Models\Template;
use App\Models\User;
use App\Support\Audit;
use App\Support\OnlyOfficeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->input('q'));
        $canRegulate = $user->hasRole('admin', 'mod');

        $documents = Document::query()
            ->with(['user', 'area', 'template', 'editor'])
            ->visibleTo($user)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($w) use ($search) {
                    $w->where('title', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($user->isAdmin() && $request->filled('area'), fn ($q) => $q->where('area_id', $request->integer('area')))
            ->when($canRegulate && $request->filled('author'), fn ($q) => $q->where('user_id', $request->integer('author')))
            ->when(in_array($request->input('status'), ['draft', 'completed'], true), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'areas' => $user->isAdmin() ? Area::orderBy('name')->get() : collect(),
            // Quien regula puede filtrar por autor: el moderador, solo entre los de su área.
            'authors' => $canRegulate
                ? User::query()
                    ->when(! $user->isAdmin(), fn ($q) => $q->where('area_id', $user->area_id ?? 0))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect(),
        ]);
    }

    /** Paso 1 del flujo: elegir una plantilla ya cargada. */
    public function create(Request $request): View
    {
        $user = $request->user();

        return view('documents.create', [
            'templates' => Template::query()->active()->availableFor($user)->with('area')->orderBy('name')->get(),
            'areas' => $user->isAdmin() ? Area::active()->orderBy('name')->get() : collect(),
        ]);
    }

    /** Paso 2: se copia la plantilla y se abre la copia en el editor. */
    public function store(Request $request, OnlyOfficeService $oo): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'template_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            // El admin no tiene área propia: elige en cuál se crea el documento.
            'area_id' => [Rule::requiredIf($user->isAdmin()), 'nullable', Rule::exists('areas', 'id')->whereNull('deleted_at')],
        ], [
            'template_id.required' => 'Elige una plantilla.',
            'title.required' => 'Escribe un título para el documento.',
            'area_id.required' => 'Selecciona el área del documento.',
        ]);

        $template = Template::query()->active()->availableFor($user)->find($data['template_id']);

        if (! $template) {
            return back()->withInput()->with('error', 'Esa plantilla no está disponible para tu área.');
        }

        $disk = $oo->disk();

        if (! $disk->exists($template->file_path)) {
            return back()->withInput()->with('error', 'El archivo de la plantilla no se encontró en el servidor. Avisa al administrador.');
        }

        $areaId = $user->isAdmin() ? (int) $data['area_id'] : $user->area_id;
        $path = 'documents/'.Str::uuid().'.docx';

        $disk->copy($template->file_path, $path);

        try {
            $document = DB::transaction(function () use ($data, $user, $areaId, $template, $path) {
                $document = Document::create([
                    'title' => $data['title'],
                    'status' => 'draft',
                    'user_id' => $user->id,
                    'area_id' => $areaId,
                    'template_id' => $template->id,
                    'file_path' => $path,
                    'doc_key' => OnlyOfficeService::newKey(),
                    'version' => 1,
                    'updated_by' => $user->id,
                ]);

                $document->update(['code' => sprintf('DOC-%s-%05d', now()->format('Y'), $document->id)]);

                return $document;
            });
        } catch (\Throwable $e) {
            $disk->delete($path); // no dejar archivos huérfanos si falla la base de datos
            throw $e;
        }

        return redirect()->route('documents.edit', $document)->with('status', 'Documento creado a partir de la plantilla.');
    }

    /** Abre el documento en OnlyOffice. */
    public function edit(Request $request, Document $document, OnlyOfficeService $oo): View|RedirectResponse
    {
        Gate::authorize('view', $document);

        if (! $document->file_path || ! $oo->disk()->exists($document->file_path)) {
            return redirect()->route('documents.index')->with('error', 'El archivo de este documento no se encontró en el servidor.');
        }

        // Completado = solo lectura para usuarios; moderador y admin siguen pudiendo editar.
        $canEdit = Gate::allows('edit', $document);

        try {
            $config = $oo->editorConfig($document, $request->user(), $canEdit);
        } catch (RuntimeException $e) {
            return redirect()->route('documents.index')->with('error', $e->getMessage());
        }

        Audit::log(
            'document.opened',
            ($canEdit ? 'Abrió' : 'Abrió en modo lectura')." el documento «{$document->title}»",
            $document,
        );

        return view('documents.edit', [
            'document' => $document,
            'config' => $config,
            'canEdit' => $canEdit,
            'apiUrl' => $oo->apiScriptUrl(),
            'serverUrl' => config('onlyoffice.url'),
        ]);
    }

    /** Regular: el moderador (de su área) o el admin marcan el documento como completado o lo reabren. */
    public function toggleStatus(Document $document): RedirectResponse
    {
        Gate::authorize('manage', $document);

        $document->update(['status' => $document->status === 'completed' ? 'draft' : 'completed']);

        return back()->with('status', $document->status === 'completed'
            ? "«{$document->title}» se marcó como completado: los usuarios ya no pueden editarlo."
            : "«{$document->title}» se reabrió: los usuarios pueden volver a editarlo.");
    }

    public function download(Document $document, OnlyOfficeService $oo): StreamedResponse
    {
        Gate::authorize('view', $document);

        abort_unless($document->file_path && $oo->disk()->exists($document->file_path), 404);

        Audit::log('document.downloaded', "Descargó el documento «{$document->title}»", $document);

        return $oo->disk()->download($document->file_path, (Str::slug($document->title) ?: 'documento').'.docx');
    }
}
