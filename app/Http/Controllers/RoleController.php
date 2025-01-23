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

    public function store(Request $request)
    {
        try {
            abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.add'), 403, "No permission");

            $ExistRole = Role::where('name', $request->input('name'))
            ->orWhere('title', $request->input('title'))
            ->first();

        if ($ExistRole) {
            return response()->json([
                'status' => false,
                'message' => 'Role or title exists'
            ], 422);
        }
            $role = Role::create([
                'name' => $request->input('name'),
                'title' => $request->input('title')
            ]);

            $role->permissions()->attach($request->input('permission_id'));

            return response()->json([
                'status' => true,
                'message' => 'Success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function index(Request $request)
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
    
            return response()->json([
                'status' => true,
                'count' => $roles->count(), 
                'roles' => $roles, 
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(Request $request, string $id){
        abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.del'), 403, "No permission");

        $roles = Role::find($id);

        if (!$roles) {
            return response()->json([
                'status' => false,
                'message' => 'Role không tồn tại'
            ], 404);
        }
        $roles->delete();

        return response()->json([
            'status' => true,
            'message' => 'success'
        ]);
    }

    public function deleteRoles(Request $request)
    {
        try {
            abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.del'), 403, "No permission");

            $validator = Validator::make($request->all(), [
                'id' => 'required|array', 
                'id.*' => 'required|integer|exists:roles,id', 
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 422);
            }

            $ids = $request->input('id');

            Role::destroy($ids);

            return response()->json([
                'status' => true,
                'message' => 'success',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function edit(string $id)
    {
        abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.update'), 403, "No permission");

        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Role not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            "role" => $role
        ]);
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

        $validator = Validator::make($request->except('id'), [
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