<?php

namespace App\Support;

use App\Models\Document;
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

    /** URL a la que el Document Server avisa de los guardados. Sin vencimiento: una sesión puede durar horas. */
    public function callbackUrl(Document $document): string
    {
        $path = URL::signedRoute('onlyoffice.callback', ['document' => $document->id], absolute: false);

        return config('onlyoffice.storage_url').$path;
    }

    /**
     * Configuración que recibe DocsAPI.DocEditor en el navegador (ya firmada con JWT).
     *
     * @param  string  $mode  'edit' = el creador edita · 'review' = control de cambios (moderador/admin) · 'view' = solo lectura
     * @return array<string, mixed>
     */
    public function editorConfig(Document $document, User $user, string $mode = 'edit'): array
    {
        $mode = in_array($mode, ['edit', 'review', 'view'], true) ? $mode : 'view';

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
                    // edit  = el creador, que además puede aceptar / rechazar los cambios de quien revisa.
                    // review = solo control de cambios: edit=false + review=true (modo revisión forzado).
                    // view   = solo lectura.
                    'edit' => $mode === 'edit',
                    'review' => $mode !== 'view',
                    'comment' => $mode !== 'view',
                ],
            ],
            'editorConfig' => [
                // Un documento completado se abre como visor; el modo revisión necesita mode=edit.
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

        if (config('onlyoffice.jwt_enabled')) {
            $config['token'] = Jwt::encode($config, $this->secret());
        }

        return $config;
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

        $document->forceFill([
            'version' => $document->version + 1,
            'doc_key' => $closeSession ? self::newKey() : $document->doc_key,
            'last_saved_at' => now(),
            'updated_by' => ($userId && User::whereKey($userId)->exists()) ? $userId : $document->updated_by,
        ])->save();

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
