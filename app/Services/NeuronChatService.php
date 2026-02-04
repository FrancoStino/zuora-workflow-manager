<?php

namespace App\Services;

use App\AI\DataAnalystAgent;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Settings\GeneralSettings;
use App\Support\LoggedPDO;
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Messages\UserMessage;

class NeuronChatService
{
    private ?LoggedPDO $pdo = null;

    public function __construct(
        private readonly GeneralSettings $settings,
    ) {}

    public function ask(ChatThread $thread, string $question): ChatMessage
    {
        if (!$this->settings->aiChatEnabled) {
            throw new \RuntimeException('AI chat is not enabled');
        }

        $thread->messages()->create([
            'role' => 'user',
            'content' => $question,
        ]);
        $thread->generateTitleFromFirstMessage();

        $this->pdo = $this->createLoggedPdo();
        $agent = new DataAnalystAgent($this->pdo);

        try {
            $response = $agent->chat(new UserMessage($question));
            $content = $response->getContent();

            $queryGenerated = $this->pdo->getLastQuery();
            
            return $thread->messages()->create([
                'role' => 'assistant',
                'content' => $content,
                'query_generated' => $queryGenerated,
                'metadata' => [
                    'provider' => $this->settings->aiProvider,
                    'model' => $this->settings->aiModel,
                    'results_count' => count($this->pdo->log),
                    'query_generated' => $queryGenerated,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('NeuronChatService error', [
                'thread_id' => $thread->id,
                'question' => $question,
                'error' => $e->getMessage(),
            ]);

            return $thread->messages()->create([
                'role' => 'assistant',
                'content' => 'Error: ' . $e->getMessage(),
                'metadata' => [
                    'error' => true,
                    'error_message' => $e->getMessage(),
                ],
            ]);
        }
    }

    public function getQueryLog(): array
    {
        return $this->pdo?->log ?? [];
    }

    public function clearQueryLog(): void
    {
        $this->pdo?->clearLog();
    }

    private function createLoggedPdo(): LoggedPDO
    {
        $config = config('database.connections.mysql');
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $config['host'],
            $config['port'] ?? 3306,
            $config['database']
        );
        return new LoggedPDO($dsn, $config['username'], $config['password']);
    }
}
