<?php

namespace App\Filament\Resources\Workflows\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkflowsTable
{
    public const DATE_TIME_FORMAT = 'Y-m-d H:i';

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('zuora_id')
                    ->label('Workflow ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('state')
                    ->label('State')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('tasks_count')
                    ->counts('tasks')
                    ->label('Tasks')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('created_on')
                    ->label('Created')
                    ->dateTime(WorkflowsTable::DATE_TIME_FORMAT)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('updated_on')
                    ->label('Updated')
                    ->dateTime(WorkflowsTable::DATE_TIME_FORMAT)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime(WorkflowsTable::DATE_TIME_FORMAT)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Never'),
            ])
            ->filters([
                SelectFilter::make('customer')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('state')
                    ->label('Workflow State')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View Details')
                    ->button(),
            ])
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession();
    }
}
