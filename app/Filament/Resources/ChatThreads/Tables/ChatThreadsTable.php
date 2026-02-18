<?php

namespace App\Filament\Resources\ChatThreads\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChatThreadsTable
{
    public const DATE_TIME_FORMAT = 'd/m/Y H:i';

    /**
     * Configure the Filament table for chat threads with columns, row actions, sorting, pagination, and session-persisted search.
     *
     * @param Table $table The Table instance to configure.
     * @return Table The configured Table instance ready for rendering (includes title, messages count, created/updated columns, actions, default sort, pagination, and search persistence).
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->placeholder('New Chat'),
                TextColumn::make('messages_count')
                    ->counts('messages')
                    ->label('Messages')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Message')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()
                    ->label('Open Chat')
                    ->button(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 25, 50])
            ->persistSearchInSession();
    }
}