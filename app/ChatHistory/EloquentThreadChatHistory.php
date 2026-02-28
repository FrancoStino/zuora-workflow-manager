<?php

namespace App\ChatHistory;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use Illuminate\Support\Facades\Auth;
use LarAgent\Context\Contracts\SessionIdentity as SessionIdentityContract;
use LarAgent\Core\Contracts\ChatHistory;
use LarAgent\Core\Contracts\Message as MessageInterface;
use LarAgent\Messages\AssistantMessage;
use LarAgent\Messages\DataModels\MessageArray;
use LarAgent\Messages\UserMessage;

class EloquentThreadChatHistory implements ChatHistory
{
    protected ChatThread $thread;

    protected ?MessageArray $messagesCache = null;

    protected string $identifier;

    /**
     * Create and initialize an EloquentThreadChatHistory instance by resolving or creating the associated ChatThread.
     *
     * The constructor accepts multiple identifier patterns:
     * - SessionIdentityContract: derives a thread name and user id from the session identity.
     * - null: generates a unique thread identifier and requires the current authenticated user.
     * - numeric string: treats the identifier as an existing ChatThread ID and loads that thread (errors if not found).
     * - string: uses the string as a thread identifier and resolves the user id from an explicit integer argument, a config array (ignored, falling back to the authenticated user), or the current authenticated user.
     *
     * @param  string|SessionIdentityContract|null  $identifier  Session identity, existing thread ID, thread title, or null to generate a new identifier.
     * @param  mixed  $userIdOrConfig  Optional explicit user ID (int) or config array (ignored); when omitted the authenticated user id is used.
     *
     * @throws \RuntimeException If a required authenticated user id is not available or when a numeric thread id does not correspond to an existing ChatThread.
     */
    public function __construct(string|SessionIdentityContract|null $identifier = null, mixed $userIdOrConfig = null)
    {
        // Pattern 1: SessionIdentity object (LarAgent internal)
        if ($identifier instanceof SessionIdentityContract) {
            $this->identifier = $identifier->getChatName() ?? $identifier->getKey();
            $userId = $identifier->getUserId() ? (int) $identifier->getUserId() : Auth::id();
            $this->thread = $this->findOrCreateThread($this->identifier, $userId);
        } elseif ($identifier === null) {
            // Pattern 2: No identifier (StorageManager/ServiceProvider pattern)
            // Generate unique identifier, use authenticated user
            $this->identifier = 'thread-'.uniqid();
            $userId = Auth::id();
            if (! $userId) {
                throw new \RuntimeException('User ID is required for EloquentThreadChatHistory. User must be authenticated.');
            }
            $this->thread = $this->findOrCreateThread($this->identifier, $userId);
        } elseif (is_numeric($identifier)) {
            // Pattern 3: Numeric string = thread ID - load existing thread directly
            $this->identifier = $identifier;
            $thread = ChatThread::find((int) $identifier);
            if (! $thread) {
                throw new \RuntimeException("ChatThread with ID {$identifier} not found.");
            }
            $this->thread = $thread;
        } else {
            // Pattern 4: String identifier + optional userId or config array
            $this->identifier = $identifier;

            // userIdOrConfig can be:
            // - int: explicit userId
            // - array: config array (ignored, for ServiceProvider compatibility)
            // - null: use Auth::id()
            if (is_int($userIdOrConfig)) {
                $userId = $userIdOrConfig;
            } elseif (is_array($userIdOrConfig)) {
                // ServiceProvider pattern: new EloquentThreadChatHistory($name, [])
                // Config array is provided but not used, fallback to Auth::id()
                $userId = Auth::id();
            } else {
                // null or other: fallback to Auth::id()
                $userId = Auth::id();
            }

            if (! $userId) {
                throw new \RuntimeException('User ID is required for EloquentThreadChatHistory. User must be authenticated.');
            }

            $this->thread = $this->findOrCreateThread($this->identifier, $userId);
        }
    }

    /**
     * Finds an existing chat thread for the given user and identifier, or creates a new one.
     *
     * The `$identifier` may be a numeric thread ID or a thread title. If numeric, the method first
     * attempts to find a thread by ID and user; otherwise it searches by user and title before creating.
     *
     * @param  string  $identifier  Thread title or numeric thread ID.
     * @param  int  $userId  ID of the user that owns the thread.
     * @return ChatThread The found or newly created ChatThread instance.
     */
    protected function findOrCreateThread(string $identifier, int $userId): ChatThread
    {
        if (is_numeric($identifier)) {
            $thread = ChatThread::where('id', $identifier)
                ->where('user_id', $userId)
                ->first();

            if ($thread) {
                return $thread;
            }
        }

        $thread = ChatThread::where('user_id', $userId)
            ->where('title', $identifier)
            ->first();

        if ($thread) {
            return $thread;
        }

        return ChatThread::create([
            'user_id' => $userId,
            'title' => $identifier,
        ]);
    }

    /**
     * Persist a message to the current chat thread and update in-memory state.
     *
     * Saves the provided MessageInterface to the thread's persistent store, clears the message cache,
     * and, if the thread has no user-defined title (or its title starts with "thread-"), generates a title
     * from the first message. If the message metadata contains `query_generated` or `query_results`,
     * those keys are promoted to top-level columns on the stored record.
     *
     * @param  MessageInterface  $message  The message to persist.
     */
    public function addMessage(MessageInterface $message): void
    {
        $metadata = $message->getMetadata();

        $data = [
            'chat_thread_id' => $this->thread->id,
            'role' => $message->getRole(),
            'content' => $message->getContentAsString(),
            'metadata' => $metadata,
        ];

        if (isset($metadata['query_generated'])) {
            $data['query_generated'] = $metadata['query_generated'];
            unset($metadata['query_generated']);
        }

        if (isset($metadata['query_results'])) {
            $data['query_results'] = $metadata['query_results'];
            unset($metadata['query_results']);
        }

        $data['metadata'] = $metadata;

        ChatMessage::create($data);

        $this->messagesCache = null;

        if (! $this->thread->title || str_starts_with($this->thread->title, 'thread-')) {
            $this->thread->generateTitleFromFirstMessage();
        }
    }

    /**
     * Retrieve all messages for the current thread, converting stored ChatMessage records into a MessageArray and caching the result.
     *
     * The method converts each database ChatMessage into a LarAgent MessageInterface (ignoring messages with unsupported roles), stores the resulting collection in an in-memory cache, and returns that cached MessageArray on subsequent calls.
     *
     * @return MessageArray The converted messages for this thread; empty if the thread has no convertible messages.
     */
    public function getMessages(): MessageArray
    {
        if ($this->messagesCache !== null) {
            return $this->messagesCache;
        }

        $dbMessages = $this->thread->messages()->get();

        $messages = [];
        foreach ($dbMessages as $dbMessage) {
            $message = $this->convertToLarAgentMessage($dbMessage);
            if ($message) {
                $messages[] = $message;
            }
        }

        $this->messagesCache = new MessageArray($messages);

        return $this->messagesCache;
    }

    /**
     * Convert a stored ChatMessage model into a LarAgent message and enrich it with DB metadata.
     *
     * Merges the ChatMessage's metadata with additional fields (`db_id`, ISO8601 `created_at`) and, when present, `query_generated` and `query_results`, then returns a `UserMessage` for role `user`, an `AssistantMessage` for role `assistant`, or `null` for any other role.
     *
     * @param  ChatMessage  $dbMessage  The database chat message to convert.
     * @return MessageInterface|null `UserMessage` for role `user`, `AssistantMessage` for role `assistant`, or `null` otherwise.
     */
    protected function convertToLarAgentMessage(ChatMessage $dbMessage): ?MessageInterface
    {
        // Skip messages with empty content — the LLM API rejects them
        if (empty($dbMessage->content)) {
            return null;
        }

        $metadata = $dbMessage->metadata ?? [];
        if ($dbMessage->query_generated) {
            $metadata['query_generated'] = $dbMessage->query_generated;
        }
        if ($dbMessage->query_results) {
            $metadata['query_results'] = $dbMessage->query_results;
        }
        $metadata['db_id'] = $dbMessage->id;
        $metadata['created_at'] = $dbMessage->created_at?->toIso8601String();

        return match ($dbMessage->role) {
            'user' => new UserMessage($dbMessage->content, $metadata),
            'assistant' => new AssistantMessage($dbMessage->content, $metadata),
            default => null,
        };
    }

    /**
     * Get the thread identifier for this chat history.
     *
     * @return string The thread identifier.
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get the most recent message from the thread.
     *
     * @return MessageInterface|null The most recently created message for the thread, or null if no messages exist.
     */
    public function getLastMessage(): ?MessageInterface
    {
        $dbMessage = $this->thread->messages()
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $dbMessage) {
            return null;
        }

        return $this->convertToLarAgentMessage($dbMessage);
    }

    /**
     * Remove all messages for the current thread and clear the in-memory messages cache.
     *
     * This deletes persisted ChatMessage records belonging to the thread and resets the internal
     * MessageArray cache so subsequent reads will reload from storage.
     */
    public function clear(): void
    {
        $this->thread->messages()->delete();
        $this->messagesCache = null;
    }

    /**
     * Get the number of messages in the current thread.
     *
     * @return int The total number of messages in the thread.
     */
    public function count(): int
    {
        return $this->thread->messages()->count();
    }

    /**
     * Get all chat messages for this thread as an array.
     *
     * @return array The thread's messages, where each element is a message represented as an associative array.
     */
    public function toArray(): array
    {
        return $this->getMessages()->toArray();
    }

    /**
     * Clears the in-memory messages cache and reloads messages from persistent storage.
     *
     * This forces the history to discard any cached MessageArray and repopulate it by fetching
     * messages from the associated chat thread.
     */
    public function readFromMemory(): void
    {
        $this->messagesCache = null;
        $this->getMessages();
    }

    /**
     * Intentionally performs no action; chat history is persisted immediately and not buffered in memory.
     */
    public function writeToMemory(): void
    {
        // No-op: saves immediately in addMessage()
    }

    /**
     * Retrieve the ChatThread associated with this chat history.
     *
     * @return ChatThread The associated ChatThread model.
     */
    public function getThread(): ChatThread
    {
        return $this->thread;
    }

    /**
     * Get the ID of the associated chat thread.
     *
     * @return int The primary key ID of the associated ChatThread.
     */
    public function getThreadId(): int
    {
        return $this->thread->id;
    }

    /**
     * Create a chat history instance bound to a specific user and thread identifier.
     *
     * @param  string  $identifier  The thread identifier or title to use for the chat history.
     * @param  int  $userId  The ID of the user who owns the thread.
     * @return static A new instance associated with the given user and identifier.
     */
    public static function forUser(string $identifier, int $userId): static
    {
        return new static($identifier, $userId);
    }

    /**
     * Create a chat history instance for the given thread identifier.
     *
     * @param  string  $identifier  Thread identifier — may be a numeric thread ID, a thread title, or a session identifier.
     * @return static A new instance of EloquentThreadChatHistory for the specified identifier.
     */
    public static function for(string $identifier): static
    {
        return new static($identifier);
    }
}
