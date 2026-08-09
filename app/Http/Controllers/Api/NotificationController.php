<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\Job;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Send notification for a job
     *
     * @return JsonResponse
     */
    public function send(): JsonResponse
    {
        // Implementation for sending notifications
        return response()->json([
            'success' => true,
            'message' => 'Notification sent'
        ]);
    }

    /**
     * Send test notification
     *
     * @return JsonResponse
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Test notification sent'
        ]);
    }
}
