<?php

namespace App\Support;

use App\Models\Document;
use App\Models\Template;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Toda la comunicación con OnlyOffice Document Server vive aquí:
 * URLs firmadas, configuración del editor, JWT y guardado desde el callback.
 */
class OnlyOfficeService
{
    public const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public function disk(): Filesystem
    {
        return Storage::disk(config('onlyoffice.disk'));
    }

    public function apiScriptUrl(): string
    {
        return config('onlyoffice.url').'/web-apps/apps/api/documents/api.js';
    }

    /** Clave de sesión de edición: [0-9a-zA-Z], máx. 128 caracteres. */
    public static function newKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * URL desde la que el Document Server descarga el .docx.
     * Firma "relativa" (sin host) para que sirva aunque el contenedor use otro nombre de host.
     */
    public function fileUrl(Document $document): string
    {
        $path = URL::temporarySignedRoute(
            'onlyoffice.file',
            now()->addMinutes(config('onlyoffice.file_url_ttl_minutes')),
            ['document' => $document->id, 'v' => $document->version],
            absolute: false,
        );

        return config('onlyoffice.storage_url').$path;
    }

    /** Igual que fileUrl(), pero para el archivo de una plantilla (solo se usa para convertirla a PDF). */
    public function templateFileUrl(Template $template): string
    {
        $path = URL::temporarySignedRoute(
            'onlyoffice.template',
            now()->addMinutes(config('onlyoffice.file_url_ttl_minutes')),
            ['template' => $template->id, 'f' => substr(sha1((string) $template->file_path), 0, 12)],
            absolute: false,
        );

        return config('onlyoffice.storage_url').$path;
    }

    /** URL a la que el Document Server avisa de los guardados. Sin vencimiento: una sesión puede durar horas. */
    public function callbackUrl(Document $document): string
    {
        $path = URL::signedRoute('onlyoffice.callback', ['document' => $document->id], absolute: false);

        return config('onlyoffice.storage_url').$path;
    }

    /**
     * Configuración que recibe DocsAPI.DocEditor en el navegador (ya firmada con JWT).
     *
     * @param  string  $mode  'edit' = edición · 'view' = solo lectura
     * @return array<string, mixed>
     */
    public function editorConfig(Document $document, User $user, string $mode = 'edit'): array
    {
        $mode = in_array($mode, ['edit', 'view'], true) ? $mode : 'view';

        $config = [
            'documentType' => 'word',
            'document' => [
                'fileType' => 'docx',
                'key' => $document->doc_key,
                'title' => $document->title.'.docx',
                'url' => $this->fileUrl($document),
                // Se muestra en Archivo → Información del documento.
                'info' => [
                    'owner' => $document->user?->name ?? '—',
                    'uploaded' => $document->created_at?->format('d/m/Y H:i'),
                ],
                'permissions' => [
                    // Las descargas e impresiones las controla la app (solo documentos completados).
                    'download' => false,
                    'print' => false,
                    // Sin la pestaña "Protección" (cifrar con contraseña, firma) ni el botón de proteger.
                    'protect' => false,
                    // edit = edición directa (sin control de cambios); view = solo lectura.
                    'edit' => $mode === 'edit',
                    'review' => false,
                    'comment' => $mode !== 'view',
                ],
            ],
            'editorConfig' => [
                // Un documento completado se abre como visor.
                'mode' => $mode === 'view' ? 'view' : 'edit',
                'lang' => config('onlyoffice.lang'),
                'callbackUrl' => $this->callbackUrl($document),
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
                'customization' => [
                    'autosave' => true,
                    'forcesave' => true,        // Ctrl+S guarda en Laravel sin cerrar el editor
                    'help' => false,            // sin el apartado "Ayuda"
                    'suggestFeature' => false,  // sin "Sugerir una función"
                    // Sin enlace al hacer clic en el logo. OnlyOffice documenta logo.url como opción de la
                    // edición Developer: en la gratuita puede ignorarse.
                    'logo' => ['url' => ''],
                ],
            ],
        ];

        // Complemento de herramientas del editor (se incorpora antes de firmar el JWT).
        // Se omite en modo lectura para respetar permisos.
        if ($mode !== 'view') {
            $config['editorConfig']['plugins'] = array_merge(
                $config['editorConfig']['plugins'] ?? [],
                [
                    'pluginsData' => array_values(array_unique(array_merge(
                        $config['editorConfig']['plugins']['pluginsData'] ?? [],
                        [route('onlyoffice.plugin.asset', ['file' => 'config.json'])]
                    ))),
                    'autostart' => array_values(array_unique(array_merge(
                        $config['editorConfig']['plugins']['autostart'] ?? [],
                        ['asc.{F7E9AB42-83CD-4C18-9B2B-981B4B2E54F4}']
                    ))),
                ]
            );
        }

        if (config('onlyoffice.jwt_enabled')) {
            $config['token'] = Jwt::encode($config, $this->secret());
        }

        return $config;
    }

    /** Ruta del PDF en disco, única por documento y versión (los completados no cambian). */
    public function pdfPath(Document $document): string
    {
        return 'pdf/'.$document->id.'-v'.$document->version.'.pdf';
    }

    /**
     * Devuelve la ruta del PDF en disco; si no existe, lo convierte una sola vez y lo guarda.
     * Así las siguientes descargas no vuelven a llamar a OnlyOffice.
     */
    public function ensurePdf(Document $document): string
    {
        $path = $this->pdfPath($document);

        if ($this->disk()->exists($path)) {
            return $path;
        }

        $tmp = $this->convertToPdf($document);

        try {
            $stream = fopen($tmp, 'rb');
            $this->disk()->put($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        } finally {
            @unlink($tmp);
        }

        return $path;
    }

    /** Borra el PDF en caché (al editar de nuevo el documento o eliminarlo). */
    public function forgetPdf(Document $document): void
    {
        $this->disk()->delete($this->pdfPath($document));
    }

    /**
     * Convierte el .docx a PDF con la API de conversión del Document Server.
     *
     * @return string Ruta del PDF temporal (hay que borrarlo después de enviarlo).
     */
    public function convertToPdf(Document $document): string
    {
        return $this->convertUrlToPdf(
            // Clave determinista: el Document Server reutiliza su caché entre descargas.
            'pdf'.$document->id.'v'.$document->version,
            (Str::slug($document->title) ?: 'documento').'.docx',
            $this->fileUrl($document),
        );
    }

    /**
     * Devuelve la ruta del PDF de la plantilla en disco; si no existe, lo convierte una sola vez.
     * La ruta incluye una huella del archivo: al reemplazar el .docx de la plantilla se genera otro PDF.
     */
    public function ensureTemplatePdf(Template $template): string
    {
        $path = $this->templatePdfPath($template);

        if ($this->disk()->exists($path)) {
            return $path;
        }

        $tmp = $this->convertUrlToPdf(
            'tpl'.$template->id.'f'.substr(sha1((string) $template->file_path), 0, 12),
            (Str::slug($template->name) ?: 'plantilla').'.docx',
            $this->templateFileUrl($template),
        );

        try {
            $stream = fopen($tmp, 'rb');
            $this->disk()->put($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        } finally {
            @unlink($tmp);
        }

        return $path;
    }

    /** Ruta del PDF de una plantilla; $filePath permite calcular la del archivo anterior al reemplazarlo. */
    public function templatePdfPath(Template $template, ?string $filePath = null): string
    {
        return 'pdf/templates/'.$template->id.'-'.substr(sha1($filePath ?? (string) $template->file_path), 0, 12).'.pdf';
    }

    /** Borra el PDF en caché de una plantilla (al reemplazar su archivo). */
    public function forgetTemplatePdf(Template $template, ?string $filePath = null): void
    {
        $this->disk()->delete($this->templatePdfPath($template, $filePath));
    }

    /**
     * Convierte un .docx (que el Document Server descarga desde $sourceUrl) a PDF con su API de conversión.
     *
     * @param  string  $key  Clave del trabajo: [0-9a-zA-Z], máx. 128 caracteres.
     * @return string Ruta del PDF temporal (hay que borrarlo después de enviarlo).
     */
    private function convertUrlToPdf(string $key, string $title, string $sourceUrl): string
    {
        $payload = [
            'async' => false,
            'filetype' => 'docx',
            'key' => $key,
            'outputtype' => 'pdf',
            'title' => $title,
            'url' => $sourceUrl,
        ];

        $request = Http::timeout(120)->acceptJson()->asJson();

        if (config('onlyoffice.jwt_enabled')) {
            $token = Jwt::encode($payload, $this->secret());
            $payload['token'] = $token;
            $request = $request->withToken($token);
        }

        $response = $request->post(config('onlyoffice.url').'/converter', $payload);

        if (! $response->successful()) {
            throw new RuntimeException("No se pudo conectar con el servicio de conversión (HTTP {$response->status()}).");
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('El servicio de conversión devolvió una respuesta no válida.');
        }

        if (isset($data['error'])) {
            throw new RuntimeException($this->conversionErrorMessage((int) $data['error']));
        }

        if (($data['endConvert'] ?? false) !== true || empty($data['fileUrl'])) {
            throw new RuntimeException('La conversión a PDF no terminó correctamente.');
        }

        $url = (string) $data['fileUrl'];
        $this->assertAllowedUrl($url);

        $tmp = tempnam(sys_get_temp_dir(), 'pdf_');

        try {
            $download = Http::timeout(120)->withOptions(['sink' => $tmp])->get($url);

            if (! $download->successful()) {
                throw new RuntimeException("No se pudo descargar el PDF convertido (HTTP {$download->status()}).");
            }

            $handle = fopen($tmp, 'rb');
            $magic = $handle ? fread($handle, 4) : '';
            if (is_resource($handle)) {
                fclose($handle);
            }

            if ($magic !== '%PDF') {
                throw new RuntimeException('El archivo recibido no es un PDF válido.');
            }
        } catch (\Throwable $e) {
            @unlink($tmp);
            throw $e;
        }

        return $tmp;
    }

    private function conversionErrorMessage(int $code): string
    {
        return match ($code) {
            -1 => 'Error desconocido al convertir el documento.',
            -2 => 'La conversión tardó demasiado tiempo.',
            -3 => 'No se pudo convertir el documento a PDF.',
            -4 => 'No se pudo leer el documento para convertirlo.',
            -5 => 'El documento está protegido con contraseña.',
            -6 => 'Error del servicio de conversión al acceder al resultado.',
            -7 => 'La petición de conversión no es válida.',
            -8 => 'El token de conversión no es válido.',
            default => "No se pudo convertir el documento a PDF (código {$code}).",
        };
    }

    /**
     * Devuelve el contenido VERIFICADO del callback.
     * Con JWT activo se usa lo que va dentro del token, no el cuerpo sin firmar.
     *
     * @return array<string, mixed>
     */
    public function verifiedCallbackData(Request $request): array
    {
        if (! config('onlyoffice.jwt_enabled')) {
            return $request->all();
        }

        $token = $this->bearerToken($request) ?? $request->input('token');

        if (! is_string($token) || $token === '') {
            abort(403, 'Falta el token JWT.');
        }

        try {
            $claims = Jwt::decode($token, $this->secret());
        } catch (\Throwable) {
            abort(403, 'Token JWT inválido.');
        }

        // En modo cabecera, OnlyOffice envuelve el cuerpo en "payload".
        return is_array($claims['payload'] ?? null) ? $claims['payload'] : $claims;
    }

    /**
     * Descarga la versión guardada desde el Document Server y reemplaza el archivo.
     *
     * @param  bool  $closeSession  true cuando todos cerraron el editor (status 2): se rota la key
     */
    public function saveFromUrl(Document $document, string $url, bool $closeSession, ?int $userId = null): void
    {
        $this->assertAllowedUrl($url);

        $tmp = tempnam(sys_get_temp_dir(), 'oo_');

        try {
            $response = Http::timeout(120)->withOptions(['sink' => $tmp])->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("No se pudo descargar el documento guardado (HTTP {$response->status()}).");
            }

            if (! DocxValidator::isValid($tmp)) {
                throw new RuntimeException('El archivo recibido de OnlyOffice no es un .docx válido.');
            }

            $stream = fopen($tmp, 'rb');
            $this->disk()->put($document->file_path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        } finally {
            @unlink($tmp);
        }

        $previousVersion = $document->version;

        $document->forceFill([
            'version' => $previousVersion + 1,
            'doc_key' => $closeSession ? self::newKey() : $document->doc_key,
            'last_saved_at' => now(),
            'updated_by' => ($userId && User::whereKey($userId)->exists()) ? $userId : $document->updated_by,
        ])->save();

        // El PDF de la versión anterior ya no corresponde al contenido guardado.
        $this->disk()->delete('pdf/'.$document->id.'-v'.$previousVersion.'.pdf');

        // El callback no tiene sesión: el autor es el último usuario que editó, según OnlyOffice.
        AuditLogger::log(
            'document.saved',
            "Guardó cambios en el documento «{$document->title}» (versión {$document->version})",
            $document,
            actor: $userId ? User::find($userId) : null,
        );
    }

    private function secret(): string
    {
        $secret = config('onlyoffice.jwt_secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Falta ONLYOFFICE_JWT_SECRET en el .env (debe coincidir con el del contenedor).');
        }

        return $secret;
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->header(config('onlyoffice.jwt_header', 'Authorization'));

        if (! is_string($header) || $header === '') {
            return null;
        }

        return Str::startsWith($header, 'Bearer ') ? substr($header, 7) : $header;
    }

    /** Anti-SSRF: la URL de descarga debe apuntar al Document Server, no a cualquier sitio. */
    private function assertAllowedUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        $allowed = array_map('strtolower', array_filter([
            parse_url(config('onlyoffice.url'), PHP_URL_HOST),
            ...config('onlyoffice.callback_hosts', []),
        ]));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || ! in_array($host, $allowed, true)) {
            throw new RuntimeException(
                "Host no permitido en la URL del callback: «{$host}». ".
                'Si es el de tu Document Server, agrégalo a ONLYOFFICE_CALLBACK_HOSTS.'
            );
        }
    }
}
