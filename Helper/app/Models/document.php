<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    public const DRAFT = 'draft';
    public const COMPLETED = 'completed';

    protected $fillable = [
        'code', 'title', 'status', 'user_id', 'area_id', 'template_id',
        'file_path', 'doc_key', 'version', 'updated_by', 'last_saved_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'last_saved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class)->withTrashed();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class)->withTrashed();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    /** Documentos creados por el usuario ("Mis documentos"). */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Documentos que el usuario supervisa: el admin, todos; el moderador, los de su área.
     * Los usuarios normales no supervisan nada (no ven documentos ajenos).
     */
    public function scopeSupervisedBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->hasRole('mod')) {
            return $query->where('area_id', $user->area_id ?? 0);
        }

        return $query->whereRaw('1 = 0');
    }
}
