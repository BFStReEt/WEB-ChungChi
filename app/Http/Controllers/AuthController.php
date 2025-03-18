<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function checkToken(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => false,
                'message' => 'No token'
            ], 400);
        }

        try {
            $admin = Auth::guard('admin')->user();
            if ($admin) {
                $permissions = $admin->roles->flatMap(function ($role) {
                    return $role->permissions->pluck('slug');
                });

                return response()->json([
                    'status' => true,
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->display_name,
                    'email' => $admin->email,
                    'permissions' => $permissions,
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token or unauthorized'
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token validation failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
