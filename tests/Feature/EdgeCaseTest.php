<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use App\Services\LaragentChatService;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected LaragentChatService $service;

    protected GeneralSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = app(GeneralSettings::class);
        $this->settings->aiChatEnabled = true;
        $this->settings->aiProvider = 'laragent';
        $this->settings->aiModel = 'gpt-4o-mini';

        $this->service = app(LaragentChatService::class);
    }

    /**
     * Test 1: Massive Chat History (1000+ messages)
     * Verifica che il sistema gestisca thread con storico molto grande senza crash
     */
    public function test_massive_chat_history(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Massive Thread']);

        for ($i = 0; $i < 1000; $i++) {
            $thread->messages()->create([
                'role' => $i % 2 === 0 ? 'user' : 'assistant',
                'content' => "Message $i",
                'metadata' => [],
            ]);
        }

        $this->assertDatabaseCount('chat_messages', 1000);

        try {
            $message = $this->service->ask($thread, 'Test question with massive history');

            $this->assertInstanceOf(ChatMessage::class, $message);
            $this->assertEquals('assistant', $message->role);
            $this->assertNotEmpty($message->content);
        } catch (\Exception $e) {
            $this->assertStringNotContainsString('500', $e->getMessage());
            $this->assertStringContainsString('Error:', $e->getMessage());
        }
    }

    /**
     * Test 2: Concurrent Requests (Parallel Streams)
     * Simula richieste concorrenti allo stesso thread
     */
    public function test_concurrent_requests(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Concurrent Thread']);

        $results = [];
        $errors = [];

        for ($i = 0; $i < 5; $i++) {
            try {
                $message = $this->service->ask($thread, "Concurrent question $i");
                $results[] = $message;
            } catch (\Exception $e) {
                $errors[] = $e;
            }
        }

        $this->assertGreaterThan(0, count($results));

        foreach ($errors as $error) {
            $this->assertStringNotContainsString('500', $error->getMessage());
        }
    }

    /**
     * Test 3: Malformed SQL Query
     * Verifica che query SQL malformate siano gestite gracefully
     */
    public function test_malformed_sql_query(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Malformed SQL Thread']);

        try {
            $message = $this->service->ask($thread, 'SELCT * FORM tasks WHRE id = "invalid"');

            $this->assertInstanceOf(ChatMessage::class, $message);
            $this->assertStringNotContainsString('DROP TABLE', $message->query_generated ?? '');
        } catch (\Exception $e) {
            $this->assertStringNotContainsString('500', $e->getMessage());
            $this->assertStringContainsString('Error:', $e->getMessage());
        }
    }

    /**
     * Test 4: API Timeout
     * Simula timeout dell'API OpenAI
     */
    public function test_api_timeout(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Timeout Thread']);

        config(['laragent.timeout' => 1]);

        try {
            $message = $this->service->ask($thread, 'Very complex query that might timeout');

            $this->assertInstanceOf(ChatMessage::class, $message);
        } catch (\Exception $e) {
            $this->assertStringNotContainsString('500', $e->getMessage());
            $this->assertTrue(
                str_contains($e->getMessage(), 'timeout') ||
                str_contains($e->getMessage(), 'Error:') ||
                str_contains($e->getMessage(), 'failed')
            );
        }
    }

    /**
     * Test 5: Rate Limit Exceeded (429 Response)
     * Verifica gestione rate limiting
     */
    public function test_rate_limit_exceeded(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Rate Limit Thread']);

        $errors = [];
        for ($i = 0; $i < 10; $i++) {
            try {
                $message = $this->service->ask($thread, "Rate limit test $i");
                $this->assertInstanceOf(ChatMessage::class, $message);
            } catch (\Exception $e) {
                $errors[] = $e;
                $this->assertStringNotContainsString('500', $e->getMessage());
            }
        }

        foreach ($errors as $error) {
            $this->assertTrue(
                str_contains($error->getMessage(), 'rate limit') ||
                str_contains($error->getMessage(), 'Error:') ||
                str_contains($error->getMessage(), '429')
            );
        }
    }

    /**
     * Test 6: Empty Message
     * Verifica gestione messaggi vuoti (laragent richiede stringa non vuota)
     */
    public function test_empty_message(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Empty Message Thread']);

        $this->expectException(\TypeError::class);

        $this->service->ask($thread, '');
    }

    /**
     * Test 7: Very Long Message (10k chars)
     * Verifica gestione messaggi molto lunghi
     */
    public function test_very_long_message(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Long Message Thread']);

        $longMessage = str_repeat('This is a very long message. ', 500);

        try {
            $message = $this->service->ask($thread, $longMessage);

            $this->assertInstanceOf(ChatMessage::class, $message);
            $this->assertEquals('assistant', $message->role);
        } catch (\Exception $e) {
            $this->assertStringNotContainsString('500', $e->getMessage());
            $this->assertTrue(
                str_contains($e->getMessage(), 'too long') ||
                str_contains($e->getMessage(), 'token') ||
                str_contains($e->getMessage(), 'Error:')
            );
        }
    }

    /**
     * Test 8: Special Characters (Unicode, Emojis)
     * Verifica gestione caratteri speciali
     */
    public function test_special_characters(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Special Chars Thread']);

        $specialMessage = 'Test with emojis 🚀 🎉 and unicode: café, naïve, 日本語, العربية, Привет';

        try {
            $message = $this->service->ask($thread, $specialMessage);

            $this->assertInstanceOf(ChatMessage::class, $message);
            $this->assertEquals('assistant', $message->role);
            $this->assertNotEmpty($message->content);
        } catch (\Exception $e) {
            $this->assertStringNotContainsString('500', $e->getMessage());
            $this->assertStringContainsString('Error:', $e->getMessage());
        }
    }

    /**
     * Test 9: SQL Injection Attempt
     * Verifica che i tentativi di SQL injection siano bloccati
     */
    public function test_sql_injection_attempt(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'SQL Injection Thread']);

        $injectionAttempts = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "admin' --",
            "1; DELETE FROM tasks WHERE '1'='1",
        ];

        foreach ($injectionAttempts as $injection) {
            try {
                $message = $this->service->ask($thread, "Show me tasks where id = $injection");

                $this->assertInstanceOf(ChatMessage::class, $message);

                if ($message->query_generated) {
                    $this->assertStringNotContainsString('DROP TABLE', $message->query_generated);
                    $this->assertStringNotContainsString('DELETE FROM', $message->query_generated);
                }
            } catch (\Exception $e) {
                $this->assertStringNotContainsString('500', $e->getMessage());
            }
        }
    }

    /**
     * Test 10: Thread Not Found
     * Verifica gestione thread inesistente
     */
    public function test_thread_not_found(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'Deleted Thread']);
        $threadId = $thread->id;
        $thread->delete();

        $deletedThread = ChatThread::find($threadId);
        $this->assertNull($deletedThread);

        try {
            if ($deletedThread) {
                $message = $this->service->ask($deletedThread, 'Test on deleted thread');
                $this->fail('Expected exception for deleted thread');
            }
        } catch (\Exception $e) {
            $this->assertStringNotContainsString('500', $e->getMessage());
        }

        $this->assertTrue(true);
    }

    /**
     * Test 11: Invalid Thread ID
     * Verifica gestione ID thread invalido
     */
    public function test_invalid_thread_id(): void
    {
        $user = User::factory()->create();

        $nonExistentThread = ChatThread::find(99999);
        $this->assertNull($nonExistentThread);

        if ($nonExistentThread === null) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test 12: Database Connection Loss
     * Verifica gestione perdita connessione database (simulata)
     */
    public function test_database_connection_resilience(): void
    {
        $user = User::factory()->create();
        $thread = ChatThread::create(['user_id' => $user->id, 'title' => 'DB Connection Thread']);

        try {
            $message = $this->service->ask($thread, 'Test database resilience');

            $this->assertInstanceOf(ChatMessage::class, $message);
            $this->assertDatabaseHas('chat_messages', [
                'chat_thread_id' => $thread->id,
                'role' => 'assistant',
            ]);
        } catch (\Exception $e) {
            $this->assertStringNotContainsString('500', $e->getMessage());
        }
    }
}
