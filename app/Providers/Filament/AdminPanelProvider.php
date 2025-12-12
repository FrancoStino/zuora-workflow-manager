<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Setup;
use App\Filament\Resources\Workflows\Pages\ViewWorkflow;
use App\Http\Middleware\CheckSetupCompleted;
use App\Http\Middleware\RequireAuthAfterSetup;
use App\Services\OAuthService;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Resma\FilamentAwinTheme\FilamentAwinTheme;

class AdminPanelProvider extends PanelProvider
{
	public function panel ( Panel $panel ) : Panel
	{
		return $panel
			-> default ()
			-> id ( 'admin' )
			-> path ( '' )
			-> login ( Login::class )
			-> maxContentWidth ( Width::Full )
			-> colors ( [
				'primary' => Color::Teal,
			] )
			-> brandName ( 'Zuora Workflows' )
			-> brandLogo ( asset ( 'images/logo.svg' ) )
			-> darkModeBrandLogo ( asset ( 'images/logo-white.svg' ) )
			-> brandLogoHeight ( '2rem' )
			-> favicon ( asset ( 'images/favicon.ico' ) )
			-> sidebarCollapsibleOnDesktop ()
			-> navigationGroups ( [
				'Zuora Management',
			] )
			-> discoverResources ( in : app_path ( 'Filament/Resources' ), for : 'App\Filament\Resources' )
			-> discoverPages ( in : app_path ( 'Filament/Pages' ), for : 'App\Filament\Pages' )
			-> pages ( [
				Dashboard::class,
				Setup::class,
			] )
			-> discoverWidgets ( in : app_path ( 'Filament/Widgets' ), for : 'App\Filament\Widgets' )
			-> widgets ( [
				AccountWidget::class,
				// FilamentInfoWidget::class,
			] )
			-> middleware ( [
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
			] )
			-> authGuard ( 'web' )
			-> renderHook (
				PanelsRenderHook::STYLES_AFTER,
				function () {
					$manifest = json_decode ( file_get_contents ( public_path ( 'build/manifest.json' ) ), true );
					$cssFile  = $manifest[ 'resources/css/workflow-graph.css' ][ 'file' ] ?? 'assets/workflow-graph.css';

					return '<link rel="stylesheet" href="' . asset ( 'build/' . $cssFile ) . '">';
				},
				scopes : [ ViewWorkflow::class ]
			)
			-> renderHook (
				PanelsRenderHook::SCRIPTS_AFTER,
				function () {
					$manifest = json_decode ( file_get_contents ( public_path ( 'build/manifest.json' ) ), true );
					$appJs    = $manifest[ 'resources/js/app.js' ][ 'file' ] ?? 'assets/app.js';

					return '<script type="module" src="' . asset ( 'build/' . $appJs ) . '"></script>';
				},
				scopes : [ ViewWorkflow::class ]
			)
			-> renderHook (
				PanelsRenderHook::FOOTER,
				fn () => view ( 'footer' ) )
			-> plugins ( [
				GlobalSearchModalPlugin ::make ()
				                        -> highlightQueryStyles ( [
					                        'background-color' => 'teal',
					                        'font-weight'      => 'bold',
				                        ] )
				                        -> showGroupSearchCounts (),   // Enable per-category count display
				FilamentAwinTheme ::make ()
				                  -> primaryColor ( Color::Teal ),
				FilamentShieldPlugin ::make (),
				FilamentSocialitePlugin ::make ()
				                        -> domainAllowList ( app ( OAuthService::class ) -> getAllowedDomains () )
				                        -> registration ( true )
				                        -> providers ( [
					                        Provider ::make ( 'google' )
					                                 -> label ( 'Google' )
					                                 -> icon ( 'fab-google' )
					                                 -> color ( Color::Red ),
				                        ] ),
			] );
	}
}
