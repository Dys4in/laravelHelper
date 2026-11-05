<?php

namespace App\Http\Requests\Admin;

use App\Support\DocxValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/** Crear y editar plantillas (admin y moderador). El moderador siempre sube a su propia área; el admin elige el área. */
class TemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->route('template') !== null;

        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            // El admin debe asignar la plantilla a un área; el moderador siempre usa la suya (se ignora lo que envíe).
            'area_id' => [
                Rule::requiredIf(fn () => $this->user()?->isAdmin() === true),
                'nullable',
                Rule::exists('areas', 'id')->whereNull('deleted_at'),
            ],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'file' => [
                $isUpdate ? 'nullable' : 'required',
                'file',
                'extensions:docx',
                'max:'.config('onlyoffice.max_upload_kb'),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value instanceof UploadedFile && ! DocxValidator::isValid($value->getRealPath())) {
                        $fail('El archivo no es un documento .docx válido.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        $limit = round(config('onlyoffice.max_upload_kb') / 1024).' MB';

        return [
            'name.required' => 'El nombre de la plantilla es obligatorio.',
            'area_id.required' => 'Selecciona el área a la que se asigna la plantilla.',
            'area_id.exists' => 'El área seleccionada no existe.',
            'file.required' => 'Selecciona el archivo .docx de la plantilla.',
            'file.extensions' => 'Solo se permiten archivos .docx.',
            'file.max' => "El archivo supera el máximo permitido ({$limit}).",
            'file.file' => 'El archivo no se pudo subir. Puede superar el límite del servidor (upload_max_filesize en php.ini).',
            'file.uploaded' => 'El archivo no se pudo subir. Puede superar el límite del servidor (upload_max_filesize en php.ini).',
        ];
    }
}
