<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\RoleController;

//Admin
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login'])->name('admin.login');

    Route::middleware('admin.auth')->group(function () {
        //Admin
        Route::post('/manage', [AdminController::class, 'manage']);
        Route::post('/create', [AdminController::class, 'create']);
        Route::delete('/{id}', [AdminController::class, 'delete']);
        Route::post('/profile', [AdminController::class, 'update']);
        Route::post('/logout', [AdminController::class, 'logout']);
        Route::get('/information', [AdminController::class, 'getInformation']);


        //Role
        Route::post('/roles', [RoleController::class, 'create']);

        Route::get('/roles', [RoleController::class, 'getAllRole']);
        Route::get('/roles', [RoleController::class, 'getRoles']);

        Route::delete('/roles/{id}',[RoleController::class, 'destroy']);
        Route::delete('/roles',[RoleController::class, 'destroyRoles']);

        Route::post('/roles/{id}',[RoleController::class, 'update']);
    });
});

//Category
Route::get('/categories/{categorySlug}/{subCategorySlug?}/{yearSlug?}', [CategoryController::class, 'show']);

//File
Route::post('/file/import/{categorySlug}/{subCategorySlug?}/{yearSlug?}', [FilesController::class, 'import']);
Route::post('/file/delete/{id}', [FilesController::class, 'delete']);
Route::get('/file/download/{id}', [FilesController::class, 'download']);

