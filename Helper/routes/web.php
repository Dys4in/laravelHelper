<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\ModController;
use App\Http\Controllers\Backend\UserController;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth', 'role:admin')->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // ◄◄ Rutas que usa el sidebar del dashboard (crea los controladores cuando los necesites).
    //    Mientras no existan, el Blade muestra '#' en esos enlaces.
    // Route::get('/admin/documents', [DocumentController::class, 'index'])->name('documents.index');
    // Route::get('/admin/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    // Route::get('/admin/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    // Route::get('/admin/templates', [TemplateController::class, 'index'])->name('templates.index');
});

Route::middleware('auth', 'role:mod')->group(function () {
    Route::get('/mod/dashboard', [ModController::class, 'dashboard'])->name('mod.dashboard');
});

Route::middleware('auth', 'role:user')->group(function () {
    Route::get('/user/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
});

// Una sola vez, al final (antes estaba repetido 3 veces)
require __DIR__.'/auth.php';