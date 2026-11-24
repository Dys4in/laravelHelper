<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sirve para crear y editar. Si la ruta trae {user}, es edición.
 * El acceso ya está restringido por las rutas: admin/jefe gestionan todo; el coordinador solo crea usuarios de sus áreas.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $signature = trim((string) $this->input('signature'));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'signature' => $signature !== '' ? $signature : null,
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $isUpdate = $user !== null;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('users', 'name')->ignore($user?->id)],
            'signature' => ['nullable', 'string', 'max:50'],
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
        ];
    }
}
