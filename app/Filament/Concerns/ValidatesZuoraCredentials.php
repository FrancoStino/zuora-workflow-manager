<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Services\ZuoraService;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

trait ValidatesZuoraCredentials
{
    /**
     * Validate Zuora credentials and show error notification if invalid.
     * Calls $this->halt() to stop the save operation on failure.
     *
     * @throws Halt
     */
    protected function validateZuoraCredentialsOrHalt(
        string $clientId,
        string $clientSecret,
        string $baseUrl,
    ): void {
        $zuoraService = app(ZuoraService::class);
        $result = $zuoraService->validateCredentials($clientId,
            $clientSecret, $baseUrl);

        if (! $result['valid']) {
            Notification::make()
                ->title('Invalid Zuora credentials')
                ->body($result['error'] ??
                    'Impossible to authenticate with Zuora. Check Client ID, Client Secret and Base URL.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
