<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use SoftDeletes;

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
}
