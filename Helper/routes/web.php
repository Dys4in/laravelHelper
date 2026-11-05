<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Backend\Admin\AdminAreaController;
use App\Http\Controllers\Backend\Admin\AdminUserController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\ModController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Cualquier usuario autenticado y habilitado
|--------------------------------------------------------------------------
| 'active' cierra la sesión si el admin desactivó al usuario o su área.
*/
Route::middleware(['auth', 'active'])->group(function () {
    // Punto de entrada único: el admin va a su dashboard; moderador y usuario, a "Mis documentos".
    Route::get('/dashboard', function () {
        return redirect()->route(auth()->user()->role->dashboardRoute());
    })->name('dashboard');

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
    Route::get('/audit/feed', [AuditLogController::class, 'feed'])->name('audit.feed');
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
