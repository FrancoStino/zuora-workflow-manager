<?php

namespace App\Filament\Resources\Workflows;

use App\Filament\Resources\Workflows\Pages\CreateWorkflow;
use App\Filament\Resources\Workflows\Pages\EditWorkflow;
use App\Filament\Resources\Workflows\Pages\ListWorkflows;
use App\Filament\Resources\Workflows\Schemas\WorkflowForm;
use App\Filament\Resources\Workflows\Tables\WorkflowsTable;
use App\Models\Workflow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form ( Schema $schema ) : Schema
    {
        return WorkflowForm ::configure ( $schema );
    }

    public static function table ( Table $table ) : Table
    {
        return WorkflowsTable ::configure ( $table );
    }

    public static function canViewAny () : bool
    {
        return auth () -> user () -> hasRole ( 'super_admin' );
    }

    public static function canCreate () : bool
    {
        return auth () -> user () -> hasRole ( 'super_admin' );
    }

    public static function canEdit ( $record ) : bool
    {
        return auth () -> user () -> hasRole ( 'super_admin' );
    }

    public static function canDelete ( $record ) : bool
    {
        return auth () -> user () -> hasRole ( 'super_admin' );
    }

    public static function getRelations () : array
    {
        return [
            //
        ];
    }

    public static function getPages () : array
    {
        return [
            'index'  => ListWorkflows ::route ( '/' ),
            'create' => CreateWorkflow ::route ( '/create' ),
            'edit'   => EditWorkflow ::route ( '/{record}/edit' ),
        ];
    }
}
