<?php

namespace Tests\Feature;

use App\Jobs\SyncCustomersJob;
use App\Models\Customer;
use App\Models\Workflow;
use App\Services\WorkflowSyncService;
use App\Services\ZuoraService;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class SyncWorkflowsTest extends TestCase
{
    private const WORKFLOW_NAME_1 = 'Workflow 1';

    private MockInterface $zuoraServiceMock;

    public function test_sync_customer_workflows_creates_new_workflows(): void
    {
        $customer = Customer::factory()->create();

        // Mock della risposta Zuora API
        $this->zuoraServiceMock
            ->shouldReceive('listWorkflows')
            ->once()
            ->with(
                $customer->zuora_client_id,
                $customer->zuora_client_secret,
                $customer->zuora_base_url,
                1,
                50
            )
            ->andReturn([
                'data' => [
                    [
                        'id' => 'wf-001',
                        'name' => self::WORKFLOW_NAME_1,
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

        $this->assertEquals(2, $stats['created']);
        $this->assertEquals(0, $stats['updated']);
        $this->assertEquals(2, $stats['total']);

        $this->assertEquals(2, $customer->workflows()->count());
        $this->assertTrue(Workflow::where('zuora_id', 'wf-001')->exists());
        $this->assertTrue(Workflow::where('zuora_id', 'wf-002')->exists());
    }

    public function test_sync_customer_workflows_handles_pagination(): void
    {
        $customer = Customer::factory()->create();

        // Prima pagina
        $this->zuoraServiceMock
            ->shouldReceive('listWorkflows')
            ->once()
            ->with($customer->zuora_client_id, $customer->zuora_client_secret, $customer->zuora_base_url, 1, 50)
            ->andReturn([
                'data' => [
                    ['id' => 'wf-001', 'name' => self::WORKFLOW_NAME_1, 'state' => 'Active', 'createdAt' => now(), 'updatedAt' => now()],
                ],
                'pagination' => ['next_page' => 2],
            ]);

        // Seconda pagina
        $this->zuoraServiceMock
            ->shouldReceive('listWorkflows')
            ->once()
            ->with($customer->zuora_client_id, $customer->zuora_client_secret, $customer->zuora_base_url, 2, 50)
            ->andReturn([
                'data' => [
                    ['id' => 'wf-002', 'name' => 'Workflow 2', 'state' => 'Inactive', 'createdAt' => now(), 'updatedAt' => now()],
                ],
                'pagination' => ['hasMore' => false],
            ]);

        $service = new WorkflowSyncService($this->zuoraServiceMock);
        $stats = $service->syncCustomerWorkflows($customer);

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(2, $customer->workflows()->count());
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

        $this->assertEquals(1, $stats['deleted']);
        $this->assertFalse(Workflow::where('zuora_id', 'wf-old')->exists());
        $this->assertTrue(Workflow::where('zuora_id', 'wf-new')->exists());
    }

    public function test_sync_customer_workflows_job_is_queued(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create();
        SyncCustomersJob::dispatch($customer);

        Queue::assertPushed(SyncCustomersJob::class, function ($job) use ($customer) {
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
                    ['id' => 'wf-001', 'name' => self::WORKFLOW_NAME_1, 'state' => 'Active', 'createdAt' => now(), 'updatedAt' => now()],
                ],
                'pagination' => ['hasMore' => false],
            ]);

        $service = new WorkflowSyncService($this->zuoraServiceMock);
        $service->syncCustomerWorkflows($customer);

        $workflow = Workflow::where('zuora_id', 'wf-001')->first();
        $this->assertNotNull($workflow->last_synced_at);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->zuoraServiceMock = $this->mock(ZuoraService::class);
    }
}
