<?php

namespace App\Providers;

use App\Listeners\AssignWorkflowRoleOnSocialiteRegistration;
use App\Listeners\UpdateUserAvatarOnSocialiteLogin;
use DutchCodingCompany\FilamentSocialite\Events\Login;
use DutchCodingCompany\FilamentSocialite\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, UpdateUserAvatarOnSocialiteLogin::class);
        Event::listen(Registered::class, UpdateUserAvatarOnSocialiteLogin::class);
        Event::listen(Registered::class, AssignWorkflowRoleOnSocialiteRegistration::class);
    }
}
