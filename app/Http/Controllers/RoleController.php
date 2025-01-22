<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Support\Facades\DB;

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
                'role' => $roles,
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
            $roles = Role::when($search, function ($query, $search) {
                return $query->where('title', 'like', '%' . $search . '%')
                            ->orWhere('name', 'like', '%' . $search . '%');
            })->get();

            $count = $roles->count();

            return response()->json([
                'status' => true,
                'count' => $count,
                'roles' => $roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'title' => $role->title,
                        'name' => $role->name,
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function delete(Request $request, string $id){
        abort_if(!$this->permissionService->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.del'), 403, "No permission");

    }
}