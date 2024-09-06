<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\FolderController;
use App\Http\Controllers\Dashboard\FileController;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::group(['prefix' => 'filemanager', 'middleware' => ['auth', 'verified'], 'as' => 'filemanager.'], function () {
    Route::get('/', [FileController::class, 'index'])->name('index');
    Route::group(['prefix' => 'file', 'as' => 'file.'], function () {
        Route::get('create', [FileController::class, 'create'])->name('create');
        Route::post('store', [FileController::class, 'store'])->name('store');
        Route::post('store/upload', [FileController::class, 'store_upload'])->name('store_upload');
        Route::delete('destroy', [FileController::class, 'destroy'])->name('destroy');
        Route::post('download/{file}', [FileController::class, 'download'])->name('download');
        Route::get('preview', [FileController::class, 'preview'])->name('preview');
        Route::post('preview', [FileController::class, 'preview_update'])->name('preview_update');
        Route::post('rename', [FileController::class, 'rename'])->name('rename');
        Route::post('extract', [FileController::class, 'extract'])->name('extract');
        Route::post('compress', [FileController::class, 'compress'])->name('compress');
        // Route::get('search/{query}', [FileController::class, 'search'])->name('search');
        // Route::get('move/{file}', [FileController::class, 'move'])->name('move');
        // Route::put('move/{file}', [FileController::class, 'moveFile'])->name('move');
    });
    Route::resource('folder', FolderController::class);
    // Route::resource('file', FileController::class);

    Route::get('upload', [FileController::class, 'upload'])->name('upload');
    Route::post('download', [FileController::class, 'download'])->name('download');
    Route::get('trash', [FileController::class, 'download'])->name('trash');
});

Route::get('/tmp/{file}', function () {
    return response()->file(base_path('tmp') . '/' . request()->file);
})->name('tmp');

require __DIR__ . '/auth.php';