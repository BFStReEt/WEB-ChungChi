<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;

//Admin
Route::match(['get', 'post'], 'login', [AdminController::class, 'login'])->name('login');
Route::post('logout', [AdminController::class, 'logout']);

Route::middleware('admin')->get('/user', function (Request $request) {
    return $request->user();
});

// Admin-access-login
Route::group(['middleware' => 'admin', 'prefix' => 'admin'], function () {
    // Admin
    Route::resource('/information', AdminController::class);
    Route::get('/admin-information', [AdminController::class, 'information']);

    // Role
    Route::resource('roles', RoleController::class);
    Route::delete('/roles/delete/multiple', [RoleController::class, 'deleteRoles']);

    // Permission
    Route::resource('permission', PermissionController::class);
});

// Category
Route::group(['middleware' => 'admin', 'prefix' => 'categories'], function () {
    Route::get('/{categorySlug}/{subCategorySlug?}/{yearSlug?}', [CategoryController::class, 'show']);
});

// File
Route::group(['middleware' => 'auth:sanctum', 'prefix' => 'file'], function () {
    Route::post('/import/{categorySlug}/{subCategorySlug?}/{yearSlug?}', [FilesController::class, 'import']);
    Route::post('/delete/{id}', [FilesController::class, 'delete']);
    Route::get('/download/{id}', [FilesController::class, 'download']);
});