<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /** Moderadores que tienen esta área entre las suyas (además de los usuarios cuya área principal es esta). */
    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
    /**
     * Personas asignadas al área: las que la tienen como área principal (users.area_id)
     * más los moderadores que la tienen entre sus áreas (area_user). Nadie se cuenta dos veces.
     */
    public function assignedUsersCount(): int
    {
       return User::query()
            ->where(fn ($q) => $q->where('area_id', $this->id)
                ->orWhereIn('id', fn ($sub) => $sub->from('area_user')->select('user_id')->where('area_id', $this->id)))
            ->count();
    }

    /**
     * Cuántas personas tiene cada área, en una sola consulta: [area_id => total].
     * Con $onlyActive solo cuenta las cuentas activas.
     *
     * @return Collection<int|string, int>
     */
    public static function memberCounts(bool $onlyActive = false): Collection
    {
        $members = DB::table('users')
            ->whereNotNull('area_id')
            ->select(['id as user_id', 'area_id'])
            ->union(DB::table('area_user')->select(['user_id', 'area_id'])); // UNION quita los repetidos

        return DB::query()
            ->fromSub($members, 'm')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->when($onlyActive, fn ($q) => $q->where('u.status', 'active'))
            ->selectRaw('m.area_id, count(*) as total')
            ->groupBy('m.area_id')
            ->pluck('total', 'area_id')
            ->map(fn ($total) => (int) $total);
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
