<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use ZipArchive;

/**
 * Deja Arial como fuente predeterminada de un .docx.
 *
 * OnlyOffice no tiene un ajuste para "fuente por defecto del documento" (customization.font solo cambia la
 * tipografía de la interfaz y es de la licencia Developer). Lo que decide la fuente de lo que se escribe son los
 * ESTILOS del propio .docx, así que se corrigen ahí: docDefaults y todos los estilos pasan a Arial.
 * El texto que ya trae una fuente explícita aplicada directamente (en document.xml) no se toca.
 */
final class DocxFonts
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const THEME_ATTRIBUTES = ['asciiTheme', 'hAnsiTheme', 'eastAsiaTheme', 'cstheme'];

    /**
     * @param  string  $path  .docx local (se modifica en el lugar)
     * @return bool true si se modificó el archivo
     */
    public static function useArial(string $path): bool
    {
        if (! class_exists(ZipArchive::class)) {
            return false;
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return false;
        }

        try {
            $xml = $zip->getFromName('word/styles.xml');

            if ($xml === false) {
                return false;
            }

            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = true;

            if (! @$dom->loadXML($xml, LIBXML_NONET)) {
                return false;
            }

            foreach ($dom->getElementsByTagNameNS(self::W, 'rFonts') as $fonts) {
                self::arial($fonts);
            }

            self::ensureDefault($dom);

            return $zip->addFromString('word/styles.xml', $dom->saveXML());
        } finally {
            $zip->close();
        }
    }

    private static function arial(DOMElement $fonts): void
    {
        foreach (['ascii', 'hAnsi', 'cs'] as $attribute) {
            $fonts->setAttributeNS(self::W, 'w:'.$attribute, 'Arial');
        }

        foreach (self::THEME_ATTRIBUTES as $attribute) {
            if ($fonts->hasAttributeNS(self::W, $attribute)) {
                $fonts->removeAttributeNS(self::W, $attribute);
            }
        }
    }

    /** Garantiza <w:docDefaults><w:rPrDefault><w:rPr><w:rFonts .../> aunque el documento no lo trajera. */
    private static function ensureDefault(DOMDocument $dom): void
    {
        $root = $dom->documentElement;

        $defaults = $dom->getElementsByTagNameNS(self::W, 'docDefaults')->item(0);

        if (! $defaults) {
            $defaults = $dom->createElementNS(self::W, 'w:docDefaults');
            $root->insertBefore($defaults, $root->firstChild);
        }

        $rPrDefault = $defaults->getElementsByTagNameNS(self::W, 'rPrDefault')->item(0);

        if (! $rPrDefault) {
            $rPrDefault = $dom->createElementNS(self::W, 'w:rPrDefault');
            $defaults->insertBefore($rPrDefault, $defaults->firstChild);
        }

        $rPr = $rPrDefault->getElementsByTagNameNS(self::W, 'rPr')->item(0);

        if (! $rPr) {
            $rPr = $dom->createElementNS(self::W, 'w:rPr');
            $rPrDefault->appendChild($rPr);
        }

        if ($rPr->getElementsByTagNameNS(self::W, 'rFonts')->length === 0) {
            $fonts = $dom->createElementNS(self::W, 'w:rFonts');
            self::arial($fonts);
            $rPr->insertBefore($fonts, $rPr->firstChild); // rFonts va al principio de rPr
        }
    }
}
