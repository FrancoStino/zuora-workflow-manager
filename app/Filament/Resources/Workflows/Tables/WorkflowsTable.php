<?php

namespace App\Filament\Resources\Workflows\Tables;

use App\Services\ZuoraService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class WorkflowsTable
{
    public static function configure ( Table $table ) : Table
    {
        return $table
            -> columns ( [
                TextColumn ::make ( 'id' ) -> label ( 'ID' ) -> sortable (),
                TextColumn ::make ( 'name' ) -> searchable () -> sortable (),
                TextColumn ::make ( 'state' ) -> badge () -> color ( fn ( string $state ) : string => match ( $state ) {
                    'Active' => 'success',
                    'Inactive' => 'gray',
                    'Error' => 'danger',
                } ),
                TextColumn ::make ( 'created_on' ) -> dateTime () -> sortable (),
                TextColumn ::make ( 'updated_on' ) -> dateTime () -> sortable (),
            ] )
            -> filters ( [
                //
            ] )
            -> recordActions ( [
                EditAction ::make (),
                Action ::make ( 'download' )
                       -> label ( 'Download' )
                       -> icon ( 'heroicon-o-arrow-down-tray' )
                       -> action ( function ( $record ) {
                           $service = new ZuoraService();
                           $data    = $service -> downloadWorkflow ( $record -> id );
                           // For simplicity, return JSON or save to file
                           return response () -> json ( $data );
                       } ),
            ] )
            -> toolbarActions ( [
                Action ::make ( 'sync' )
                       -> label ( 'Sync from Zuora' )
                       -> icon ( 'heroicon-o-arrow-path' )
                       -> action ( function () {
                           Artisan ::call ( 'app:sync-workflows' );
                           // Refresh the table
                       } )
                       -> successNotificationTitle ( 'Workflows synced successfully' ),
                BulkActionGroup ::make ( [
                    DeleteBulkAction ::make (),
                ] ),
            ] );
    }
}
