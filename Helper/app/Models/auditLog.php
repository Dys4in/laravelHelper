<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Registro de auditoría. Solo se crea: nunca se edita ni se elimina desde la aplicación.
 * Columnas: ver la migración extend_audit_logs_table.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null; // solo created_at

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
        'auth.failed' => 'Intento fallido',
        'auth.login_blocked' => 'Acceso bloqueado',
        'auth.lockout' => 'Bloqueo temporal',
        'auth.session_revoked' => 'Sesión revocada',
        'document.opened' => 'Documento abierto',
        'document.saved' => 'Documento guardado',
        'document.downloaded' => 'Descarga',
        'template.downloaded' => 'Descarga',
    ];

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

    protected $fillable = [
        'user_id', 'user_name', 'user_role', 'area_id', 'event', 'description',
        'auditable_type', 'auditable_id', 'auditable_label',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
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

    /** @param  array{q?:?string,module?:?string,user?:?string,area?:?string,from?:?string,to?:?string}  $f */
    public function scopeFiltered(Builder $query, array $f): Builder
    {
        return $query
            ->when(! empty($f['q']), function (Builder $q) use ($f) {
                $like = '%'.$f['q'].'%';
                $q->where(function (Builder $w) use ($like) {
                    $w->where('description', 'like', $like)
                        ->orWhere('auditable_label', 'like', $like)
                        ->orWhere('auditable_type', 'like', $like)
                        ->orWhere('event', 'like', $like)
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

    // ------------------------------------------------------------------ presentación

    public static function eventLabel(string $event): string
    {
        if (isset(self::EVENTS[$event])) {
            return self::EVENTS[$event];
        }

        return match (Str::after($event, '.')) {
            'created' => 'Creación',
            'updated' => 'Modificación',
            'status_changed' => 'Cambio de estado',
            'deleted' => 'Eliminación',
            'restored' => 'Restauración',
            default => $event,
        };
    }

    /** Color semántico del evento: green | red | amber | indigo | gray. */
    public static function tone(string $event): string
    {
        return match (true) {
            in_array($event, ['auth.failed', 'auth.login_blocked', 'auth.lockout'], true),
            str_ends_with($event, '.deleted') => 'red',
            $event === 'auth.session_revoked' => 'amber',
            $event === 'auth.login',
            str_ends_with($event, '.created') => 'green',
            $event === 'document.saved',
            str_ends_with($event, '.updated'),
            str_ends_with($event, '.status_changed') => 'indigo',
            default => 'gray',
        };
    }

    /**
     * Cambios "antes → después" ya legibles (solo para eventos de modificación).
     *
     * @return array<int, array{label:string,old:string,new:string}>
     */
    public function changes(): array
    {
        $old = $this->old_values;
        $new = $this->new_values;

        if (empty($old) || empty($new)) {
            return [];
        }

        $type = (string) $this->auditable_type;
        $out = [];

        foreach ($new as $field => $value) {
            $out[] = [
                'label' => self::FIELDS[$field] ?? $field,
                'old' => self::humanize($type, $field, $old[$field] ?? null),
                'new' => self::humanize($type, $field, $value),
            ];
        }

        return $out;
    }

    private static function humanize(string $type, string $field, mixed $value): string
    {
        if ($field === 'password') {
            return '(oculto)';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        static $names = [];

        return match (true) {
            $field === 'area_id' => $names["a{$value}"] ??= Area::withTrashed()->find($value)?->name ?? "#{$value}",
            $field === 'template_id' => $names["t{$value}"] ??= Template::withTrashed()->find($value)?->name ?? "#{$value}",
            $field === 'file_size' => Number::fileSize((int) $value, precision: 1),
            is_scalar($value) && isset(self::VALUES["{$type}.{$field}.{$value}"]) => self::VALUES["{$type}.{$field}.{$value}"],
            default => Str::limit(is_scalar($value) ? (string) $value : json_encode($value), 120),
        };
    }
}
