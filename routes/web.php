<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminBookController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\AdminSiteController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SiteController::class, 'robots'])->name('robots');
Route::get('/book-data', BookController::class)->name('book.data');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

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

        Route::get('/dashboard', [AdminSiteController::class, 'dashboard'])->name('dashboard');
        Route::get('/site-settings', [AdminSiteController::class, 'settings'])->name('site-settings.show');
        Route::put('/site-settings/{group}', [AdminSiteController::class, 'update'])->name('site-settings.update');
        Route::post('/mail/test', [AdminSiteController::class, 'testMail'])
            ->middleware('throttle:5,1')
            ->name('mail.test');
        Route::post('/media', [AdminSiteController::class, 'upload'])->name('media.store');

        Route::get('/messages', [AdminMessageController::class, 'index'])->name('messages.index');
        Route::patch('/messages/{message}', [AdminMessageController::class, 'update'])->name('messages.update');
        Route::delete('/messages/{message}', [AdminMessageController::class, 'destroy'])->name('messages.destroy');
    });
});
