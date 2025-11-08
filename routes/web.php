<?php

use Illuminate\Support\Facades\Route;

// Redirect to Filament admin panel
Route ::get ( '/', function () {
    return redirect () -> to ( '/admin' );
} );
