<?php

namespace App\Jobs;

use App\Models\Job;
use App\Models\Notification;
use App\Models\CrawlerLog;
use App\Models\SystemLog;
use App\Services\LoggingService;
use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout in seconds.
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('system');
    }

    /**
     * Execute the job.
     */
    public function handle(
        LoggingService $logger,
        SettingsService $settings
    ): void {
        $deleted = [
            'jobs' => 0,
            'notifications' => 0,
            'crawler_logs' => 0,
            'debug_logs' => 0,
        ];

        // Get retention settings
        $jobRetentionHours = $settings->int('system.job_retention_hours', 2);
        $logRetentionDays = $settings->int('system.log_retention_days', 1);

        // Delete old jobs that were never notified
        $deleted['jobs'] = Job::where('created_at', '<', now()->subHours($jobRetentionHours))
            ->where('status', '!=', 'notified')
            ->where('status', '!=', 'scored')
            ->delete();

        // Delete old notifications
        $deleted['notifications'] = Notification::where('created_at', '<', now()->subDays($logRetentionDays))
            ->delete();

        // Delete old crawler logs
        $deleted['crawler_logs'] = CrawlerLog::where('created_at', '<', now()->subDays($logRetentionDays))
            ->delete();

        // Delete old debug logs (keep error logs longer)
        $deleted['debug_logs'] = SystemLog::where('type', 'debug')
            ->where('created_at', '<', now()->subDay())
            ->delete();

        $logger->cleanup($deleted);

        Log::info('Cleanup completed', [
            'deleted' => $deleted,
            'job_retention_hours' => $jobRetentionHours,
            'log_retention_days' => $logRetentionDays,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Cleanup job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
