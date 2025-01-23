<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

use App\Models\Permission;
use App\Policies\AdminPolicy;
use App\Models\Admin;

class AuthServiceProvider extends ServiceProvider
{

    protected $policies = [
        Admin::class => AdminPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        $permissions=Permission::all();
        foreach($permissions as $permission){
            Gate::define($permission->slug, function ($user = null) use ($permission){
                $user = Auth::guard('admin')->user();
                return  $user->hasPermission($permission->slug);
            });
        }
        
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
