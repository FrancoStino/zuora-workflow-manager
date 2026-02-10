<?php

use App\Http\Controllers\Api\ChatBenchmarkController;
use App\Services\ZuoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/zuora/token', function () {
    try {
        $service = new ZuoraService;
        $token = $service->getAccessToken();

        return response()->json(['accessToken' => $token]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/zuora/download/{workflowId}', function ($workflowId, Request $request) {
    try {
        $clientId = $request->query('client_id');
        $clientSecret = $request->query('client_secret');
        $baseUrl = $request->query('base_url', 'https://rest.zuora.com');
        $service = new ZuoraService;
        $data = $service->downloadWorkflow($clientId, $clientSecret, $baseUrl, $workflowId);

        return response()->json($data);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::prefix('chat')->group(function () {
    Route::get('/threads', [ChatBenchmarkController::class, 'threads']);
    Route::post('/threads/{thread}/messages', [ChatBenchmarkController::class, 'messages']);
});
