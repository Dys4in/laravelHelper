<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use SoftDeletes;

    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';

    protected $fillable = [
        'name', 'description', 'area_id', 'file_path',
        'original_name', 'file_size', 'status', 'created_by',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    /** Plantillas que un usuario puede usar al crear un documento: activas, de su área o generales. */
    public function scopeAvailableFor(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->whereNull('area_id')->orWhere('area_id', $user->area_id ?? 0);
        });
    }

    /**
     * Plantillas que el usuario ve en la pantalla "Plantillas".
     *  - Admin: todas.
     *  - Moderador: las de su área (activas o no, para administrarlas) y las generales activas.
     *  - Usuario: solo las activas de su área y las generales.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->hasRole('mod')) {
            return $query->where(function (Builder $q) use ($user) {
                $q->where('area_id', $user->area_id ?? 0)
                    ->orWhere(fn (Builder $g) => $g->whereNull('area_id')->where('status', self::ACTIVE));
            });
        }

        return $query->where('status', self::ACTIVE)->where(function (Builder $q) use ($user) {
            $q->whereNull('area_id')->orWhere('area_id', $user->area_id ?? 0);
        });
    }
}
