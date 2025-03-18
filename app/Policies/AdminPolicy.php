<?php

namespace App\Policies;

use App\Models\Admin;

class AdminPolicy
{
    public function hasPermission(Admin $admin, $slug)
    {
        $permissions = $admin->roles->flatMap(function ($role) {
            return $role->permissions->pluck('slug');
        });

        return $permissions->contains($slug);
    }
}
