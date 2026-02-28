<?php

namespace Tests\Feature;

use App\Filament\Resources\ChatThreads\ChatThreadResource;
use App\Filament\Resources\ChatThreads\Pages\ListChatThreads;
use App\Filament\Resources\ChatThreads\Pages\ViewChatThread;
use App\Livewire\ChatBox;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatThreadResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user->assignRole('super_admin');
        $this->actingAs($this->user);
    }

    /**
     * Smoke test: the list page renders without errors.
     */
    public function test_list_page_renders(): void
    {
        Livewire::test(ListChatThreads::class)
            ->assertSuccessful();
    }

    /**
     * Smoke test: the view page renders without errors for a thread with messages.
     */
    public function test_view_page_renders_with_messages(): void
    {
        $thread = ChatThread::factory()->create(['user_id' => $this->user->id]);

        ChatMessage::factory()->user()->create([
            'chat_thread_id' => $thread->id,
            'content' => 'Hello, how many workflows?',
        ]);

        ChatMessage::factory()->assistant()->create([
            'chat_thread_id' => $thread->id,
            'content' => 'There are 57 workflows in the database.',
        ]);

        Livewire::test(ViewChatThread::class, ['record' => $thread->id])
            ->assertSuccessful();
    }

    /**
     * Smoke test: the view page renders without errors for an empty thread.
     */
    public function test_view_page_renders_with_empty_thread(): void
    {
        $thread = ChatThread::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(ViewChatThread::class, ['record' => $thread->id])
            ->assertSuccessful();
    }

    /**
     * Smoke test: ChatBox Livewire component renders without RootTagMissingFromViewException.
     */
    public function test_chatbox_component_renders(): void
    {
        $thread = ChatThread::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(ChatBox::class, ['thread' => $thread])
            ->assertSuccessful()
            ->assertSee('Chat with AI Assistant');
    }

    /**
     * ChatBox renders correctly even when a message has empty content.
     */
    public function test_chatbox_renders_with_empty_assistant_message(): void
    {
        $thread = ChatThread::factory()->create(['user_id' => $this->user->id]);

        ChatMessage::factory()->user()->create([
            'chat_thread_id' => $thread->id,
            'content' => 'Test question',
        ]);

        ChatMessage::factory()->assistant()->create([
            'chat_thread_id' => $thread->id,
            'content' => '',
        ]);

        Livewire::test(ChatBox::class, ['thread' => $thread])
            ->assertSuccessful();
    }

    /**
     * ChatBox renders correctly when an assistant message contains thinking tags.
     */
    public function test_chatbox_renders_with_thinking_tags(): void
    {
        $thread = ChatThread::factory()->create(['user_id' => $this->user->id]);

        ChatMessage::factory()->assistant()->create([
            'chat_thread_id' => $thread->id,
            'content' => '<think>Let me analyze the data...</think>There are 57 workflows.',
        ]);

        Livewire::test(ChatBox::class, ['thread' => $thread])
            ->assertSuccessful()
            ->assertSee('Thinking')
            ->assertSee('57 workflows');
    }

    /**
     * Verify the resource generates correct URLs.
     */
    public function test_resource_url_generation(): void
    {
        $url = ChatThreadResource::getUrl('index');

        $this->assertStringContainsString('ai-chat', $url);
    }
}
