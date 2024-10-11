<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\FolderController;
use App\Http\Controllers\Dashboard\FileController;
use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\UserManagementController;

Route::get('/user-management', function () {
    return view('user-management');
});


Route::post('/user/register', [UserManagementController::class, 'createUser'])->name('register.createUser');
Route::post('/user/addApache', [UserManagementController::class, 'addApache'])->name('register.addApache');
Route::post('/user/addPhpFpm', [UserManagementController::class, 'addPhpFpm'])->name('register.addPhpFpm');








Route::get('/', [DashBoardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

use App\Http\Controllers\PhpExtensionController;

Route::middleware(['auth'])->prefix('extensions')->name('extensions.')->group(function () {
    // PHP extension'ları listeleme
    Route::get('/', [PhpExtensionController::class, 'index'])->name('index');

    // Toplu işlemler (Aktif et, devre dışı bırak, sil)
    Route::post('/bulk-action', [PhpExtensionController::class, 'bulkAction'])->name('bulkAction');
});


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
        Route::post('permissions', [FileController::class, 'permissions'])->name('permissions');
        Route::post('permissions_update', [FileController::class, 'permissions_update'])->name('permissions_update');
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
