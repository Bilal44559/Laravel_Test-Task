<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/docs');
});

Route::get('/docs/openapi.json', [DocsController::class, 'json'])->name('docs.json');
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
Route::get('/swagger', [DocsController::class, 'index'])->name('swagger.index');
Route::get('/api/documentation', [DocsController::class, 'index'])->name('l5-swagger.default.api');
