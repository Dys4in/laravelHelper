<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * OJO: role, status y area_id son asignables en masa, así que solo deben
     * llegar desde formularios del administrador usando $request->validated().
     * Nunca usar $request->all() con este modelo.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'signature',
        'username',
        'email',
        'password',
        'must_change_password',
        'password_changed_at',
        'role',
        'status',
        'area_id',
    ];

    protected $hidden = [
        'password',
        'temporary_password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'temporary_password' => 'encrypted', // cifrada en la base de datos
            'role' => UserRole::class,
        ];
    }

    protected static function booted(): void
    {
        // La contraseña temporal solo vive mientras la persona no haya puesto la suya.
        static::saving(function (User $user) {
            if ($user->isDirty('password') && ($user->exists || ! $user->password_changed_at)) {
                $user->password_changed_at = now();
            }
            if (! $user->must_change_password && $user->temporary_password !== null) {
                $user->temporary_password = null;
            }
        });
    }

    public function passwordExpired(): bool
    {
        if (! $this->password_changed_at) {
            return false;
        }

        return $this->password_changed_at->lessThanOrEqualTo(
            now()->subMonths(config('auth.password_rotation_months', 3))
        );
    }

    public function needsNewPassword(): bool
    {
        return $this->must_change_password || $this->passwordExpired();
    }

    public function area(): BelongsTo
    {
        // withTrashed: un usuario sigue mostrando su área aunque esta se haya eliminado
        return $this->belongsTo(Area::class)->withTrashed();
    }

    /**
     * Áreas del coordinador (todas, incluida la principal). Para el resto de roles esta tabla no se usa:
     * ver assignedAreas() para obtener las áreas de cualquier usuario.
     */
    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class);   
    }
    /** Se calcula una vez por petición: las políticas lo consultan por cada tarjeta de la lista. */
    private ?Collection $assignedAreasCache = null;

    /**
     * Todas las áreas asignadas (activas o no, incluso eliminadas), ordenadas por nombre.
     *  - Coordinador: su área principal (area_id) + las de la tabla area_user.
     *  - Usuario / administrador: solo area_id.
     *
     * @return Collection<int, Area>
     */
    public function assignedAreas(): Collection
    {
        return $this->assignedAreasCache ??= $this->loadAssignedAreas();
    }

    /** IDs de las áreas ACTIVAS del usuario (las suspendidas o eliminadas no dan acceso). */
    public function areaIds(): array
    {
        return $this->assignedAreas()
            ->filter(fn (Area $area) => ! $area->trashed() && $area->isActive())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /** ¿Puede el usuario actuar sobre esta área (activa y asignada a él)? */
    public function hasArea(?int $areaId): bool
    {
        return $areaId !== null && in_array($areaId, $this->areaIds(), true);
    }

    /** Hay que llamarlo si cambian el rol o las áreas del usuario dentro de la misma petición. */
    public function forgetAreaCache(): void
    {
        $this->assignedAreasCache = null;
    }

    private function loadAssignedAreas(): Collection
    {
        if (! $this->hasRole('mod') || ! $this->exists) {
            // Sin consulta extra: la relación suele estar cargada para mostrar el área en pantalla.
            return $this->area ? collect([$this->area]) : collect();
        }

        return Area::withTrashed()
            ->where(function ($query) {
                $query->whereKey($this->area_id ?? 0)
                    ->orWhereIn('id', fn ($sub) => $sub->from('area_user')->select('area_id')->where('user_id', $this->id));
            })
            ->orderBy('name')
            ->get();
    }

    public function hasRole(string|UserRole ...$roles): bool
    {
        foreach ($roles as $role) {
            $value = $role instanceof UserRole ? $role->value : $role;
            if ($this->role?->value === $value) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::JefeArea], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Firma del usuario: la personalizada si la hay; si no, se calcula del nombre:
     * - Dos palabras: inicial del primero + segundo completo ("Juan Carlos" => "J.Carlos").
     * - Tres o más: iniciales de todas menos la última + apellido completo
     *   ("Juan Carlos Pérez" => "J.C.Pérez").
     */
    protected function shortName(): Attribute
    {
        return Attribute::get(function () {
            if (($signature = trim((string) $this->signature)) !== '') {
                return $signature;
            }

            $parts = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY);

            if (count($parts) <= 1) {
                return (string) $this->name;
            }

            $last = array_pop($parts);
            $initials = implode('', array_map(
                fn (string $part) => Str::upper(Str::substr($part, 0, 1)).'.',
                $parts,
            ));

            return $initials.$last;
        });
    }

    /** Primer carácter del nombre, para el avatar. */
    protected function initial(): Attribute
    {
        return Attribute::get(fn () => Str::upper(Str::substr(trim((string) $this->name), 0, 1)));
    }

    /**
     * Motivo por el que este usuario NO puede entrar, o null si puede.
     * Reglas: cuenta activa y, salvo administradores, al menos un área activa asignada.
     */
    public function accessDeniedReason(): ?string
    {
        if (! $this->isActive()) {
            return 'Tu cuenta está desactivada. Contacta al administrador.';
        }

        // Salvo los administradores, hace falta al menos un área activa (un coordinador puede tener varias).
        if (! $this->isAdmin() && $this->areaIds() === []) {
            return 'Tu área está suspendida o no tienes un área asignada. Contacta al administrador.';
        }

        return null;
    }
}
