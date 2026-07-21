<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LogController extends Controller
{
    public function __construct(
        protected LoggingService $logger
    ) {}

    /**
     * Display all logs.
     */
    public function index(Request $request): View
    {
        $type = $request->get('type');
        $limit = $request->get('limit', 100);

        $logs = $this->logger->getRecent($limit, $type);

        $stats = [
            'total' => SystemLog::count(),
            'info' => SystemLog::where('type', 'info')->count(),
            'warning' => SystemLog::where('type', 'warning')->count(),
            'error' => SystemLog::where('type', 'error')->count(),
            'debug' => SystemLog::where('type', 'debug')->count(),
        ];

        return view('dashboard.logs', compact('logs', 'stats'));
    }

    /**
     * Display crawler logs.
     */
    public function crawler(Request $request): View
    {
        $logs = $this->logger->getBySource('crawler', $request->get('limit', 100));

        return view('dashboard.logs-crawler', compact('logs'));
    }

    /**
     * Display AI logs.
     */
    public function ai(Request $request): View
    {
        $logs = $this->logger->getBySource('ai', $request->get('limit', 100));

        return view('dashboard.logs-ai', compact('logs'));
    }

    /**
     * Display notification logs.
     */
    public function notifications(Request $request): View
    {
        $logs = $this->logger->getBySource('notification', $request->get('limit', 100));

        return view('dashboard.logs-notifications', compact('logs'));
    }

    /**
     * Clear old logs.
     */
    public function clear(Request $request): RedirectResponse
    {
        $days = (int) $request->input('days', 1);

        if ($days < 1) {
            return redirect()
                ->route('logs')
                ->with('error', 'Days must be at least 1.');
        }

        $deleted = $this->logger->cleanOldLogs($days);

        return redirect()
            ->route('logs')
            ->with('success', "Deleted {$deleted} old log entries.");
    }
}
