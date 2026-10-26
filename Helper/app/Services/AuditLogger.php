<?php

namespace App\Services;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class AuditLogger
{
    private const TYPES = [
        User::class     => ['user', 'Usuario'],
        Area::class     => ['area', 'Área'],
        Template::class => ['template', 'Plantilla'],
        Document::class => ['document', 'Documento'],
    ];

    /**
     * Registra un movimiento. Nunca rompe la acción original: si falla el registro, solo se reporta al log de Laravel.
     *
     * @param  string      $event   p. ej. 'document.saved' (ver AuditLog::EVENTS)
     * @param  Model|null  $subject Modelo afectado
     * @param  User|null   $actor   Por defecto, el usuario autenticado
     */
    public static function log(
        string $event,
        string $description,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
        ?User $actor = null,
    ): void {
        try {
            $actor ??= auth()->user();
            $request = app()->runningInConsole() ? null : request();

            AuditLog::create([
                'user_id'         => $actor?->id,
                'user_name'       => $actor?->name,
                'user_role'       => $actor?->role?->value,
                'area_id'         => $actor?->area_id,
                'event'           => $event,
                'auditable_type'  => $subject ? (self::TYPES[$subject::class][0] ?? class_basename($subject)) : null,
                'auditable_id'    => $subject?->getKey(),
                'auditable_label' => $subject ? self::label($subject) : null,
                'description'     => $description,
                'old_values'      => $old ?: null,
                'new_values'      => $new ?: null,
                'ip_address'      => $request?->ip(),
                'user_agent'      => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Nombre legible del tipo de modelo ("Usuario", "Plantilla"...). */
    public static function typeName(Model $model): string
    {
        return self::TYPES[$model::class][1] ?? class_basename($model);
    }

    public static function typeKey(Model $model): string
    {
        return self::TYPES[$model::class][0] ?? strtolower(class_basename($model));
    }

    public static function label(Model $model): string
    {
        if ($model instanceof User) {
            return "{$model->name} ({$model->email})";
        }

        return (string) ($model->name ?? $model->title ?? $model->code ?? '#'.$model->getKey());
    }
}
