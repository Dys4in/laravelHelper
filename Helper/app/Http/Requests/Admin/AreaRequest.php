<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $area = $this->route('area');

        return [
            // Las áreas eliminadas (soft delete) no cuentan para la unicidad.
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('areas', 'name')->whereNull('deleted_at')->ignore($area?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del área es obligatorio.',
            'name.unique' => 'Ya existe un área con ese nombre.',
        ];
    }
}
