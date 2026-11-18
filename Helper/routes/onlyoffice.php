<?php

use App\Http\Controllers\OnlyOfficeController;
use Illuminate\Support\Facades\Route;

/*
| Se registran en bootstrap/app.php con prefijo "onlyoffice", nombre "onlyoffice."
| y el grupo "api" (sin sesión ni CSRF): los llama el Document Server, no el navegador.
*/
Route::get('file/{document}', [OnlyOfficeController::class, 'file'])->name('file');
Route::get('template-file/{template}', [OnlyOfficeController::class, 'templateFile'])->name('template');
Route::post('callback/{document}', [OnlyOfficeController::class, 'callback'])->name('callback');
