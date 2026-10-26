<?php

namespace App\Support;

use ZipArchive;

/**
 * Un .docx es un ZIP con [Content_Types].xml y word/document.xml.
 * No nos fiamos de la extensión ni del MIME que declara el navegador.
 */
final class DocxValidator
{
    public static function isValid(?string $path): bool
    {
        if (! $path || ! is_file($path) || filesize($path) < 100) {
            return false;
        }

        $handle = fopen($path, 'rb');
        $signature = $handle ? fread($handle, 4) : '';
        if ($handle) {
            fclose($handle);
        }

        if ($signature !== "PK\x03\x04") {
            return false;
        }

        // Sin la extensión zip de PHP solo podemos comprobar la firma.
        if (! class_exists(ZipArchive::class)) {
            return true;
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return false;
        }

        $valid = $zip->locateName('[Content_Types].xml') !== false
            && $zip->locateName('word/document.xml') !== false;

        $zip->close();

        return $valid;
    }
}
