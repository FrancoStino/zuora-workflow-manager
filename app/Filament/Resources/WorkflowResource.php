<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkflowResource\Pages;
use App\Models\Workflow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Workflows';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'workflows';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    //	public static function getGloballySearchableAttributes () : array
    //	{
    //		return [ 'name', 'zuora_id', 'customer.name' ];
    //	}

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Customer' => $record->customer->name ?? 'N/A',
            'Workflow ID' => $record->zuora_id,
            'State' => $record->state,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflows::route('/'),
            'view' => Pages\ViewWorkflow::route('/{record}'),
        ];
    }
}
