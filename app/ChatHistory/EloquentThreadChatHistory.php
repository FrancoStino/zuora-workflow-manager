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

    public function __construct(string|SessionIdentityContract|null $identifier = null, mixed $userIdOrConfig = null)
    {
        // Pattern 1: SessionIdentity object (LarAgent internal)
        if ($identifier instanceof SessionIdentityContract) {
            $this->identifier = $identifier->getChatName() ?? $identifier->getKey();
            $userId = $identifier->getUserId() ? (int) $identifier->getUserId() : Auth::id();
        } elseif ($identifier === null) {
            // Pattern 2: No identifier (StorageManager/ServiceProvider pattern)
            // Generate unique identifier, use authenticated user
            $this->identifier = 'thread-' . uniqid();
            $userId = Auth::id();
        } else {
            // Pattern 3: String identifier + optional userId or config array
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
        }

        if (!$userId) {
            throw new \RuntimeException('User ID is required for EloquentThreadChatHistory. User must be authenticated.');
        }

        $this->thread = $this->findOrCreateThread($this->identifier, $userId);
    }

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

        if (!$this->thread->title || str_starts_with($this->thread->title, 'thread-')) {
            $this->thread->generateTitleFromFirstMessage();
        }
    }

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

    protected function convertToLarAgentMessage(ChatMessage $dbMessage): ?MessageInterface
    {
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
            'user' => new UserMessage($dbMessage->content ?? '', $metadata),
            'assistant' => new AssistantMessage($dbMessage->content ?? '', $metadata),
            default => null,
        };
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLastMessage(): ?MessageInterface
    {
        $dbMessage = $this->thread->messages()
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$dbMessage) {
            return null;
        }

        return $this->convertToLarAgentMessage($dbMessage);
    }

    public function clear(): void
    {
        $this->thread->messages()->delete();
        $this->messagesCache = null;
    }

    public function count(): int
    {
        return $this->thread->messages()->count();
    }

    public function toArray(): array
    {
        return $this->getMessages()->toArray();
    }

    public function readFromMemory(): void
    {
        $this->messagesCache = null;
        $this->getMessages();
    }

    public function writeToMemory(): void
    {
        // No-op: saves immediately in addMessage()
    }

    public function getThread(): ChatThread
    {
        return $this->thread;
    }

    public function getThreadId(): int
    {
        return $this->thread->id;
    }

    public static function forUser(string $identifier, int $userId): static
    {
        return new static($identifier, $userId);
    }

    public static function for(string $identifier): static
    {
        return new static($identifier);
    }
}
