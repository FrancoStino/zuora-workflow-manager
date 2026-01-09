<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithSetupBypass extends FilamentAuthenticate
{
    /**
     * Handle an incoming request with setup check and authentication bypass.
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        $isSetupRoute = $this->isSetupRoute($request);
        $isLoginRoute = $request->is('login');
        $isOAuthRoute = $request->is('oauth*');
        $setupCompleted = $this->isSetupCompleted();

        // Handle setup not completed scenario
        if (! $setupCompleted) {
            return $this->handleSetupNotCompleted($request, $isSetupRoute, $next);
        }

        // Handle setup completed scenario
        return $this->handleSetupCompleted($request, $isSetupRoute, $isLoginRoute, $isOAuthRoute, $next, $guards);
    }

    private function handleSetupNotCompleted($request, bool $isSetupRoute, Closure $next): Response
    {
        if ($isSetupRoute) {
            return $next($request);
        }

        return redirect('/setup');
    }

    private function handleSetupCompleted($request, bool $isSetupRoute, bool $isLoginRoute, bool $isOAuthRoute, Closure $next, array $guards): Response
    {
        if ($isSetupRoute) {
            return Auth::check() ? redirect('/') : redirect('/login');
        }

        if ($isLoginRoute || $isOAuthRoute) {
            return $next($request);
        }

        return parent::handle($request, $next, ...$guards);
    }

    /**
     * Determine if the request is for the setup page.
     */
    private function isSetupRoute($request): bool
    {
        return $request->is('setup') || $request->is('setup/*');
    }

    /**
     * Determine if setup is completed.
     */
    private function isSetupCompleted(): bool
    {
        try {
            if (! Schema::hasTable('setup_completed')) {
                return false;
            }

            $setupCompleted = DB::table('setup_completed')->first();

            return $setupCompleted && $setupCompleted->completed;
        } catch (Exception) {
            return false;
        }
    }
}
