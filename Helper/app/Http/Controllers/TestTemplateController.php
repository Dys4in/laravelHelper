<?php

namespace App\Http\Controllers;

use PhpOffice\PhpWord\TemplateProcessor;

class TestTemplateController extends Controller
{
    public function generate()
    {
        $template = new TemplateProcessor(
            storage_path('app/templates/PlantillaPrueba.docx')
        );

        $template->setValue('', 'Ulhman 3');
        $template->setValue('', 'realizado el 26/08/2026');
        $template->setValue('', now()->format('Y-m-d'));
        $template->setValue('', 'revision 03');

        $output = storage_path(
            'app/generated/Platilla_Prueba.docx'
        );

        $template->saveAs($output);

        return response()->download($output);
    }
}