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

        // If setup not completed
        if (! $setupCompleted) {
            // Allow access to the setup page without authentication
            if ($isSetupRoute) {
                return $next($request);
            }

            // Redirect all other requests to the setup
            return redirect('/setup');
        }

        // Setup completed
        if ($isSetupRoute) {
            // Redirect based on authentication status
            return Auth::check() ? redirect('/') : redirect('/login');
        }

        // Allow access to login without authentication
        if ($isLoginRoute) {
            return $next($request);
        }

        // Allow access to OAuth routes without authentication
        if ($isOAuthRoute) {
            return $next($request);
        }

        // Setup completed: apply normal Filament authentication
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
