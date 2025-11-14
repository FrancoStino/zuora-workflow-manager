<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAuthAfterSetup
{
    /**
     * Handle an incoming request.
     * Requires authentication except for login and setup routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow unauthenticated access to setup and login
        if ($request->is('setup') || $request->is('setup/*') || $request->is('login') || $request->is('login/*')) {
            return $next($request);
        }

        // Require authentication for all other routes
        if (!auth()->check()) {
            return redirect('/login');
        }

        return $next($request);
    }
}
