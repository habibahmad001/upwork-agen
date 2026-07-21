<?php

namespace App\Console\Commands;

use App\Jobs\CleanupJob;
use Illuminate\Console\Command;

class CleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'system:cleanup
        {--background : Dispatch to background queue}
        {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Run cleanup job to delete old data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will delete old jobs, logs, and notifications. Continue?')) {
            $this->info('Cleanup cancelled.');
            return self::SUCCESS;
        }

        $this->info('Starting cleanup...');

        if ($this->option('background')) {
            dispatch(new CleanupJob());
            $this->info('Cleanup job dispatched to queue.');
        } else {
            $job = new CleanupJob();
            $job->handle(
                app(\App\Services\LoggingService::class),
                app(\App\Services\SettingsService::class)
            );

            $this->info('Cleanup completed successfully.');
        }

        return self::SUCCESS;
    }
}
