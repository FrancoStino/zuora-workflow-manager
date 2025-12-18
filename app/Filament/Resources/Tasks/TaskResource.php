<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|null|UnitEnum $navigationGroup = 'Zuora Management';

    protected static ?int $navigationSort = 3;

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Workflow Name' => $record->workflow->name ?? 'N/A',
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'view' => Pages\ViewTask::route('/{record}'),
        ];
    }
}
