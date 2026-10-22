<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        'username',
        'phone',
        'email',
        'password',
        'role',
        'status',
        'area_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function area(): BelongsTo
    {
        // withTrashed: un usuario sigue mostrando su área aunque esta se haya eliminado
        return $this->belongsTo(Area::class)->withTrashed();
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
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Motivo por el que este usuario NO puede entrar, o null si puede.
     * Reglas: cuenta activa y, salvo administradores, un área activa asignada.
     */
    public function accessDeniedReason(): ?string
    {
        if (! $this->isActive()) {
            return 'Tu cuenta está desactivada. Contacta al administrador.';
        }

        if (! $this->isAdmin()) {
            $area = $this->area;

            if (! $area || $area->trashed() || ! $area->isActive()) {
                return 'Tu área está suspendida o no tienes un área asignada. Contacta al administrador.';
            }
        }

        return null;
    }
}
