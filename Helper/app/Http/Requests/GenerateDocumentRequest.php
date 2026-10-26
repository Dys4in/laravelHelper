<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GenerateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Document::class);
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:templates,id'],
            'title' => ['required', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => 'Elige una plantilla.',
            'template_id.exists' => 'La plantilla elegida ya no existe.',
            'title.required' => 'Ponle un título al documento.',
        ];
    }
}
