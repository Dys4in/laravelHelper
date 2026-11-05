<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));

        $users = User::query()
            ->with('area')
            ->when($search !== '', function ($query) use ($search) {
                // La abreviatura se calcula en PHP, así que resolvemos los IDs que coinciden.
                $abbreviationIds = User::query()
                    ->get(['id', 'name'])
                    ->filter(fn (User $user) => Str::contains(Str::lower($user->short_name), Str::lower($search)))
                    ->pluck('id');

                $query->where(function ($w) use ($search, $abbreviationIds) {
                    $w->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->when($abbreviationIds->isNotEmpty(), fn ($q) => $q->orWhereIn('id', $abbreviationIds));
                });
            })
            ->when($request->filled('area'), fn ($q) => $q->where('area_id', $request->integer('area')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'areas' => Area::orderBy('name')->get(),
            'roles' => UserRole::cases(),
            // Formulario de "Nuevo usuario" (ventana)
            'formAreas' => Area::active()->orderBy('name')->get(),
            'newUser' => new User(['role' => UserRole::User, 'status' => 'active']),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            // Áreas activas + la actual del usuario (aunque esté suspendida) para no perderla al guardar.
            'areas' => Area::query()
                ->where(fn ($q) => $q->where('status', Area::ACTIVE)->orWhere('id', $user->area_id))
                ->orderBy('name')
                ->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Un admin no puede quitarse su propio rol ni desactivarse (evita quedarse sin acceso).
        if ($user->is($request->user()) && ($data['role'] !== UserRole::Admin->value || $data['status'] !== 'active')) {
            return back()->withInput()->with('error', 'No puedes cambiar tu propio rol ni desactivar tu propia cuenta.');
        }

        // Contraseña vacía = no cambiarla.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->update(['status' => $user->isActive() ? 'inactive' : 'active']);

        return back()->with('status', $user->isActive()
            ? "{$user->name} fue activado."
            : "{$user->name} fue desactivado y ya no podrá iniciar sesión.");
    }
}
