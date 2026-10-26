<?php

namespace App\Support;

use App\Models\Area;
use App\Models\AuditLog;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Punto único para registrar movimientos.
 *
 *  - Cambios en datos (crear / modificar / eliminar): automático con el trait Auditable del modelo.
 *  - Todo lo demás (login, descargas, abrir un documento...): Audit::log(...)
 *
 * Registrar NUNCA debe romper la operación del usuario: si falla, se reporta al log de Laravel.
 */
final class Audit
{
    /** Módulos para filtrar (prefijo del evento). */
    public const MODULES = [
        'auth' => 'Autenticación',
        'user' => 'Usuarios',
        'area' => 'Áreas',
        'template' => 'Plantillas',
        'document' => 'Documentos',
    ];

    private const EVENTS = [
        'auth.login' => 'Inicio de sesión',
        'auth.logout' => 'Cierre de sesión',
        'auth.login_failed' => 'Intento fallido',
        'auth.login_blocked' => 'Acceso bloqueado',
        'auth.lockout' => 'Bloqueo temporal',
        'auth.session_revoked' => 'Sesión revocada',
        'document.opened' => 'Documento abierto',
        'document.saved' => 'Documento guardado',
        'document.downloaded' => 'Descarga',
        'template.downloaded' => 'Descarga',
    ];

    private const NOUNS = [
        'user' => 'el usuario',
        'area' => 'el área',
        'template' => 'la plantilla',
        'document' => 'el documento',
    ];

    private const VERBS = ['created' => 'Creó', 'updated' => 'Actualizó', 'deleted' => 'Eliminó'];

    private const FIELDS = [
        'name' => 'Nombre', 'username' => 'Usuario', 'email' => 'Correo', 'phone' => 'Teléfono',
        'role' => 'Rol', 'status' => 'Estado', 'area_id' => 'Área', 'description' => 'Descripción',
        'title' => 'Título', 'original_name' => 'Archivo', 'file_size' => 'Tamaño',
        'template_id' => 'Plantilla', 'password' => 'Contraseña',
    ];

    private const VALUES = [
        'user.status.active' => 'Activo', 'user.status.inactive' => 'Inactivo',
        'area.status.active' => 'Activa', 'area.status.suspended' => 'Suspendida',
        'template.status.active' => 'Activa', 'template.status.inactive' => 'Inactiva',
        'document.status.draft' => 'Borrador', 'document.status.completed' => 'Completado',
        'user.role.admin' => 'Administrador', 'user.role.mod' => 'Moderador', 'user.role.user' => 'Usuario',
    ];

    /**
     * Registra un movimiento.
     *
     * @param  array<int, array{field:string,label:string,old:string,new:string}>  $changes
     * @param  array<string, mixed>  $meta
     * @param  User|null  $actor  quién lo hizo; por defecto el usuario autenticado
     * @param  int|null  $areaId  área de contexto; por defecto la del objeto o la del actor
     */
    public static function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $changes = [],
        array $meta = [],
        ?User $actor = null,
        ?int $areaId = null,
    ): ?AuditLog {
        try {
            $actor ??= Auth::user();
            $request = app()->runningInConsole() ? null : request();

            $areaId ??= ($subject && method_exists($subject, 'auditAreaId')) ? $subject->auditAreaId() : null;
            $areaId ??= $actor?->area_id;

            return AuditLog::create([
                'user_id' => $actor?->id,
                'user_name' => $actor?->name,
                'user_role' => $actor?->role?->value,
                'area_id' => $areaId,
                'event' => $event,
                'description' => Str::limit($description, 1000, '…'),
                'subject_type' => $subject && method_exists($subject, 'auditType') ? $subject->auditType() : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject ? Str::limit(self::subjectLabel($subject), 250, '') : null,
                'changes' => $changes ?: null,
                'meta' => $meta ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Lo llama el trait Auditable en created / updated / deleted. */
    public static function model(string $action, Model $model): void
    {
        $type = $model->auditType();
        $changes = [];

        if ($action === 'updated') {
            $changes = self::diff($model);

            // Solo cambió algo técnico (token, versión, llave del editor...): no es un movimiento.
            if ($changes === []) {
                return;
            }
        }

        $description = self::VERBS[$action].' '.(self::NOUNS[$type] ?? 'el registro').' «'.self::subjectLabel($model).'»';

        if ($changes) {
            $description .= ' ('.implode(', ', array_column($changes, 'label')).')';
        }

        self::log("{$type}.{$action}", $description, $model, $changes);
    }

    public static function eventLabel(string $event): string
    {
        if (isset(self::EVENTS[$event])) {
            return self::EVENTS[$event];
        }

        return match (Str::after($event, '.')) {
            'created' => 'Creación',
            'updated' => 'Modificación',
            'deleted' => 'Eliminación',
            default => $event,
        };
    }

    /** Color semántico del evento: green | red | amber | indigo | gray. */
    public static function tone(string $event): string
    {
        return match (true) {
            in_array($event, ['auth.login_failed', 'auth.login_blocked', 'auth.lockout'], true),
            str_ends_with($event, '.deleted') => 'red',
            $event === 'auth.session_revoked' => 'amber',
            $event === 'auth.login',
            str_ends_with($event, '.created') => 'green',
            $event === 'document.saved',
            str_ends_with($event, '.updated') => 'indigo',
            default => 'gray',
        };
    }

    private static function subjectLabel(Model $model): string
    {
        return method_exists($model, 'auditLabel') ? $model->auditLabel() : '#'.$model->getKey();
    }

    /** @return array<int, array{field:string,label:string,old:string,new:string}> */
    private static function diff(Model $model): array
    {
        $type = $model->auditType();
        $skip = array_merge(['created_at', 'updated_at', 'deleted_at'], $model->auditExcept());
        $masked = $model->auditMasked();
        $out = [];

        foreach ($model->getChanges() as $field => $new) {
            if (in_array($field, $skip, true)) {
                continue;
            }

            $label = self::FIELDS[$field] ?? $field;

            if (in_array($field, $masked, true)) {
                // Nunca se guardan valores sensibles, solo el hecho de que cambiaron.
                $out[] = ['field' => $field, 'label' => $label, 'old' => '(oculto)', 'new' => '(modificado)'];

                continue;
            }

            $out[] = [
                'field' => $field,
                'label' => $label,
                'old' => self::humanize($type, $field, $model->getRawOriginal($field)),
                'new' => self::humanize($type, $field, $new),
            ];
        }

        return $out;
    }

    private static function humanize(string $type, string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match (true) {
            $field === 'area_id' => Area::withTrashed()->find($value)?->name ?? "#{$value}",
            $field === 'template_id' => Template::withTrashed()->find($value)?->name ?? "#{$value}",
            $field === 'file_size' => Number::fileSize((int) $value, precision: 1),
            isset(self::VALUES["{$type}.{$field}.{$value}"]) => self::VALUES["{$type}.{$field}.{$value}"],
            default => Str::limit((string) $value, 120),
        };
    }
}
