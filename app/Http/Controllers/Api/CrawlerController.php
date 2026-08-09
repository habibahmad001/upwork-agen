<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\RunCrawlerJob;
use App\Models\CrawlerSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CrawlerController extends Controller
{
    /**
     * Trigger crawler run
     *
     * @return JsonResponse
     */
    public function run(): JsonResponse
    {
        // Check for concurrent runs
        $runningCount = Cache::get('crawler:running_count', 0);
        $maxConcurrent = 1;

        if ($runningCount >= $maxConcurrent) {
            return response()->json([
                'success' => false,
                'message' => 'Crawler already running',
                'running_count' => $runningCount
            ], 409);
        }

        dispatch(new RunCrawlerJob());

        return response()->json([
            'success' => true,
            'message' => 'Crawler dispatched'
        ]);
    }

    /**
     * Stop crawler
     *
     * @return JsonResponse
     */
    public function stop(): JsonResponse
    {
        Cache::decrement('crawler:running_count');

        return response()->json([
            'success' => true,
            'message' => 'Crawler stop signal sent'
        ]);
    }

    /**
     * Get crawler status
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'running_count' => Cache::get('crawler:running_count', 0),
                'last_run' => CrawlerSession::latest()->first()?->created_at?->toIso8601String(),
            ]
        ]);
    }

    /**
     * Get crawler sessions
     *
     * @return JsonResponse
     */
    public function sessions(): JsonResponse
    {
        $sessions = CrawlerSession::latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }
}
