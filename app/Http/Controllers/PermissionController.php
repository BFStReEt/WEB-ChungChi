<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
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
    
}
