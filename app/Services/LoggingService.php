<?php

namespace App\Services;

use App\Models\SystemLog;

class LoggingService
{
    /**
     * Log info message.
     */
    public function info(string $message, array $context = [], string $source = 'system'): SystemLog
    {
        return SystemLog::createInfo($message, $this->sanitizeContext($context), $source);
    }

    /**
     * Log warning message.
     */
    public function warning(string $message, array $context = [], string $source = 'system'): SystemLog
    {
        return SystemLog::createWarning($message, $this->sanitizeContext($context), $source);
    }

    /**
     * Log error message.
     */
    public function error(string $message, array $context = [], string $source = 'system'): SystemLog
    {
        return SystemLog::createError($message, $this->sanitizeContext($context), $source);
    }

    /**
     * Log debug message.
     */
    public function debug(string $message, array $context = [], string $source = 'system'): SystemLog
    {
        return SystemLog::createDebug($message, $this->sanitizeContext($context), $source);
    }

    /**
     * Log with custom type.
     */
    public function log(string $type, string $message, array $context = [], string $source = 'system'): SystemLog
    {
        return SystemLog::log($type, $message, $this->sanitizeContext($context), $source);
    }

    // Crawler logging methods

    /**
     * Log crawler started.
     */
    public function crawlerStarted(string $sessionId): SystemLog
    {
        return $this->info('Crawler started', ['session_id' => $sessionId], 'crawler');
    }

    /**
     * Log crawler finished.
     */
    public function crawlerFinished(string $sessionId, int $jobsFound, int $durationMs): SystemLog
    {
        return $this->info('Crawler finished', [
            'session_id' => $sessionId,
            'jobs_found' => $jobsFound,
            'duration_ms' => $durationMs,
        ], 'crawler');
    }

    /**
     * Log crawler error.
     */
    public function crawlerError(string $sessionId, \Throwable $e): SystemLog
    {
        return $this->error('Crawler error', [
            'session_id' => $sessionId,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 'crawler');
    }

    /**
     * Log new jobs discovered.
     */
    public function newJobs(int $count, array $jobIds = []): SystemLog
    {
        return $this->info("New jobs discovered: {$count}", [
            'count' => $count,
            'job_ids' => array_slice($jobIds, 0, 10), // Log first 10
        ], 'crawler');
    }

    /**
     * Log duplicate jobs skipped.
     */
    public function duplicateJobs(int $count): SystemLog
    {
        return $this->info("Duplicate jobs skipped: {$count}", [
            'count' => $count,
        ], 'crawler');
    }

    // AI logging methods

    /**
     * Log AI request.
     */
    public function aiRequest(int $jobId, int $promptLength): SystemLog
    {
        return $this->debug('AI scoring request', [
            'job_id' => $jobId,
            'prompt_length' => $promptLength,
        ], 'ai');
    }

    /**
     * Log AI response.
     */
    public function aiResponse(int $jobId, float $score, int $durationMs): SystemLog
    {
        return $this->debug('AI scoring response', [
            'job_id' => $jobId,
            'score' => $score,
            'duration_ms' => $durationMs,
        ], 'ai');
    }

    /**
     * Log AI error.
     */
    public function aiError(int $jobId, \Throwable $e): SystemLog
    {
        return $this->error('AI scoring error', [
            'job_id' => $jobId,
            'error' => $e->getMessage(),
        ], 'ai');
    }

    // Notification logging methods

    /**
     * Log notification sent.
     */
    public function notificationSent(int $jobId, string $messageId): SystemLog
    {
        return $this->info('WhatsApp notification sent', [
            'job_id' => $jobId,
            'message_id' => $messageId,
        ], 'notification');
    }

    /**
     * Log notification failed.
     */
    public function notificationFailed(int $jobId, string $error, int $attempt): SystemLog
    {
        return $this->warning('WhatsApp notification failed', [
            'job_id' => $jobId,
            'error' => $error,
            'attempt' => $attempt,
        ], 'notification');
    }

    /**
     * Log rate limit hit.
     */
    public function rateLimitHit(int $limit): SystemLog
    {
        return $this->warning('WhatsApp rate limit hit', [
            'limit' => $limit,
        ], 'notification');
    }

    // System logging methods

    /**
     * Log cleanup job.
     */
    public function cleanup(array $deleted): SystemLog
    {
        return $this->info('Cleanup completed', [
            'deleted' => $deleted,
        ], 'system');
    }

    /**
     * Log login expired.
     */
    public function loginExpired(string $sessionId): SystemLog
    {
        return $this->warning('Upwork login session expired', [
            'session_id' => $sessionId,
        ], 'crawler');
    }

    /**
     * Log memory high.
     */
    public function memoryHigh(float $memoryMb): SystemLog
    {
        return $this->warning('Memory usage high', [
            'memory_mb' => $memoryMb,
        ], 'system');
    }

    /**
     * Log CPU high.
     */
    public function cpuHigh(float $cpuPercent): SystemLog
    {
        return $this->warning('CPU usage high', [
            'cpu_percent' => $cpuPercent,
        ], 'system');
    }

    /**
     * Sanitize context to remove sensitive data.
     */
    protected function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password', 'api_key', 'secret', 'token', 'access_token',
            'phone_id', 'authorization', 'cookie', 'session',
        ];

        return collect($context)->map(function ($value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                return '***REDACTED***';
            }

            // Recursively sanitize arrays
            if (is_array($value)) {
                return $this->sanitizeContext($value);
            }

            return $value;
        })->toArray();
    }

    /**
     * Get recent logs.
     */
    public function getRecent(int $limit = 50, string $type = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = SystemLog::orderBy('created_at', 'desc')
            ->limit($limit);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get();
    }

    /**
     * Get logs by source.
     */
    public function getBySource(string $source, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return SystemLog::fromSource($source)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get error logs.
     */
    public function getErrors(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return SystemLog::error()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clean old logs.
     */
    public function cleanOldLogs(int $daysToKeep = 1): int
    {
        $deleted = SystemLog::where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();

        $this->info("Old logs cleaned", [
            'deleted' => $deleted,
            'days_kept' => $daysToKeep,
        ], 'system');

        return $deleted;
    }
}
