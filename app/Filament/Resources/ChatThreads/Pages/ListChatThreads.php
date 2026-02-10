<?php

namespace App\Filament\Resources\ChatThreads\Pages;

use App\Filament\Resources\ChatThreads\ChatThreadResource;
use App\Models\ChatThread;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListChatThreads extends ListRecords
{
    protected static string $resource = ChatThreadResource::class;

    public function getTitle(): string
    {
        return 'AI Chat (Beta)';
    }

    protected function getHeaderActions(): array
    {
        $settings = app(GeneralSettings::class);

        return [
            Action::make('new_chat')
                ->label('New Chat')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->disabled(! $settings->aiChatEnabled)
                ->action(function () use ($settings) {
                    if (! $settings->aiChatEnabled) {
                        Notification::make()
                            ->title('AI Chat disabled')
                            ->body('Enable AI Chat in settings.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $thread = ChatThread::create([
                        'user_id' => auth()->id(),
                    ]);

                    $this->redirect(ChatThreadResource::getUrl('view',
                        ['record' => $thread]));
                }),
        ];
    }
}
