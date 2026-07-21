<?php

namespace App\Console\Commands;

use App\Jobs\RunCrawlerJob;
use App\Services\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CrawlerRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crawler:run
        {--force : Force run even if crawler is disabled}
        {--background : Dispatch to background queue}';

    /**
     * The console command description.
     */
    protected $description = 'Run the Upwork crawler manually';

    /**
     * Execute the console command.
     */
    public function handle(SettingsService $settings): int
    {
        // Check if crawler is enabled
        if (!$this->option('force') && !$settings->isCrawlerEnabled()) {
            $this->warn('Crawler is disabled in settings.');
            $this->info('Use --force to run anyway.');
            return self::SUCCESS;
        }

        // Check for concurrent runs
        $runningCount = Cache::get('crawler:running_count', 0);
        $maxConcurrent = $settings->int('system.max_concurrent_runs', 1);

        if ($runningCount >= $maxConcurrent) {
            $this->warn("A crawler is already running (count: {$runningCount}).");
            $this->info("Max concurrent runs: {$maxConcurrent}");
            return self::FAILURE;
        }

        if ($this->option('background')) {
            dispatch(new RunCrawlerJob());
            $this->info('Crawler job dispatched to queue.');
        } else {
            $this->info('Starting crawler...');

            $job = new RunCrawlerJob();
            $job->handle(app(\App\Services\LoggingService::class));

            $this->info('Crawler completed successfully.');
        }

        return self::SUCCESS;
    }
}
