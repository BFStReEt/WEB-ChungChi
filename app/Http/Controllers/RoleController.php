<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    protected $user;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->user = auth('admin')->user();
        $this->permissionService = $permissionService;
    }

    public function create(Request $request)
    {
        try {
            abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.add'), 403, "No permission");

            $existingRole = Role::where('name', $request->input('name'))
            ->orWhere('title', $request->input('title'))
            ->first();

        if ($existingRole) {
            return response()->json([
                'status' => false,
                'message' => 'Role or title already exists'
            ], 422);
        }
            $role = Role::create([
                'name' => $request->input('name'),
                'title' => $request->input('title')
            ]);

            $role->permissions()->attach($request->input('permission_id'));

            return response()->json([
                'status' => true,
                'message' => 'Create Role success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function getAllRole(Request $request){
        try{
            abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.get'), 403, "No permission");
            $roles = Role::all();

            return response()->json([
                'status' => true,
                'roles' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'title' => $role->title,
                        'name' => $role->name,
                    ];
                }),
            ]);

        }catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function getRoles(Request $request)
    {
        try {
            abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.get'), 403, "No permission");

            $search = $request->input('data'); 

            $query = Role::query();

            if ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            }

            $roles = $query->select('id', 'title', 'name')->get();

            $count = $roles->count();

            $perPage = $request->input('per_page', 5); 
            $roles = $query->select('id', 'title', 'name')->paginate($perPage);

            return response()->json([
                'status' => true,
                'count' => $roles->total(), 
                'roles' => $roles->items(), 
                'pagination' => [
                    'current_page' => $roles->currentPage(),
                    'per_page' => $roles->perPage(),
                    'total_pages' => $roles->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy($id)
    {
        try {
            // Kiểm tra quyền truy cập
            abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.delete'), 403, "No permission");

            // Tìm và xóa bản ghi
            $role = Role::findOrFail($id);
            $role->delete();

            return response()->json([
                'status' => true,
                'message' => 'Role deleted successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMesssage()
            ], 500);
        }
    }

    public function destroyRoles(Request $request)
{
    try {
        // Kiểm tra quyền truy cập
        abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.delete'), 403, "No permission");

        // Lấy mảng ID từ request
        $ids = $request->input('ids');

        // Kiểm tra nếu mảng ID không tồn tại hoặc rỗng
        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'No IDs provided for deletion.'
            ], 400);
        }

        // Lấy các ID tồn tại trong CSDL
        $existingIds = Role::whereIn('id', $ids)->pluck('id')->toArray();

        // Kiểm tra xem có ID nào không tồn tại không
        $nonExistingIds = array_diff($ids, $existingIds);

        if (!empty($nonExistingIds)) {
            return response()->json([
                'status' => false,
                'message' => 'The following IDs do not exist: ' . implode(', ', $nonExistingIds),
            ], 404);
        }

        // Xóa các bản ghi dựa trên mảng ID hợp lệ
        Role::destroy($existingIds);

        return response()->json([
            'status' => true,
            'message' => 'Roles deleted successfully.',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function update(Request $request, string $id){
        abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.update'), 403, "No permission");

        $roles = Role::find($id);
        if (!$roles) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'string|required|max:255',
            'name' => 'string|required|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()           
            ], 422);
        }

        if ($request->has('title')) {
            $roles->title = $request->title;
        }

        if ($request->has('name')) {
            $roles->name = $request->name;
        }

        $roles->save();

        return response()->json([
            'status' => true,
            'message' => "success"
        ]);
    }


}