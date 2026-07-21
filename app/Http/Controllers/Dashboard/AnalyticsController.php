<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobAiScore;
use App\Models\CrawlerLog;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard.
     */
    public function index(): View
    {
        return view('dashboard.analytics');
    }

    /**
     * Get jobs analytics data.
     */
    public function jobs(Request $request): array
    {
        $days = (int) $request->get('days', 7);

        $data = Job::where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('COUNT(CASE WHEN status = "notified" THEN 1 END) as notified'),
                DB::raw('COUNT(CASE WHEN status = "skipped" THEN 1 END) as skipped'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get score distribution
        $scoreDistribution = JobAiScore::select(
                DB::raw('FLOOR(score / 10) * 10 as bucket'),
                DB::raw('COUNT(*) as count')
            )
            ->join('jobs', 'job_ai_scores.job_id', '=', 'jobs.id')
            ->where('jobs.created_at', '>=', now()->subDays($days))
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'timeline' => $data,
            'score_distribution' => $scoreDistribution,
            'total_jobs' => Job::where('created_at', '>=', now()->subDays($days))->count(),
            'notified_jobs' => Job::where('created_at', '>=', now()->subDays($days))
                ->where('status', 'notified')
                ->count(),
            'avg_score' => JobAiScore::where('created_at', '>=', now()->subDays($days))
                ->avg('score') ?? 0,
        ];
    }

    /**
     * Get crawler analytics data.
     */
    public function crawler(Request $request): array
    {
        $days = (int) $request->get('days', 7);

        $data = CrawlerLog::where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(jobs_found) as total_jobs_found'),
                DB::raw('SUM(jobs_new) as total_jobs_new'),
                DB::raw('SUM(jobs_duplicate) as total_duplicates'),
                DB::raw('AVG(duration_ms) as avg_duration'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $successRate = CrawlerLog::where('created_at', '>=', now()->subDays($days))
            ->select(
                'status',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('status')
            ->get();

        return [
            'timeline' => $data,
            'success_rate' => $successRate,
            'total_runs' => CrawlerLog::where('created_at', '>=', now()->subDays($days))->count(),
            'total_jobs_found' => CrawlerLog::where('created_at', '>=', now()->subDays($days))
                ->sum('jobs_found'),
        ];
    }

    /**
     * Get AI analytics data.
     */
    public function ai(Request $request): array
    {
        $days = (int) $request->get('days', 7);

        $scores = JobAiScore::where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(score) as avg_score'),
                DB::raw('MAX(score) as max_score'),
                DB::raw('MIN(score) as min_score'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $redFlags = JobAiScore::select(
                DB::raw('JSON_LENGTH(red_flags) as flag_count'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('red_flags')
            ->groupBy('flag_count')
            ->orderBy('flag_count')
            ->get();

        return [
            'scores' => $scores,
            'red_flags' => $redFlags,
            'total_scores' => JobAiScore::where('created_at', '>=', now()->subDays($days))->count(),
            'avg_score' => JobAiScore::where('created_at', '>=', now()->subDays($days))->avg('score') ?? 0,
        ];
    }
}
