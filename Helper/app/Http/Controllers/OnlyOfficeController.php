<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Support\OnlyOfficeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Endpoints que llama el Document Server (no el navegador).
 * Sin sesión ni CSRF: se protegen con URL firmada + JWT.
 */
class OnlyOfficeController extends Controller
{
    /** El Document Server descarga aquí el .docx que va a abrir. */
    public function file(Request $request, Document $document, OnlyOfficeService $oo): StreamedResponse
    {
        abort_unless($request->hasValidRelativeSignature(), 403);

        $disk = $oo->disk();
        abort_unless($document->file_path && $disk->exists($document->file_path), 404);

        return $disk->response($document->file_path, 'document.docx', [
            'Content-Type' => OnlyOfficeService::DOCX_MIME,
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Avisos de guardado del Document Server.
     * status: 1 editando · 2 listo para guardar (todos cerraron) · 3 error al guardar
     *         4 cerrado sin cambios · 6 guardado forzado (Ctrl+S) · 7 error en guardado forzado
     */
    public function callback(Request $request, Document $document, OnlyOfficeService $oo): JsonResponse
    {
        abort_unless($request->hasValidRelativeSignature(), 403);

        $data = $oo->verifiedCallbackData($request);
        $status = (int) ($data['status'] ?? 0);

        // Sesión vieja (otra key): ya se guardó y rotó; se ignora para no pisar cambios.
        if (isset($data['key']) && $data['key'] !== $document->doc_key) {
            Log::warning('OnlyOffice callback con key obsoleta', ['document' => $document->id, 'status' => $status]);

            return response()->json(['error' => 0]);
        }

        if (in_array($status, [3, 7], true)) {
            Log::error('OnlyOffice reportó error al guardar', ['document' => $document->id, 'status' => $status]);
        }

        if (in_array($status, [2, 6], true)) {
            try {
                $users = array_filter((array) ($data['users'] ?? []), 'is_numeric');
                $lastUser = $users ? (int) end($users) : null;

                $oo->saveFromUrl($document, (string) ($data['url'] ?? ''), $status === 2, $lastUser);
            } catch (\Throwable $e) {
                Log::error('No se pudo guardar el documento desde OnlyOffice', [
                    'document' => $document->id,
                    'error' => $e->getMessage(),
                ]);

                // error != 0: el Document Server reintenta
                return response()->json(['error' => 1]);
            }
        }

        return response()->json(['error' => 0]);
    }
}
