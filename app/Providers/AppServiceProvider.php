<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public $bindings = [
        'App\Services\Interfaces\AdminServiceInterface' => 'App\Services\AdminService',
        'App\Repositories\Interfaces\AdminRepositoryInterface' => 'App\Repositories\AdminRepository',
    ];
    public function register(): void
    {
        foreach ($this->bindings as $key => $val) {
            $this->app->bind($key, $val);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //Passport::routes();
    }
}
