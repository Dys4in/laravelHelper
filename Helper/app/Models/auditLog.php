<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Registro de auditoría. Solo se crea: nunca se edita ni se elimina desde la aplicación.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null; // solo created_at

    protected $fillable = [
        'user_id', 'user_name', 'user_role', 'area_id', 'event', 'description',
        'subject_type', 'subject_id', 'subject_label', 'changes', 'meta',
        'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Devolver false cancela la operación: el historial es inmutable.
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class)->withTrashed();
    }

    /** El admin ve todo; el moderador solo los movimientos de su área. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('area_id', $user->area_id ?? 0);
    }

    /**
     * @param  array{q?:?string,module?:?string,user?:?string,area?:?string,from?:?string,to?:?string}  $f
     */
    public function scopeFiltered(Builder $query, array $f): Builder
    {
        return $query
            ->when(! empty($f['q']), function (Builder $q) use ($f) {
                $like = '%'.$f['q'].'%';
                $q->where(function (Builder $w) use ($like) {
                    $w->where('description', 'like', $like)
                        ->orWhere('subject_label', 'like', $like)
                        ->orWhere('user_name', 'like', $like)
                        ->orWhere('ip_address', 'like', $like);
                });
            })
            ->when(! empty($f['module']), fn (Builder $q) => $q->where('event', 'like', $f['module'].'.%'))
            ->when(! empty($f['user']), fn (Builder $q) => $q->where('user_id', (int) $f['user']))
            ->when(! empty($f['area']), fn (Builder $q) => $q->where('area_id', (int) $f['area']))
            ->when(! empty($f['from']), fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($f['from'])->startOfDay()))
            ->when(! empty($f['to']), fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($f['to'])->endOfDay()));
    }
}
