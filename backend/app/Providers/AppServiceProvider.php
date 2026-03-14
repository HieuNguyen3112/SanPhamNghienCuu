<?php

namespace App\Providers;

use App\Repositories\UserManagement\EloquentLecturerAccountRepository;
use App\Repositories\UserManagement\LecturerAccountRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(LecturerAccountRepository::class, EloquentLecturerAccountRepository::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
