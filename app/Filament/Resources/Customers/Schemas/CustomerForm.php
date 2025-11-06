<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure ( Schema $schema ) : Schema
    {
        return $schema
            -> components ( [
                TextInput ::make ( 'name' )
                          -> required ()
                          -> maxLength ( 255 ),

                TextInput ::make ( 'client_id' )
                          -> required ()
                          -> maxLength ( 255 ),

                TextInput ::make ( 'client_secret' )
                          -> label ( 'Client Secret' )
                          -> required ( fn ( $context ) => $context === 'create' )
                          -> password ()
                          -> revealable ()
                          -> maxLength ( 255 )
                          -> dehydrateStateUsing ( fn ( $state, $record ) => $state ? : ( $record ? $record -> client_secret : null ) )
                          -> placeholder ( fn ( $record ) => $record ? '***** (già impostato)' : null ),

                TextInput ::make ( 'base_url' )
                          -> label ( 'Base URL' )
                          -> default ( 'https://rest.zuora.com' )
                          -> url ()
                          -> maxLength ( 255 ),
            ] );
    }
}
