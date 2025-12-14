<?php

namespace App\Filament\Resources\Tasks\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('task_id')
                    ->label('Task ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->tooltip('Unique task identifier from Zuora'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('workflow.name')
                    ->label('Workflow')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('action_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Email' => 'info',
                        'Export' => 'success',
                        'Iterate' => 'warning',
                        'SOAP' => 'primary',
                        'Cancel' => 'danger',
                        'WriteOff' => 'warning',
                        'Query' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('object')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->object),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'High' => 'danger',
                        'Medium' => 'warning',
                        'Low' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('call_type')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('state')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'pending' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_on')
                    ->label('Created On')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_on')
                    ->label('Updated On')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('workflow')
                    ->relationship('workflow', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('action_type')
                    ->options([
                        'Email' => 'Email',
                        'Export' => 'Export',
                        'Import' => 'Import',
                        'SOAP' => 'SOAP',
                        'Query' => 'Query',
                        'Iterate' => 'Iterate',
                        'Cancel' => 'Cancel',
                        'WriteOff' => 'WriteOff',
                    ]),

                SelectFilter::make('priority')
                    ->options([
                        'High' => 'High',
                        'Medium' => 'Medium',
                        'Low' => 'Low',
                    ]),

                SelectFilter::make('state')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View Details')
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
