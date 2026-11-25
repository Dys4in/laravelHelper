<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Area;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\LoginThrottle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $actor = $request->user();
        $isAdmin = $actor->isAdmin();
        $areaIds = $isAdmin ? [] : ($actor->areaIds() ?: [0]);
        $search = trim((string) $request->input('q'));

        $scope = function ($query) use ($isAdmin, $areaIds) {
            if ($isAdmin) {
                return;
            }

            $query->where(function ($w) use ($areaIds) {
                $w->whereIn('area_id', $areaIds)
                    ->orWhereIn('id', fn ($sub) => $sub->from('area_user')->select('user_id')->whereIn('area_id', $areaIds));
            })->whereNotIn('role', [UserRole::Admin->value, UserRole::JefeArea->value]);
        };

        $users = User::query()->with(['area', 'areas']);
        $scope($users);

        $users = $users->when($search !== '', function ($query) use ($search, $scope) {
            // La abreviatura se calcula en PHP, así que resolvemos los IDs que coinciden.
            $abbreviationQuery = User::query();
            $scope($abbreviationQuery);
            $abbreviationIds = $abbreviationQuery
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
            ->when($request->filled('area'), fn ($q) => $q->where(function ($w) use ($request) {
                // Incluye a los coordinadores que tienen esa área entre las suyas.
                $w->where('area_id', $request->integer('area'))
                    ->orWhereIn('id', fn ($sub) => $sub->from('area_user')->select('user_id')->where('area_id', $request->integer('area')));
            }))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $visibleAreas = $isAdmin
            ? Area::orderBy('name')->get()
            : Area::whereIn('id', $areaIds)->orderBy('name')->get();
        $formAreas = $isAdmin
            ? Area::active()->orderBy('name')->get()
            : $visibleAreas;

        return view('admin.users.index', [
            'users' => $users,
            'areas' => $visibleAreas,
            'roles' => UserRole::cases(),
            'formRoles' => $isAdmin ? UserRole::cases() : [UserRole::User],
            'canEditUser' => fn (User $target) => $this->canEdit($actor, $target),
            'canToggleStatus' => true,
            // Formulario de "Nuevo usuario" (ventana)
            'formAreas' => $formAreas,
            'newUser' => new User(['role' => UserRole::User, 'status' => 'active']),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! $request->user()->isAdmin()) {
            abort_unless(($data['role'] ?? null) === UserRole::User->value, 403);
            abort_unless(in_array($data['area_id'] ?? null, $request->user()->areaIds(), true), 403);
        }

        [$primaryId, $areaIds] = $this->resolveAreas($data, null);

        $temporaryPassword = Str::password(18, symbols: true);
        DB::transaction(function () use ($data, $primaryId, $areaIds, $temporaryPassword) {
            $user = User::create(array_merge(Arr::except($data, ['areas', 'password', 'must_change_password']), [
                'area_id' => $primaryId, 'password' => Hash::make($temporaryPassword), 'must_change_password' => true,
            ]));
            // Se guarda cifrada para mostrarla en «Editar» hasta que la persona la cambie.
            $user->forceFill(['temporary_password' => $temporaryPassword])->save();
            $user->areas()->sync($areaIds);
        });

        return redirect()->route('admin.users.index')->with('status', 'Usuario creado correctamente.')
            ->with('new_user_email', $data['email'])->with('temporary_password', $temporaryPassword);
    }

    public function edit(Request $request, User $user): View|RedirectResponse
    {
        $actor = $request->user();

        if ($user->hasRole(UserRole::Admin)) {
            return redirect()->route('admin.users.index')->with('error', 'La cuenta de administrador no se puede editar.');
        }

        abort_unless($this->canEdit($actor, $user), 403);

        $areas = $actor->isAdmin()
            ? Area::query()
                ->where(fn ($q) => $q->where('status', Area::ACTIVE)
                    ->orWhereIn('id', $user->assignedAreas()->pluck('id')))
                ->orderBy('name')
                ->get()
            : Area::whereIn('id', $actor->areaIds())->orderBy('name')->get();

        return view('admin.users.edit', [
            'user' => $user,
            'areas' => $areas,
            'roles' => $actor->isAdmin() ? UserRole::cases() : [UserRole::User],
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->hasRole(UserRole::Admin)) {
            return back()->with('error', 'La cuenta de administrador no se puede editar.');
        }

        if (! $request->user()->isAdmin()) {
            abort_unless($this->canEdit($request->user(), $user), 403);
            abort_unless(($data['role'] ?? null) === UserRole::User->value, 403);
            abort_unless(in_array($data['area_id'] ?? null, $request->user()->areaIds(), true), 403);
        }

        // Un jefe de área no puede quitarse su propio rol ni desactivarse (evita quedarse sin acceso).
        if ($user->is($request->user()) && ($data['role'] !== $user->role?->value || $data['status'] !== 'active')) {
            return back()->withInput()->with('error', 'No puedes cambiar tu propio rol ni desactivar tu propia cuenta.');
        }

        // Impedir edición de credenciales incluso mediante solicitudes manipuladas.
        unset($data['password'], $data['must_change_password'], $data['temporary_password'], $data['signature']);

        $before = $user->assignedAreas()->pluck('name')->join(', ');
        [$primaryId, $areaIds] = $this->resolveAreas($data, $user);

        $currentAreas = $user->areas()->pluck('areas.id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        sort($areaIds);

        $unchanged = (string) $data['name'] === (string) $user->name
            && strtolower((string) $data['email']) === strtolower((string) $user->email)
            && (! array_key_exists('username', $data) || (string) $data['username'] === (string) $user->username)
            && (string) $data['role'] === (string) $user->role?->value
            && (string) $data['status'] === (string) $user->status
            && (int) $primaryId === (int) $user->area_id
            && $areaIds === $currentAreas
            && ! array_key_exists('password', $data);

        if ($unchanged) {
            return back()->withInput()->with('status', 'No hay cambios para guardar.');
        }

        DB::transaction(function () use ($user, $data, $primaryId, $areaIds) {
            $user->update(array_merge(Arr::except($data, ['areas']), ['area_id' => $primaryId]));
            $user->areas()->sync($areaIds);
        });

        // El registro automático (AuditObserver) no ve la tabla area_user: los cambios de áreas del coordinador se anotan aquí.
        $user->forgetAreaCache();
        $after = $user->assignedAreas()->pluck('name')->join(', ');

        if ($user->hasRole('mod') && $before !== $after) {
            AuditLogger::log('user.updated', "Áreas de {$user->name} cambiadas", $user, ['areas' => $before], ['areas' => $after]);
        }

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado correctamente.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($user->hasRole(UserRole::Admin)) {
            return back()->with('error', 'La cuenta de administrador no se puede desactivar.');
        }

        if ($user->is($request->user())) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        if (! $request->user()->isAdmin()) {
            abort_unless($this->canEdit($request->user(), $user), 403);
        }

        $user->update(['status' => $user->isActive() ? 'inactive' : 'active']);

        return back()->with('status', $user->isActive()
            ? "{$user->name} fue activado."
            : "{$user->name} fue desactivado y ya no podrá iniciar sesión.");
    }

    /** Quita el bloqueo de 30 minutos por contraseñas incorrectas (desde «Editar usuario»). */
    public function unlock(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->canEdit($request->user(), $user), 403);

        if (LoginThrottle::lockedFor($user->email) === 0) {
            return back()->with('status', "{$user->name} no está bloqueado.");
        }

        LoginThrottle::clear($user->email);
        AuditLogger::log('user.updated', "Desbloqueó el acceso de {$user->name} (bloqueo por intentos fallidos)", $user);

        return back()->with('status', "{$user->name} fue desbloqueado y ya puede iniciar sesión.");
    }

    /** Admin/jefe editan cualquier cuenta excepto admin; el coordinador solo usuarios de sus áreas. */
    private function canEdit(User $actor, User $target): bool
    {
        if ($actor->isAdmin()) {
            return ! $target->hasRole(UserRole::Admin);
        }

        if (! $target->hasRole(UserRole::User)) {
            return false;
        }

        $targetAreas = $target->assignedAreas()->pluck('id')->map(fn ($id) => (int) $id)->all();

        return array_intersect($actor->areaIds(), $targetAreas) !== [];
    }

    /**
     * Área principal (users.area_id) y lista completa de áreas (tabla area_user) según el rol:
     *  - Coordinador: todas las marcadas; la principal es la que ya tenía (si sigue marcada) o la primera.
     *  - Usuario / administrador: solo area_id y sin áreas extra.
     *  - Jefe de área: sin ninguna área asignada.
     *
     * @param  array<string, mixed>  $data  Datos validados del formulario.
     * @return array{0: ?int, 1: list<int>}
     */
    private function resolveAreas(array $data, ?User $current): array
    {
        if (($data['role'] ?? null) === UserRole::JefeArea->value) {
            return [null, []];
        }

        if (($data['role'] ?? null) === UserRole::Mod->value) {
            $ids = array_values(array_unique(array_map('intval', $data['areas'] ?? [])));
            $kept = $current && in_array((int) $current->area_id, $ids, true) ? (int) $current->area_id : null;

            return [$kept ?? ($ids[0] ?? null), $ids];
        }

        $areaId = $data['area_id'] ?? null;

        return [filled($areaId) ? (int) $areaId : null, []];
    }
}
