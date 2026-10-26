<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use Auditable;

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

    /** El admin ve todo; los demás solo los documentos de su área. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('area_id', $user->area_id ?? 0);
    }

    public function auditType(): string
    {
        return 'document';
    }

    public function auditLabel(): string
    {
        return (string) ($this->title ?: $this->code ?: '#'.$this->getKey());
    }

    /** Estos cambios los hace el sistema al guardar desde el editor; el movimiento se registra como "Documento guardado". */
    public function auditExcept(): array
    {
        return ['code', 'file_path', 'doc_key', 'version', 'updated_by', 'last_saved_at'];
    }
}
