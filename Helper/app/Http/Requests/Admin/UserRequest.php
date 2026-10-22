<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Sirve para crear y editar. Si la ruta trae {user}, es edición.
 * El acceso ya está restringido por el middleware role:admin.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $isUpdate = $user !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'area_id' => [
                Rule::requiredIf(fn () => $this->input('role') !== UserRole::Admin->value),
                'nullable',
                Rule::exists('areas', 'id')->whereNull('deleted_at'),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$isUpdate ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Escribe un correo válido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'username.unique' => 'Ese nombre de usuario ya está en uso.',
            'role.required' => 'Selecciona un rol.',
            'area_id.required' => 'Asigna un área (solo los administradores pueden quedar sin área).',
            'area_id.exists' => 'El área seleccionada no existe.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}
