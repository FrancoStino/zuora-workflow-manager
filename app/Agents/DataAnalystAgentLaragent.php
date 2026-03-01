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
use LarAgent\Context\Truncation\SummarizationStrategy;
use LarAgent\Core\Contracts\Tool as ToolInterface;
use LarAgent\Core\Contracts\ToolCall as ToolCallInterface;
use LarAgent\Drivers\OpenAi\OpenAiCompatible;
use PDO;

class DataAnalystAgentLaragent extends Agent
{
    protected PDO $pdo;

    public static bool $isExecutingQuery = false;

    protected ?string $lastExecutedQuery = null;

    private const MAX_FETCH_ROWS = 1000;

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

    /**
     * Initialize the agent: establish a PDO connection and configure provider/model settings.
     *
     * @param  mixed  $key  Numeric thread ID or an agent/session key.
     * @param  bool  $usesUserId  Whether the provided key should be interpreted as a user identifier.
     * @param  string|null  $group  Optional group identifier for scoping the agent instance.
     */
    public function __construct($key, bool $usesUserId = false, ?string $group = null)
    {
        $this->pdo = DB::connection()->getPdo();
        // Store thread ID if numeric for reference
        if (is_numeric($key)) {
            $this->threadId = (int) $key;
        }
        $this->configureDynamicProvider();
        parent::__construct($key, $usesUserId, $group);
    }

    /**
     * Provide a SummarizationStrategy to condense older messages when conversation context grows.
     *
     * @return SummarizationStrategy The strategy configured with the agent's model and a chunk size of 10.
     */
    protected function truncationStrategy(): SummarizationStrategy
    {
        return new SummarizationStrategy([
            'summary_model' => $this->model, // Use the same model for summarization
            'chunk_size' => 10, // Summarize 10 messages at a time
        ]);
    }

    /**
     * Handle post-tool execution for monitoring and observability.
     *
     * @param  ToolInterface  $tool  The tool instance that was executed.
     * @param  ToolCallInterface  $toolCall  Metadata about the tool invocation.
     * @param  mixed  &$result  The tool execution result; may be `null` if the tool failed or returned no value.
     * @return bool `true` if post-execution processing completed successfully.
     */
    protected function afterToolExecution(ToolInterface $tool, ToolCallInterface $toolCall, &$result): bool
    {
        $toolName = method_exists($tool, 'getName') ? $tool->getName() : 'unknown';

        Log::channel('laragent')->info('LarAgent Tool Executed', [
            'tool' => $toolName,
            'success' => ! is_null($result),
            'timestamp' => now()->toIso8601String(),
        ]);

        return true;
    }

    /**
     * Configure provider, model, API credentials, and driver from application settings and ModelsDevService, and log the resulting configuration.
     *
     * Reads GeneralSettings to set the agent provider and model, applies a stored AI API key if present, queries ModelsDevService for a provider-specific API endpoint (setting apiUrl and selecting the OpenAiCompatible driver when an endpoint is returned), and emits a debug entry to the 'laragent' log channel summarizing the configuration.
     */
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
            'hasApiKey' => ! empty($this->apiKey),
        ]);
    }

    /**
     * Map an external AI provider identifier to the LarAgent provider key.
     *
     * @param  string  $provider  External provider id (e.g. 'openai', 'anthropic', 'gemini').
     * @return string LarAgent provider identifier ('default', 'anthropic', 'gemini').
     */
    protected function mapProviderToLaragent(string $provider): string
    {
        return match ($provider) {
            'openai' => 'default',
            'anthropic' => 'anthropic',
            'gemini' => 'gemini',
            default => 'default',
        };
    }

    /**
     * Provides the agent's role instructions, including the current date.
     *
     * The instruction string tells the agent to act as a data analyst and appends
     * today's date in YYYY-MM-DD format.
     *
     * @return string The instruction text containing the current date in YYYY-MM-DD format.
     */
    public function instructions(): string
    {
        return 'You are a data analyst. Analyze database queries and provide insights. The current date is '.date('Y-m-d').'.';
    }

    /**
     * Retrieve MySQL database schema information including tables, columns, relationships, and indexes.
     *
     * The returned string contains a serialized representation of the database schema (typically JSON)
     * describing tables, their columns and types, foreign-key relationships, and indexes.
     *
     * @return string The database schema representation as a string (typically JSON).
     */
    #[Tool('Retrieves MySQL database schema information including tables, columns, relationships, and indexes. Use this tool first to understand the database structure before writing any SQL queries. Essential for generating accurate queries with proper table/column names, JOIN conditions, and performance optimization. DO NOT call this tool if you already have database schema information in the context.')]
    public function getDatabaseSchema(): string
    {
        return app(DatabaseSchemaService::class)->getSchema();
    }

    /**
     * Execute a read-only SQL SELECT-style query against the configured PDO connection and return the results.
     *
     * The query must be read-only (SELECT, WITH, SHOW, DESCRIBE, EXPLAIN); write or schema-changing statements are rejected.
     *
     * @param  string  $query  The SQL query to execute (must be read-only).
     * @param  array|null  $parameters  Optional associative array of parameters for binding. Parameter names may be provided with or without a leading colon (e.g. 'id' or ':id').
     * @return array|string An array of result rows as associative arrays on success, or a string error message if the query was rejected or execution failed.
     */
    #[Tool('Use this tool only to run SELECT query against the MySQL database. This the tool to use only to gather information from the MySQL database.', [
        'query' => 'string - The SELECT query to execute (only read-only queries allowed)',
        'parameters' => 'array|null - Optional: Key-value pairs for parameter binding',
    ])]
    public function executeQuery(string $query, ?array $parameters = null): string|array
    {
        if (! $this->validateReadOnly($query)) {
            Log::error('AI Security: Blocked write operation in executeQuery', [
                'query' => $query,
                'tool' => 'executeQuery',
            ]);

            return 'The query was rejected for security reasons. '.
                   'It looks like you are trying to run a write query using the read-only query tool.';
        }

        $this->lastExecutedQuery = $query;

        try {
            static::$isExecutingQuery = true;

            Log::info('AI Query Executed', ['query' => $query, 'parameters' => $parameters]);

            $statement = $this->pdo->prepare($query);

            if ($parameters && is_array($parameters)) {
                foreach ($parameters as $name => $value) {
                    $paramName = str_starts_with($name, ':') ? $name : ':'.$name;
                    $statement->bindValue($paramName, $value);
                }
            }

            $statement->execute();

            $rows = [];
            while (count($rows) < self::MAX_FETCH_ROWS && ($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                $rows[] = $row;
            }

            return $rows;
        } catch (\Exception $e) {
            Log::error('AI Query Execution Failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return 'Query execution failed: '.$e->getMessage();
        } finally {
            static::$isExecutingQuery = false;
        }
    }

    /**
     * Starts a streamed agent response for the given user query and chat thread.
     *
     * @param  string  $userQuery  The user's natural language query to analyze.
     * @param  ChatThread  $thread  Chat thread whose context/history should be used for the response.
     * @return Generator A generator that yields streamed response chunks from the agent.
     */
    public function analyze(string $userQuery, ChatThread $thread): Generator
    {
        $this->threadId = $thread->id;

        return $this->respondStreamed($userQuery);
    }

    /**
     * Get the last SQL query executed by this agent.
     *
     * @return string|null The last executed SQL query, or null if none.
     */
    public function getLastExecutedQuery(): ?string
    {
        return $this->lastExecutedQuery;
    }

    /**
     * Determine whether an SQL query is permitted as a read-only operation.
     *
     * Checks that the query's first keyword is one of the configured allowed statements
     * and that it does not contain any of the configured forbidden keywords (checked as whole words).
     *
     * @param  string  $query  The SQL query to validate.
     * @return bool `true` if the query appears read-only and allowed, `false` otherwise.
     */
    protected function validateReadOnly(string $query): bool
    {
        $cleanQuery = $this->sanitizeQuery($query);
        $firstKeyword = $this->getFirstKeyword($cleanQuery);

        if (! in_array($firstKeyword, $this->allowedStatements)) {
            return false;
        }

        // Strip quoted strings to avoid false positives on keywords inside string literals
        $unquotedQuery = $this->stripQuotedStrings($cleanQuery);

        foreach ($this->forbiddenStatements as $forbidden) {
            if ($this->containsKeyword($unquotedQuery, $forbidden)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove single-quoted and double-quoted string literals from a SQL query.
     *
     * This prevents false-positive keyword detection on values like "DELETE" inside strings.
     *
     * @param  string  $query  The SQL query to process.
     * @return string The query with all quoted string literals replaced by empty strings.
     */
    protected function stripQuotedStrings(string $query): string
    {
        $result = '';
        $len = strlen($query);
        $i = 0;

        while ($i < $len) {
            $char = $query[$i];

            if ($char === "'" || $char === '"') {
                $quote = $char;
                $i++;
                while ($i < $len) {
                    if ($query[$i] === '\\') {
                        $i += 2;
                    } elseif ($query[$i] === $quote) {
                        $i++;
                        break;
                    } else {
                        $i++;
                    }
                }
            } else {
                $result .= $char;
                $i++;
            }
        }

        return $result;
    }

    /**
     * Remove SQL comments and normalize whitespace in a query string.
     *
     * @param  string  $query  The raw SQL query.
     * @return string The sanitized SQL query.
     */
    protected function sanitizeQuery(string $query): string
    {
        $query = preg_replace('/--.*$/m', '', $query);
        $query = preg_replace('/\/\*.*?\*\//s', '', (string) $query);

        return preg_replace('/\s+/', ' ', trim((string) $query));
    }

    /**
     * Extracts the first word from the given SQL string and returns it in uppercase.
     *
     * @param  string  $query  The SQL query or fragment to inspect.
     * @return string The first token or keyword in uppercase, or an empty string if none is found.
     */
    protected function getFirstKeyword(string $query): string
    {
        if (preg_match('/^\s*(\w+)/i', $query, $matches)) {
            return strtoupper($matches[1]);
        }

        return '';
    }

    /**
     * Determines whether the given SQL query contains the specified keyword as a whole word (case-insensitive).
     *
     * @param  string  $query  The SQL query string to search.
     * @param  string  $keyword  The keyword to look for.
     * @return bool `true` if the keyword is present as a whole word, `false` otherwise.
     */
    protected function containsKeyword(string $query, string $keyword): bool
    {
        return preg_match('/\b'.preg_quote($keyword, '/').'\b/i', $query) === 1;
    }
}
