<?php

use App\Http\Controllers\Admin\AccessCodeController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CvVersionController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\UnlockController;
use Illuminate\Support\Facades\Route;

// Public: PIN entry and the unlocked CV
Route::get('/', [UnlockController::class, 'show'])->name('unlock');
Route::post('/unlock', [UnlockController::class, 'store'])->middleware('throttle:pin')->name('unlock.store');
Route::post('/lock', [UnlockController::class, 'destroy'])->name('lock');
Route::get('/cv', [CvController::class, 'show'])->middleware('cv.access')->name('cv.show');
Route::get('/cv-photos/{cvVersion}', [CvVersionController::class, 'showPhoto'])->middleware('signed')->name('cv-versions.photo');

// Admin
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'show'])->name('login');
        Route::post('login', [AuthController::class, 'store'])->middleware('throttle:admin-login')->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'destroy'])->name('logout');
        Route::redirect('/', '/admin/access-codes');

        Route::resource('access-codes', AccessCodeController::class)->except('show');
        Route::get('access-codes/{accessCode}/preview', [CvController::class, 'preview'])->name('access-codes.preview');

        Route::resource('cv-versions', CvVersionController::class)->except('show');
        Route::post('cv-versions/{cvVersion}/photo', [CvVersionController::class, 'storePhoto'])->name('cv-versions.photo.store');
        Route::delete('cv-versions/{cvVersion}/photo', [CvVersionController::class, 'destroyPhoto'])->name('cv-versions.photo.destroy');
        Route::post('cv-versions/{cvVersion}/duplicate', [CvVersionController::class, 'duplicate'])->name('cv-versions.duplicate');
    });
});
