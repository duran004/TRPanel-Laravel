<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashBoardController;
use App\Http\Controllers\PhpExtensionController;
use App\Http\Controllers\Dashboard\FileController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\Dashboard\FolderController;


Route::group([
    'middleware' => [],
    'prefix' => 'user',
], function () {

    Route::post('/register', [UserManagementController::class, 'createUser'])->name('register.createUser');
    Route::post('/addApache', [UserManagementController::class, 'addApache'])->name('register.addApache');
    Route::post('/addPhpFpm', [UserManagementController::class, 'addPhpFpm'])->name('register.addPhpFpm');
    Route::post('/addPermissions', [UserManagementController::class, 'addPermissions'])->name('register.addPermissions');
    Route::post('/createPhpIni', [UserManagementController::class, 'createPhpIni'])->name('register.createPhpIni');
    Route::post('/createIndexPhp', [UserManagementController::class, 'createIndexPhp'])->name('register.createIndexPhp');
    Route::post('/reloadServices', [UserManagementController::class, 'reloadServices'])->name('register.reloadServices');
    Route::post('/loginUser', [UserManagementController::class, 'loginUser'])->name('register.loginUser');
});

//auth routes
Route::group([
    'middleware' => [],
    'prefix' => 'auth',
], function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.login');
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
});









Route::get('/', [DashBoardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');



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

// require __DIR__ . '/auth.php';