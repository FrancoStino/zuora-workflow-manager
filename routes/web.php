<?php

use App\Http\Controllers\WorkflowDownloadController;
use Illuminate\Support\Facades\Route;

// Redirect to Filament admin panel
Route ::get ( '/', function () {
    return redirect () -> to ( '/admin' );
} );

Route ::get ( '/download-workflow/{customer}/{workflowId}/{name?}', [ WorkflowDownloadController::class, 'download' ] )
      -> name ( 'workflow.download' )
      -> middleware ( 'web' );
