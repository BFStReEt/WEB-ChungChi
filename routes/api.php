<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;

//Admin
Route::match(['get', 'post'], 'login', [AdminController::class, 'login'])->name('login');
Route::post('logout', [AdminController::class, 'logout']);
// Admin-access-login
Route::group(['middleware' => 'admin', 'prefix' => 'admin'], function () {
    // Admin
    Route::resource('/information', AdminController::class);
    Route::get('/admin-information', [AdminController::class, 'information']);
    Route::patch('/profile',[AdminController::class,'UpdateProfile']);

    //Log
    Route::get('/select-name-admin',[AdminController::class,'showSelectAdmin']);
    Route::get('/admin-log',[AdminController::class,'log']);

    Route::delete('/delete-all-admin',[AdminController::class,'delete']);

    // Role
    Route::resource('roles', RoleController::class);
    Route::delete('/roles-delete', [RoleController::class, 'delete']);

    // Permission
    Route::resource('permission', PermissionController::class);

    Route::get('/permissions/show', [PermissionController::class, 'showPermission']);
});

// Category
Route::group(['middleware' => 'admin', 'prefix' => 'categories'], function () {
    //Route::get('/{categorySlug}/{subCategorySlug?}/{yearSlug?}', [CategoryController::class, 'show']);
    Route::get('/',[CategoryController::class,'show']);
});

// File
Route::group(['middleware' => 'auth:sanctum', 'prefix' => 'file'], function () {
    Route::post('/import/{categorySlug}/{subCategorySlug?}/{yearSlug?}', [FilesController::class, 'import']);
    Route::post('/delete/{id}', [FilesController::class, 'delete']);
    Route::get('/download/{id}', [FilesController::class, 'download']);
});