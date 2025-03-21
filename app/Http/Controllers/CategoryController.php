<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\File;
use App\Models\Category;
use Carbon\Carbon;

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

    public function showFiles(Request $request, $categoryId)
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

            $query = $category->files()
                ->select('id', 'name', 'mime_type', 'path', 'author', 'code', 'effective_date', 'revision_number', 'created_at', 'updated_at')
                ->orderBy('id', 'desc');

            $data = $request->input('data');
            $startTime = $request->input('start_time');
            $endTime = $request->input('end_time');

            if ($data) {
                $query->where(function ($q) use ($data) {
                    $q->where('name', 'LIKE', "%{$data}%")
                        ->orWhere('code', 'LIKE', "%{$data}%")
                        ->orWhere('author', 'LIKE', "%{$data}%");
                });
            }

            if ($startTime && $endTime) {
                try {
                    $start = Carbon::createFromTimestamp((int)$startTime)
                        ->setTimezone('Asia/Ho_Chi_Minh')
                        ->startOfDay();
                    $end = Carbon::createFromTimestamp((int)$endTime)
                        ->setTimezone('Asia/Ho_Chi_Minh')
                        ->endOfDay();
                    $query->whereBetween('created_at', [$start, $end]);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid date format'
                    ], 400);
                }
            } elseif ($startTime) {
                try {
                    $start = Carbon::createFromTimestamp((int)$startTime)
                        ->setTimezone('Asia/Ho_Chi_Minh')
                        ->startOfDay();
                    $query->whereDate('created_at', '>=', $start);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid start date'
                    ], 400);
                }
            } elseif ($endTime) {
                try {
                    $end = Carbon::createFromTimestamp((int)$endTime)
                        ->setTimezone('Asia/Ho_Chi_Minh')
                        ->endOfDay();
                    $query->whereDate('created_at', '<=', $end);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid end date'
                    ], 400);
                }
            }

            $files = $query->paginate(10);

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
                    'files' => $files->items(),
                    'pagination' => [
                        'current_page' => $files->currentPage(),
                        'total_pages' => $files->lastPage(),
                        'per_page' => $files->perPage(),
                        'total' => $files->total(),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadFile(Request $request, $categoryId)
    {
        try {
            $category = Category::with(['parent', 'children'])->findOrFail($categoryId);
            $username = Auth::guard('admin')->user()->username;

            if ($category->children->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot upload in parent category.'
                ], 403);
            }

            $slugParts = [];
            $folderParts = [];
            $currentCategory = $category;
            while ($currentCategory) {
                array_unshift($slugParts, $currentCategory->name);
                array_unshift($folderParts, $currentCategory->name);
                $currentCategory = $currentCategory->parent;
            }

            if (count($slugParts) > 1) {
                array_pop($slugParts);
            }

            $permissionSlug = implode('.', $slugParts) . '.upload';
            if (!Gate::allows($permissionSlug)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No permission to upload files in this directory'
                ], 403);
            }

            $storagePath = implode('/', $folderParts);
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'required|file',
                'google_id' => 'required|string',
            ]);

            $uploadedFiles = [];
            $errors = [];

            foreach ($request->file('files') as $file) {
                try {
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $mimeType = $file->getMimeType();

                    $nameParts = explode('-', $originalName, 3);
                    $code = isset($nameParts[0], $nameParts[1]) ? trim($nameParts[0] . '-' . $nameParts[1]) : '';
                    $name = isset($nameParts[2]) ? trim($nameParts[2]) : $originalName;

                    $fileName = Str::slug(pathinfo($name, PATHINFO_FILENAME)) .
                        '_' . time() . '_' . Str::random(8) .
                        '.' . $extension;

                    $path = $file->storeAs(
                        'uploads/' . $storagePath,
                        $fileName,
                        'public'
                    );

                    $fileRecord = new File();
                    $fileRecord->category_id = $category->id;
                    $fileRecord->name = $name;
                    $fileRecord->code = $code;
                    $fileRecord->path = $path;
                    $fileRecord->mime_type = $mimeType;
                    $fileRecord->author = $username;
                    $fileRecord->google_id = $google_id;
                    $fileRecord->save();

                    $uploadedFiles[] = [
                        'id' => $fileRecord->id,
                        'name' => $fileRecord->name,
                        'code' => $fileRecord->code,
                        'mime_type' => $fileRecord->mime_type,
                        'path' => Storage::url($fileRecord->path),
                        'author' => $fileRecord->author,
                        'google_id' => $fileRecord->google_id,
                        'created_at' => $fileRecord->created_at,
                        'updated_at' => $fileRecord->updated_at
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'name' => $originalName ?? 'Unknown file',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'message' => count($uploadedFiles) . ' files uploaded successfully' .
                    (count($errors) > 0 ? ' with ' . count($errors) . ' errors' : ''),
                'data' => [
                    'uploaded_files' => $uploadedFiles,
                    'errors' => $errors
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadFile($fileId)
    {
        try {
            $file = File::with(['category.parent'])->findOrFail($fileId);
            $category = $file->category;

            $slugParts = [];
            $currentCategory = $category;

            while ($currentCategory) {
                array_unshift($slugParts, $currentCategory->name);
                $currentCategory = $currentCategory->parent;
            }

            if (count($slugParts) > 1) {
                array_pop($slugParts);
            }
            $permissionSlug = implode('.', $slugParts) . '.download';
            if (!Gate::allows($permissionSlug)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No permission to download this file'
                ], 403);
            }

            if (!Storage::disk('public')->exists($file->path)) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $path = Storage::disk('public')->path($file->path);

            return response()->download(
                $path,
                $file->name,
                ['Content-Type' => $file->mime_type]
            );

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function deleteFile($fileId)
    {
        try {
            $file = File::with(['category.parent'])->findOrFail($fileId);
            $category = $file->category;

            $slugParts = [];
            $currentCategory = $category;

            while ($currentCategory) {
                array_unshift($slugParts, $currentCategory->name);
                $currentCategory = $currentCategory->parent;
            }

            if (count($slugParts) > 1) {
                array_pop($slugParts);
            }

            $permissionSlug = implode('.', $slugParts) . '.del';
            if (!Gate::allows($permissionSlug)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No permission to delete this file'
                ], 403);
            }

            if (Storage::disk('public')->exists($file->path)) {
                Storage::disk('public')->delete($file->path);
            }

            $file->delete();

            return response()->json([
                'status' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteMultipleFiles(Request $request){
        try {
            $request->validate([
                'ids' => 'required'
            ]);

            $fileIds = is_array($request->ids)
                ? $request->ids
                : array_map('intval', explode(',', $request->ids));

            if (empty($fileIds)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Danh sách ID không hợp lệ'
                ], 400);
            }

            $files = File::with(['category.parent'])
                        ->whereIn('id', $fileIds)
                        ->get();

            if ($files->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Không tìm thấy file nào để xóa'
                ], 404);
            }

            $successCount = 0;
            $errors = [];

            foreach ($files as $file) {
                try {
                    $category = $file->category;

                    $slugParts = [];
                    $currentCategory = $category;

                    while ($currentCategory) {
                        array_unshift($slugParts, $currentCategory->name);
                        $currentCategory = $currentCategory->parent;
                    }

                    if (count($slugParts) > 1) {
                        array_pop($slugParts);
                    }

                    $permissionSlug = implode('.', $slugParts) . '.del';
                    if (!Gate::allows($permissionSlug)) {
                        $errors[] = [
                            'id' => $file->id,
                            'name' => $file->name,
                            'error' => 'Không có quyền xóa file này'
                        ];
                        continue;
                    }

                    if (Storage::disk('public')->exists($file->path)) {
                        Storage::disk('public')->delete($file->path);
                    }

                    $file->delete();
                    $successCount++;

                } catch (\Exception $e) {
                    $errors[] = [
                        'id' => $file->id,
                        'name' => $file->name ?? 'Unknown file',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'message' => "Đã xóa thành công {$successCount} file" .
                            (count($errors) > 0 ? " và có " . count($errors) . " file lỗi" : ''),
                'data' => [
                    'success_count' => $successCount,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function editFile($fileId){
        try {
            $file = File::findOrFail($fileId);

            return response()->json([
                'status' => true,
                'data' => $file
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateFile(Request $request,$fileId)
    {
        $request->validate([
            //'name' => 'required|string|max:255',
            //'code' => 'nullable|string|max:255',
            'effective_date' => 'string',
            'revision_number' => 'string',
        ]);

        try {
            $file = File::findOrFail($fileId);

            //$file->name = $request->input('name');
            //$file->code = $request->input('code');
            $file->effective_date = $request->input('effective_date');
            $file->revision_number = $request->input('revision_number');
            $file->save();

            return response()->json([
                'status' => true,
                'message' => 'File updated successfully',
                'data' => $file
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}