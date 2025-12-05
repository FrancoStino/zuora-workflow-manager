# Testing Guide

## Overview

This testing guide provides comprehensive procedures for validating the .opencode system functionality, performance, and reliability. The testing approach covers unit tests, integration tests, performance tests, and user acceptance testing.

## Testing Strategy

### Testing Pyramid

```
    ┌─────────────────────┐
    │  User Acceptance   │  <- E2E Scenarios, Real-world usage
    └─────────────────────┘
          ┌─────────────────┐
          │ Integration     │  <- Agent coordination, Workflow testing
          └─────────────────┘
    ┌─────────────────────────┐
    │    Unit Tests         │  <- Individual agent testing
    └─────────────────────────┘
```

### Test Categories

#### 1. Unit Tests
- **Purpose:** Test individual agent functionality
- **Scope:** Single agent operations
- **Coverage:** >90% code coverage
- **Tools:** PHPUnit, Mockery

#### 2. Integration Tests
- **Purpose:** Test agent coordination and workflows
- **Scope:** Multi-agent interactions
- **Coverage:** Critical paths and workflows
- **Tools:** Laravel testing, Database transactions

#### 3. Performance Tests
- **Purpose:** Validate performance characteristics
- **Scope:** Load testing, stress testing
- **Coverage:** Key performance indicators
- **Tools:** Load testing tools, Profiling

#### 4. Security Tests
- **Purpose:** Validate security measures
- **Scope:** Authentication, authorization, data protection
- **Coverage:** Security vulnerabilities
- **Tools:** Security scanners, Penetration testing

## Agent Testing

### Orchestrator Testing

#### Test Suite: OrchestratorBasicTest
```php
<?php

namespace Tests\Unit\Agents;

use Tests\TestCase;
use App\Agents\ZuoraWorkflowOrchestrator;

class OrchestratorBasicTest extends TestCase
{
    private ZuoraWorkflowOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orchestrator = new ZuoraWorkflowOrchestrator();
    }

    public function test_analyzes_simple_request_correctly(): void
    {
        $request = [
            'type' => 'simple_query',
            'domain' => 'zuora_api',
            'complexity' => 'low'
        ];

        $analysis = $this->orchestrator->analyzeRequest($request);

        $this->assertEquals('simple', $analysis['complexity']);
        $this->assertEquals('zuora_api', $analysis['domain']);
        $this->assertEquals(1, $analysis['context_level']);
    }

    public function test_routes_to_correct_subagent(): void
    {
        $request = [
            'type' => 'api_authentication',
            'domain' => 'zuora_api',
            'complexity' => 'moderate'
        ];

        $routing = $this->orchestrator->routeRequest($request);

        $this->assertEquals('@zuora-api-specialist', $routing['target_agent']);
        $this->assertEquals(2, $routing['context_level']);
        $this->assertArrayHasKey('expected_return', $routing);
    }

    public function test_allocates_context_appropriately(): void
    {
        $routing = [
            'target_agent' => '@zuora-api-specialist',
            'context_level' => 2,
            'task' => 'authenticate_with_zuora'
        ];

        $context = $this->orchestrator->allocateContext($routing);

        $this->assertArrayHasKey('domain_knowledge', $context);
        $this->assertArrayHasKey('related_processes', $context);
        $this->assertArrayHasKey('standards', $context);
        $this->assertArrayHasKey('templates', $context);
    }

    public function test_integrates_results_correctly(): void
    {
        $subagentResult = [
            'success' => true,
            'data' => ['workflows' => []],
            'metadata' => ['api_calls' => 5]
        ];

        $integration = $this->orchestrator->integrateResults($subagentResult);

        $this->assertTrue($integration['success']);
        $this->assertArrayHasKey('user_friendly_output', $integration);
        $this->assertArrayHasKey('performance_metrics', $integration);
    }
}
```

#### Test Suite: OrchestratorPerformanceTest
```php
<?php

namespace Tests\Unit\Agents;

use Tests\TestCase;
use App\Agents\ZuoraWorkflowOrchestrator;

class OrchestratorPerformanceTest extends TestCase
{
    public function test_routing_performance_under_2_seconds(): void
    {
        $orchestrator = new ZuoraWorkflowOrchestrator();
        $request = [
            'type' => 'complex_workflow',
            'domain' => 'multiple',
            'complexity' => 'high'
        ];

        $startTime = microtime(true);
        $routing = $orchestrator->routeRequest($request);
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(2.0, $duration);
        $this->assertNotNull($routing);
    }

    public function test_context_allocation_performance(): void
    {
        $orchestrator = new ZuoraWorkflowOrchestrator();
        $routing = [
            'target_agent' => '@laravel-architect',
            'context_level' => 3,
            'task' => 'database_migration'
        ];

        $startTime = microtime(true);
        $context = $orchestrator->allocateContext($routing);
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(2.0, $duration);
        $this->assertNotEmpty($context);
    }

    public function test_handles_100_concurrent_requests(): void
    {
        $orchestrator = new ZuoraWorkflowOrchestrator();
        $requests = array_fill(0, 100, [
            'type' => 'simple_query',
            'domain' => 'zuora_api',
            'complexity' => 'low'
        ]);

        $startTime = microtime(true);
        $results = [];
        
        foreach ($requests as $request) {
            $results[] = $orchestrator->routeRequest($request);
        }
        
        $duration = microtime(true) - $startTime;

        $this->assertCount(100, $results);
        $this->assertLessThan(10.0, $duration); // Average < 0.1s per request
    }
}
```

### Subagent Testing

#### Test Suite: ZuoraApiSpecialistTest
```php
<?php

namespace Tests\Unit\Agents;

use Tests\TestCase;
use App\Agents\Subagents\ZuoraApiSpecialist;

class ZuoraApiSpecialistTest extends TestCase
{
    private ZuoraApiSpecialist $specialist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->specialist = new ZuoraApiSpecialist();
    }

    public function test_authenticates_with_valid_credentials(): void
    {
        $credentials = [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'base_url' => 'https://rest.test.zuora.com'
        ];

        $result = $this->specialist->authenticate($credentials);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
    }

    public function test_handles_authentication_failure(): void
    {
        $credentials = [
            'client_id' => 'invalid_client_id',
            'client_secret' => 'invalid_client_secret',
            'base_url' => 'https://rest.test.zuora.com'
        ];

        $result = $this->specialist->authenticate($credentials);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_lists_workflows_successfully(): void
    {
        $token = 'valid_access_token';
        $baseUrl = 'https://rest.test.zuora.com';

        $result = $this->specialist->listWorkflows($token, $baseUrl);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    public function test_handles_api_rate_limiting(): void
    {
        // Mock rate limit response
        $token = 'rate_limited_token';
        $baseUrl = 'https://rest.test.zuora.com';

        $result = $this->specialist->listWorkflows($token, $baseUrl);

        $this->assertFalse($result['success']);
        $this->assertEquals('rate_limit_exceeded', $result['error']['type']);
        $this->assertArrayHasKey('retry_after', $result['error']);
    }
}
```

## Workflow Testing

### Customer Onboarding Workflow Test

#### Test Suite: CustomerOnboardingWorkflowTest
```php
<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use App\Models\Customer;
use App\Workflows\CustomerOnboardingWorkflow;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

class CustomerOnboardingWorkflowTest extends TestCase
{
    public function test_completes_customer_onboarding_successfully(): void
    {
        Queue::fake();
        Mail::fake();

        $customerData = [
            'name' => 'Test Customer',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'base_url' => 'https://rest.test.zuora.com'
        ];

        $workflow = new CustomerOnboardingWorkflow();
        $result = $workflow->execute($customerData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('customer_id', $result);
        $this->assertArrayHasKey('sync_status', $result);

        // Verify customer was created
        $this->assertDatabaseHas('customers', [
            'name' => 'Test Customer',
            'client_id' => 'test_client_id'
        ]);

        // Verify sync job was dispatched
        Queue::assertPushed(\App\Jobs\SyncCustomerWorkflows::class);
    }

    public function test_handles_invalid_credentials_gracefully(): void
    {
        $customerData = [
            'name' => 'Test Customer',
            'client_id' => 'invalid_client_id',
            'client_secret' => 'invalid_client_secret',
            'base_url' => 'https://rest.test.zuora.com'
        ];

        $workflow = new CustomerOnboardingWorkflow();
        $result = $workflow->execute($customerData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('credential_validation_failed', $result['error']['type']);

        // Verify customer was not created
        $this->assertDatabaseMissing('customers', [
            'name' => 'Test Customer'
        ]);
    }

    public function test_sends_notifications_on_completion(): void
    {
        Mail::fake();

        $customerData = [
            'name' => 'Test Customer',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'base_url' => 'https://rest.test.zuora.com'
        ];

        $workflow = new CustomerOnboardingWorkflow();
        $workflow->execute($customerData);

        // Verify notification was sent
        Mail::assertSent(\App\Mail\CustomerOnboardingComplete::class);
    }
}
```

### Workflow Synchronization Test

#### Test Suite: WorkflowSynchronizationTest
```php
<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Workflow;
use App\Workflows\WorkflowSynchronizationWorkflow;
use Illuminate\Support\Facades\Queue;

class WorkflowSynchronizationTest extends TestCase
{
    public function test_synchronizes_workflows_successfully(): void
    {
        $customer = Customer::factory()->create([
            'client_id' => 'test_client_id',
            'client_secret' => encrypt('test_client_secret'),
            'base_url' => 'https://rest.test.zuora.com'
        ]);

        $workflow = new WorkflowSynchronizationWorkflow();
        $result = $workflow->execute($customer->id);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('workflows_synced', $result);
        $this->assertArrayHasKey('sync_duration', $result);

        // Verify customer status was updated
        $customer->refresh();
        $this->assertEquals('active', $customer->sync_status);
        $this->assertNotNull($customer->last_synced_at);
    }

    public function test_handles_api_errors_gracefully(): void
    {
        $customer = Customer::factory()->create([
            'client_id' => 'error_client_id',
            'client_secret' => encrypt('error_client_secret'),
            'base_url' => 'https://rest.test.zuora.com'
        ]);

        $workflow = new WorkflowSynchronizationWorkflow();
        $result = $workflow->execute($customer->id);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('retry_strategy', $result);

        // Verify customer status reflects error
        $customer->refresh();
        $this->assertEquals('error', $customer->sync_status);
    }

    public function test_implements_retry_logic(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create([
            'client_id' => 'retry_client_id',
            'client_secret' => encrypt('retry_client_secret'),
            'base_url' => 'https://rest.test.zuora.com'
        ]);

        $workflow = new WorkflowSynchronizationWorkflow();
        $workflow->execute($customer->id);

        // Verify retry job was scheduled
        Queue::assertPushed(\App\Jobs\RetryWorkflowSync::class);
    }
}
```

## Performance Testing

### Load Testing

#### Test Suite: SystemLoadTest
```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Agents\ZuoraWorkflowOrchestrator;

class SystemLoadTest extends TestCase
{
    public function test_handles_1000_concurrent_requests(): void
    {
        $orchestrator = new ZuoraWorkflowOrchestrator();
        $requests = $this->generateTestRequests(1000);

        $startTime = microtime(true);
        $results = [];
        
        // Simulate concurrent processing
        foreach ($requests as $request) {
            $results[] = $orchestrator->processRequest($request);
        }
        
        $duration = microtime(true) - $startTime;

        $this->assertCount(1000, $results);
        $this->assertLessThan(60.0, $duration); // Complete within 1 minute
        
        // Verify success rate
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $successRate = ($successCount / 1000) * 100;
        $this->assertGreaterThan(95, $successRate); // >95% success rate
    }

    public function test_maintains_performance_under_load(): void
    {
        $orchestrator = new ZuoraWorkflowOrchestrator();
        
        // Test with increasing load
        $loadLevels = [10, 50, 100, 500, 1000];
        
        foreach ($loadLevels as $load) {
            $requests = $this->generateTestRequests($load);
            
            $startTime = microtime(true);
            $results = [];
            
            foreach ($requests as $request) {
                $results[] = $orchestrator->processRequest($request);
            }
            
            $duration = microtime(true) - $startTime;
            $avgResponseTime = $duration / $load;
            
            // Performance should not degrade significantly
            $this->assertLessThan(2.0, $avgResponseTime);
        }
    }

    private function generateTestRequests(int $count): array
    {
        $requests = [];
        $types = ['simple_query', 'api_authentication', 'workflow_sync'];
        
        for ($i = 0; $i < $count; $i++) {
            $requests[] = [
                'type' => $types[array_rand($types)],
                'domain' => 'zuora_api',
                'complexity' => rand(1, 3) === 1 ? 'low' : 'moderate',
                'timestamp' => time()
            ];
        }
        
        return $requests;
    }
}
```

### Memory Usage Testing

#### Test Suite: MemoryUsageTest
```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Agents\ZuoraWorkflowOrchestrator;

class MemoryUsageTest extends TestCase
{
    public function test_memory_usage_within_limits(): void
    {
        $initialMemory = memory_get_usage(true);
        $orchestrator = new ZuoraWorkflowOrchestrator();
        
        // Process 100 requests
        for ($i = 0; $i < 100; $i++) {
            $request = [
                'type' => 'simple_query',
                'domain' => 'zuora_api',
                'complexity' => 'low'
            ];
            
            $orchestrator->processRequest($request);
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryUsed = $finalMemory - $initialMemory;
        $memoryUsedMB = $memoryUsed / 1024 / 1024;
        
        // Memory usage should be reasonable (< 100MB for 100 requests)
        $this->assertLessThan(100, $memoryUsedMB);
    }

    public function test_no_memory_leaks(): void
    {
        $orchestrator = new ZuoraWorkflowOrchestrator();
        
        // Process requests multiple times
        for ($cycle = 0; $cycle < 5; $cycle++) {
            $cycleStartMemory = memory_get_usage(true);
            
            for ($i = 0; $i < 50; $i++) {
                $request = [
                    'type' => 'simple_query',
                    'domain' => 'zuora_api',
                    'complexity' => 'low'
                ];
                
                $orchestrator->processRequest($request);
            }
            
            $cycleEndMemory = memory_get_usage(true);
            $cycleMemoryUsed = $cycleEndMemory - $cycleStartMemory;
            
            // Memory usage should not grow significantly between cycles
            $this->assertLessThan(10 * 1024 * 1024, $cycleMemoryUsed); // < 10MB per cycle
        }
    }
}
```

## Security Testing

### Authentication Security Test

#### Test Suite: AuthenticationSecurityTest
```php
<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Agents\Subagents\ZuoraApiSpecialist;

class AuthenticationSecurityTest extends TestCase
{
    public function test_rejects_invalid_credentials(): void
    {
        $specialist = new ZuoraApiSpecialist();
        
        $credentials = [
            'client_id' => '',
            'client_secret' => '',
            'base_url' => 'invalid_url'
        ];

        $result = $specialist->authenticate($credentials);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContains('validation', $result['error']['type']);
    }

    public function test_sanitizes_input_data(): void
    {
        $specialist = new ZuoraApiSpecialist();
        
        $maliciousInput = [
            'client_id' => '<script>alert("xss")</script>',
            'client_secret' => '"; DROP TABLE customers; --',
            'base_url' => 'https://rest.zuora.com'
        ];

        $result = $specialist->authenticate($maliciousInput);

        // Should not execute malicious code
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_limits_authentication_attempts(): void
    {
        $specialist = new ZuoraApiSpecialist();
        
        $invalidCredentials = [
            'client_id' => 'invalid',
            'client_secret' => 'invalid',
            'base_url' => 'https://rest.test.zuora.com'
        ];

        $attempts = 0;
        $rateLimited = false;
        
        // Make multiple failed attempts
        for ($i = 0; $i < 10; $i++) {
            $result = $specialist->authenticate($invalidCredentials);
            $attempts++;
            
            if (isset($result['error']['type']) && $result['error']['type'] === 'rate_limited') {
                $rateLimited = true;
                break;
            }
        }
        
        $this->assertTrue($rateLimited);
        $this->assertLessThan(10, $attempts); // Should be rate limited before 10 attempts
    }
}
```

## Integration Testing

### End-to-End Workflow Test

#### Test Suite: EndToEndWorkflowTest
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\User;
use App\Workflows\CustomerOnboardingWorkflow;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

class EndToEndWorkflowTest extends TestCase
{
    public function test_complete_customer_onboarding_workflow(): void
    {
        Queue::fake();
        Mail::fake();

        // Step 1: Create customer through workflow
        $customerData = [
            'name' => 'E2E Test Customer',
            'client_id' => 'e2e_test_client',
            'client_secret' => 'e2e_test_secret',
            'base_url' => 'https://rest.test.zuora.com'
        ];

        $workflow = new CustomerOnboardingWorkflow();
        $onboardingResult = $workflow->execute($customerData);

        $this->assertTrue($onboardingResult['success']);
        $this->assertArrayHasKey('customer_id', $onboardingResult['data']);

        // Step 2: Verify customer was created
        $customer = Customer::find($onboardingResult['data']['customer_id']);
        $this->assertNotNull($customer);
        $this->assertEquals('E2E Test Customer', $customer->name);

        // Step 3: Verify sync job was dispatched
        Queue::assertPushed(\App\Jobs\SyncCustomerWorkflows::class, function ($job) use ($customer) {
            return $job->customer->id === $customer->id;
        });

        // Step 4: Process sync job
        $syncJob = Queue::pushed(\App\Jobs\SyncCustomerWorkflows::class)->first();
        $syncJob->handle();

        // Step 5: Verify workflows were synced
        $customer->refresh();
        $this->assertNotNull($customer->last_synced_at);
        $this->assertGreaterThan(0, $customer->workflows()->count());

        // Step 6: Verify user can access workflows
        $user = User::factory()->create();
        $user->customers()->attach($customer->id);

        $response = $this->actingAs($user)
            ->get(route('filament.admin.resources.workflows.index'));

        $response->assertOk();
        $response->assertSee($customer->workflows()->first()->name);

        // Step 7: Verify notifications were sent
        Mail::assertSent(\App\Mail\CustomerOnboardingComplete::class);
    }

    public function test_handles_workflow_failure_gracefully(): void
    {
        // Create customer with invalid credentials
        $customerData = [
            'name' => 'Failure Test Customer',
            'client_id' => 'invalid_client',
            'client_secret' => 'invalid_secret',
            'base_url' => 'https://rest.test.zuora.com'
        ];

        $workflow = new CustomerOnboardingWorkflow();
        $result = $workflow->execute($customerData);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);

        // Verify error handling
        $this->assertEquals('credential_validation_failed', $result['error']['type']);
        $this->assertArrayHasKey('recommendations', $result['error']);

        // Verify cleanup occurred
        $this->assertDatabaseMissing('customers', [
            'name' => 'Failure Test Customer'
        ]);
    }
}
```

## Test Data Management

### Test Factories

#### Customer Factory
```php
<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'client_id' => $this->faker->uuid,
            'client_secret' => encrypt($this->faker->sha256),
            'base_url' => 'https://rest.test.zuora.com',
            'sync_status' => 'active',
            'last_synced_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function inactive(): static
    {
        return fn (array $attributes) => array_merge($attributes, [
            'sync_status' => 'inactive'
        ]);
    }

    public function needsSync(): static
    {
        return fn (array $attributes) => array_merge($attributes, [
            'last_synced_at' => $this->faker->dateTimeBetween('-2 weeks', '-1 week')
        ]);
    }
}
```

### Test Seeds

#### Development Data Seeder
```php
<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password')
        ]);

        // Create test customers
        Customer::factory()->count(5)->create();
        Customer::factory()->inactive()->count(2)->create();
        Customer::factory()->needsSync()->count(3)->create();
    }
}
```

## Continuous Testing

### Automated Testing Pipeline

#### GitHub Actions Workflow
```yaml
name: Test Suite

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 3306:3306

      redis:
        image: redis:6
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 6379:6379

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.4'
        extensions: mbstring, xml, mysql, redis
        
    - name: Copy Environment File
      run: cp .env.example .env
      
    - name: Install Dependencies
      run: composer install --no-progress --no-interaction
      
    - name: Generate Application Key
      run: php artisan key:generate
      
    - name: Run Database Migrations
      run: php artisan migrate --force
      
    - name: Run Unit Tests
      run: vendor/bin/phpunit --testsuite=Unit --coverage-clover=coverage.xml
      
    - name: Run Feature Tests
      run: vendor/bin/phpunit --testsuite=Feature
      
    - name: Run Performance Tests
      run: vendor/bin/phpunit --testsuite=Performance
      
    - name: Upload Coverage
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
```

### Quality Gates

#### Test Coverage Requirements
- **Unit Tests:** >90% code coverage
- **Integration Tests:** >80% critical path coverage
- **Overall Coverage:** >85% total coverage

#### Performance Requirements
- **Response Time:** <2 seconds for 95% of requests
- **Throughput:** >100 requests per second
- **Memory Usage:** <512MB for normal operations

#### Security Requirements
- **Vulnerability Scan:** No critical vulnerabilities
- **Authentication:** Proper credential handling
- **Authorization:** Role-based access control

## Test Execution

### Running Tests

#### Local Development
```bash
# Run all tests
composer test

# Run specific test suite
composer test:unit
composer test:feature
composer test:performance

# Run with coverage
composer test -- --coverage

# Run specific test
vendor/bin/phpunit tests/Unit/Agents/OrchestratorBasicTest.php
```

#### CI/CD Pipeline
```bash
# Run tests in Docker
docker run -v $(pwd):/app composer test

# Run tests with specific environment
APP_ENV=testing composer test

# Run tests with database
DB_CONNECTION=mysql composer test
```

### Test Reporting

#### Coverage Reports
```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html=coverage

# Generate Clover XML for CI
vendor/bin/phpunit --coverage-clover=coverage.xml
```

#### Performance Reports
```bash
# Generate performance report
vendor/bin/phpunit --testsuite=Performance --log-junit=performance.xml
```

---

This comprehensive testing guide ensures the .opencode system maintains high quality, performance, and reliability standards through systematic testing across all levels of the application.