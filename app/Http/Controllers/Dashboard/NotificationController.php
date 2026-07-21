<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Job;
use App\Jobs\SendNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display all notifications.
     */
    public function index(Request $request): View
    {
        $query = Notification::with('job', 'aiScore')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $notifications = $query->paginate(50);

        $stats = [
            'total' => Notification::count(),
            'pending' => Notification::where('status', 'pending')->count(),
            'processing' => Notification::where('status', 'processing')->count(),
            'sent' => Notification::where('status', 'sent')->count(),
            'failed' => Notification::where('status', 'failed')->count(),
        ];

        return view('dashboard.notifications', compact('notifications', 'stats'));
    }

    /**
     * Display a single notification.
     */
    public function show(Notification $notification): View
    {
        $notification->load('job', 'aiScore');

        return view('dashboard.notifications-show', compact('notification'));
    }

    /**
     * Retry a failed notification.
     */
    public function retry(Notification $notification): RedirectResponse
    {
        if ($notification->status !== 'failed') {
            return redirect()
                ->route('notifications.show', $notification)
                ->with('error', 'Only failed notifications can be retried.');
        }

        // Reset the notification
        $notification->update([
            'status' => 'pending',
            'error_message' => null,
            'retry_count' => $notification->retry_count + 1,
        ]);

        // Dispatch the job
        dispatch(new SendNotificationJob(
            $notification->job_id,
            $notification->ai_score_id
        ));

        return redirect()
            ->route('notifications.show', $notification)
            ->with('success', 'Notification queued for retry.');
    }
}
