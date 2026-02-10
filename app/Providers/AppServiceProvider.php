<?php

namespace App\Providers;

use App\Listeners\AssignWorkflowRoleOnSocialiteRegistration;
use App\Listeners\UpdateUserAvatarOnSocialiteLogin;
use DutchCodingCompany\FilamentSocialite\Events\Login;
use DutchCodingCompany\FilamentSocialite\Events\Registered;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // LaragentChatService is the primary chat service
        // No feature flag needed - using LarAgent only
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

        DB::listen(function (QueryExecuted $query) {
            $enableSecurityListener = config('app.enable_ai_security_listener', true);

            if (! $enableSecurityListener) {
                return;
            }

            if (preg_match('/\b(INSERT|UPDATE|DELETE)\b/i', $query->sql)) {
                Log::critical('SECURITY BREACH: AI attempted write', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                ]);

                throw new \RuntimeException('AI write operations forbidden');
            }
        });
    }
}
