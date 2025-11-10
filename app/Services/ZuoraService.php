<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ZuoraService
{
    public function listWorkflows(string $clientId, string $clientSecret, string $baseUrl = 'https://rest.zuora.com', int $page = 1, int $pageSize = 12): array
    {
        $token = $this->getAccessToken($clientId, $clientSecret, $baseUrl);

        $response = Http::withToken($token)
            ->get($baseUrl.'/workflows', [
                'page' => $page,
                'page_length' => $pageSize,
            ]);

        if ($response->failed()) {
            $this->throwHttpException($response);
        }

        $data = $response->json();

        // Normalize the response to extract workflow details from the new API structure
        if (isset($data['data']) && is_array($data['data'])) {
            $normalizedWorkflows = [];
            foreach ($data['data'] as $workflow) {
                $normalizedWorkflows[] = $this->normalizeWorkflow($workflow);
            }

            return [
                'data' => $normalizedWorkflows,
                'pagination' => $data['pagination'] ?? null,
            ];
        }

        return $data;
    }

    public function getAccessToken(?string $clientId = null, ?string $clientSecret = null, ?string $baseUrl = null): string
    {
        if (! $clientId || ! $clientSecret) {
            throw new Exception('Zuora credentials must be provided.');
        }

        $cacheKey = 'zuora_access_token_'.md5($clientId.$clientSecret);

        return Cache::remember($cacheKey, 3600, function () use ($clientId, $clientSecret, $baseUrl) { // Cache for 1 hour

            $response = Http::asForm()->post($baseUrl.'/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if ($response->failed()) {
                throw new Exception('Failed to authenticate with Zuora: '.$response->body());
            }

            return $response->json()['access_token'];
        });
    }

    /**
     * Normalize Zuora workflow data to a consistent structure.
     * Maps the complex nested structure to a flattened view-friendly format.
     */
    private function normalizeWorkflow(array $workflow): array
    {
        // Get the active version details (highest priority)
        $activeVersion = $workflow['active_version'] ?? null;

        // Fallback to basic workflow properties
        return [
            'id' => $workflow['id'] ?? null,
            'name' => $workflow['name'] ?? 'Unnamed Workflow',
            'description' => $workflow['description'] ?? '',
            'state' => $workflow['status'] ?? 'Unknown',
            'status' => $workflow['status'] ?? 'Unknown',
            'type' => $activeVersion['type'] ?? $workflow['type'] ?? 'Workflow::Setup',
            'version' => $activeVersion['version'] ?? 'N/A',
            'created_on' => $workflow['createdAt'] ?? null,
            'updated_on' => $workflow['updatedAt'] ?? null,
            'timezone' => $workflow['timezone'] ?? null,
            'calloutTrigger' => $workflow['calloutTrigger'] ?? false,
            'ondemandTrigger' => $workflow['ondemandTrigger'] ?? false,
            'scheduledTrigger' => $workflow['scheduledTrigger'] ?? false,
            'priority' => $activeVersion['priority'] ?? null,
            'activeVersion' => $activeVersion,
            'inactiveVersions' => $workflow['latest_inactive_verisons'] ?? [],
        ];
    }

    public function downloadWorkflow(string $clientId, string $clientSecret, string $baseUrl, string $workflowId): array
    {
        $token = $this->getAccessToken($clientId, $clientSecret, $baseUrl);

        $response = Http::withToken($token)
            ->get($baseUrl."/workflows/{$workflowId}/export");

        if ($response->failed()) {
            $this->throwHttpException($response);
        }

        return $response->json();
    }

    /**
     * Extract error message from failed HTTP response.
     * Attempts multiple error keys before falling back to raw body.
     */
    private function extractErrorMessage(array $errorJson, string $errorBody): string
    {
        if (isset($errorJson['message'])) {
            return $errorJson['message'];
        }

        if (isset($errorJson['error'])) {
            return $errorJson['error'];
        }

        if (isset($errorJson['error_description'])) {
            return $errorJson['error_description'];
        }

        return $errorBody;
    }

    /**
     * Throw formatted exception from failed HTTP response.
     */
    private function throwHttpException($response): void
    {
        $statusCode = $response->status();
        $errorBody = $response->body();
        $errorJson = $response->json() ?? [];

        $message = $this->extractErrorMessage($errorJson, $errorBody);
        $errorMessage = "HTTP {$statusCode}: {$message}";

        throw new Exception($errorMessage);
    }
}
