<?php

use App\Http\Controllers\Backend\Admin\AdminAreaController;
use App\Http\Controllers\Backend\Admin\AdminUserController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\ModController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Rutas para cualquier usuario autenticado y habilitado
|--------------------------------------------------------------------------
| 'active' cierra la sesión si el admin desactivó al usuario o su área.
*/
Route::middleware(['auth', 'active'])->group(function () {
    // Punto de entrada único: manda a cada rol a su panel.
    Route::get('/dashboard', function () {
        return redirect()->route(auth()->user()->role->dashboardRoute());
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
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

        Route::resource('users', AdminUserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('users.status');

        Route::resource('areas', AdminAreaController::class)->except(['show', 'create']);
        Route::patch('areas/{area}/status', [AdminAreaController::class, 'toggleStatus'])->name('areas.status');

        // Cuando lleguemos a documentos/plantillas:
        // Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    });

/*
|--------------------------------------------------------------------------
| Moderador y Usuario
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:mod'])->group(function () {
    Route::get('/mod/dashboard', [ModController::class, 'dashboard'])->name('mod.dashboard');
});

Route::middleware(['auth', 'active', 'role:user'])->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
});

require __DIR__.'/auth.php';
