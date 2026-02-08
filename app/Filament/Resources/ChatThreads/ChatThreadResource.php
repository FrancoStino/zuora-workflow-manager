<?php

namespace App\Filament\Resources\ChatThreads;

use App\Filament\Resources\ChatThreads\Tables\ChatThreadsTable;
use App\Models\ChatThread;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ChatThreadResource extends Resource
{
    protected static ?string $model = ChatThread::class;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'AI Chat';

    protected static string|UnitEnum|null $navigationGroup = 'Tools';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'ai-chat';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title ?? 'New Chat';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Messages' => $record->messages()->count(),
            'Created' => $record->created_at->format('d/m/Y H:i'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatThreads::route('/'),
            'view' => Pages\ViewChatThread::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Beta';
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'AI Chat is in beta - features may change';
    }

    public static function table(Table $table): Table
    {
        return ChatThreadsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return true;
    }
}
