<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Backend\Admin\AdminAreaController;
use App\Http\Controllers\Backend\Admin\AdminUserController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\ModController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReusableContentController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

// Archivos públicos del complemento OnlyOffice con CORS limitado al Document Server.
// Una ruta Laravel (en vez del archivo estático) garantiza los encabezados también con "artisan serve".
Route::get('/helper-office-assets/{file}', function (string $file) {
    abort_unless(in_array($file, ['config.json', 'index.html', 'bridge.js'], true), 404);

    $path = public_path('helper-editor-plugin/'.$file);
    abort_unless(is_file($path), 404);

    $types = [
        'config.json' => 'application/json; charset=utf-8',
        'index.html' => 'text/html; charset=utf-8',
        'bridge.js' => 'application/javascript; charset=utf-8',
    ];

    return response()->file($path, [
        'Content-Type' => $types[$file],
        // Este es el origen real de OnlyOffice en tu Docker (puerto publicado 8081).
        'Access-Control-Allow-Origin' => 'http://localhost:8081',
        'Vary' => 'Origin',
        'Cross-Origin-Resource-Policy' => 'cross-origin',
        'Cache-Control' => 'no-store',
    ]);
})->name('onlyoffice.plugin.asset');

Route::get('/', [HomeController::class, 'root']);

/*
|--------------------------------------------------------------------------
| Cualquier usuario autenticado y habilitado
|--------------------------------------------------------------------------
| 'active' cierra la sesión si el admin desactivó al usuario o su área.
*/
Route::middleware(['auth', 'active'])->group(function () {
    // Textos reutilizables del editor; aislados por usuario autenticado.
    Route::get('/editor/reusable', [ReusableContentController::class, 'index'])->name('editor.reusable.index');
    Route::post('/editor/reusable', [ReusableContentController::class, 'store'])->name('editor.reusable.store');
    Route::delete('/editor/reusable/{reusableContent}', [ReusableContentController::class, 'destroy'])->name('editor.reusable.destroy');

    // Punto de entrada único: el admin va a su dashboard; moderador y usuario, a "Mis documentos".
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

    // El navegador lo llama mientras la persona interactúa, para que el servidor sepa que sigue activa.
    Route::get('/session/ping', [HomeController::class, 'ping'])->name('session.ping');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Mis documentos: se crean con "Usar plantilla" y se editan en OnlyOffice.
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create'); // redirige a Plantillas
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::patch('/documents/{document}/status', [DocumentController::class, 'toggleStatus'])->name('documents.status');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Plantillas: todos las ven y las usan; subir / editar / activar / eliminar lo limita TemplatePolicy
    // (moderador: las de su área · administrador: todas).
    Route::resource('templates', TemplateController::class)->except(['show', 'create']);
    Route::patch('templates/{template}/status', [TemplateController::class, 'toggleStatus'])->name('templates.status');
    Route::get('templates/{template}/download', [TemplateController::class, 'download'])->name('templates.download');

    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
});

/*
|--------------------------------------------------------------------------
| Moderador y Administrador: supervisión de documentos
|--------------------------------------------------------------------------
| Moderador = documentos de su área · Administrador = todos.
*/
Route::middleware(['auth', 'active', 'role:admin,mod'])->group(function () {
    Route::get('/supervision', [DocumentController::class, 'supervision'])->name('supervision.index');
});

/*
|--------------------------------------------------------------------------
| Administrador
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::resource('users', AdminUserController::class)->except(['show', 'destroy', 'create']);
        Route::patch('users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');

        Route::resource('areas', AdminAreaController::class)->except(['show', 'create']);
        Route::patch('areas/{area}/status', [AdminAreaController::class, 'toggleStatus'])->name('areas.status');
    });

/*
|--------------------------------------------------------------------------
| URLs antiguas de inicio (se conservan por compatibilidad)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:mod'])->group(function () {
    Route::get('/mod/dashboard', [ModController::class, 'dashboard'])->name('mod.dashboard');
});

Route::middleware(['auth', 'active', 'role:user'])->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
});

require __DIR__.'/auth.php';
