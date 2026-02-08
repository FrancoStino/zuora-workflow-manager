<?php

namespace Tests\Feature;

use App\Agents\DataAnalystAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use LarAgent\Core\Contracts\Tool as ToolInterface;
use LarAgent\Core\Contracts\ToolCall as ToolCallInterface;
use Tests\TestCase;

class AiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected DataAnalystAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        // Autenticazione obbligatoria per ChatHistory
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->agent = new DataAnalystAgent('test-agent');
    }

    public function test_blocks_insert(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('AI Security: Blocked write operation', \Mockery::on(function ($context) {
                return str_contains($context['sql'], 'INSERT');
            }));

        $tool = \Mockery::mock(ToolInterface::class);
        $tool->shouldReceive('getName')->andReturn('database_query');

        $toolCall = \Mockery::mock(ToolCallInterface::class);
        $toolCall->shouldReceive('getId')->andReturn('call_123');
        $toolCall->shouldReceive('getArguments')->andReturn(json_encode([
            'query' => 'INSERT INTO tasks (name) VALUES ("hack")',
        ]));

        $hookMethod = new \ReflectionMethod($this->agent, 'beforeToolExecution');
        $hookMethod->setAccessible(true);
        $result = $hookMethod->invoke($this->agent, $tool, $toolCall);

        $this->assertFalse($result);
    }

    public function test_blocks_update(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('AI Security: Blocked write operation', \Mockery::on(function ($context) {
                return str_contains($context['sql'], 'UPDATE');
            }));

        $tool = \Mockery::mock(ToolInterface::class);
        $tool->shouldReceive('getName')->andReturn('database_query');

        $toolCall = \Mockery::mock(ToolCallInterface::class);
        $toolCall->shouldReceive('getId')->andReturn('call_456');
        $toolCall->shouldReceive('getArguments')->andReturn(json_encode([
            'query' => 'UPDATE tasks SET name = "hacked" WHERE id = 1',
        ]));

        $hookMethod = new \ReflectionMethod($this->agent, 'beforeToolExecution');
        $hookMethod->setAccessible(true);
        $result = $hookMethod->invoke($this->agent, $tool, $toolCall);

        $this->assertFalse($result);
    }

    public function test_blocks_delete(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('AI Security: Blocked write operation', \Mockery::on(function ($context) {
                return str_contains($context['sql'], 'DELETE');
            }));

        $tool = \Mockery::mock(ToolInterface::class);
        $tool->shouldReceive('getName')->andReturn('database_query');

        $toolCall = \Mockery::mock(ToolCallInterface::class);
        $toolCall->shouldReceive('getId')->andReturn('call_789');
        $toolCall->shouldReceive('getArguments')->andReturn(json_encode([
            'query' => 'DELETE FROM tasks WHERE id = 1',
        ]));

        $hookMethod = new \ReflectionMethod($this->agent, 'beforeToolExecution');
        $hookMethod->setAccessible(true);
        $result = $hookMethod->invoke($this->agent, $tool, $toolCall);

        $this->assertFalse($result);
    }

    public function test_allows_select(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('AI Query Executed', \Mockery::on(function ($context) {
                return str_contains($context['sql'], 'SELECT');
            }));

        $tool = \Mockery::mock(ToolInterface::class);
        $tool->shouldReceive('getName')->andReturn('database_query');

        $toolCall = \Mockery::mock(ToolCallInterface::class);
        $toolCall->shouldReceive('getArguments')->andReturn(json_encode([
            'query' => 'SELECT * FROM tasks WHERE id = 1',
        ]));

        $hookMethod = new \ReflectionMethod($this->agent, 'beforeToolExecution');
        $hookMethod->setAccessible(true);
        $result = $hookMethod->invoke($this->agent, $tool, $toolCall);

        $this->assertTrue($result);
    }

    public function test_global_listener_fallback(): void
    {
        Config::set('app.enable_ai_security_listener', true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI write operations forbidden');

        Log::shouldReceive('critical')
            ->once()
            ->with('SECURITY BREACH: AI attempted write', \Mockery::on(function ($context) {
                return str_contains($context['sql'], 'INSERT');
            }));

        \DB::insert('INSERT INTO users (name, email, password) VALUES (?, ?, ?)', [
            'Test User',
            'test@example.com',
            'password123',
        ]);
    }
}
