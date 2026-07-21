<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\CrawlerRunCommand;
use App\Console\Commands\CleanupCommand;
use App\Console\Commands\ImportJobsCommand;

// Crawler commands
Artisan::command('crawler:run', function () {
    $this->call(CrawlerRunCommand::class);
})->describe('Run the Upwork crawler manually');

Artisan::command('crawler:import {path}', function ($path) {
    $this->call(ImportJobsCommand::class, ['path' => $path]);
})->describe('Import jobs from a JSON file');

// Cleanup command
Artisan::command('system:cleanup', function () {
    $this->call(CleanupCommand::class);
})->describe('Run the cleanup job to delete old data');

// Queue commands
Artisan::command('queue:monitor', function () {
    $this->info('Queue monitor started...');
    // Implementation can be added later
})->describe('Monitor queue workers');

// System commands
Artisan::command('system:status', function () {
    $this->info('=== Upwork Job Agent Status ===');
    $this->info('Queue Status: ' . (cache()->get('queue_status') ?? 'Unknown'));
    $this->info('Last Crawl: ' . (cache()->get('last_crawl_time') ?? 'Never'));
    $this->info('Active Sessions: ' . \App\Models\CrawlerSession::where('status', 'running')->count());
})->describe('Show system status');

// Inspire command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
