<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use Auditable, SoftDeletes;

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

    /** Plantillas que un usuario puede usar: las de su área y las generales (sin área). */
    public function scopeAvailableFor(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->whereNull('area_id')->orWhere('area_id', $user->area_id ?? 0);
        });
    }

    public function auditType(): string
    {
        return 'template';
    }
}
