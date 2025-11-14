<?php

namespace Tests\Feature;

use App\Jobs\SyncCustomerWorkflows;
use App\Models\Customer;
use App\Models\Workflow;
use App\Services\WorkflowSyncService;
use App\Services\ZuoraService;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class SyncWorkflowsTest extends TestCase
{
    private MockInterface $zuoraServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zuoraServiceMock = $this->mock(ZuoraService::class);
    }

    public function test_sync_customer_workflows_creates_new_workflows(): void
    {
        $customer = Customer::factory()->create();

        // Mock della risposta Zuora API
        $this->zuoraServiceMock
            ->shouldReceive('listWorkflows')
            ->once()
            ->with(
                $customer->client_id,
                $customer->client_secret,
                $customer->base_url,
                1,
                50
            )
            ->andReturn([
                'data' => [
                    [
                        'id' => 'wf-001',
                        'name' => 'Workflow 1',
                        'description' => 'Test workflow',
                        'state' => 'Active',
                        'createdAt' => now(),
                        'updatedAt' => now(),
                    ],
                    [
                        'id' => 'wf-002',
                        'name' => 'Workflow 2',
                        'state' => 'Inactive',
                        'createdAt' => now(),
                        'updatedAt' => now(),
                    ],
                ],
                'pagination' => ['hasMore' => false],
            ]);

        $service = new WorkflowSyncService($this->zuoraServiceMock);
        $stats = $service->syncCustomerWorkflows($customer);

        expect($stats['created'])->toBe(2);
        expect($stats['updated'])->toBe(0);
        expect($stats['total'])->toBe(2);

        expect($customer->workflows()->count())->toBe(2);
        expect(Workflow::where('zuora_id', 'wf-001')->exists())->toBeTrue();
        expect(Workflow::where('zuora_id', 'wf-002')->exists())->toBeTrue();
    }

    public function test_sync_customer_workflows_handles_pagination(): void
    {
        $customer = Customer::factory()->create();

        // Prima pagina
        $this->zuoraServiceMock
            ->shouldReceive('listWorkflows')
            ->once()
            ->with($customer->client_id, $customer->client_secret, $customer->base_url, 1, 50)
            ->andReturn([
                'data' => [
                    ['id' => 'wf-001', 'name' => 'Workflow 1', 'state' => 'Active', 'createdAt' => now(), 'updatedAt' => now()],
                ],
                'pagination' => ['hasMore' => true],
            ]);

        // Seconda pagina
        $this->zuoraServiceMock
            ->shouldReceive('listWorkflows')
            ->once()
            ->with($customer->client_id, $customer->client_secret, $customer->base_url, 2, 50)
            ->andReturn([
                'data' => [
                    ['id' => 'wf-002', 'name' => 'Workflow 2', 'state' => 'Inactive', 'createdAt' => now(), 'updatedAt' => now()],
                ],
                'pagination' => ['hasMore' => false],
            ]);

        $service = new WorkflowSyncService($this->zuoraServiceMock);
        $stats = $service->syncCustomerWorkflows($customer);

        expect($stats['total'])->toBe(2);
        expect($customer->workflows()->count())->toBe(2);
    }

    public function test_sync_customer_workflows_deletes_stale_workflows(): void
    {
        $customer = Customer::factory()->create();

        // Crea un workflow stale nel DB
        Workflow::create([
            'customer_id' => $customer->id,
            'zuora_id' => 'wf-old',
            'name' => 'Old Workflow',
            'state' => 'Active',
        ]);

        // Mock della risposta Zuora (non include wf-old)
        $this->zuoraServiceMock
            ->shouldReceive('listWorkflows')
            ->once()
            ->andReturn([
                'data' => [
                    ['id' => 'wf-new', 'name' => 'New Workflow', 'state' => 'Active', 'createdAt' => now(), 'updatedAt' => now()],
                ],
                'pagination' => ['hasMore' => false],
            ]);

        $service = new WorkflowSyncService($this->zuoraServiceMock);
        $stats = $service->syncCustomerWorkflows($customer);

        expect($stats['deleted'])->toBe(1);
        expect(Workflow::where('zuora_id', 'wf-old')->exists())->toBeFalse();
        expect(Workflow::where('zuora_id', 'wf-new')->exists())->toBeTrue();
    }

    public function test_sync_customer_workflows_job_is_queued(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create();
        SyncCustomerWorkflows::dispatch($customer);

        Queue::assertPushed(SyncCustomerWorkflows::class, function ($job) use ($customer) {
            return $job->customer->id === $customer->id;
        });
    }

    public function test_sync_customer_workflows_updates_last_synced_at(): void
    {
        $customer = Customer::factory()->create();

        $this->zuoraServiceMock
            ->shouldReceive('listWorkflows')
            ->once()
            ->andReturn([
                'data' => [
                    ['id' => 'wf-001', 'name' => 'Workflow 1', 'state' => 'Active', 'createdAt' => now(), 'updatedAt' => now()],
                ],
                'pagination' => ['hasMore' => false],
            ]);

        $service = new WorkflowSyncService($this->zuoraServiceMock);
        $service->syncCustomerWorkflows($customer);

        $workflow = Workflow::where('zuora_id', 'wf-001')->first();
        expect($workflow->last_synced_at)->not->toBeNull();
    }
}
