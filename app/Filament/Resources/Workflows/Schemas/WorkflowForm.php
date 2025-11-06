<?php

namespace App\Filament\Resources\Workflows\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WorkflowForm
{
    public static function configure ( Schema $schema ) : Schema
    {
        return $schema
            -> components ( [
                TextInput ::make ( 'name' )
                          -> required ()
                          -> maxLength ( 255 ),

                Textarea ::make ( 'description' )
                         -> maxLength ( 1000 ),

                Select ::make ( 'state' )
                       -> options ( [
                           'Active'   => 'Active',
                           'Inactive' => 'Inactive',
                           'Error'    => 'Error',
                       ] )
                       -> required (),

                // Note: id, created_on, updated_on are managed by Zuora API
            ] );
    }
}
