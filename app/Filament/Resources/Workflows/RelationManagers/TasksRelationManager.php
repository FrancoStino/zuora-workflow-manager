<?php

namespace App\Filament\Resources\Workflows\RelationManagers;

use App\Filament\Concerns\HasTaskInfolist;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    use HasTaskInfolist;

    protected static string $relationship = 'tasks';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('task_id')
                    ->label('Task ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->tooltip('Unique task identifier from Zuora'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('action_type')
                    ->label('Action Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Email' => 'info',
                        'Export' => 'success',
                        'Iterate' => 'warning',
                        'SOAP' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('object')
                    ->label('Object')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->object),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'High' => 'danger',
                        'Medium' => 'warning',
                        'Low' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('call_type')
                    ->label('Call Type')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('concurrent_limit')
                    ->label('Concurrent Limit')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('state')
                    ->label('State')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('viewDetails')
                    ->label('Details')
                    ->icon(Heroicon::OutlinedEye)
                    ->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalHeading(fn ($record) => 'Task: '.$record->name)
                    ->modalDescription(fn ($record) => 'Task ID: '.$record->task_id)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema(fn (): array => $this->getTaskInfolistSchema()),
            ])
            ->recordAction('viewDetails')
            ->defaultSort('task_id');
    }
}
