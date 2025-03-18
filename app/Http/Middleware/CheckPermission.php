<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::guard('admin')->user();
        if (!$user) {
            return redirect()->route('admin.login');
        }

        if (!$user->role->permissions->contains('slug', $permission)) {
            abort(403, 'No permission.');
        }

        return $next($request);
    }
}
