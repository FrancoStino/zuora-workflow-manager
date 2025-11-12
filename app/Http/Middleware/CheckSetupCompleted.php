<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckSetupCompleted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for setup route itself
        if ($request->is('setup') || $request->is('setup/*')) {
            return $next($request);
        }

        // Redirect to setup if table exists and setup is incomplete
        if ($this->shouldRedirectToSetup()) {
            return redirect('/setup');
        }

        return $next($request);
    }

    /**
     * Determine if request should be redirected to setup page.
     */
    private function shouldRedirectToSetup(): bool
    {
        try {
            if (! Schema::hasTable('setup_completed')) {
                return false;
            }

            $setupCompleted = DB::table('setup_completed')->first();

            return ! $setupCompleted || ! $setupCompleted->completed;
        } catch (\Exception) {
            // If there's any error, allow the request to continue
            // This prevents the app from breaking during migrations or setup
            return false;
        }
    }
}
