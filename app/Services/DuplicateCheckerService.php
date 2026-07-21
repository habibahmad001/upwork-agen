<?php

namespace App\Services;

use App\DTOs\JobDTO;
use App\Models\Job;
use Illuminate\Support\Facades\Cache;

class DuplicateCheckerService
{
    /**
     * Check if a job is a duplicate.
     */
    public function isDuplicate(JobDTO $dto): bool
    {
        // First check by job_id (most reliable)
        if ($dto->jobId) {
            $exists = $this->checkByJobId($dto->jobId);
            if ($exists) {
                return true;
            }
        }

        // Fallback to fingerprint check
        $fingerprint = $this->generateFingerprint($dto);
        return $this->checkByFingerprint($fingerprint);
    }

    /**
     * Check if job_id exists.
     */
    protected function checkByJobId(string $jobId): bool
    {
        // Use cache for faster lookups
        $cacheKey = "job_exists:{$jobId}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($jobId) {
            return Job::where('job_id', $jobId)->exists();
        });
    }

    /**
     * Check if fingerprint exists.
     */
    protected function checkByFingerprint(string $fingerprint): bool
    {
        // Only check fingerprints from recent jobs (last 24 hours)
        return Job::where('fingerprint', $fingerprint)
            ->where('created_at', '>', now()->subDay())
            ->exists();
    }

    /**
     * Generate fingerprint for duplicate detection.
     */
    public function generateFingerprint(JobDTO $dto): string
    {
        $data = $dto->title . '|' . ($dto->clientCountry ?? '') . '|' . ($dto->timePosted ?? '');
        return md5(strtolower($data));
    }

    /**
     * Find duplicate job.
     */
    public function findDuplicate(JobDTO $dto): ?Job
    {
        // First try by job_id
        if ($dto->jobId) {
            $job = Job::where('job_id', $dto->jobId)->first();
            if ($job) {
                return $job;
            }
        }

        // Then try by fingerprint
        $fingerprint = $this->generateFingerprint($dto);
        return Job::where('fingerprint', $fingerprint)
            ->where('created_at', '>', now()->subDay())
            ->first();
    }

    /**
     * Clear job existence cache.
     */
    public function clearCache(string $jobId): void
    {
        Cache::forget("job_exists:{$jobId}");
    }

    /**
     * Get duplicate statistics.
     */
    public function getStats(): array
    {
        $total = Job::count();
        $duplicates = 0; // We don't store duplicates, we skip them

        // Get jobs with same fingerprint (potential missed duplicates)
        $fingerprints = Job::select('fingerprint')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('fingerprint')
            ->having('count', '>', 1)
            ->get();

        return [
            'total_jobs' => $total,
            'skipped_duplicates' => $duplicates,
            'potential_duplicates' => $fingerprints->count(),
        ];
    }
}
