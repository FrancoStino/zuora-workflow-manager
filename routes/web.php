<?php

use App\Http\Controllers\WorkflowDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/download-workflow/{customer}/{workflowId}/{name?}', [WorkflowDownloadController::class, 'download'])
    ->name('workflow.download')
    ->middleware('web');
