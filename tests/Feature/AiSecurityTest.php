<?php

namespace Tests\Feature;

use App\Agents\DataAnalystAgentLaragent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected DataAnalystAgentLaragent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        // Pass a non-numeric key to avoid DB message loading logic
        $this->agent = new DataAnalystAgentLaragent('test-agent-key');
    }

    public function test_blocks_insert(): void
    {
        // Mock the error log for the blocked operation
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'AI Security: Blocked write operation in executeQuery' &&
                       str_contains($context['query'], 'INSERT');
            });

        // Allow other logs to pass through (e.g. from configureDynamicProvider)
        Log::shouldReceive('debug')->andReturnNull();
        Log::shouldReceive('info')->andReturnNull();

        $result = $this->agent->executeQuery("INSERT INTO tasks (name) VALUES ('hack')");
        
        $this->assertIsString($result);
        $this->assertStringContainsString('query was rejected for security reasons', $result);
    }

    public function test_blocks_update(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'AI Security: Blocked write operation in executeQuery' &&
                       str_contains($context['query'], 'UPDATE');
            });
            
        Log::shouldReceive('debug')->andReturnNull();
        Log::shouldReceive('info')->andReturnNull();

        $result = $this->agent->executeQuery("UPDATE tasks SET name = 'hacked' WHERE id = 1");

        $this->assertIsString($result);
        $this->assertStringContainsString('query was rejected for security reasons', $result);
    }

    public function test_blocks_delete(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'AI Security: Blocked write operation in executeQuery' &&
                       str_contains($context['query'], 'DELETE');
            });
            
        Log::shouldReceive('debug')->andReturnNull();
        Log::shouldReceive('info')->andReturnNull();

        $result = $this->agent->executeQuery('DELETE FROM tasks WHERE id = 1');

        $this->assertIsString($result);
        $this->assertStringContainsString('query was rejected for security reasons', $result);
    }

    public function test_allows_select(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'AI Query Executed';
            });
            
        Log::shouldReceive('debug')->andReturnNull();

        // Create a dummy table and insert data directly via DB facade (bypassing agent security)
        DB::statement('CREATE TABLE test_ai_items (id INT, name VARCHAR(255))');
        DB::table('test_ai_items')->insert(['id' => 1, 'name' => 'test_value']);

        $result = $this->agent->executeQuery('SELECT * FROM test_ai_items');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('test_value', $result[0]['name']);
    }
}
