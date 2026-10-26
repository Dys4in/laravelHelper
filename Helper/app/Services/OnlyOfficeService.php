<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use RuntimeException;

/**
 * Integración con el Document Server de OnlyOffice: JWT (HS256) y configuración del editor.
 */
class OnlyOfficeService
{
    private function secret(): string
    {
        $secret = (string) config('onlyoffice.jwt_secret');

        if ($secret === '') {
            throw new RuntimeException('Falta ONLYOFFICE_JWT_SECRET en el .env de Laravel.');
        }

        return $secret;
    }

    // ------------------------------------------------------------------ JWT

    public function encode(array $payload): string
    {
        $segments = [
            $this->b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])),
            $this->b64(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        ];

        $segments[] = $this->b64(hash_hmac('sha256', implode('.', $segments), $this->secret(), true));

        return implode('.', $segments);
    }

    /** Devuelve el payload si la firma es válida y no expiró; null en caso contrario. */
    public function decode(?string $jwt): ?array
    {
        $parts = explode('.', (string) $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $head = json_decode($this->unb64($header), true);
        if (! is_array($head) || ($head['alg'] ?? null) !== 'HS256') {
            return null;
        }

        $expected = $this->b64(hash_hmac('sha256', "{$header}.{$payload}", $this->secret(), true));
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $data = json_decode($this->unb64($payload), true);
        if (! is_array($data)) {
            return null;
        }

        if (isset($data['exp']) && $data['exp'] < time()) {
            return null;
        }

        return $data;
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function unb64(string $b64): string
    {
        return (string) base64_decode(strtr($b64, '-_', '+/'));
    }

    // ------------------------------------------------------------ URLs para el Document Server

    /** URL (vista desde Docker) de descarga del .docx, con token de corta vida. */
    public function fileUrl(Document $document): string
    {
        $token = $this->encode([
            'doc'  => $document->id,
            'kind' => 'file',
            'exp'  => time() + (int) config('onlyoffice.file_token_ttl'),
        ]);

        return config('onlyoffice.app_url')
            .route('onlyoffice.file', ['document' => $document->id], false)
            .'?t='.$token;
    }

    public function callbackUrl(Document $document): string
    {
        return config('onlyoffice.app_url')
            .route('onlyoffice.callback', ['document' => $document->id], false);
    }

    // ------------------------------------------------------------ Configuración del editor

    public function editorConfig(Document $document, User $user, bool $canEdit): array
    {
        $config = [
            'documentType' => 'word',
            'document' => [
                'fileType' => 'docx',
                'key'      => $document->editorKey(),
                'title'    => $this->fileName($document),
                'url'      => $this->fileUrl($document),
                'permissions' => [
                    'edit'     => $canEdit,
                    'download' => true,
                    'print'    => true,
                    'comment'  => $canEdit,
                    'review'   => $canEdit,
                ],
            ],
            'editorConfig' => [
                'mode'        => $canEdit ? 'edit' : 'view',
                'lang'        => 'es',
                'callbackUrl' => $this->callbackUrl($document),
                'user'        => ['id' => (string) $user->id, 'name' => $user->name],
                'customization' => [
                    'autosave'  => true,   // guarda solo al cerrar / tras unos segundos
                    'forcesave' => true,   // Ctrl+S guarda al instante
                    'compactHeader' => true,
                    'toolbarNoTabs' => false,
                ],
            ],
            'width'  => '100%',
            'height' => '100%',
        ];

        $config['token'] = $this->encode($config);

        return $config;
    }

    public function fileName(Document $document): string
    {
        $base = preg_replace('/[\\\\\/:*?"<>|]+/', '-', $document->displayName());

        return trim($base).'.docx';
    }

    public function apiScriptUrl(): string
    {
        return config('onlyoffice.server_url').'/web-apps/apps/api/documents/api.js';
    }
}
