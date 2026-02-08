<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ModelsDevService
{
    private const string API_URL = 'https://models.dev/api.json';

    private const int CACHE_TTL_HOURS = 24;

    private const string CACHE_KEY = 'models_dev_api';

    /**
     * Get provider options for Filament Select.
     */
    public function getProviderOptions(): array
    {
        return $this
            ->getProviders()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get all providers with their models dynamically from models.dev API.
     */
    public function getProviders(): Collection
    {
        $data = $this->fetchData();

        return collect($data)
            ->map(function (array $provider, string $providerId) {
                // Skip providers without models
                if (empty($provider['models'])) {
                    return null;
                }

                // Filter to only chat-capable models
                $chatModels = $this->filterChatModels($provider['models']);

                // Skip providers with no chat models
                if (empty($chatModels)) {
                    return null;
                }

                return [
                    'id' => $providerId,
                    'name' => $provider['name'] ?? $providerId,
                    'api' => $provider['api'] ?? null,
                    'doc' => $provider['doc'] ?? null,
                    'env' => $provider['env'] ?? [],
                    'models' => $chatModels,
                ];
            })
            ->filter()
            ->sortBy('name')
            ->values();
    }

    /**
     * Fetch and cache API data.
     */
    private function fetchData(): array
    {
        return Cache::remember(self::CACHE_KEY,
            now()->addHours(self::CACHE_TTL_HOURS), function () {
                try {
                    $response = Http::timeout(30)->get(self::API_URL);

                    if ($response->successful()) {
                        return $response->json() ?? [];
                    }

                    Log::warning('ModelsDevService: Failed to fetch models.dev API',
                        [
                            'status' => $response->status(),
                        ]);

                    return [];
                } catch (Exception $e) {
                    Log::error('ModelsDevService: Exception fetching models.dev API',
                        [
                            'error' => $e->getMessage(),
                        ]);

                    return [];
                }
            });
    }

    /**
     * Filter models to only include chat-capable models.
     */
    private function filterChatModels(array $models): array
    {
        return collect($models)
            ->filter(function (array $model) {
                // Must support text input/output
                $inputModalities = $model['modalities']['input'] ?? [];
                $outputModalities = $model['modalities']['output'] ?? [];

                if (! in_array('text', $inputModalities)
                    || ! in_array('text', $outputModalities)
                ) {
                    return false;
                }

                // Exclude embedding models
                $family = $model['family'] ?? '';
                if (str_contains(strtolower($family), 'embedding')) {
                    return false;
                }

                // Exclude audio-only models (whisper, tts, etc.)
                $id = strtolower($model['id'] ?? '');
                if (str_contains($id, 'whisper') || str_contains($id, 'tts')
                    || str_contains($id, 'dall-e')
                ) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn ($model) => $model['release_date'] ?? '1970-01-01')
            ->values()
            ->toArray();
    }

    /**
     * Get model options for Filament Select.
     */
    public function getModelOptions(string $providerId): array
    {
        return $this
            ->getModelsForProvider($providerId)
            ->mapWithKeys(function (array $model) {
                $label = $model['name'];

                // Add context info if available
                if (isset($model['limit']['context'])) {
                    $contextK = round($model['limit']['context'] / 1000);
                    $label .= " ({$contextK}K context)";
                }

                return [$model['id'] => $label];
            })
            ->toArray();
    }

    /**
     * Get models for a specific provider.
     */
    public function getModelsForProvider(string $providerId): Collection
    {
        $provider = $this->getProvider($providerId);

        if (! $provider) {
            return collect();
        }

        return collect($provider['models']);
    }

    /**
     * Get a specific provider's data.
     */
    public function getProvider(string $providerId): ?array
    {
        $providers = $this->getProviders();

        return $providers->firstWhere('id', $providerId);
    }

    /**
     * Get the API endpoint for a provider.
     */
    public function getApiEndpoint(string $providerId): ?string
    {
        $provider = $this->getProvider($providerId);

        return $provider['api'] ?? null;
    }

    /**
     * Clear the cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
