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
    /** "Mis documentos": los que creó el usuario. Es la pantalla de inicio de usuarios y coordinadores. */
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
     * Supervisión: coordinador = documentos de sus áreas; administrador = todos.
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
            ->when(
                $request->filled('area') && ($user->isAdmin() || $user->hasArea($request->integer('area'))),
                fn ($q) => $q->where('area_id', $request->integer('area')),
            )
            ->when($request->filled('author'), fn ($q) => $q->where('user_id', $request->integer('author')))
            ->when(in_array($request->input('status'), [Document::DRAFT, Document::COMPLETED], true), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('documents.supervision', [
            'documents' => $documents,
            // Lista del filtro «área»: el admin ve todas; el coordinador, las suyas (solo si tiene más de una).
            'areas' => $user->isAdmin()
                ? Area::orderBy('name')->get()
                : $user->assignedAreas()->filter(fn (Area $area) => ! $area->trashed() && $area->isActive())->values(),
            'authors' => User::query()
                ->when(! $user->isAdmin(), function ($q) use ($user) {
                    $ids = $user->areaIds() ?: [0];

                    $q->where(fn ($w) => $w->whereIn('area_id', $ids)
                        ->orWhereIn('id', fn ($sub) => $sub->from('area_user')->select('user_id')->whereIn('area_id', $ids)));
                })
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
        $areaIds = $user->areaIds();

        // El admin y el coordinador con varias áreas eligen en cuál se crea el documento.
        $mustPickArea = $user->isAdmin() || count($areaIds) > 1;

        if (Document::limitReachedFor($user)) {
            return back()->withInput()->with(
                'error',
                'Alcanzaste el límite de '.Document::MAX_PER_USER.' documentos. Elimina uno para poder crear otro.',
            );
        }

        $data = $request->validate([
            'template_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'area_id' => [
                Rule::requiredIf($mustPickArea),
                'nullable',
                $user->isAdmin()
                    ? Rule::exists('areas', 'id')->whereNull('deleted_at')
                    : Rule::in($areaIds ?: [0]),
            ],
        ], [
            'template_id.required' => 'Elige una plantilla.',
            'title.required' => 'Escribe un título para el documento.',
            'area_id.required' => 'Selecciona el área del documento.',
            'area_id.in' => 'Elige una de tus áreas.',
        ]);

        $template = Template::query()->active()->availableFor($user)->find($data['template_id']);

        if (! $template) {
            return back()->withInput()->with('error', 'Esa plantilla no está disponible para tu área.');
        }

        $disk = $oo->disk();

        if (! $disk->exists($template->file_path)) {
            return back()->withInput()->with('error', 'El archivo de la plantilla no se encontró en el servidor. Avisa al coordinador de tu área.');
        }

        // El nombre de la plantilla queda fijo al usarla; lo que escriba el usuario se añade al final.
        $templateName = $template->name;
        $title = Str::limit(trim($templateName.' - '.$data['title']), 150, '');

        $areaId = $mustPickArea ? (int) $data['area_id'] : ($areaIds[0] ?? null);
        $extension = OnlyOfficeService::extensionOf($template->file_path); // docx (Word) o xlsx (Excel)
        $path = 'documents/'.Str::uuid().'.'.$extension;

        $disk->copy($template->file_path, $path);

        if ($extension === 'docx') {
            $this->useArialByDefault($oo, $path);
        }

        try {
            $document = DB::transaction(function () use ($title, $templateName, $user, $areaId, $template, $path) {
                $document = Document::create([
                    'title' => $title,
                    'template_name' => $templateName,
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

    /** Cambia solo el nombre visible del documento; el archivo y su contenido no se tocan. */
    public function rename(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_id' => ['required', 'integer', 'exists:documents,id'],
            'title' => ['required', 'string', 'max:150'],
        ], [
            'title.required' => 'Escribe el nombre del documento.',
            'title.max' => 'El nombre no puede superar los 150 caracteres.',
        ]);

        $document = Document::findOrFail((int) $data['document_id']);
        Gate::authorize('rename', $document);

        $title = trim($data['title']);

        if ($title === $document->title) {
            return back()->with('status', 'No hay cambios para guardar.');
        }

        $document->update(['title' => $title]); // AuditObserver anota el cambio de "title"

        return back()->with('status', 'Nombre del documento actualizado.');
    }

    /**
     * Abre el documento en OnlyOffice. El modo depende de quién entra:
     *  - completado → solo lectura, para cualquier usuario
     *  - creador o coordinador de su área → edición
     *  - resto      → solo lectura
     */
    public function edit(Request $request, Document $document, OnlyOfficeService $oo): View|RedirectResponse
    {
        Gate::authorize('view', $document);

        if (! $document->file_path || ! $oo->disk()->exists($document->file_path)) {
            return redirect()->route('documents.index')->with('error', 'El archivo de este documento no se encontró en el servidor.');
        }

        $document->loadMissing('user');

        $mode = Gate::allows('edit', $document) ? 'edit' : 'view';

        try {
            $config = $oo->editorConfig($document, $request->user(), $mode);
        } catch (RuntimeException $e) {
            return redirect()->route('documents.index')->with('error', $e->getMessage());
        }

        AuditLogger::log(
            'document.opened',
            ($mode === 'edit' ? 'Abrió' : 'Abrió en modo lectura')." el documento «{$document->title}»",
            $document,
        );

        return view('documents.edit', [
            'document' => $document,
            'config' => $config,
            'mode' => $mode,
            'isSheet' => OnlyOfficeService::extensionOf($document->file_path) === 'xlsx',
            'apiUrl' => $oo->apiScriptUrl(),
            'serverUrl' => config('onlyoffice.url'),
        ]);
    }

    /** Completar / reabrir: coordinador (de su área) y administrador. */
    public function toggleStatus(Request $request, Document $document, OnlyOfficeService $oo): RedirectResponse
    {
        Gate::authorize('changeStatus', $document);

        $target = $request->input('status');

        if (! in_array($target, [Document::DRAFT, Document::COMPLETED], true)) {
            $target = $document->isCompleted() ? Document::DRAFT : Document::COMPLETED;
        }

        $document->update(['status' => $target]);

        // Al reabrir puede volver a editarse: el PDF en caché deja de ser válido.
        if ($target === Document::DRAFT) {
            $oo->forgetPdf($document);
        } else {
            // Se deja el PDF listo desde ya para que la primera descarga sea instantánea.
            try {
                $oo->ensurePdf($document);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('status', $document->isCompleted()
            ? "«{$document->title}» se marcó como completado: ya se puede descargar y los usuarios no pueden editarlo."
            : "«{$document->title}» volvió a En proceso: los usuarios pueden editarlo de nuevo y no se puede descargar.");
    }

    /** Descarga en PDF: solo documentos completados, sin excepciones. */
    public function download(Document $document, OnlyOfficeService $oo): StreamedResponse|RedirectResponse
    {
        Gate::authorize('download', $document);

        abort_unless($document->file_path && $oo->disk()->exists($document->file_path), 404);

        $filename = (Str::slug($document->title) ?: 'documento');

        try {
            $pdfPath = $oo->ensurePdf($document);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo generar el PDF: '.$e->getMessage());
        }

        AuditLogger::log('document.downloaded', "Descargó el documento «{$document->title}» en PDF", $document);

        return response()->streamDownload(function () use ($oo, $pdfPath) {
            $stream = $oo->disk()->readStream($pdfPath);

            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $filename.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    /** Descarga el archivo editable tal como está guardado: Word (.docx) o Excel (.xlsx), en cualquier estado. */
    public function downloadOriginal(Document $document, OnlyOfficeService $oo): StreamedResponse
    {
        Gate::authorize('downloadOriginal', $document);

        abort_unless($document->file_path && $oo->disk()->exists($document->file_path), 404, 'El archivo del documento no existe en el servidor.');

        $extension = OnlyOfficeService::extensionOf($document->file_path);
        $label = $extension === 'xlsx' ? 'Excel' : 'Word';

        AuditLogger::log('document.downloaded', "Descargó el documento «{$document->title}» en {$label}", $document);

        return $oo->disk()->download(
            $document->file_path,
            (Str::slug($document->title) ?: 'documento').'.'.$extension,
            ['Content-Type' => OnlyOfficeService::mimeFor($extension)],
        );
    }

    /** Eliminar: el dueño (usuario o coordinador) en cualquier estado; el admin siempre. */
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
