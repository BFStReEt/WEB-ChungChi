<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\File;
use App\Models\Category;

class CategoryController extends Controller
{
    public function show()
    {
        try {
            $categories = Category::where('parent_id', null)
                ->with('childrenRecursive')  
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $parent_categories = Category::whereNull('parent_id')->get();

            return response()->json([
                'status' => true,
                'parent_categories' => $parent_categories,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }


    public function showFiles($categoryId)
    {
        try {
            $category = Category::with(['parent', 'files'])->findOrFail($categoryId);
            
            $slugParts = [];
            $breadcrumbs = [];
            $currentCategory = $category;
            
            while ($currentCategory) {
                array_unshift($slugParts, $currentCategory->name);
                array_unshift($breadcrumbs, [
                    'id' => $currentCategory->id,
                    'name' => $currentCategory->name
                ]);
                $currentCategory = $currentCategory->parent;
            }

            if (count($slugParts) > 1) {
                array_pop($slugParts);
            }
            
            $permissionSlug = implode('.', $slugParts) . '.manage';
            if (!Gate::allows($permissionSlug)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No permission to access this directory'
                ], 403);
            }

            $files = $category->files()
                ->select('id', 'name', 'mime_type', 'path', 'created_at', 'updated_at')
                ->get();

            $baseSlug = implode('.', $slugParts);
            
            return response()->json([
                'status' => true,
                'data' => [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'full_path' => implode('/', array_column($breadcrumbs, 'name')),
                        'breadcrumbs' => $breadcrumbs
                    ],
                    'files' => $files,
                    'permissions' => [
                        'can_upload' => Gate::allows($baseSlug . '.upload'),
                        'can_delete' => Gate::allows($baseSlug . '.delete'),
                        'can_download' => Gate::allows($baseSlug . '.download')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}