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

    private const string CACHE_KEY = 'models_dev_providers';

    /**
     * Provide provider options keyed by provider ID for select inputs.
     *
     * @return array<string, string> Map of provider ID => provider display name suitable for Filament Select.
     */
    public function getProviderOptions(): array
    {
        return $this
            ->getProviders()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Retrieve providers from models.dev that include at least one chat-capable model.
     *
     * Each returned provider is an associative array with the keys:
     * - `id`: provider identifier
     * - `name`: provider display name (falls back to the id)
     * - `api`: provider API endpoint (if available)
     * - `doc`: provider documentation URL (if available)
     * - `env`: provider environment variables map (if any)
     * - `models`: array of models filtered to include only chat-capable models
     *
     * Providers without models or without any chat-capable models are excluded. The
     * resulting collection is sorted by provider `name`.
     *
     * Results are cached using the file store for CACHE_TTL_HOURS to avoid database
     * column size limits that can corrupt large serialized payloads.
     *
     * @return Collection<array> The collection of provider arrays described above.
     */
    public function getProviders(): Collection
    {
        try {
            $cached = $this->cacheStore()->get(self::CACHE_KEY);

            if (is_array($cached) && ! empty($cached)) {
                return collect($cached);
            }
        } catch (Exception $e) {
            Log::warning('ModelsDevService: Corrupted cache detected, clearing', [
                'error' => $e->getMessage(),
            ]);
            $this->clearCache();
        }

        $data = $this->fetchData();

        if (empty($data)) {
            return collect();
        }

        $providers = collect($data)
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

        // Cache the processed (much smaller) result using file store
        if ($providers->isNotEmpty()) {
            try {
                $this->cacheStore()->put(
                    self::CACHE_KEY,
                    $providers->toArray(),
                    now()->addHours(self::CACHE_TTL_HOURS)
                );
            } catch (Exception $e) {
                Log::warning('ModelsDevService: Failed to write cache', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $providers;
    }

    /**
     * Fetch raw data from the models.dev API without caching.
     *
     * @return array The decoded API data as an associative array, or an empty array on failure.
     */
    private function fetchData(): array
    {
        try {
            $response = Http::timeout(30)->get(self::API_URL);

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            Log::warning('ModelsDevService: Failed to fetch models.dev API', [
                'status' => $response->status(),
            ]);

            return [];
        } catch (Exception $e) {
            Log::error('ModelsDevService: Exception fetching models.dev API', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Filter a list of models to those suitable for text chat usage.
     *
     * Filters out models that do not support text input and output, models whose
     * family indicates embeddings, and models whose id indicates audio/image-only
     * variants (for example whisper, tts, or dall-e). The resulting models are
     * sorted by `release_date` descending.
     *
     * @param  array  $models  Array of model records as returned by the models.dev API.
     * @return array Zero-based array of models that support text chat, sorted by `release_date` descending.
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
     * Build an option map of model IDs to display labels for a given provider.
     *
     * @param  string  $providerId  Provider identifier used to look up models.
     * @return array<string,string> Map where keys are model IDs and values are display labels (model name, with " (NK context)" appended when a context limit is present, e.g. "8K context").
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
     * Retrieve the models registered for a given provider.
     *
     * @param  string  $providerId  The provider identifier.
     * @return \Illuminate\Support\Collection A collection of the provider's models; an empty collection if the provider is not found.
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
     * Retrieve data for the provider identified by the given ID.
     *
     * @param  string  $providerId  The provider identifier to look up.
     * @return array|null The provider's data array if found, or `null` if no provider matches.
     */
    public function getProvider(string $providerId): ?array
    {
        $providers = $this->getProviders();

        return $providers->firstWhere('id', $providerId);
    }

    /**
     * Retrieve the API endpoint URL for the given provider.
     *
     * @param  string  $providerId  The provider identifier.
     * @return string|null The provider's API endpoint URL, or null if not found.
     */
    public function getApiEndpoint(string $providerId): ?string
    {
        $provider = $this->getProvider($providerId);

        return $provider['api'] ?? null;
    }

    /**
     * Clears the cached models.dev processed provider data.
     */
    public function clearCache(): void
    {
        $this->cacheStore()->forget(self::CACHE_KEY);
    }

    /**
     * Get the cache store instance used for models.dev data.
     *
     * Uses the file store to avoid database column size limits (max_allowed_packet)
     * that can silently truncate large serialized payloads and cause unserialize errors.
     */
    private function cacheStore(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::store('file');
    }
}
