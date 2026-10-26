<?php

namespace App\Http\Requests;

use App\Models\Area;
use App\Rules\ValidDocx;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Editar una plantilla: moderador (de su área) o administrador. Solo el administrador puede cambiarla de área. */
class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('template'));
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'file' => [
                'nullable', 'file',
                'max:'.(config('onlyoffice.max_docx_mb') * 1024),
                new ValidDocx,
            ],
        ];

        if ($this->user()->isAdmin()) {
            $rules['area_id'] = ['required', Rule::exists(Area::class, 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }
}
