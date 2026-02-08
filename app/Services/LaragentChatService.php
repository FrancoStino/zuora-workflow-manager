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

    public function __construct(
        private readonly GeneralSettings $settings,
    ) {}

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

    public function askStream(ChatThread $thread, string $question): \Generator
    {
        if (! $this->settings->aiChatEnabled) {
            throw new \RuntimeException('AI chat is not enabled');
        }

        // Headers anti-buffering
        header('X-Accel-Buffering: no');
        header('Cache-Control: no-cache');
        header('Content-Type: text/event-stream');

        $fullResponse = '';

        try {
            $agent = $this->getAgent($thread);
            
            foreach ($agent->streamResponse($question, 'sse') as $chunk) {
                $fullResponse .= $chunk;
                yield $chunk;
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

    protected function getAgent(ChatThread $thread): DataAnalystAgentLaragent
    {
        return DataAnalystAgentLaragent::forUserId((string) $thread->user_id);
    }

    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    public function clearQueryLog(): void
    {
        $this->queryLog = [];
    }

    private function extractQueryFromThread(ChatThread $thread): ?string
    {
        $lastAssistantMessage = $thread->messages()
            ->where('role', 'assistant')
            ->orderBy('created_at', 'desc')
            ->first();
        
        if (!$lastAssistantMessage) {
            return null;
        }

        return $lastAssistantMessage->query_generated;
    }
}
