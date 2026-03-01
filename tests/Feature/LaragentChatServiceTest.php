<?php

namespace Tests\Feature;

use App\Agents\DataAnalystAgentLaragent;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use App\Services\LaragentChatService;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaragentChatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LaragentChatService $service;

    protected GeneralSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = app(GeneralSettings::class);
        $this->service = app(LaragentChatService::class);
    }

    /**
     * A. Integration Tests
     */
    public function test_ask_creates_chat_message_in_thread(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = true;

        $message = $this->service->ask($thread, 'Test question');

        $this->assertInstanceOf(ChatMessage::class, $message);
        $this->assertEquals('assistant', $message->role);
        $this->assertDatabaseHas('chat_messages', [
            'chat_thread_id' => $thread->id,
            'role' => 'assistant',
        ]);
    }

    public function test_ask_populates_provider_metadata(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = true;
        $this->settings->aiProvider = 'laragent';

        $message = $this->service->ask($thread, 'test');

        $this->assertEquals('laragent', $message->metadata['provider']);
    }

    public function test_ask_populates_model_metadata(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = true;
        $this->settings->aiModel = 'gpt-4';

        $message = $this->service->ask($thread, 'test');

        $this->assertEquals('gpt-4', $message->metadata['model']);
    }

    public function test_service_creates_agent_with_eloquent_history(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = true;

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAgent');
        $method->setAccessible(true);

        $agent = $method->invoke($this->service, $thread);

        $this->assertInstanceOf(DataAnalystAgentLaragent::class, $agent);
    }

    public function test_ask_throws_exception_when_ai_disabled(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Test Thread']);

        $this->settings->aiChatEnabled = false;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI chat is not enabled');

        $this->service->ask($thread, 'test');
    }
}
