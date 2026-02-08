<?php

namespace App\Agents;

use App\ChatHistory\EloquentThreadChatHistory;
use Illuminate\Support\Facades\Log;
use LarAgent\Agent;
use LarAgent\Core\Contracts\Tool as ToolInterface;
use LarAgent\Core\Contracts\ToolCall as ToolCallInterface;

class DataAnalystAgent extends Agent
{
    protected $model = 'gpt-4';

    protected $history = 'database';

    protected $provider = 'default';

    protected $tools = [];

    public function instructions(): string
    {
        return 'You are a data analyst. Analyze database queries and provide insights. You can only execute SELECT queries for security reasons.';
    }

    public function prompt($message): string
    {
        return $message;
    }

    protected function beforeToolExecution(ToolInterface $tool, ToolCallInterface $toolCall): bool
    {
        $args = json_decode($toolCall->getArguments(), true);
        $sql = $args['query'] ?? '';

        if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE)\b/i', $sql)) {
            Log::error('AI Security: Blocked write operation', [
                'sql' => $sql,
                'tool' => $tool->getName(),
                'tool_call_id' => $toolCall->getId(),
            ]);

            return false;
        }

        Log::info('AI Query Executed', [
            'sql' => $sql,
            'tool' => $tool->getName(),
        ]);

        return true;
    }
}
