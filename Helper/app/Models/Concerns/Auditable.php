<?php

namespace App\Models\Concerns;

use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;

/**
 * Agregar `use Auditable;` a un modelo hace que cada alta, cambio y baja quede en el audit trail.
 * El modelo solo debe definir auditType() y, si hace falta, sobrescribir los demás métodos.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => Audit::model('created', $model));
        static::updated(fn (Model $model) => Audit::model('updated', $model));
        static::deleted(fn (Model $model) => Audit::model('deleted', $model));
    }

    /** Clave corta del tipo: user, area, template, document... Es el prefijo del evento. */
    abstract public function auditType(): string;

    /** Cómo se nombra este registro en el historial. */
    public function auditLabel(): string
    {
        return (string) ($this->name ?? $this->title ?? '#'.$this->getKey());
    }

    /** Área a la que pertenece el movimiento (para que el moderador vea lo suyo). */
    public function auditAreaId(): ?int
    {
        return $this->area_id ?? null;
    }

    /** Columnas técnicas que no se consideran un movimiento. */
    public function auditExcept(): array
    {
        return [];
    }

    /** Columnas sensibles: se registra que cambiaron, pero no su valor. */
    public function auditMasked(): array
    {
        return [];
    }
}
