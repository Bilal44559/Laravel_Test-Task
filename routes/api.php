<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\LocalizationController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('auth.login');

Route::middleware([AuthenticateApiToken::class])->group(function (): void {
    Route::controller(AuthController::class)->name('auth.')->group(function (): void {
        Route::post('/logout', 'logout')->name('logout');
        Route::get('/user', 'user')->name('user');
    });

    Route::prefix('languages')->name('languages.')->controller(LanguageController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
    });

    Route::prefix('labels')->name('labels.')->controller(LabelController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
    });

    Route::prefix('localizations')->name('localizations.')->controller(LocalizationController::class)->group(function (): void {
        Route::get('/export', 'export')->name('export');
        Route::get('/search', 'search')->name('search');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{localization}', 'show')->name('show');
        Route::put('/{localization}', 'update')->name('update');
        Route::patch('/{localization}', 'update')->name('patch');
        Route::delete('/{localization}', 'destroy')->name('destroy');
    });
});
