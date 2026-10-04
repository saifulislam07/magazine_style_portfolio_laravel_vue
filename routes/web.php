<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminBookController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'magazine');
Route::get('/book-data', BookController::class)->name('book.data');

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('admin.login.store');

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin')->name('dashboard');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');

    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/data', [AdminBookController::class, 'data'])->name('data');
        Route::put('/settings', [AdminBookController::class, 'updateSettings'])->name('settings.update');
        Route::post('/pages', [AdminBookController::class, 'storePage'])->name('pages.store');
        Route::put('/pages/reorder', [AdminBookController::class, 'reorderPages'])->name('pages.reorder');
        Route::put('/pages/{page}', [AdminBookController::class, 'updatePage'])->name('pages.update');
        Route::delete('/pages/{page}', [AdminBookController::class, 'deletePage'])->name('pages.destroy');
    });
});
