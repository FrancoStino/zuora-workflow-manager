# Laravel Service Class Template

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\{ServiceException};
use App\Models\{ModelName};
use Illuminate\Support\Facades\{Log, Cache};
use Illuminate\Database\Eloquent\Collection;

class {ServiceName}
{
    public function __construct(
        private {Dependency1} $dependency1,
        private {Dependency2} $dependency2
    ) {}

    /**
     * {Service description}
     *
     * @param {ParameterType} $parameter {Parameter description}
     * @return {ReturnType} {Return description}
     * @throws {ExceptionType} {Exception description}
     */
    public function {methodName}({ParameterType} $parameter): {ReturnType}
    {
        try {
            // Validate input
            $this->validate{ValidationName}($parameter);

            // Check cache first
            $cacheKey = $this->getCacheKey($parameter);
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                return $cached;
            }

            // Execute business logic
            $result = $this->execute{BusinessLogic}($parameter);

            // Cache result
            Cache::put($cacheKey, $result, now()->addMinutes(30));

            // Log success
            Log::info('{Service} operation completed', [
                'operation' => '{methodName}',
                'parameter_id' => $parameter->id ?? null,
                'result_count' => is_countable($result) ? count($result) : 1,
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('{Service} operation failed', [
                'operation' => '{methodName}',
                'parameter_id' => $parameter->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException(
                "Failed to {operation description}: {$e->getMessage()}"
            );
        }
    }

    /**
     * Validate input parameters
     */
    private function validate{ValidationName}({ParameterType} $parameter): void
    {
        if (!$parameter) {
            throw new InvalidArgumentException('Parameter cannot be null');
        }

        // Add specific validation logic here
    }

    /**
     * Execute main business logic
     */
    private function execute{BusinessLogic}({ParameterType} $parameter): {ReturnType}
    {
        // Implement core business logic here
        // Use dependencies injected in constructor
        // Return appropriate result type
    }

    /**
     * Generate cache key for operation
     */
    private function getCacheKey({ParameterType} $parameter): string
    {
        return '{service_name}_' . md5(json_encode([
            'operation' => '{methodName}',
            'parameter_id' => $parameter->id ?? null,
            'parameter_hash' => md5(serialize($parameter)),
        ]));
    }

    /**
     * Clear cache for specific parameter
     */
    public function clearCache({ParameterType} $parameter): void
    {
        $cacheKey = $this->getCacheKey($parameter);
        Cache::forget($cacheKey);
        
        Log::info('Cache cleared', [
            'service' => '{ServiceName}',
            'operation' => '{methodName}',
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Batch operation for multiple parameters
     */
    public function batch{OperationName}(Collection $parameters): Collection
    {
        $results = collect();

        foreach ($parameters as $parameter) {
            try {
                $result = $this->{methodName}($parameter);
                $results->push([
                    'parameter' => $parameter,
                    'result' => $result,
                    'success' => true,
                ]);
            } catch (Exception $e) {
                $results->push([
                    'parameter' => $parameter,
                    'result' => null,
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
```

## Usage Examples

### Basic Service Implementation
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\WorkflowSyncException;
use App\Models\Customer;
use App\Services\ZuoraService;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class WorkflowSyncService
{
    public function __construct(
        private ZuoraService $zuoraService,
        private WorkflowRepository $workflowRepository
    ) {}

    public function syncCustomerWorkflows(Customer $customer): SyncResult
    {
        try {
            $this->validateCustomer($customer);

            $workflows = $this->zuoraService->listWorkflows(
                $customer->client_id,
                $customer->client_secret,
                $customer->base_url
            );

            $result = $this->workflowRepository->upsertWorkflows(
                $customer->id,
                $workflows['data']
            );

            $customer->update(['last_synced_at' => now()]);

            Log::info('Customer workflows synced successfully', [
                'customer_id' => $customer->id,
                'workflows_count' => $result->getTotal(),
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Customer workflow sync failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            throw new WorkflowSyncException(
                "Failed to sync workflows for customer {$customer->id}: {$e->getMessage()}"
            );
        }
    }

    private function validateCustomer(Customer $customer): void
    {
        if (!$customer->client_id || !$customer->client_secret) {
            throw new InvalidArgumentException('Customer credentials are missing');
        }
    }
}
```

### Service with Caching
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Cache;

class CustomerAnalyticsService
{
    public function getCustomerStats(Customer $customer): array
    {
        $cacheKey = "customer_stats_{$customer->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($customer) {
            return [
                'total_workflows' => $customer->workflows()->count(),
                'active_workflows' => $customer->workflows()->active()->count(),
                'last_sync' => $customer->last_synced_at,
                'sync_health' => $this->calculateSyncHealth($customer),
            ];
        });
    }

    private function calculateSyncHealth(Customer $customer): float
    {
        // Implement health calculation logic
        return 0.95; // Example
    }
}
```

## Best Practices

### Dependency Injection
- Use constructor injection for dependencies
- Type-hint all dependencies
- Use Laravel's service container for automatic resolution
- Avoid service location pattern

### Error Handling
- Create custom exception classes for domain errors
- Log detailed error information with context
- Provide meaningful error messages
- Handle exceptions gracefully

### Caching Strategy
- Cache expensive operations
- Use appropriate cache keys
- Set reasonable expiration times
- Provide cache invalidation methods

### Performance
- Use database transactions for data consistency
- Implement batch operations for bulk processing
- Optimize database queries with proper indexing
- Monitor service performance metrics

### Testing
- Write unit tests for all public methods
- Mock external dependencies
- Test error scenarios and edge cases
- Use proper test data factories