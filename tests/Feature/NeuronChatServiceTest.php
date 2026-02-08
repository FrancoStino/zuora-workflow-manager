<?php

namespace Tests\Feature;

use App\AI\DataAnalystAgent;
use App\Exceptions\SecurityException;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use App\Services\NeuronChatService;
use App\Settings\GeneralSettings;
use App\Support\LoggedPDO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NeuronChatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LoggedPDO $pdo;

    protected NeuronChatService $service;

    protected GeneralSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($connection === 'sqlite') {
            $dsn = "sqlite:{$config['database']}";
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                $config['host'],
                $config['port'] ?? 3306,
                $config['database']
            );
        }

        $this->pdo = new LoggedPDO($dsn, $config['username'] ?? '', $config['password'] ?? '');
        $this->settings = app(GeneralSettings::class);
        $this->service = app(NeuronChatService::class);
    }

    /**
     * A. Security Tests
     */
    public function test_logged_pdo_blocks_insert_queries(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Write operation detected and blocked');

        $this->pdo->prepare('INSERT INTO workflows (name) VALUES ("test")');
    }

    public function test_logged_pdo_blocks_update_queries(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Write operation detected and blocked');

        $this->pdo->prepare('UPDATE workflows SET name = "hacked" WHERE id = 1');
    }

    public function test_logged_pdo_blocks_delete_queries(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Write operation detected and blocked');

        $this->pdo->prepare('DELETE FROM workflows WHERE id = 1');
    }

    public function test_logged_pdo_blocks_drop_queries(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionMessage('Write operation detected and blocked');

        $this->pdo->prepare('DROP TABLE workflows');
    }

    public function test_logged_pdo_allows_select_queries(): void
    {
        $stmt = $this->pdo->prepare('SELECT 1');
        $this->assertNotFalse($stmt);

        $stmt = $this->pdo->query('SELECT 1');
        $this->assertNotFalse($stmt);
    }

    /**
     * B. Logging Tests
     */
    public function test_query_logged_in_array(): void
    {
        $this->pdo->query('SELECT 1');
        $this->pdo->prepare('SELECT 2');

        $this->assertCount(2, $this->pdo->log);
        $this->assertStringContainsString('SELECT 1', $this->pdo->log[0]);
        $this->assertStringContainsString('SELECT 2', $this->pdo->log[1]);
    }

    public function test_get_last_query_returns_latest(): void
    {
        $this->pdo->query('SELECT "first"');
        $this->pdo->query('SELECT "second"');

        $this->assertEquals('SELECT "second"', $this->pdo->getLastQuery());
    }

    public function test_save_log_to_file_writes_disk(): void
    {
        $path = storage_path('logs/test_pdo.log');
        if (file_exists($path)) {
            unlink($path);
        }

        $this->pdo->query('SELECT "to_file"');
        $this->pdo->saveLogToFile($path);

        $this->assertFileExists($path);
        $this->assertStringContainsString('SELECT "to_file"', file_get_contents($path));

        unlink($path);
    }

    public function test_clear_query_log_empties_array(): void
    {
        $this->pdo->query('SELECT 1');
        $this->assertNotEmpty($this->pdo->log);

        $this->pdo->clearLog();
        $this->assertEmpty($this->pdo->log);
    }

    /**
     * C. Integration Tests
     */
    public function test_ask_creates_chat_message_in_thread(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = true;

        $mockResponse = \Mockery::mock(\NeuronAI\Chat\Messages\AssistantMessage::class);
        $mockResponse->shouldReceive('getContent')->andReturn('AI Response');

        $mockAgent = \Mockery::mock(DataAnalystAgent::class);
        $mockAgent->shouldReceive('chat')->once()->andReturn($mockResponse);

        $service = \Mockery::mock(NeuronChatService::class, [$this->settings])->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('getAgent')->andReturn($mockAgent);

        $message = $service->ask($thread, 'What is the total count of workflows?');

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals('assistant', $message->role);
        $this->assertEquals('AI Response', $message->content);
        $this->assertDatabaseHas('chat_messages', [
            'chat_thread_id' => $thread->id,
            'role' => 'assistant',
            'content' => 'AI Response',
        ]);
    }

    public function test_ask_populates_provider_metadata(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = true;
        $this->settings->aiProvider = 'openai';

        $mockResponse = \Mockery::mock(\NeuronAI\Chat\Messages\AssistantMessage::class);
        $mockResponse->shouldReceive('getContent')->andReturn('AI Response');

        $mockAgent = \Mockery::mock(DataAnalystAgent::class);
        $mockAgent->shouldReceive('chat')->andReturn($mockResponse);

        $service = \Mockery::mock(NeuronChatService::class, [$this->settings])->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('getAgent')->andReturn($mockAgent);

        $message = $service->ask($thread, 'test');

        $this->assertEquals('openai', $message->metadata['provider']);
    }

    public function test_ask_populates_model_metadata(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = true;
        $this->settings->aiModel = 'gpt-4';

        $mockResponse = \Mockery::mock(\NeuronAI\Chat\Messages\AssistantMessage::class);
        $mockResponse->shouldReceive('getContent')->andReturn('AI Response');

        $mockAgent = \Mockery::mock(DataAnalystAgent::class);
        $mockAgent->shouldReceive('chat')->andReturn($mockResponse);

        $service = \Mockery::mock(NeuronChatService::class, [$this->settings])->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('getAgent')->andReturn($mockAgent);

        $message = $service->ask($thread, 'test');

        $this->assertEquals('gpt-4', $message->metadata['model']);
    }

    public function test_ask_populates_query_generated_metadata(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = true;

        $mockResponse = \Mockery::mock(\NeuronAI\Chat\Messages\AssistantMessage::class);
        $mockResponse->shouldReceive('getContent')->andReturn('AI Response');

        $mockAgent = \Mockery::mock(DataAnalystAgent::class);
        $mockAgent->shouldReceive('chat')->andReturn($mockResponse);

        $service = \Mockery::mock(NeuronChatService::class, [$this->settings])->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('getAgent')->andReturn($mockAgent);

        $message = $service->ask($thread, 'test');
        $this->assertArrayHasKey('query_generated', $message->metadata);
    }

    public function test_ask_populates_results_count_metadata(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = true;

        $mockResponse = \Mockery::mock(\NeuronAI\Chat\Messages\AssistantMessage::class);
        $mockResponse->shouldReceive('getContent')->andReturn('AI Response');

        $mockAgent = \Mockery::mock(DataAnalystAgent::class);
        $mockAgent->shouldReceive('chat')->andReturn($mockResponse);

        $service = \Mockery::mock(NeuronChatService::class, [$this->settings])->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('getAgent')->andReturn($mockAgent);

        $message = $service->ask($thread, 'test');

        $this->assertArrayHasKey('results_count', $message->metadata);
        $this->assertIsInt($message->metadata['results_count']);
    }

    /**
     * D. Database View Tests
     */
    public function test_ai_accessible_schema_view_exists(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $view = DB::select("SELECT name FROM sqlite_master WHERE type = 'view' AND name = 'ai_accessible_schema'");
        } else {
            $view = DB::select("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_laravel = 'ai_accessible_schema'");
        }

        $this->assertNotEmpty($view);
    }

    public function test_ai_accessible_schema_exposes_safe_tables(): void
    {
        $tables = DB::table('ai_accessible_schema')->distinct()->pluck('table_name')->toArray();

        $this->assertContains('workflows', $tables);
        $this->assertContains('tasks', $tables);
        $this->assertContains('customers', $tables);
        $this->assertContains('chat_threads', $tables);
        $this->assertContains('chat_messages', $tables);
    }

    public function test_ai_accessible_schema_hides_sensitive_tables(): void
    {
        $tables = DB::table('ai_accessible_schema')->distinct()->pluck('table_name')->toArray();

        $this->assertNotContains('users', $tables);
        $this->assertNotContains('settings', $tables);
        $this->assertNotContains('failed_jobs', $tables);
    }
}
