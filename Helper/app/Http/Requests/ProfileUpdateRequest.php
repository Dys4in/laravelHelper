<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $signature = trim((string) $this->input('signature'));

        $this->merge(['signature' => $signature !== '' ? $signature : null]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'signature' => ['nullable', 'string', 'max:50'],
        ];
    }
}
