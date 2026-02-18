<?php

namespace App\Filament\Resources\ChatThreads\Pages;

use App\Filament\Resources\ChatThreads\ChatThreadResource;
use App\Livewire\ChatBox;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewChatThread extends ViewRecord
{
    protected static string $resource = ChatThreadResource::class;

    /**
     * Provide the page title for the current chat thread.
     *
     * @return string The thread's title if present, otherwise "AI Chat".
     */
    public function getTitle(): string
    {
        return $title = $this->record->title ?? 'AI Chat';
    }

    /**
     * Provide a subheading string containing the record's creation timestamp.
     *
     * @return string|null The subheading formatted as "Created: dd/mm/YYYY HH:MM", or `null` if no record is available.
     */
    public function getSubheading(): ?string
    {
        return 'Created: '.$this->record->created_at->format('d/m/Y H:i');
    }

    /**
     * Builds the page content schema by embedding a ChatBox Livewire component scoped to the current thread.
     *
     * @param Schema $schema The base content schema to extend.
     * @return Schema The schema containing the ChatBox component keyed to the current record.
     */
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Livewire::make(ChatBox::class, ['thread' => $this->record])
                    ->key('chat-box-'.$this->record->id),
            ]);
    }

    /**
     * Removes all messages from the current chat thread, clears its title, persists the change, and displays a success notification.
     */
    public function clearHistory(): void
    {
        $this->record->messages()->delete();
        $this->record->title = null;
        $this->record->save();

        Notification::make()
            ->title('Chat history cleared')
            ->success()
            ->send();
    }

    /**
     * Get header actions for the page: a Delete action with confirmation and a Clear action that prompts confirmation and invokes `clearHistory` to remove the thread's messages.
     *
     * @return array<int, \Filament\Pages\Actions\Action> The header actions displayed in the page header.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->icon(Heroicon::OutlinedTrash),
            Action::make('clear')
                ->label('Clear')
                ->icon(Heroicon::OutlinedPaintBrush)
                ->color('warning')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Clear chat history')
                ->modalDescription('Are you sure you want to clear all messages in this conversation?')
                ->modalSubmitActionLabel('Yes, clear all')
                ->action('clearHistory'),
        ];
    }
}