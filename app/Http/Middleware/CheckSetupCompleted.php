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
     * Redirects to setup if not yet completed.
     * Skips check for setup routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldRedirectToSetup() && !$request->is('setup') && !$request->is('setup/*')) {
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
            return false;
        }
    }
}
