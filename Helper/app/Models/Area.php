<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use Auditable, SoftDeletes;

    public const ACTIVE = 'active';
    public const SUSPENDED = 'suspended';

    protected $fillable = ['name', 'description', 'status'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::ACTIVE);
    }

    public function auditType(): string
    {
        return 'area';
    }

    public function auditAreaId(): ?int
    {
        return $this->id;
    }
}
