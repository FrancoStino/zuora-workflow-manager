# Code Quality Standards

## PHP Standards

### Coding Style
- **PSR-12 Compliance**: Follow PHP Standard Recommendations
- **Strict Types**: Always declare `declare(strict_types=1);`
- **Type Hints**: Use proper return types and parameter types
- **Naming Conventions**: PascalCase for classes, camelCase for methods
- **Documentation**: PHPDoc blocks for all public methods

### Code Structure
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\WorkflowException;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    public function __construct(
        private ZuoraService $zuoraService,
        private WorkflowRepository $repository
    ) {}
    
    /**
     * Synchronize workflows from Zuora API.
     *
     * @param Customer $customer The customer to sync workflows for
     * @return SyncResult The synchronization result
     * @throws WorkflowException When synchronization fails
     */
    public function syncWorkflows(Customer $customer): SyncResult
    {
        try {
            $workflows = $this->zuoraService->listWorkflows(
                $customer->client_id,
                $customer->client_secret,
                $customer->base_url
            );
            
            return $this->repository->upsertWorkflows(
                $customer->id,
                $workflows['data']
            );
        } catch (Exception $e) {
            Log::error('Workflow sync failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
            
            throw new WorkflowException(
                "Failed to sync workflows: {$e->getMessage()}"
            );
        }
    }
}
```

### Error Handling
- **Custom Exceptions**: Create domain-specific exception classes
- **Logging**: Structured logging with context information
- **Graceful Degradation**: Handle failures gracefully
- **User Feedback**: Provide clear error messages to users

## Database Standards

### Migration Patterns
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['workflow_id', 'created_at']);
            $table->index(['customer_id', 'event_type']);
            $table->index('created_at');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('workflow_logs');
    }
};
```

### Model Standards
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = [
        'customer_id',
        'zuora_id',
        'name',
        'description',
        'state',
        'created_on',
        'updated_on',
        'last_synced_at',
        'json_export',
    ];

    protected $casts = [
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'last_synced_at' => 'datetime',
        'json_export' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('state', 'Active');
    }

    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }
}
```

## API Standards

### Service Layer
```php
class ZuoraService
{
    public function listWorkflows(
        string $clientId,
        string $clientSecret,
        string $baseUrl = 'https://rest.zuora.com',
        int $page = 1,
        int $pageSize = 12
    ): array {
        $token = $this->getAccessToken($clientId, $clientSecret, $baseUrl);

        $response = Http::withToken($token)
            ->timeout(30)
            ->retry(3, 1000)
            ->get($baseUrl.'/workflows', [
                'page' => $page,
                'page_length' => $pageSize,
            ]);

        if ($response->failed()) {
            $this->throwHttpException($response);
        }

        return $this->normalizeResponse($response->json());
    }

    private function throwHttpException($response): void
    {
        $statusCode = $response->status();
        $errorBody = $response->body();
        
        throw new ZuoraHttpException($statusCode, $errorBody);
    }
}
```

### Response Formatting
```php
class ApiResponse
{
    public static function success($data = null, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];
    }

    public static function error(string $message, int $code = 400, $errors = null): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

## Testing Standards

### Unit Tests
```php
class WorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private WorkflowService $service;
    private MockInterface $zuoraService;
    private MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->zuoraService = $this->mock(ZuoraService::class);
        $this->repository = $this->mock(WorkflowRepository::class);
        
        $this->service = new WorkflowService(
            $this->zuoraService,
            $this->repository
        );
    }

    public function test_sync_workflows_success(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $workflows = [
            ['id' => 'wf_1', 'name' => 'Test Workflow'],
            ['id' => 'wf_2', 'name' => 'Another Workflow'],
        ];

        $this->zuoraService
            ->shouldReceive('listWorkflows')
            ->once()
            ->with($customer->client_id, $customer->client_secret, $customer->base_url)
            ->andReturn(['data' => $workflows]);

        $this->repository
            ->shouldReceive('upsertWorkflows')
            ->once()
            ->with($customer->id, $workflows)
            ->andReturn(new SyncResult(2, 0));

        // Act
        $result = $this->service->syncWorkflows($customer);

        // Assert
        $this->assertInstanceOf(SyncResult::class, $result);
        $this->assertEquals(2, $result->getInserted());
        $this->assertEquals(0, $result->getUpdated());
    }

    public function test_sync_workflows_handles_api_failure(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $exception = new ZuoraHttpException(500, 'API Error');

        $this->zuoraService
            ->shouldReceive('listWorkflows')
            ->once()
            ->andThrow($exception);

        // Act & Assert
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Failed to sync workflows: API Error');

        $this->service->syncWorkflows($customer);
    }
}
```

### Feature Tests
```php
class WorkflowManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_view_workflows_index(): void
    {
        // Arrange
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        Workflow::factory()->count(5)->create(['customer_id' => $customer->id]);

        // Act
        $response = $this->actingAs($user)
            ->get(route('filament.admin.resources.workflows.index'));

        // Assert
        $response->assertOk();
        $response->assertSee('Workflows');
        $response->assertSee(Workflow::first()->name);
    }

    public function test_user_can_create_workflow(): void
    {
        // Arrange
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $workflowData = [
            'customer_id' => $customer->id,
            'zuora_id' => $this->faker->uuid,
            'name' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'state' => 'Active',
        ];

        // Act
        $response = $this->actingAs($user)
            ->post(route('filament.admin.resources.workflows.store'), $workflowData);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('workflows', [
            'name' => $workflowData['name'],
            'zuora_id' => $workflowData['zuora_id'],
        ]);
    }
}
```

## Frontend Standards

### JavaScript/TypeScript
```typescript
// interfaces/Workflow.ts
export interface Workflow {
    id: number;
    customerId: number;
    zuoraId: string;
    name: string;
    description?: string;
    state: 'Active' | 'Inactive' | 'Draft';
    createdAt: string;
    updatedAt: string;
}

// services/WorkflowService.ts
export class WorkflowService {
    private baseUrl: string;

    constructor(baseUrl: string) {
        this.baseUrl = baseUrl;
    }

    async getWorkflows(customerId: number): Promise<Workflow[]> {
        const response = await fetch(`${this.baseUrl}/api/customers/${customerId}/workflows`);
        
        if (!response.ok) {
            throw new Error(`Failed to fetch workflows: ${response.statusText}`);
        }
        
        return response.json();
    }

    async syncWorkflows(customerId: number): Promise<void> {
        const response = await fetch(`${this.baseUrl}/api/customers/${customerId}/sync`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.getCSRFToken(),
            },
        });

        if (!response.ok) {
            throw new Error(`Failed to sync workflows: ${response.statusText}`);
        }
    }

    private getCSRFToken(): string {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta?.getAttribute('content') || '';
    }
}
```

### CSS Standards
```css
/* components/workflow-card.css */
.workflow-card {
    @apply bg-white rounded-lg shadow-md p-6 border border-gray-200;
    transition: all 0.3s ease;
}

.workflow-card:hover {
    @apply shadow-lg transform -translate-y-1;
}

.workflow-card__header {
    @apply flex justify-between items-start mb-4;
}

.workflow-card__title {
    @apply text-lg font-semibold text-gray-900;
}

.workflow-card__status {
    @apply px-2 py-1 rounded-full text-xs font-medium;
}

.workflow-card__status--active {
    @apply bg-green-100 text-green-800;
}

.workflow-card__status--inactive {
    @apply bg-red-100 text-red-800;
}

.workflow-card__description {
    @apply text-gray-600 text-sm mb-4;
}

.workflow-card__actions {
    @apply flex gap-2;
}
```

## Performance Standards

### Database Optimization
- **Query Optimization**: Use EXPLAIN to analyze queries
- **Eager Loading**: Prevent N+1 query problems
- **Indexing Strategy**: Proper indexes for common queries
- **Connection Pooling**: Reuse database connections
- **Query Caching**: Cache frequently accessed data

### Memory Management
- **Chunked Processing**: Process large datasets in chunks
- **Lazy Loading**: Load data only when needed
- **Resource Cleanup**: Properly release resources
- **Memory Profiling**: Identify memory leaks

### API Performance
- **Response Time**: API responses under 200ms
- **Rate Limiting**: Implement appropriate rate limits
- **Caching Strategy**: Cache API responses when appropriate
- **Compression**: Use gzip compression for responses

## Security Standards

### Authentication & Authorization
- **Password Hashing**: Use bcrypt for password storage
- **Session Management**: Secure session handling
- **CSRF Protection**: Implement CSRF tokens
- **Input Validation**: Validate all user inputs
- **SQL Injection Prevention**: Use parameterized queries

### Data Protection
- **Encryption**: Encrypt sensitive data at rest
- **HTTPS Only**: Use TLS for all communications
- **Data Sanitization**: Sanitize all outputs
- **Access Controls**: Implement proper access controls
- **Audit Logging**: Log all sensitive operations

### API Security
- **Authentication**: JWT or OAuth for API access
- **Rate Limiting**: Prevent API abuse
- **Input Validation**: Validate API inputs
- **Error Handling**: Don't expose sensitive information
- **CORS Configuration**: Proper CORS setup

## Documentation Standards

### Code Documentation
- **PHPDoc**: Document all public methods and classes
- **Inline Comments**: Explain complex logic
- **README Files**: Document setup and usage
- **API Documentation**: Comprehensive API docs
- **Change Logs**: Track all changes

### Process Documentation
- **Deployment Guides**: Step-by-step deployment instructions
- **Troubleshooting Guides**: Common issues and solutions
- **Development Setup**: Local development environment setup
- **Testing Guidelines**: Testing procedures and standards
- **Code Review Guidelines**: Review process and standards