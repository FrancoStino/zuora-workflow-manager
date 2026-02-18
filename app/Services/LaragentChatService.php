<?php

namespace App\Services;

use App\Agents\DataAnalystAgentLaragent;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Log;

class LaragentChatService
{
    private array $queryLog = [];

    /**
     * Create a LaragentChatService configured with application general settings.
     *
     * @param GeneralSettings $settings Application-level settings used to control AI chat behavior (e.g., enabled flag, provider, model).
     */
    public function __construct(
        private readonly GeneralSettings $settings,
    ) {}

    /**
     * Send a question to the thread's AI agent, persist the assistant's response as a ChatMessage, and return that message.
     *
     * @param ChatThread $thread The chat thread to send the question in.
     * @param string $question The user's question to send to the AI agent.
     * @throws \RuntimeException If AI chat is disabled in settings.
     * @return ChatMessage The persisted assistant ChatMessage. On failure the returned message's content contains the error text and its metadata includes an `error` flag and `error_message`.
     */
    public function ask(ChatThread $thread, string $question): ChatMessage
    {
        if (! $this->settings->aiChatEnabled) {
            throw new \RuntimeException('AI chat is not enabled');
        }

        try {
            $agent = $this->getAgent($thread);
            $response = $agent->respond($question);

            $queryGenerated = $this->extractQueryFromThread($thread);

            return $thread->messages()->create([
                'role' => 'assistant',
                'content' => $response,
                'query_generated' => $queryGenerated,
                'metadata' => [
                    'provider' => $this->settings->aiProvider,
                    'model' => $this->settings->aiModel,
                    'results_count' => 0,
                    'query_generated' => $queryGenerated,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('LaragentChatService error', [
                'thread_id' => $thread->id,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);

            return $thread->messages()->create([
                'role' => 'assistant',
                'content' => 'Error: '.$e->getMessage(),
                'metadata' => [
                    'provider' => $this->settings->aiProvider,
                    'model' => $this->settings->aiModel,
                    'error' => true,
                    'error_message' => $e->getMessage(),
                ],
            ]);
        }
    }

    /**
     * Streams an assistant response for a chat thread by yielding incremental text chunks and persisting the final message.
     *
     * Yields successive string deltas of the assistant's streamed response as they arrive; after streaming completes the full response is stored on the thread with metadata (provider, model, streaming flag) and the thread's last assistant-generated query is recorded.
     *
     * @param ChatThread $thread The chat thread to which the response belongs.
     * @param string $question The user question to send to the agent.
     * @return \Generator Yields string chunks of the assistant response as they arrive; completes after persisting the assembled full response.
     * @throws \RuntimeException If AI chat is disabled in settings.
     * @throws \Exception If an error occurs during streaming or when persisting the final message.
     */
    public function askStream(ChatThread $thread, string $question): \Generator
    {
        if (! $this->settings->aiChatEnabled) {
            throw new \RuntimeException('AI chat is not enabled');
        }

        $fullResponse = '';

        try {
            $agent = $this->getAgent($thread);

            // Use respondStreamed() which returns a Generator with StreamedAssistantMessage chunks
            foreach ($agent->respondStreamed($question) as $chunk) {
                if ($chunk instanceof \LarAgent\Messages\StreamedAssistantMessage) {
                    $delta = $chunk->getLastChunk();
                    if ($delta !== null && $delta !== '') {
                        $fullResponse .= $delta;
                        yield $delta;
                    }
                } elseif (is_string($chunk) && $chunk !== '') {
                    $fullResponse .= $chunk;
                    yield $chunk;
                }
            }

            // Salva messaggio completo alla fine
            $queryGenerated = $this->extractQueryFromThread($thread);

            $thread->messages()->create([
                'role' => 'assistant',
                'content' => $fullResponse,
                'query_generated' => $queryGenerated,
                'metadata' => [
                    'provider' => $this->settings->aiProvider,
                    'model' => $this->settings->aiModel,
                    'streaming' => true,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('LaragentChatService streaming error', [
                'thread_id' => $thread->id,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw per permettere fallback nel componente Livewire
        }
    }

    /**
     * Retrieve an agent instance scoped to the given chat thread's session.
     *
     * @param ChatThread $thread The chat thread whose ID is used as the agent's per-thread session key.
     * @return DataAnalystAgentLaragent An agent instance bound to the thread's ID so it preserves that thread's conversation context.
     */
    protected function getAgent(ChatThread $thread): DataAnalystAgentLaragent
    {
        // Use thread ID as the chat session key to maintain per-thread context
        // This ensures each conversation has its own history
        return DataAnalystAgentLaragent::forUserId((string) $thread->id);
    }

    /**
     * Retrieve the recorded query log for this service.
     *
     * @return array An array of logged query entries; empty if no queries are recorded.
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Reset the service's stored query log to an empty array.
     */
    public function clearQueryLog(): void
    {
        $this->queryLog = [];
    }

    /**
     * Retrieve the most recent assistant message's `query_generated` value from the thread.
     *
     * @param ChatThread $thread The chat thread to inspect.
     * @return string|null The `query_generated` value of the most recent assistant message, or `null` if no assistant message exists.
     */
    private function extractQueryFromThread(ChatThread $thread): ?string
    {
        $lastAssistantMessage = $thread->messages()
            ->where('role', 'assistant')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $lastAssistantMessage) {
            return null;
        }

        return $lastAssistantMessage->query_generated;
    }
}