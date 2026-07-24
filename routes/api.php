<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\CrawlerController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;

// API routes (for AJAX calls from dashboard)
Route::middleware('auth:sanctum')->group(function () {
    // Jobs API
    Route::prefix('jobs')->name('api.jobs.')->group(function () {
        Route::get('/', [JobController::class, 'index'])->name('index');
        Route::get('/{job}', [JobController::class, 'show'])->name('show');
        Route::post('/{job}/rescore', [JobController::class, 'rescore'])->name('rescore');
    });

    // Crawler API
    Route::prefix('crawler')->name('api.crawler.')->group(function () {
        Route::post('/run', [CrawlerController::class, 'run'])->name('run');
        Route::post('/stop', [CrawlerController::class, 'stop'])->name('stop');
        Route::get('/status', [CrawlerController::class, 'status'])->name('status');
        Route::get('/sessions', [CrawlerController::class, 'sessions'])->name('sessions');
    });

    // Notifications API
    Route::prefix('notifications')->name('api.notifications.')->group(function () {
        Route::post('/send', [NotificationController::class, 'send'])->name('send');
        Route::post('/test', [NotificationController::class, 'test'])->name('test');
    });

    // Settings API
    Route::prefix('settings')->name('api.settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
    });
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');

// Jobs Listing API (public - for jobs-listing page)
Route::get('/jobs-listing', function () {
    $jobsPath = base_path('crawler/jobs.json');

    if (!file_exists($jobsPath)) {
        return response()->json([
            'jobs' => [],
            'timestamp' => now()->toIso8601String(),
            'message' => 'No jobs data available'
        ]);
    }

    $jobsData = json_decode(file_get_contents($jobsPath), true);

    return response()->json([
        'jobs' => $jobsData['jobs'] ?? [],
        'timestamp' => $jobsData['timestamp'] ?? now()->toIso8601String(),
        'total_found' => $jobsData['total_found'] ?? 0,
        'new_jobs' => $jobsData['new_jobs'] ?? 0,
    ]);
})->name('api.jobs-listing');
