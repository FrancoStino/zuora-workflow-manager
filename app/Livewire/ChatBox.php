<?php

namespace App\Livewire;

use App\Models\ChatThread;
use App\Services\LaragentChatService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

class ChatBox extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ChatThread $thread;

    public ?array $data = [];

    public bool $isLoading = false;

    public bool $hasError = false;

    public ?string $lastQuestion = null;

    /**
     * Initialize the component with the given chat thread and prepare the form state.
     *
     * @param ChatThread $thread The chat thread instance this component will manage.
     */
    public function mount(ChatThread $thread): void
    {
        $this->thread = $thread;
        $this->form->fill();
    }

    /**
     * Builds and returns the form schema used for the chat input.
     *
     * Configures a single `message` textarea (placeholder, required, single row,
     * Enter-to-send behaviour, and disabled while loading) and sets the schema's
     * state path to `data`.
     *
     * @param Schema $schema The form schema builder to configure.
     * @return Schema The configured schema containing the chat message textarea.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('message')
                    ->hiddenLabel()
                    ->placeholder('Type your message... (Shift+Enter for new line)')
                    ->rows(1)
                    ->required()
                    ->disabled($this->isLoading)
                    ->extraInputAttributes([
                        'x-on:keydown.enter' => 'if (!$event.shiftKey) { $event.preventDefault(); $wire.sendMessage() }',
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Create the "Send" action for submitting the current chat message.
     *
     * @return Action An Action that triggers `sendMessage` when invoked; labeled "Send", uses the `heroicon-o-paper-airplane` icon, and is disabled while the component is loading.
     */
    public function sendAction(): Action
    {
        return Action::make('send')
            ->label('Send')
            ->icon('heroicon-o-paper-airplane')
            ->disabled(fn (): bool => $this->isLoading)
            ->action('sendMessage');
    }

    /**
     * Create the "retry" UI action used to re-run the most recent failed question.
     *
     * The action is visible only when the component is in an error state and a last question exists.
     *
     * @return \Filament\Actions\Action The configured Action instance for retrying the last failed question.
     */
    public function retryAction(): Action
    {
        return Action::make('retry')
            ->label('Retry')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (): bool => $this->hasError
                && $this->lastQuestion !== null)
            ->action('retryLastQuestion');
    }

    /**
     * Retries the most recent user question by scheduling a client-side request to regenerate its response.
     *
     * If there is no last question recorded, the method does nothing. Otherwise it clears the error flag,
     * marks the component as loading, and schedules a JavaScript call to invoke `generateResponse` with the
     * escaped last question after a short delay.
     */
    public function retryLastQuestion(): void
    {
        if ($this->lastQuestion === null) {
            return;
        }

        $this->hasError = false;
        $this->isLoading = true;

        $this->js("setTimeout(() => \$wire.generateResponse('{$this->escapeJs($this->lastQuestion)}'), 50)");
    }

    /**
     * Escape a string for safe embedding in JavaScript string literals.
     *
     * Converts carriage returns and newlines to the literal `\r` and `\n`
     * sequences and adds backslashes before quotes and backslashes.
     *
     * @param string $value The input string to escape for JavaScript.
     * @return string The escaped string suitable for inclusion in a JS string literal.
     */
    private function escapeJs(string $value): string
    {
        return addslashes(str_replace(["\r", "\n"], ['\\r', '\\n'], $value));
    }

    /**
     * Handles the current form message: stores it as a user message, resets input state, and initiates assistant response generation.
     *
     * Trims and ignores empty messages. When a valid message is present, clears the form input, clears error state, records the message as the last question, persists the message on the thread with role `user`, triggers title generation from the first message, marks the component as loading, and schedules a JavaScript call to start generating the assistant response.
     */
    public function sendMessage(): void
    {
        $state = $this->form->getState();
        $message = trim($state['message'] ?? '');

        if (empty($message)) {
            return;
        }

        $this->form->fill(['message' => '']);
        $this->hasError = false;
        $this->lastQuestion = $message;

        $this->thread->messages()->create([
            'role' => 'user',
            'content' => $message,
        ]);
        $this->thread->generateTitleFromFirstMessage();

        $this->isLoading = true;

        $this->js("setTimeout(() => \$wire.generateResponse('{$this->escapeJs($message)}'), 50)");
    }

    /**
     * Request and apply an AI-generated response for the given question, streaming updates to the frontend and updating component state.
     *
     * Streams response chunks to the frontend, refreshes the chat thread, and updates component flags:
     * - clears `isLoading` when finished,
     * - clears `lastQuestion` on success,
     * - sets `hasError` when streaming and fallback attempts fail or the fallback response reports an error.
     *
     * @param string $question The user's question to send to the chat service.
     */
    public function generateResponse(string $question): void
    {
        try {
            $chatService = app(LaragentChatService::class);

            foreach ($chatService->askStream($this->thread, $question) as $chunk) {
                $this->stream('streamContent', $chunk);
            }

            $this->thread->refresh();
            $this->hasError = false;
            $this->lastQuestion = null;
        } catch (Exception $e) {
            try {
                $chatService = app(LaragentChatService::class);
                $response = $chatService->ask($this->thread, $question);
                $this->thread->refresh();

                if ($response->metadata['error'] ?? false) {
                    $this->hasError = true;
                } else {
                    $this->hasError = false;
                    $this->lastQuestion = null;
                }
            } catch (Exception $fallbackError) {
                $this->hasError = true;
            }
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Render the chat box view with the current thread's messages separated into user/assistant and system messages.
     *
     * The view receives two variables:
     * - `messages`: a collection of messages whose `role` is 'user' or 'assistant', ordered by `created_at` ascending.
     * - `systemMessages`: a collection of messages whose `role` is not 'user' or 'assistant', ordered by `created_at` ascending.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $allMessages = $this->thread
            ->messages()->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.chat-box', [
            'messages' => $allMessages->filter(fn ($msg,
            ) => in_array($msg->role, ['user', 'assistant'])),
            'systemMessages' => $allMessages->filter(fn ($msg,
            ) => ! in_array($msg->role, ['user', 'assistant'])),
        ]);
    }
}