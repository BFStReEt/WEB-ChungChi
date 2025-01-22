<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
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
