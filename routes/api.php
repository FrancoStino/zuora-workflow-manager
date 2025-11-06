<?php

use App\Services\ZuoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route ::get ( '/user', function ( Request $request ) {
    return $request -> user ();
} ) -> middleware ( 'auth:sanctum' );

Route ::middleware ( 'auth:sanctum' ) -> get ( '/zuora/token', function () {
    try {
        $service = new ZuoraService();
        $token   = $service -> getAccessToken ();
        return response () -> json ( [ 'accessToken' => $token ] );
    } catch ( Exception $e ) {
        return response () -> json ( [ 'error' => $e -> getMessage () ], 500 );
    }
} );
