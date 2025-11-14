<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Setup;
use App\Filament\Pages\WorkflowDashboard;
use App\Http\Middleware\CheckSetupCompleted;
use App\Http\Middleware\RequireAuthAfterSetup;
use App\Models\AppSetting;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Exception;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Resma\FilamentAwinTheme\FilamentAwinTheme;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Teal,
            ])
            ->brandName('Zuora Workflows')
            ->brandLogo(asset('images/logo.svg'))
            ->darkModeBrandLogo(asset('images/logo-white.svg'))
            ->brandLogoHeight('2rem')
            ->discoverResources(in : app_path('Filament/Resources'), for : 'App\Filament\Resources')
            ->discoverPages(in : app_path('Filament/Pages'), for : 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                WorkflowDashboard::class,
                Setup::class,
            ])
            ->discoverWidgets(in : app_path('Filament/Widgets'), for : 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                CheckSetupCompleted::class,
                RequireAuthAfterSetup::class,
            ])
            ->authGuard('web')
//			-> renderHook (
//			// PanelsRenderHook::BODY_END,
//				PanelsRenderHook::FOOTER,
//				fn () => view ( 'footer' )
//			)
            ->plugins([
                FilamentAwinTheme::make()
                    ->primaryColor(Color::Teal),
                FilamentShieldPlugin::make(),
                FilamentSocialitePlugin::make()
                    ->domainAllowList($this->getOAuthAllowedDomains())
                    ->registration(true)
                    ->providers([
                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google')
                            ->color(Color::Red),
                    ]),
            ]);
    }

    /**
     * Get OAuth allowed domains from database or fallback to config
     */
    private function getOAuthAllowedDomains(): array
    {
        try {
            if (Schema::hasTable('app_settings')) {
                $domains = AppSetting::getOAuthDomains();
                if (! empty($domains)) {
                    return $domains;
                }
            }
        } catch (Exception $e) {
            // Silently fail and use fallback
        }

        return config('services.oauth.allowed_domains', []);
    }
}
