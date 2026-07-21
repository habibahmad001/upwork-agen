<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\JobController;
use App\Http\Controllers\Dashboard\NotificationController;
use App\Http\Controllers\Dashboard\SettingController;
use App\Http\Controllers\Dashboard\LogController;
use App\Http\Controllers\Dashboard\AnalyticsController;

// Authentication routes (simplified - handled in layout)
Route::match(['get', 'post'], '/login', function () {
    if (request()->isMethod('post')) {
        $credentials = request()->only('email', 'password');

        if (auth()->attempt($credentials)) {
            request()->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    return view('layouts.app');
})->name('login');

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// Protected dashboard routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Jobs
    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/', [JobController::class, 'index'])->name('index');
        Route::get('/{job}', [JobController::class, 'show'])->name('show');
        Route::post('/{job}/archive', [JobController::class, 'archive'])->name('archive');
        Route::post('/{job}/restore', [JobController::class, 'restore'])->name('restore');
    });

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
        Route::post('/{notification}/retry', [NotificationController::class, 'retry'])->name('retry');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
        Route::post('/reset', [SettingController::class, 'reset'])->name('reset');
    });

    // Logs
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [LogController::class, 'index'])->name('index');
        Route::get('/crawler', [LogController::class, 'crawler'])->name('crawler');
        Route::get('/ai', [LogController::class, 'ai'])->name('ai');
        Route::get('/notifications', [LogController::class, 'notifications'])->name('notifications');
        Route::delete('/', [LogController::class, 'clear'])->name('clear');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/jobs', [AnalyticsController::class, 'jobs'])->name('jobs');
        Route::get('/crawler', [AnalyticsController::class, 'crawler'])->name('crawler');
        Route::get('/ai', [AnalyticsController::class, 'ai'])->name('ai');
    });
});
