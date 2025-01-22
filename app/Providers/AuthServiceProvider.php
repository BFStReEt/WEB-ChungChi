<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
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
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
