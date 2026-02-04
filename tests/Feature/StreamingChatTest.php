<?php

namespace Tests\Feature;

use App\Models\ChatThread;
use App\Models\User;
use App\Services\NeuronChatService;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class StreamingChatTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ChatThread $thread;
    protected GeneralSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->thread = ChatThread::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->settings = app(GeneralSettings::class);
        $this->settings->aiChatEnabled = true;
        $this->settings->save();
    }

    public function test_askStream_returns_a_generator(): void
    {
        $service = app(NeuronChatService::class);

        $result = $service->askStream($this->thread, 'Test question');

        $this->assertInstanceOf(\Generator::class, $result);
    }

    public function test_askStream_throws_exception_when_ai_chat_is_disabled(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->aiChatEnabled = false;
        $settings->save();

        $service = new NeuronChatService($settings);

        try {
            $service->askStream($this->thread, 'Test question')->current();
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('AI chat is not enabled', $e->getMessage());
        }
    }

    public function test_askStream_has_anti_buffering_headers_configured(): void
    {
        $reflection = new ReflectionMethod(NeuronChatService::class, 'askStream');
        
        $methodStart = $reflection->getStartLine();
        $methodEnd = $reflection->getEndLine();
        $lines = array_slice(file($reflection->getFileName()), $methodStart - 1, $methodEnd - $methodStart + 1);
        $methodCode = implode('', $lines);

        $this->assertStringContainsString("header('X-Accel-Buffering: no')", $methodCode);
        $this->assertStringContainsString("header('Cache-Control: no-cache')", $methodCode);
        $this->assertStringContainsString("header('Content-Type: text/event-stream')", $methodCode);
    }
}
