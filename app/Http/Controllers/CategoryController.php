<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    protected $user;
    public function __construct()
    {
        $this->user = auth('admin')->user();
    }
    public function show($categorySlug, $subCategorySlug = null, $yearSlug = null)
    {
        try {
            //$user = auth('admin')->user();

            $category = Str::slug($categorySlug . "manage");
            if (!$this->hasPermission($user, $category)) {
                abort(403, "No permission");
            }

            $category = Category::where('slug', $categorySlug)->firstOrFail();

            $files = [];

            if ($subCategorySlug) {
                $categorys = Str::slug($categorySlug . $subCategorySlug . "manage");
                if (!$this->hasPermission($user, $categorys)) {
                    abort(403, "No permission");
                }
                $subCategory = Category::where('slug', $subCategorySlug)
                    ->where('parent_id', $category->id)
                    ->firstOrFail();

                if ($yearSlug) {
                    $categoryes = Str::slug($categorySlug . $subCategorySlug . $yearSlug . "manage");
                    if (!$this->hasPermission($user, $categoryes)) {
                        abort(403, "No permission");
                    }
                    $yearCategory = Category::where('slug', $yearSlug)
                        ->where('parent_id', $subCategory->id)
                        ->firstOrFail();

                    $files = $yearCategory->files;
                } else {
                    $files = $subCategory->files;
                }
            } else {
                $files = $category->files;
            }

            return response()->json([
                'status' => true,
                'data' => $files,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    protected function hasPermission(Admin $admin, $slug)
    {
        $normalizedSlug = Str::slug($slug);

        $permissions = $admin->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug');
        });

        $normalizedPermissions = $permissions->map(function ($permission) {
            return Str::slug($permission);
        });

        return $normalizedPermissions->contains($normalizedSlug);
    }
}
