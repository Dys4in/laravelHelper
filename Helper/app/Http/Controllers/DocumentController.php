<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Document;
use App\Models\Template;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\DocxFonts;
use App\Support\DocxValidator;
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
    /** "Mis documentos": los que creó el usuario. Es la pantalla de inicio de usuarios y moderadores. */
    public function index(Request $request): View
    {
        return view('documents.index', [
            'documents' => Document::query()
                ->ownedBy($request->user())
                ->with(['user', 'editor', 'area', 'template'])
                ->latest('updated_at')
                ->get(),
        ]);
    }

    /**
     * Supervisión: moderador = documentos de su área; administrador = todos.
     * Solo supervisa; no se crean documentos desde aquí.
     */
    public function supervision(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->input('q'));

        $documents = Document::query()
            ->supervisedBy($user)
            ->with(['user', 'editor', 'area', 'template'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($w) use ($search) {
                    $w->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($user->isAdmin() && $request->filled('area'), fn ($q) => $q->where('area_id', $request->integer('area')))
            ->when($request->filled('author'), fn ($q) => $q->where('user_id', $request->integer('author')))
            ->when(in_array($request->input('status'), [Document::DRAFT, Document::COMPLETED], true), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('documents.supervision', [
            'documents' => $documents,
            'areas' => $user->isAdmin() ? Area::orderBy('name')->get() : collect(),
            'authors' => User::query()
                ->when(! $user->isAdmin(), fn ($q) => $q->where('area_id', $user->area_id ?? 0))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /** Ya no hay pantalla propia: crear un documento empieza eligiendo plantilla. */
    public function create(): RedirectResponse
    {
        return redirect()->route('templates.index');
    }

    /** "Usar plantilla": se copia la plantilla y se abre la copia en el editor. */
    public function store(Request $request, OnlyOfficeService $oo): RedirectResponse
    {
        Gate::authorize('create', Document::class);

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
            return back()->withInput()->with('error', 'El archivo de la plantilla no se encontró en el servidor. Avisa al moderador de tu área.');
        }

        $areaId = $user->isAdmin() ? (int) $data['area_id'] : $user->area_id;
        $path = 'documents/'.Str::uuid().'.docx';

        $disk->copy($template->file_path, $path);
        $this->useArialByDefault($oo, $path);

        try {
            $document = DB::transaction(function () use ($data, $user, $areaId, $template, $path) {
                $document = Document::create([
                    'title' => $data['title'],
                    'status' => Document::DRAFT,
                    'user_id' => $user->id,
                    'area_id' => $areaId,
                    'template_id' => $template->id,
                    'file_path' => $path,
                    'doc_key' => OnlyOfficeService::newKey(),
                    'version' => 1,
                    'updated_by' => $user->id,
                ]);

                // Sin eventos: no es un cambio del usuario, solo se completa el código.
                $document->updateQuietly(['code' => sprintf('DOC-%s-%05d', now()->format('Y'), $document->id)]);

                return $document;
            });
        } catch (\Throwable $e) {
            $disk->delete($path); // no dejar archivos huérfanos si falla la base de datos
            throw $e;
        }

        return redirect()->route('documents.edit', $document)->with('status', 'Documento creado a partir de la plantilla.');
    }

    /**
     * Abre el documento en OnlyOffice. El modo depende de quién entra:
     *  - completado        → solo lectura, para cualquier usuario
     *  - el creador        → edición
     *  - moderador / admin → revisión (control de cambios), si no son el creador
     */
    public function edit(Request $request, Document $document, OnlyOfficeService $oo): View|RedirectResponse
    {
        Gate::authorize('view', $document);

        if (! $document->file_path || ! $oo->disk()->exists($document->file_path)) {
            return redirect()->route('documents.index')->with('error', 'El archivo de este documento no se encontró en el servidor.');
        }

        $document->loadMissing('user');

        $mode = match (true) {
            Gate::allows('edit', $document) => 'edit',
            Gate::allows('review', $document) => 'review',
            default => 'view',
        };

        try {
            $config = $oo->editorConfig($document, $request->user(), $mode);
        } catch (RuntimeException $e) {
            return redirect()->route('documents.index')->with('error', $e->getMessage());
        }

        AuditLogger::log(
            'document.opened',
            match ($mode) {
                'edit' => 'Abrió',
                'review' => 'Abrió en modo revisión',
                default => 'Abrió en modo lectura',
            }." el documento «{$document->title}»",
            $document,
        );

        return view('documents.edit', [
            'document' => $document,
            'config' => $config,
            'mode' => $mode,
            'apiUrl' => $oo->apiScriptUrl(),
            'serverUrl' => config('onlyoffice.url'),
        ]);
    }

    /** Completar / reabrir: moderador (de su área) y administrador. */
    public function toggleStatus(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('changeStatus', $document);

        $target = $request->input('status');

        if (! in_array($target, [Document::DRAFT, Document::COMPLETED], true)) {
            $target = $document->isCompleted() ? Document::DRAFT : Document::COMPLETED;
        }

        $document->update(['status' => $target]);

        return back()->with('status', $document->isCompleted()
            ? "«{$document->title}» se marcó como completado: ya se puede descargar y los usuarios no pueden editarlo."
            : "«{$document->title}» volvió a borrador: los usuarios pueden editarlo de nuevo y no se puede descargar.");
    }

    /** Descarga: solo documentos completados (el admin siempre). */
    public function download(Document $document, OnlyOfficeService $oo): StreamedResponse|RedirectResponse
    {
        Gate::authorize('view', $document);

        if (! Gate::allows('download', $document)) {
            return back()->with('error', 'El documento se podrá descargar cuando esté marcado como completado.');
        }

        abort_unless($document->file_path && $oo->disk()->exists($document->file_path), 404);

        AuditLogger::log('document.downloaded', "Descargó el documento «{$document->title}»", $document);

        return $oo->disk()->download($document->file_path, (Str::slug($document->title) ?: 'documento').'.docx');
    }

    /** Eliminar: el dueño mientras esté en borrador; el admin siempre. */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $title = $document->title;
        $document->delete(); // soft delete: el archivo y el historial se conservan

        return back()->with('status', "El documento «{$title}» fue eliminado.");
    }

    /**
     * La copia del documento queda con Arial como fuente predeterminada (ver DocxFonts).
     * Si algo falla, el documento se crea igual con la fuente de la plantilla.
     */
    private function useArialByDefault(OnlyOfficeService $oo, string $path): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx_');

        try {
            file_put_contents($tmp, $oo->disk()->get($path));

            // Solo se reemplaza el archivo si el resultado sigue siendo un .docx válido.
            if (DocxFonts::useArial($tmp) && DocxValidator::isValid($tmp)) {
                $stream = fopen($tmp, 'rb');
                $oo->disk()->put($path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        } finally {
            @unlink($tmp);
        }
    }
}
