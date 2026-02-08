<?php

namespace App\Providers;

use App\Listeners\AssignWorkflowRoleOnSocialiteRegistration;
use App\Listeners\UpdateUserAvatarOnSocialiteLogin;
use DutchCodingCompany\FilamentSocialite\Events\Login;
use DutchCodingCompany\FilamentSocialite\Events\Registered;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // NOTE: Conditional ChatService binding will be implemented in Task 7
        // after LaragentChatService is created. For now, NeuronChatService
        // is automatically resolved by Laravel's container with its dependencies.
        //
        // Future implementation (Task 7):
        // $this->app->singleton(ChatServiceInterface::class, function ($app) {
        //     return config('app.ai_provider') === 'laragent'
        //         ? $app->make(LaragentChatService::class)
        //         : $app->make(NeuronChatService::class);
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, UpdateUserAvatarOnSocialiteLogin::class);
        Event::listen(Registered::class, UpdateUserAvatarOnSocialiteLogin::class);
        Event::listen(Registered::class, AssignWorkflowRoleOnSocialiteRegistration::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            fn (): string => Blade::render('<livewire:documentation-button />'),
        );
    }
}
