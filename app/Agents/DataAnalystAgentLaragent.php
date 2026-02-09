<?php

namespace App\Agents;

use App\Models\ChatThread;
use App\Services\DatabaseSchemaService;
use App\Services\ModelsDevService;
use App\Settings\GeneralSettings;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LarAgent\Agent;
use LarAgent\Attributes\Tool;
use LarAgent\Core\Contracts\Tool as ToolInterface;
use LarAgent\Core\Contracts\ToolCall as ToolCallInterface;
use LarAgent\Drivers\OpenAi\OpenAiCompatible;
use LarAgent\Context\Truncation\SummarizationStrategy;
use LarAgent\Messages\AssistantMessage;
use LarAgent\Messages\UserMessage;
use PDO;

class DataAnalystAgentLaragent extends Agent
{
    protected PDO $pdo;
    protected $provider = null;
    protected $model = null;
    protected $temperature = null;
    protected $maxCompletionTokens = null;

    /**
     * Enable truncation to prevent context window overflow for long conversations.
     */
    protected $enableTruncation = true;

    /**
     * Truncation threshold in tokens (conservative: ~30% of typical 128K context).
     */
    protected $truncationThreshold = 40000;

    /**
     * Thread ID used as chat session key
     */
    protected ?int $threadId = null;

    protected array $allowedStatements = ['SELECT', 'WITH', 'SHOW', 'DESCRIBE', 'EXPLAIN'];
    protected array $forbiddenStatements = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
        'TRUNCATE', 'REPLACE', 'MERGE', 'CALL', 'EXECUTE',
        'INTO', 'OUTFILE', 'DUMPFILE', 'LOAD_FILE',
    ];

    public function __construct($key, bool $usesUserId = false, ?string $group = null)
    {
        $this->pdo = DB::connection()->getPdo();
        
        // Store thread ID if numeric for later history loading
        if (is_numeric($key)) {
            $this->threadId = (int) $key;
        }
        
        $this->configureDynamicProvider();
        parent::__construct($key, $usesUserId, $group);
        
        // Load existing messages from database into LarAgent's history
        if ($this->threadId) {
            $this->loadExistingMessages();
        }
    }

    /**
     * Load existing messages from the database into LarAgent's chat history.
     * This syncs our DB-stored messages with LarAgent's internal history.
     */
    protected function loadExistingMessages(): void
    {
        $thread = ChatThread::find($this->threadId);
        if (!$thread) {
            return;
        }

        $thread->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->each(function ($dbMessage) {
                $message = match ($dbMessage->role) {
                    'user' => new UserMessage($dbMessage->content ?? ''),
                    'assistant' => new AssistantMessage($dbMessage->content ?? ''),
                    default => null,
                };
                
                if ($message) {
                    $this->chatHistory()->addMessage($message);
                }
            });
    }

    /**
     * Truncation strategy: summarize older messages when context overflows.
     * This keeps the memory relevant without losing key details.
     */
    protected function truncationStrategy(): SummarizationStrategy
    {
        return new SummarizationStrategy([
            'summary_model' => $this->model, // Use the same model for summarization
            'chunk_size' => 10, // Summarize 10 messages at a time
        ]);
    }

    /**
     * Event triggered after executing a tool - used for monitoring/observability.
     */
    protected function afterToolExecution(ToolInterface $tool, ToolCallInterface $toolCall, &$result): bool
    {
        $toolName = method_exists($tool, 'getName') ? $tool->getName() : 'unknown';

        Log::channel('laragent')->info('LarAgent Tool Executed', [
            'tool' => $toolName,
            'success' => !is_null($result),
            'timestamp' => now()->toIso8601String(),
        ]);

        return true;
    }

    protected function configureDynamicProvider(): void
    {
        $settings = app(GeneralSettings::class);
        $modelsService = app(ModelsDevService::class);

        $this->provider = $this->mapProviderToLaragent($settings->aiProvider);
        $this->model = $settings->aiModel;

        // Set API key from GeneralSettings (stored in database)
        if ($settings->aiApiKey) {
            $this->apiKey = $settings->aiApiKey;
        }

        // Set API URL from ModelsDevService (e.g., nvidia -> https://integrate.api.nvidia.com/v1)
        $baseUri = $modelsService->getApiEndpoint($settings->aiProvider);
        if ($baseUri) {
            $this->apiUrl = $baseUri;
            // Use OpenAiCompatible driver for external providers (nvidia, openrouter, etc.)
            $this->driver = OpenAiCompatible::class;
        }

        Log::channel('laragent')->debug('DataAnalystAgentLaragent configured', [
            'provider' => $this->provider,
            'model' => $this->model,
            'apiUrl' => $this->apiUrl ?? 'default',
            'driver' => $this->driver ?? 'default',
            'hasApiKey' => !empty($this->apiKey),
        ]);
    }

    protected function mapProviderToLaragent(string $provider): string
    {
        return match ($provider) {
            'openai' => 'default',
            'anthropic' => 'anthropic',
            'gemini' => 'gemini',
            default => 'default',
        };
    }

    public function instructions(): string
    {
        return 'You are a data analyst. Analyze database queries and provide insights. The current date is ' . date('Y-m-d') . '.';
    }

    #[Tool('Retrieves MySQL database schema information including tables, columns, relationships, and indexes. Use this tool first to understand the database structure before writing any SQL queries. Essential for generating accurate queries with proper table/column names, JOIN conditions, and performance optimization. DO NOT call this tool if you already have database schema information in the context.')]
    public function getDatabaseSchema(): string
    {
        return app(DatabaseSchemaService::class)->getSchema();
    }

    #[Tool('Use this tool only to run SELECT query against the MySQL database. This the tool to use only to gather information from the MySQL database.', [
        'query' => 'string - The SELECT query to execute (only read-only queries allowed)',
        'parameters' => 'array|null - Optional: Key-value pairs for parameter binding'
    ])]
    public function executeQuery(string $query, ?array $parameters = null): string|array
    {
        if (!$this->validateReadOnly($query)) {
            Log::error('AI Security: Blocked write operation in executeQuery', [
                'query' => $query,
                'tool' => 'executeQuery'
            ]);
            
            return "The query was rejected for security reasons. " .
                   "It looks like you are trying to run a write query using the read-only query tool.";
        }

        try {
            Log::info('AI Query Executed', ['query' => $query, 'parameters' => $parameters]);

            $statement = $this->pdo->prepare($query);

            if ($parameters && is_array($parameters)) {
                foreach ($parameters as $name => $value) {
                    $paramName = str_starts_with($name, ':') ? $name : ':' . $name;
                    $statement->bindValue($paramName, $value);
                }
            }

            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            Log::error('AI Query Execution Failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return "Query execution failed: " . $e->getMessage();
        }
    }

    public function analyze(string $userQuery, ChatThread $thread): Generator
    {
        return $this->respondStreamed($userQuery);
    }

    /**
     * Security validation: regex patterns to block write operations
     */
    protected function validateReadOnly(string $query): bool
    {
        $cleanQuery = $this->sanitizeQuery($query);
        $firstKeyword = $this->getFirstKeyword($cleanQuery);
        
        if (!in_array($firstKeyword, $this->allowedStatements)) {
            return false;
        }

        foreach ($this->forbiddenStatements as $forbidden) {
            if ($this->containsKeyword($cleanQuery, $forbidden)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Regex: remove SQL comments (--...$, /* ... *\/)
     */
    protected function sanitizeQuery(string $query): string
    {
        $query = preg_replace('/--.*$/m', '', $query);
        $query = preg_replace('/\/\*.*?\*\//s', '', (string)$query);
        return preg_replace('/\s+/', ' ', trim((string)$query));
    }

    protected function getFirstKeyword(string $query): string
    {
        if (preg_match('/^\s*(\w+)/i', $query, $matches)) {
            return strtoupper($matches[1]);
        }
        return '';
    }

    /**
     * Regex: word boundary check for forbidden keywords
     */
    protected function containsKeyword(string $query, string $keyword): bool
    {
        return preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $query) === 1;
    }
}
