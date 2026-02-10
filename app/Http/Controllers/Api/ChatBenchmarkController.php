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
     * List chat threads (paginated)
     *
     * Endpoint: GET /api/chat/threads
     * Purpose: Apache Bench baseline for thread listing performance
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
     * Send message and get AI response
     *
     * Endpoint: POST /api/chat/threads/{thread}/messages
     * Purpose: Apache Bench test for message processing latency
     *
     * Request body:
     * {
     *   "message": "Your question here"
     * }
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
