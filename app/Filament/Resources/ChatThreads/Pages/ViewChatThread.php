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

    public function getTitle(): string
    {
        return $title = $this->record->title ?? 'AI Chat';
    }

    public function getSubheading(): ?string
    {
        return 'Created: '.$this->record->created_at->format('d/m/Y H:i');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Livewire::make(ChatBox::class, ['thread' => $this->record])
                    ->key('chat-box-'.$this->record->id),
            ]);
    }

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
