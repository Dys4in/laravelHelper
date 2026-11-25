<?php

namespace App\Support;

use ZipArchive;

/**
 * Un .docx es un ZIP con [Content_Types].xml y word/document.xml.
 * No nos fiamos de la extensión ni del MIME que declara el navegador.
 */
final class DocxValidator
{
    /** Valida según el tipo de archivo: 'docx' (Word) o 'xlsx' (Excel). */
    public static function isValidFor(?string $path, string $extension): bool
    {
        return self::isValid($path, in_array(strtolower($extension), ['xlsx', 'xlsm'], true) ? 'xl/workbook.xml' : 'word/document.xml');
    }

    public static function isValid(?string $path, string $mainPart = 'word/document.xml'): bool
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
            && $zip->locateName($mainPart) !== false;

        $zip->close();

        return $valid;
    }
}
