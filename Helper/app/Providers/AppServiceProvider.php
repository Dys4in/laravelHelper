<?php

namespace App\Providers;

use App\Models\Area;
use App\Models\Document;
use App\Models\Template;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Policies\DocumentPolicy;
use App\Policies\TemplatePolicy;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Gate::policy(Template::class, TemplatePolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);

        // Audit trail: cambios en usuarios, áreas, plantillas y documentos.
        User::observe(AuditObserver::class);
        Area::observe(AuditObserver::class);
        Template::observe(AuditObserver::class);
        Document::observe(AuditObserver::class);

        // Audit trail: autenticación.
        Event::listen(Login::class, function (Login $e) {
            // Credenciales correctas pero cuenta desactivada / área suspendida: no es un inicio de sesión válido.
            if ($reason = $e->user->accessDeniedReason()) {
                AuditLogger::log('auth.login_blocked', "Acceso denegado ({$e->user->email}): {$reason}", $e->user, actor: $e->user);

                return;
            }

            AuditLogger::log('auth.login', "Inició sesión: {$e->user->email}", $e->user, actor: $e->user);
        });

        Event::listen(Logout::class, function (Logout $e) {
            // Los cierres forzados (cuenta bloqueada) ya se registran como login_blocked / session_revoked.
            if ($e->user && ! $e->user->accessDeniedReason()) {
                AuditLogger::log('auth.logout', "Cerró sesión: {$e->user->email}", $e->user, actor: $e->user);
            }
        });

        Event::listen(Failed::class, function (Failed $e) {
            $email = (string) ($e->credentials['email'] ?? '—');
            AuditLogger::log('auth.failed', "Intento de acceso fallido con el correo: {$email}", actor: null);
        });
    }
}
