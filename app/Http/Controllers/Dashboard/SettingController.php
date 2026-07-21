<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(
        protected SettingsService $settings
    ) {}

    /**
     * Display all settings.
     */
    public function index(): View
    {
        $settings = [
            'crawler' => $this->settings->crawler(),
            'ai' => $this->settings->ai(),
            'notification' => $this->settings->notification(),
            'filter' => $this->settings->filter(),
            'system' => $this->settings->system(),
        ];

        return view('dashboard.settings', compact('settings'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'crawler.enabled' => 'sometimes|boolean',
            'crawler.interval' => 'sometimes|integer|min:10|max:3600',
            'crawler.timeout' => 'sometimes|integer|min:30|max:600',
            'crawler.max_jobs' => 'sometimes|integer|min:1|max:1000',

            'ai.provider' => 'sometimes|in:mock,openai,groq,ollama',
            'ai.model' => 'sometimes|string|max:100',
            'ai.threshold' => 'sometimes|numeric|min:0|max:100',
            'ai.openai_key' => 'sometimes|string|max:255',
            'ai.groq_key' => 'sometimes|string|max:255',

            'notification.enabled' => 'sometimes|boolean',
            'notification.method' => 'sometimes|in:email,whatsapp,both',
            'notification.email_address' => 'sometimes|email|max:255',
            'notification.phone_number' => 'sometimes|string|max:20',
            'notification.rate_limit' => 'sometimes|integer|min:1|max:100',
            'notification.phone_id' => 'sometimes|string|max:100',
            'notification.access_token' => 'sometimes|string|max:500',

            'filter.budget_min' => 'sometimes|numeric|min:0',
            'filter.hourly_min' => 'sometimes|numeric|min:0',
            'filter.ignored_countries' => 'sometimes|array',
            'filter.ignored_skills' => 'sometimes|array',
            'filter.require_payment_verified' => 'sometimes|boolean',

            'system.log_retention_days' => 'sometimes|integer|min:1|max:90',
            'system.job_retention_hours' => 'sometimes|integer|min:1|max:720',
            'system.cleanup_interval' => 'sometimes|integer|min:1|max:60',
        ]);

        foreach ($validated as $key => $value) {
            $this->settings->set($key, $value);
        }

        return redirect()
            ->route('settings')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Reset settings to defaults.
     */
    public function reset(Request $request): RedirectResponse
    {
        $confirmed = $request->input('confirm');

        if ($confirmed !== 'RESET') {
            return redirect()
                ->route('settings')
                ->with('error', 'Settings reset cancelled.');
        }

        // Reset all settings to defaults by calling seeder
        \Artisan::call('db:seed', ['--class' => 'SettingsSeeder', '--force' => true]);

        return redirect()
            ->route('settings')
            ->with('success', 'Settings reset to defaults.');
    }
}
