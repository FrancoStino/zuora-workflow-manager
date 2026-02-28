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

    /**
     * Get the display title for a global search result from the given record.
     *
     * @param  Model  $record  The model instance to obtain the title from.
     * @return string The record's title if present, otherwise 'New Chat'.
     */
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->title ?? 'New Chat';
    }

    /**
     * Provide label/value pairs used to display a chat thread in global search results.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $record  The chat thread record to extract details from.
     * @return array<string, int|string> An associative array with:
     *                                   - 'Messages' => the number of related messages,
     *                                   - 'Created'  => the creation timestamp formatted as 'd/m/Y H:i'.
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Messages' => $record->messages()->count(),
            'Created' => $record->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Register the resource pages and their URI routes.
     *
     * @return array<string,mixed> Associative array mapping page keys to route definitions:
     *                             - 'index' => route for listing chat threads
     *                             - 'view'  => route for viewing a single chat thread
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatThreads::route('/'),
            'view' => Pages\ViewChatThread::route('/{record}'),
        ];
    }

    /**
     * Provides the label displayed as a badge in the resource navigation.
     *
     * @return string|null The badge text (e.g., `'Beta'`), or `null` when no badge should be shown.
     */
    public static function getNavigationBadge(): ?string
    {
        return 'Beta';
    }

    /**
     * Specifies the color or color configuration for the resource's navigation badge.
     *
     * @return string|array|null The badge color name (e.g., `'warning'`), a color configuration array, or `null` to use the default/no color.
     */
    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    /**
     * Builds the resource's base Eloquent query scoped to the currently authenticated user.
     *
     * @return \Illuminate\Database\Eloquent\Builder The query builder filtered to records where `user_id` equals the authenticated user's ID.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id());
    }

    /**
     * Tooltip text for the navigation badge indicating the feature's beta status.
     *
     * @return string|null The tooltip text to display, or `null` to show no tooltip.
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'AI Chat is in beta - features may change';
    }

    /**
     * Configure and return the Filament table schema for this resource.
     *
     * @param  \Filament\Tables\Table  $table  The table instance to configure.
     * @return \Filament\Tables\Table The configured table instance.
     */
    public static function table(Table $table): Table
    {
        return ChatThreadsTable::configure($table);
    }

    /**
     * Indicates whether creating new ChatThread records is permitted.
     *
     * Defers to the ChatThreadPolicy::create method for authorization.
     *
     * @return bool `true` if the current user is authorized to create ChatThread records, `false` otherwise.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', ChatThread::class) ?? false;
    }
}
