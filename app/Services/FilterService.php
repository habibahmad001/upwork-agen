<?php

namespace App\Services;

use App\DTOs\AIScoreDTO;
use App\Models\Job;
use Illuminate\Support\Facades\Cache;

class FilterService
{
    /**
     * Determine if a job should trigger notification.
     */
    public function shouldNotify(AIScoreDTO $score, Job $job): bool
    {
        // Check if score meets threshold
        if (!$score->meetsThreshold($this->getThreshold())) {
            return false;
        }

        // Check if job has too many red flags
        if ($this->hasTooManyRedFlags($score)) {
            return false;
        }

        // Check if notifications are enabled
        if (!$this->notificationsEnabled()) {
            return false;
        }

        // Apply additional filters
        if (!$this->passesFilters($job)) {
            return false;
        }

        return true;
    }

    /**
     * Get the AI score threshold.
     */
    protected function getThreshold(): float
    {
        return (float) config('openai.threshold', 80.0);
    }

    /**
     * Check if score has too many red flags.
     */
    protected function hasTooManyRedFlags(AIScoreDTO $score): bool
    {
        $redFlags = $score->redFlags ?? [];
        $maxRedFlags = (int) config('filter.max_red_flags', 2);

        return count($redFlags) > $maxRedFlags;
    }

    /**
     * Check if notifications are enabled.
     */
    protected function notificationsEnabled(): bool
    {
        return (bool) config('whatsapp.enabled', true);
    }

    /**
     * Check if job passes all configured filters.
     */
    protected function passesFilters(Job $job): bool
    {
        // Budget filter
        if (!$this->passesBudgetFilter($job)) {
            return false;
        }

        // Country filter
        if (!$this->passesCountryFilter($job)) {
            return false;
        }

        // Skills filter
        if (!$this->passesSkillsFilter($job)) {
            return false;
        }

        // Payment verified filter
        if (!$this->passesPaymentVerifiedFilter($job)) {
            return false;
        }

        return true;
    }

    /**
     * Check budget filter.
     */
    protected function passesBudgetFilter(Job $job): bool
    {
        $minBudget = (float) config('filter.budget_min', 0);
        $minHourly = (float) config('filter.hourly_min', 0);

        if ($job->isFixedPrice() && $minBudget > 0) {
            return $job->budget >= $minBudget;
        }

        if ($job->isHourly() && $minHourly > 0) {
            return ($job->hourly_min ?? 0) >= $minHourly;
        }

        return true;
    }

    /**
     * Check country filter.
     */
    protected function passesCountryFilter(Job $job): bool
    {
        $ignoredCountries = config('filter.ignored_countries', []);

        if (empty($ignoredCountries)) {
            return true;
        }

        return !in_array($job->client_country, $ignoredCountries);
    }

    /**
     * Check skills filter.
     */
    protected function passesSkillsFilter(Job $job): bool
    {
        $ignoredSkills = config('filter.ignored_skills', []);

        if (empty($ignoredSkills)) {
            return true;
        }

        $jobSkills = array_map('strtolower', $job->skills_list);

        foreach ($ignoredSkills as $ignoredSkill) {
            if (in_array(strtolower($ignoredSkill), $jobSkills)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check payment verified filter.
     */
    protected function passesPaymentVerifiedFilter(Job $job): bool
    {
        $requireVerified = (bool) config('filter.require_payment_verified', false);

        if ($requireVerified) {
            return $job->payment_verified;
        }

        return true;
    }

    /**
     * Get filter statistics.
     */
    public function getStats(): array
    {
        return [
            'threshold' => $this->getThreshold(),
            'notifications_enabled' => $this->notificationsEnabled(),
            'budget_filter' => [
                'min_budget' => (float) config('filter.budget_min', 0),
                'min_hourly' => (float) config('filter.hourly_min', 0),
            ],
            'ignored_countries' => config('filter.ignored_countries', []),
            'ignored_skills' => config('filter.ignored_skills', []),
            'require_payment_verified' => (bool) config('filter.require_payment_verified', false),
        ];
    }

    /**
     * Get filter summary for a job.
     */
    public function getFilterSummary(Job $job, AIScoreDTO $score): array
    {
        return [
            'meets_threshold' => $score->meetsThreshold($this->getThreshold()),
            'threshold' => $this->getThreshold(),
            'red_flags_count' => count($score->redFlags ?? []),
            'too_many_red_flags' => $this->hasTooManyRedFlags($score),
            'passes_budget' => $this->passesBudgetFilter($job),
            'passes_country' => $this->passesCountryFilter($job),
            'passes_skills' => $this->passesSkillsFilter($job),
            'passes_payment' => $this->passesPaymentVerifiedFilter($job),
            'should_notify' => $this->shouldNotify($score, $job),
        ];
    }
}
