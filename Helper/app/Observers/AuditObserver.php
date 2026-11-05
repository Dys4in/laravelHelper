<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Registra altas, cambios y bajas de Usuarios, Áreas, Plantillas y Documentos.
 * Los guardados que llegan desde OnlyOffice se registran aparte (document.saved).
 */
class AuditObserver
{
    /** Campos que no aportan al historial. */
    private const IGNORED = ['updated_at', 'created_at', 'remember_token', 'deleted_at', 'email_verified_at', 'file_path', 'doc_key', 'version', 'last_saved_at', 'updated_by'];

    /** Campos cuyo valor nunca se guarda (solo se anota que cambiaron). */
    private const SECRET = ['password'];

    public function created(Model $model): void
    {
        $type = AuditLogger::typeName($model);

        AuditLogger::log(
            AuditLogger::typeKey($model).'.created',
            "{$type} creado: ".AuditLogger::label($model),
            $model,
            null,
            $this->clean($model->getAttributes()),
        );
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), self::IGNORED);

        // Un soft-delete/restore también "actualiza" deleted_at: ya lo registran deleted()/restored().
        if (! $changes) {
            return;
        }

        $old = array_intersect_key($model->getOriginal(), $changes);
        $keys = array_keys($changes);
        $isStatus = $keys === ['status'] || $keys === ['is_active'];

        $type = AuditLogger::typeName($model);
        $event = AuditLogger::typeKey($model).($isStatus ? '.status_changed' : '.updated');
        $verb = $isStatus ? 'cambió de estado' : 'fue editado';

        AuditLogger::log(
            $event,
            "{$type} {$verb}: ".AuditLogger::label($model),
            $model,
            $this->clean($old),
            $this->clean($changes),
        );
    }

    public function deleted(Model $model): void
    {
        AuditLogger::log(
            AuditLogger::typeKey($model).'.deleted',
            AuditLogger::typeName($model).' eliminado: '.AuditLogger::label($model),
            $model,
        );
    }

    public function restored(Model $model): void
    {
        AuditLogger::log(
            AuditLogger::typeKey($model).'.restored',
            AuditLogger::typeName($model).' restaurado: '.AuditLogger::label($model),
            $model,
        );
    }

    private function clean(array $attributes): array
    {
        $attributes = Arr::except($attributes, self::IGNORED);

        foreach (self::SECRET as $field) {
            if (array_key_exists($field, $attributes)) {
                $attributes[$field] = '[oculto]';
            }
        }

        // Los enums (rol) llegan como valor plano al ser atributos crudos; los objetos se pasan a texto.
        return array_map(fn ($v) => $v instanceof \BackedEnum ? $v->value : $v, $attributes);
    }
}
