<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * No se fía de la extensión ni del mime que declara el navegador:
 * abre el archivo y comprueba que sea un .docx de verdad (OOXML de Word, sin macros).
 */
class ValidDocx implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('No se pudo leer el archivo subido.');

            return;
        }

        if (strtolower($value->getClientOriginalExtension()) !== 'docx') {
            $fail('El archivo debe tener extensión .docx.');

            return;
        }

        if (! class_exists(ZipArchive::class)) {
            $fail('El servidor no tiene habilitada la extensión PHP "zip" (php_zip). Actívala en php.ini.');

            return;
        }

        if (! self::isDocx($value->getRealPath())) {
            $fail('El archivo no es un documento Word (.docx) válido.');
        }
    }

    public static function isDocx(string $path): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return false;
        }

        $types = $zip->getFromName('[Content_Types].xml');
        $hasBody = $zip->locateName('word/document.xml') !== false;
        $zip->close();

        return $types !== false
            && $hasBody
            && str_contains($types, 'wordprocessingml.document.main+xml');
    }
}
