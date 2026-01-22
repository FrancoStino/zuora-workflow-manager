<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    private array $excludedRoutes
        = [
            'login',
            'logout',
            'oauth*',
            'livewire/*',
        ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        if ($this->isMaintenanceModeEnabled()) {
            if ($this->canBypassMaintenance()) {
                return $next($request);
            }

            return $this->maintenanceResponse($request);
        }

        return $next($request);
    }

    private function isExcludedRoute(Request $request): bool
    {
        foreach ($this->excludedRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    private function isMaintenanceModeEnabled(): bool
    {
        try {
            if (! Schema::hasTable('settings')) {
                return false;
            }

            return app(GeneralSettings::class)->maintenanceMode;
        } catch (Exception) {
            return false;
        }
    }

    private function canBypassMaintenance(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    private function maintenanceResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Application is currently under maintenance. Please try again later.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->view('pages.maintenance', [
            'message' => 'Site under maintenance',
            'description' => 'The application is temporarily under maintenance. Please try again later.',

        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
