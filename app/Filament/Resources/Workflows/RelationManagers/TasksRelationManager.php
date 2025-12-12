<?php

namespace App\Filament\Resources\Workflows\RelationManagers;

use CodebarAg\FilamentJsonField\Infolists\Components\JsonEntry;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('zuora_id')
                    ->label('Zuora ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->tooltip('Task ID in Zuora'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('action_type')
                    ->label('Action Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
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
                    ->tooltip(fn($record) => $record->object),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
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
                    ->color(fn(?string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'pending' => 'gray',
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
                    ->icon('heroicon-o-eye')
                    ->slideOver()
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalHeading(fn($record) => 'Task: ' . $record->name)
                    ->modalDescription(fn($record) => 'Zuora ID: ' . $record->zuora_id)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema(fn(): array => $this->getTaskInfolistSchema()),
            ])
            ->recordAction('viewDetails')
            ->defaultSort('zuora_id', 'asc');
    }

    protected function getTaskInfolistSchema(): array
    {
        return [
            Section::make('General Information')
                ->icon('heroicon-o-information-circle')
                ->collapsible()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('zuora_id')
                                ->label('Zuora ID')
                                ->icon('heroicon-o-hashtag')
                                ->copyable()
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('name')
                                ->label('Task Name')
                                ->icon('heroicon-o-document-text')
                                ->weight('bold')
                                ->columnSpanFull(),

                            TextEntry::make('action_type')
                                ->label('Action Type')
                                ->icon('heroicon-o-bolt')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'Email' => 'info',
                                    'Export' => 'success',
                                    'Iterate' => 'warning',
                                    'SOAP' => 'primary',
                                    'Cancel' => 'danger',
                                    'WriteOff' => 'warning',
                                    'Query' => 'info',
                                    default => 'gray',
                                }),

                            TextEntry::make('priority')
                                ->label('Priority')
                                ->icon('heroicon-o-flag')
                                ->badge()
                                ->color(fn(string $state): string => match ($state) {
                                    'High' => 'danger',
                                    'Medium' => 'warning',
                                    'Low' => 'success',
                                    default => 'gray',
                                }),

                            TextEntry::make('object')
                                ->label('Object')
                                ->icon('heroicon-o-cube')
                                ->placeholder('N/A'),

                            TextEntry::make('object_id')
                                ->label('Object ID')
                                ->icon('heroicon-o-key')
                                ->copyable()
                                ->placeholder('N/A'),

                            TextEntry::make('call_type')
                                ->label('Call Type')
                                ->icon('heroicon-o-arrow-path')
                                ->badge()
                                ->placeholder('N/A'),

                            TextEntry::make('task_id')
                                ->label('Parent Task ID')
                                ->icon('heroicon-o-link')
                                ->placeholder('N/A'),

                            TextEntry::make('concurrent_limit')
                                ->label('Concurrent Limit')
                                ->icon('heroicon-o-server')
                                ->numeric()
                                ->default(9999999),

                            TextEntry::make('state')
                                ->label('State')
                                ->icon('heroicon-o-signal')
                                ->badge()
                                ->color(fn(?string $state): string => match ($state) {
                                    'completed' => 'success',
                                    'in_progress' => 'warning',
                                    'pending' => 'gray',
                                    default => 'gray',
                                })
                                ->placeholder('N/A'),
                        ]),
                ]),

            Section::make('Parameters')
                ->icon('heroicon-o-cog-6-tooth')
                ->description('Complete task configuration')
                ->visible(fn($record) => !empty($record->parameters))
                ->collapsible()
                ->schema([
                    JsonEntry::make('parameters')
                        ->hiddenLabel()
                        ->darkTheme(),
                ]),

            Section::make('CSS Position')
                ->icon('heroicon-o-map-pin')
                ->description('Task coordinates in workflow graph')
                ->collapsible()
                ->collapsed()
                ->visible(fn($record) => !empty($record->css))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('css.top')
                                ->label('Top')
                                ->icon('heroicon-o-arrow-up'),

                            TextEntry::make('css.left')
                                ->label('Left')
                                ->icon('heroicon-o-arrow-left'),
                        ]),
                ]),

            Section::make('Tags & Assignments')
                ->icon('heroicon-o-tag')
                ->collapsible()
                ->collapsed()
                ->visible(fn($record) => !empty($record->tags) || !empty($record->assignment))
                ->schema([
                    TextEntry::make('tags')
                        ->label('Tags')
                        ->badge()
                        ->separator(',')
                        ->visible(fn($record) => !empty($record->tags))
                        ->placeholder('No tags'),

                    JsonEntry::make('assignment')
                        ->label('Assignments')
                        ->darkTheme()
                        ->visible(fn($record) => !empty($record->assignment))
                        ->columnSpanFull(),
                ]),

            Section::make('Zuora Details')
                ->icon('heroicon-o-building-office')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('zuora_org_id')
                                ->label('Zuora Org ID')
                                ->icon('heroicon-o-building-office-2')
                                ->copyable()
                                ->placeholder('N/A'),

                            TextEntry::make('subprocess_id')
                                ->label('Subprocess ID')
                                ->icon('heroicon-o-rectangle-stack')
                                ->placeholder('N/A'),

                            TextEntry::make('zuora_org_ids')
                                ->label('Zuora Org IDs')
                                ->badge()
                                ->separator(',')
                                ->visible(fn($record) => !empty($record->zuora_org_ids))
                                ->placeholder('N/A')
                                ->columnSpanFull(),
                        ]),
                ]),

            Section::make('Timestamps')
                ->icon('heroicon-o-clock')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('created_on')
                                ->label('Created On')
                                ->icon('heroicon-o-calendar')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('N/A'),

                            TextEntry::make('updated_on')
                                ->label('Updated On')
                                ->icon('heroicon-o-calendar')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('N/A'),

                            TextEntry::make('created_at')
                                ->label('Created in DB')
                                ->icon('heroicon-o-calendar-days')
                                ->dateTime('d/m/Y H:i'),

                            TextEntry::make('updated_at')
                                ->label('Updated in DB')
                                ->icon('heroicon-o-calendar-days')
                                ->dateTime('d/m/Y H:i'),
                        ]),
                ]),
        ];
    }
}
