<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File as FileSystem;
use Illuminate\Support\Str;
use App\Models\Admin;

class FilesController extends Controller
{
    protected $user;
    public function __construct()
    {
        $this->user = auth('admin')->user();
    }

    public function import(Request $request, $categorySlug, $subCategorySlug = null, $yearSlug = null)
    {
        $category = Str::slug($categorySlug . "manage");
        if (!$this->hasPermission($this->user, $category)) {
            abort(403, "No permission");
        }
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file'
        ]);

        $files = $request->file('files');

        $category = Category::where('slug', $categorySlug)->first();
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại.',
            ], 404);
        }

        $subCategory = $subCategorySlug ? Category::where('slug', $subCategorySlug)->where('parent_id', $category->id)->first() : null;


        if ($subCategorySlug && !$subCategory) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại.',
            ], 404);
            $category = Str::slug($categorySlug . $subCategorySlug . "manage");
            if (!$this->hasPermission($this->user, $category)) {
                abort(403, "No permission");
            }
        }

        $yearCategory = null;
        if ($yearSlug) {
            $yearCategory = Category::where('slug', $yearSlug)
                ->where('parent_id', $subCategory ? $subCategory->id : $category->id)
                ->first();

            if ($yearCategory) {
                $categoryes = Str::slug($categorySlug . $subCategorySlug . $yearSlug . "manage");
                if (!$this->hasPermission($this->user, $categoryes)) {
                    abort(403, "No permission");
                }
            }

            if (!$yearCategory) {
                return response()->json([
                    'status' => false,
                    'message' => 'Danh mục không tồn tại.',
                ], 404);
            }
        }

        $hasSubCategory = Category::where('parent_id', $category->id)->exists();
        if ($hasSubCategory && !$subCategorySlug) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể import vào danh mục cha vì danh mục này có danh mục con.',
            ], 400);
        }

        if ($subCategory) {
            $hasYearCategory = Category::where('parent_id', $subCategory->id)->exists();
            if ($hasYearCategory && !$yearSlug) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không thể import vào danh mục con vì danh mục này có danh mục năm. Vui lòng chỉ định danh mục năm.',
                ], 400);
            }
        }

        $destinationPath = public_path($category->name);
        if ($subCategory) {
            $destinationPath .= '/' . $subCategory->name;
        }
        if ($yearSlug) {
            $destinationPath .= '/' . $yearSlug;
        }

        $importedFiles = [];
        $existingFiles = [];

        foreach ($files as $file) {
            $fileName = $file->getClientOriginalName();
            $filePath = $destinationPath . '/' . $fileName;

            if (FileSystem::exists($filePath)) {
                $existingFiles[] = $fileName;
                continue;
            }

            if (!FileSystem::exists($destinationPath)) {
                FileSystem::makeDirectory($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $fileName);

            $categoryId = $category->id;
            if ($subCategory) {
                $categoryId = $subCategory->id;
            }
            if ($yearCategory) {
                $categoryId = $yearCategory->id;
            }

            $fileRecord = File::create([
                'name' => $fileName,
                'mime_type' => $file->getClientMimeType(),
                'path' => $category->name .
                    ($subCategory ? '/' . $subCategory->name : '') .
                    ($yearSlug ? '/' . $yearSlug : '') .
                    '/' . $fileName,
                'category_id' => $categoryId,
            ]);

            $importedFiles[] = $fileRecord;
        }

        if ($existingFiles) {
            return response()->json([
                'status' => false,
                'message' => 'File đã tồn tại',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Import thành công.',
            'imported_files' => $importedFiles,
        ]);
    }

    public function download($id)
    {
        $file = File::find($id);
        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File không tồn tại.',
            ], 404);
        }

        $category = Category::find($file->category_id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại.',
            ], 404);
        }

        $slugPath = $category->slug;

        $parentCategory = Category::find($category->parent_id);
        if ($parentCategory) {
            $slugPath = $parentCategory->slug . $slugPath;

            $grandParentCategory = Category::find($parentCategory->parent_id);
            if ($grandParentCategory) {
                $slugPath = $grandParentCategory->slug . $slugPath;
            }
        }

        $permissionSlug = $slugPath . "download";

        if (!$this->hasPermission($this->user, $permissionSlug)) {
            abort(403, "No permission");
        }

        $filePath = public_path($file->path);

        if (!FileSystem::exists($filePath)) {
            return response()->json([
                'status' => false,
                'message' => 'File không tồn tại trên hệ thống.',
            ], 404);
        }

        return Response()->download($filePath, $file->name, [
            'Content-Type' => FileSystem::mimeType($filePath),
        ]);
    }
    public function delete($id)
    {
        $file = File::find($id);
        if (!$file) {
            return response()->json([
                'status' => false,
                'message' => 'File không tồn tại.',
            ], 404);
        }

        $category = Category::find($file->category_id);
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Danh mục không tồn tại.',
            ], 404);
        }

        $slugPath = $category->slug;

        $parentCategory = Category::find($category->parent_id);
        if ($parentCategory) {
            $slugPath = $parentCategory->slug . $slugPath;

            $grandParentCategory = Category::find($parentCategory->parent_id);
            if ($grandParentCategory) {
                $slugPath = $grandParentCategory->slug . $slugPath;
            }
        }

        $permissionSlug = $slugPath . "delete";

        if (!$this->hasPermission($this->user, $permissionSlug)) {
            abort(403, "No permission");
        }

        $filePath = public_path($file->path);
        if (FileSystem::exists($filePath)) {
            FileSystem::delete($filePath);
        }

        $file->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa file thành công.',
        ]);
    }
    public function hasPermission(Admin $admin, $slug)
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