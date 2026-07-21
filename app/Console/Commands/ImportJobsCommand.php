<?php

namespace App\Console\Commands;

use App\Jobs\ImportJobsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crawler:import
        {path : Path to the JSON file containing jobs}
        {--session-id= : Custom session ID for tracking}';

    /**
     * The console command description.
     */
    protected $description = 'Import jobs from a JSON file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->argument('path');
        $sessionId = $this->option('session-id') ?? (string) Str::uuid();

        // Check if file exists
        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        // Read and validate JSON
        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON: " . json_last_error_msg());
            return self::FAILURE;
        }

        if (!isset($data['jobs']) || !is_array($data['jobs'])) {
            $this->error("JSON must contain a 'jobs' array");
            return self::FAILURE;
        }

        $jobCount = count($data['jobs']);

        $this->info("Found {$jobCount} jobs to import.");
        $this->info("Session ID: {$sessionId}");

        if (!$this->confirm("Proceed with importing {$jobCount} jobs?")) {
            $this->info('Import cancelled.');
            return self::SUCCESS;
        }

        // Move file to expected location
        $storagePath = config('crawler.storage_json');
        $storageDir = dirname($storagePath);

        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        copy($path, $storagePath);

        // Dispatch import job
        dispatch(new ImportJobsJob($sessionId));

        $this->info('Import job dispatched to queue.');
        $this->info('You can track progress using: tail -f storage/logs/laravel.log');

        return self::SUCCESS;
    }
}
