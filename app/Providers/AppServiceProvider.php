<?php

namespace App\Providers;

use App\Agents\DataAnalystAgentLaragent;
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
use Livewire\Blaze\Blaze;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Configure and register application-wide services.
     *
     * Notes that the LaragentChatService is the primary chat service and no feature flag is required.
     */
    public function register(): void
    {
        // LaragentChatService is the primary chat service
        // No feature flag needed - using LarAgent only
    }

    /**
     * Bootstraps application services: registers authentication event listeners, a Filament user-menu render hook, and a database query listener that enforces AI write-operation protection.
     *
     * When the configuration key `app.enable_ai_security_listener` is enabled (defaults to true), the database listener detects INSERT, UPDATE, or DELETE SQL statements, logs a critical security event, and prevents the operation.
     *
     * @throws RuntimeException If a database write statement is detected while the AI security listener is enabled.
     */
    public function boot(): void
    {
        Blaze::optimize()
            ->in(resource_path('views'), fold: true, memo: true)
            ->in(resource_path('views/livewire'), compile: false);

        Event::listen(Login::class, UpdateUserAvatarOnSocialiteLogin::class);
        Event::listen(Registered::class,
            UpdateUserAvatarOnSocialiteLogin::class);
        Event::listen(Registered::class,
            AssignWorkflowRoleOnSocialiteRegistration::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_BEFORE,
            fn (): string => Blade::render('<livewire:documentation-button />'),
        );

        DB::listen(function (QueryExecuted $query) {
            // Only enforce on queries executed by the AI agent
            if (! DataAnalystAgentLaragent::$isExecutingQuery) {
                return;
            }

            $enableSecurityListener = config('app.enable_ai_security_listener',
                true);

            if (! $enableSecurityListener) {
                return;
            }

            if (preg_match('/\b(INSERT|UPDATE|DELETE)\b/i', $query->sql)) {
                Log::critical('SECURITY BREACH: AI attempted write', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                ]);

                throw new RuntimeException('AI write operations forbidden');
            }
        });
    }
}
