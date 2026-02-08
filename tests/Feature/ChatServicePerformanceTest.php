<?php

namespace Tests\Feature;

use App\Models\ChatThread;
use App\Models\User;
use App\Services\LaragentChatService;
use App\Services\NeuronChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatServicePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private ChatThread $thread;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->thread = ChatThread::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_neuron_service_latency(): void
    {
        config(['app.ai_provider' => 'neuron']);
        
        $startTime = microtime(true);
        
        $service = app(NeuronChatService::class);
        $response = $service->ask($this->thread, 'Quanti task ci sono nel database?');
        
        $latency = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(5000, $latency, 'Neuron service latency should be < 5000ms');
        $this->assertNotNull($response);
        $this->assertEquals('assistant', $response->role);
        
        dump("Neuron latency: " . round($latency, 2) . "ms");
    }

    public function test_laragent_service_latency(): void
    {
        config(['app.ai_provider' => 'laragent']);
        
        $startTime = microtime(true);
        
        $service = app(LaragentChatService::class);
        $response = $service->ask($this->thread, 'Quanti task ci sono nel database?');
        
        $latency = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(6000, $latency, 'Laragent service latency should be < 6000ms (+20% of neuron baseline 5000ms)');
        $this->assertNotNull($response);
        $this->assertEquals('assistant', $response->role);
        
        dump("Laragent latency: " . round($latency, 2) . "ms");
    }

    public function test_performance_comparison(): void
    {
        $neuronLatencies = [];
        $laragentLatencies = [];

        for ($i = 0; $i < 5; $i++) {
            config(['app.ai_provider' => 'neuron']);
            $startTime = microtime(true);
            app(NeuronChatService::class)->ask($this->thread, "Test query {$i}");
            $neuronLatencies[] = (microtime(true) - $startTime) * 1000;

            config(['app.ai_provider' => 'laragent']);
            $startTime = microtime(true);
            app(LaragentChatService::class)->ask($this->thread, "Test query {$i}");
            $laragentLatencies[] = (microtime(true) - $startTime) * 1000;
        }

        $neuronAvg = array_sum($neuronLatencies) / count($neuronLatencies);
        $laragentAvg = array_sum($laragentLatencies) / count($laragentLatencies);
        $percentageDiff = (($laragentAvg - $neuronAvg) / $neuronAvg) * 100;

        $this->assertLessThanOrEqual(20, $percentageDiff, 
            "Laragent should be within +20% of neuron baseline. Actual: +{$percentageDiff}%");

        dump([
            'neuron_avg_ms' => round($neuronAvg, 2),
            'laragent_avg_ms' => round($laragentAvg, 2),
            'difference_percent' => round($percentageDiff, 2),
        ]);
    }
}
