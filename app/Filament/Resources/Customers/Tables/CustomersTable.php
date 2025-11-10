<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
	public static function configure ( Table $table ) : Table
	{
		return $table
			-> columns ( [
				TextColumn ::make ( 'name' ) -> searchable () -> sortable (),
				TextColumn ::make ( 'client_id' ) -> searchable () -> label ( 'Client ID' ),
				TextColumn ::make ( 'base_url' ) -> label ( 'Base URL' ),
				TextColumn ::make ( 'created_at' ) -> dateTime () -> sortable (),
			] )
			-> filters ( [
				//
			] )
			-> recordActions ( [
				EditAction ::make (),
			] )
			-> toolbarActions ( [
				BulkActionGroup ::make ( [
					DeleteBulkAction ::make (),
				] ),
			] );
	}
}
