<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;

//Admin
    Route::match(['get','post'],'admin/login',[AdminController::class,'login'])->name('admin-login');
    Route::post('/admin/logout',[App\Http\Controllers\Admin\AdminController::class,'logout']);

    //Admin-access-login
    Route::group(['middleware' => 'admin', 'prefix' => 'admin'], function () {

        //Admin
        Route::post('/manage', [AdminController::class, 'manage']);
        Route::post('/create', [AdminController::class, 'create']);
        Route::delete('/{id}', [AdminController::class, 'delete']);
        Route::post('/profile', [AdminController::class, 'update']);
        Route::get('/{id}', [AdminController::class, 'edit']);

        //Role
        Route::resource('roles', RoleController::class);
        Route::delete('/roles/delete/multiple', [RoleController::class, 'deleteRoles']);

        //Permission
        Route::resource('permission',PermissionController::class);
    });


//Category
Route::get('/categories/{categorySlug}/{subCategorySlug?}/{yearSlug?}', [CategoryController::class, 'show']);

//File
Route::post('/file/import/{categorySlug}/{subCategorySlug?}/{yearSlug?}', [FilesController::class, 'import']);
Route::post('/file/delete/{id}', [FilesController::class, 'delete']);
Route::get('/file/download/{id}', [FilesController::class, 'download']);

