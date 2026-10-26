<?php

namespace App\Http\Requests;

use App\Models\Template;
use App\Rules\ValidDocx;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/** Subir una plantilla nueva: solo el moderador (queda asignada a SU área). */
class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Template::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'file' => [
                'required', 'file',
                'max:'.(config('onlyoffice.max_docx_mb') * 1024),
                new ValidDocx,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la plantilla es obligatorio.',
            'file.required' => 'Debes seleccionar un archivo .docx.',
            'file.max' => 'El archivo supera el tamaño máximo de '.config('onlyoffice.max_docx_mb').' MB.',
            'file.uploaded' => 'No se pudo subir el archivo. Revisa upload_max_filesize y post_max_size en php.ini.',
        ];
    }
}
