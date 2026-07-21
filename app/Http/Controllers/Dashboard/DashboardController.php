<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\CrawlerSession;
use App\Models\CrawlerLog;
use App\Models\SystemLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard.
     */
    public function index(): View
    {
        // Get summary statistics
        $stats = [
            'jobs_total' => Job::count(),
            'jobs_today' => Job::whereDate('created_at', today())->count(),
            'jobs_notified' => Job::where('status', 'notified')->count(),
            'jobs_scoring' => Job::where('status', 'scoring')->count(),
            'avg_score' => Job::whereHas('aiScore')->with('aiScore')
                ->get()
                ->avg('aiScore.score') ?? 0,
            'crawler_sessions_today' => CrawlerSession::whereDate('created_at', today())->count(),
            'crawler_sessions_active' => CrawlerSession::where('status', 'running')->count(),
            'last_crawl' => CrawlerSession::latest()->first()?->created_at?->diffForHumans() ?? 'Never',
        ];

        // Get recent jobs
        $recentJobs = Job::with('aiScore')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get recent crawler activity
        $recentCrawls = CrawlerLog::with('session')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent errors
        $recentErrors = SystemLog::error()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'stats',
            'recentJobs',
            'recentCrawls',
            'recentErrors'
        ));
    }
}
