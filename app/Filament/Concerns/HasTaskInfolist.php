<?php

namespace App\Filament\Concerns;

use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

trait HasTaskInfolist
{
    private const DATETIME_FORMAT = 'd/m/Y H:i';

    protected function getTaskInfolistSchema(): array
    {
        return [
            Section::make('General Information')
                ->columnSpanFull()
                ->icon(Heroicon::OutlinedInformationCircle)
                ->collapsible()
                ->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('task_id')
                                ->label('Task ID')
                                ->icon(Heroicon::OutlinedHashtag)
                                ->copyable()
                                ->badge()
                                ->color('primary'),

                            TextEntry::make('action_type')
                                ->label('Action Type')
                                ->icon(Heroicon::OutlinedBolt)
                                ->badge()
                                ->color(fn (string $state,
                                ): string => match ($state) {
                                    'Email', 'Query' => 'info',
                                    'Export' => 'success',
                                    'Iterate', 'WriteOff' => 'warning',
                                    'SOAP' => 'primary',
                                    'Cancel' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('priority')
                                ->label('Priority')
                                ->icon(Heroicon::OutlinedFlag)
                                ->badge()
                                ->color(fn (string $state,
                                ): string => match ($state) {
                                    'High' => 'danger',
                                    'Medium' => 'warning',
                                    'Low' => 'success',
                                    default => 'gray',
                                }),

                            TextEntry::make('object')
                                ->label('Object')
                                ->icon(Heroicon::OutlinedCube)
                                ->placeholder('N/A'),

                            TextEntry::make('object_id')
                                ->label('Object ID')
                                ->icon(Heroicon::OutlinedKey)
                                ->copyable()
                                ->placeholder('N/A'),

                            TextEntry::make('call_type')
                                ->label('Call Type')
                                ->icon(Heroicon::OutlinedArrowPath)
                                ->badge()
                                ->placeholder('N/A'),

                            TextEntry::make('next_task_id')
                                ->label('Next Task ID')
                                ->icon(Heroicon::OutlinedLink)
                                ->placeholder('N/A'),

                            TextEntry::make('concurrent_limit')
                                ->label('Concurrent Limit')
                                ->icon(Heroicon::OutlinedServer)
                                ->numeric()
                                ->default(9999999),

                            TextEntry::make('state')
                                ->label('State')
                                ->icon(Heroicon::OutlinedSignal)
                                ->badge()
                                ->color(fn (?string $state,
                                ): string => match ($state) {
                                    'completed' => 'success',
                                    'in_progress' => 'warning',
                                    default => 'gray',
                                })
                                ->placeholder('N/A'),
                        ]),
                ]),

            Section::make('Parameters')
                ->columnSpanFull()
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->description('Complete task configuration')
                ->visible(fn ($record) => ! empty($record->parameters))
                ->collapsible()
                ->collapsed()
                ->schema([
                    CodeEntry::make('parameters')
                        ->hiddenLabel()
                        ->copyable()
                        ->copyMessage('Copied!'),
                ]),

            Section::make('CSS Position')
                ->icon(Heroicon::OutlinedMapPin)
                ->description('Task coordinates in workflow graph')
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record) => ! empty($record->css))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('css.top')
                                ->label('Top')
                                ->icon(Heroicon::OutlinedArrowUp),

                            TextEntry::make('css.left')
                                ->label('Left')
                                ->icon(Heroicon::OutlinedArrowLeft),
                        ]),
                ]),

            Section::make('Tags & Assignments')
                ->icon(Heroicon::OutlinedTag)
                ->collapsible()
                ->collapsed()
                ->visible(fn ($record) => ! empty($record->tags)
                    || ! empty($record->assignment))
                ->schema([
                    TextEntry::make('tags')
                        ->label('Tags')
                        ->badge()
                        ->separator(',')
                        ->visible(fn ($record) => ! empty($record->tags))
                        ->placeholder('No tags'),

                    CodeEntry::make('assignment')
                        ->label('Assignments')
                        ->copyable()
                        ->copyMessage('Copied!')
                        ->visible(fn ($record) => ! empty($record->assignment)),
                ]),

            Section::make('Timestamps')
                ->description('Task creation and update timestamps')
                ->icon(Heroicon::OutlinedClock)
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('created_on')
                                ->label('Created On')
                                ->icon(Heroicon::OutlinedCalendar)
                                ->dateTime(self::DATETIME_FORMAT)
                                ->placeholder('N/A'),

                            TextEntry::make('updated_on')
                                ->label('Updated On')
                                ->icon(Heroicon::OutlinedCalendar)
                                ->dateTime(self::DATETIME_FORMAT)
                                ->placeholder('N/A'),

                            TextEntry::make('created_at')
                                ->label('Created in DB')
                                ->icon(Heroicon::OutlinedCalendarDays)
                                ->dateTime(self::DATETIME_FORMAT),

                            TextEntry::make('updated_at')
                                ->label('Updated in DB')
                                ->icon(Heroicon::OutlinedCalendarDays)
                                ->dateTime(self::DATETIME_FORMAT),
                        ]),
                ]),
        ];
    }
}
