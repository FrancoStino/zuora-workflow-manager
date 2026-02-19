<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Services\LaragentChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ChatBenchmarkController
 *
 * TEST-ONLY controller for performance benchmarking.
 * Provides minimal REST API endpoints for Apache Bench load testing.
 *
 * @deprecated Remove after benchmarking completion
 */
class ChatBenchmarkController extends Controller
{
    /**
     * Return a paginated list of chat threads including each thread's user.
     *
     * Responds with a JSON object containing a `success` flag, `data` with the paginated
     * threads (50 per page, ordered by latest), and a `meta` object with `provider` set
     * to "laragent" and an ISO8601 `timestamp`.
     *
     * @return \Illuminate\Http\JsonResponse JSON response with the paginated threads and metadata.
     */
    public function threads(): JsonResponse
    {
        $threads = ChatThread::with('user')
            ->latest()
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $threads,
            'meta' => [
                'provider' => 'laragent',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Send a message to a chat thread and return the AI-generated response while measuring processing latency.
     *
     * @param Request $request HTTP request containing 'message' (required string, maximum 5000 characters).
     * @param ChatThread $thread The target chat thread.
     * @return JsonResponse On success: JSON with `success: true`, `data` containing `message` (AI response) and `thread_id`, and `meta` with `provider`, `latency_ms` (milliseconds, rounded to 2 decimals), and `timestamp`. On error: JSON with `success: false`, `error` (exception message), `meta` as above, and HTTP status 500.
     */
    public function messages(Request $request, ChatThread $thread): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $startTime = microtime(true);

        try {
            $chatService = app(LaragentChatService::class);
            $response = $chatService->ask($thread, $validated['message']);
            $latency = (microtime(true) - $startTime) * 1000;

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $response,
                    'thread_id' => $thread->id,
                ],
                'meta' => [
                    'provider' => 'laragent',
                    'latency_ms' => round($latency, 2),
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            $latency = (microtime(true) - $startTime) * 1000;

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'meta' => [
                    'provider' => 'laragent',
                    'latency_ms' => round($latency, 2),
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }
}