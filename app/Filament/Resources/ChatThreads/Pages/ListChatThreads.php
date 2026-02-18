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

    /**
     * Get the page title displayed in the Filament header.
     *
     * @return string The page title.
     */
    public function getTitle(): string
    {
        return 'AI Chat (Beta)';
    }

    /**
     * Builds header actions for the page, including a "New Chat" action that starts a chat thread or warns when AI Chat is disabled.
     *
     * The "New Chat" action is disabled when GeneralSettings->aiChatEnabled is false. When triggered while enabled,
     * it creates a new ChatThread for the current user and redirects to the thread's view; when disabled it sends a warning notification.
     *
     * @return array<int, \Filament\Actions\Action> Header actions for the page.
     */
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