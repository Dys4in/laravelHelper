<?php

namespace App\Http\Requests\Admin;

use App\Support\DocxValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Crear y editar plantillas (admin y coordinador).
 *  - Admin: elige el área.
 *  - Coordinador con una sola área: siempre se usa esa.
 *  - Coordinador con varias áreas: elige una de las suyas al subir (al editar no se puede mover de área).
 */
class TemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->route('template') !== null;
        $user = $this->user();
        $areaIds = $user?->areaIds() ?? [];

        $mustPickArea = $user?->isAdmin() === true || (! $isUpdate && count($areaIds) > 1);

        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'area_id' => [
                Rule::requiredIf($mustPickArea),
                'nullable',
                $user?->isAdmin() === true
                    ? Rule::exists('areas', 'id')->whereNull('deleted_at')
                    : Rule::in($areaIds ?: [0]),
            ],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'file' => [
                $isUpdate ? 'nullable' : 'required',
                'file',
                'extensions:docx,xlsx,xlsm',
                'max:'.config('onlyoffice.max_upload_kb'),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value instanceof UploadedFile && ! DocxValidator::isValidFor($value->getRealPath(), $value->getClientOriginalExtension())) {
                        $fail('El archivo no es un documento Word (.docx) ni una hoja de Excel (.xlsx) válidos.');
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
            'area_id.in' => 'Elige una de tus áreas.',
            'file.required' => 'Selecciona el archivo .docx o .xlsx de la plantilla.',
            'file.extensions' => 'Solo se permiten archivos .docx (Word), .xlsx o .xlsm (Excel).',
            'file.max' => "El archivo supera el máximo permitido ({$limit}).",
            'file.file' => 'El archivo no se pudo subir. Puede superar el límite del servidor (upload_max_filesize en php.ini).',
            'file.uploaded' => 'El archivo no se pudo subir. Puede superar el límite del servidor (upload_max_filesize en php.ini).',
        ];
    }
}
