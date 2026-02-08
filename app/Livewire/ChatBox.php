<?php

namespace App\Livewire;

use App\Models\ChatThread;
use App\Services\NeuronChatService;
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

    public function mount(ChatThread $thread): void
    {
        $this->thread = $thread;
        $this->form->fill();
    }

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

    public function sendAction(): Action
    {
        return Action::make('send')
            ->label('Send')
            ->icon('heroicon-o-paper-airplane')
            ->disabled(fn (): bool => $this->isLoading)
            ->action('sendMessage');
    }

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

    public function retryLastQuestion(): void
    {
        if ($this->lastQuestion === null) {
            return;
        }

        $this->hasError = false;
        $this->isLoading = true;

        $this->js("setTimeout(() => \$wire.generateResponse('{$this->escapeJs($this->lastQuestion)}'), 50)");
    }

    private function escapeJs(string $value): string
    {
        return addslashes(str_replace(["\r", "\n"], ['\\r', '\\n'], $value));
    }

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

    public function generateResponse(string $question): void
    {
        try {
            // Use container to get ChatService based on AI_PROVIDER config
            // This supports both neuron-ai and laragent seamlessly
            $chatService = app(NeuronChatService::class);

            // Streaming
            foreach ($chatService->askStream($this->thread, $question) as $chunk) {
                $this->stream('streamContent', $chunk);
            }

            $this->thread->refresh();
            $this->hasError = false;
            $this->lastQuestion = null;
        } catch (Exception $e) {
            // Fallback to sync if streaming fails
            try {
                $chatService = app(NeuronChatService::class);
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
