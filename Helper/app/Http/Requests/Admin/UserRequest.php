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
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $isUpdate = $user !== null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('users', 'name')->ignore($user?->id)],
            'username' => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            // Usuario: una sola área (obligatoria). Administrador: opcional.
            'area_id' => [
                Rule::requiredIf(fn () => $this->input('role') === UserRole::User->value),
                'nullable',
                Rule::exists('areas', 'id')->whereNull('deleted_at'),
            ],
            // Coordinador: una o varias áreas (la primera que se marque queda como principal).
            'areas' => [
                Rule::requiredIf(fn () => $this->input('role') === UserRole::Mod->value),
                'nullable',
                'array',
            ],
            'areas.*' => ['integer', 'distinct', Rule::exists('areas', 'id')->whereNull('deleted_at')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$isUpdate ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'Ya existe un usuario con ese nombre.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Escribe un correo válido.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'username.unique' => 'Ese nombre de usuario ya está en uso.',
            'role.required' => 'Selecciona un rol.',
            'area_id.required' => 'Asigna un área al usuario.',
            'area_id.exists' => 'El área seleccionada no existe.',
            'areas.required' => 'Asigna al menos un área al coordinador.',
            'areas.*.exists' => 'Una de las áreas seleccionadas no existe.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}
