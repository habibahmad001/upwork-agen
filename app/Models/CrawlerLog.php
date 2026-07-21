<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrawlerLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'jobs_found',
        'jobs_new',
        'jobs_duplicate',
        'status',
        'error_message',
        'duration_ms',
        'memory_mb',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jobs_found' => 'integer',
        'jobs_new' => 'integer',
        'jobs_duplicate' => 'integer',
        'duration_ms' => 'integer',
        'memory_mb' => 'decimal:2',
    ];

    /**
     * Crawler session relationship.
     */
    public function session()
    {
        return $this->belongsTo(CrawlerSession::class, 'session_id', 'session_id');
    }

    /**
     * Scope for successful runs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed runs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failure');
    }

    /**
     * Scope for running.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope for recent logs.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>', now()->subHours($hours));
    }

    /**
     * Get total jobs found.
     */
    public function getTotalJobsAttribute(): int
    {
        return $this->jobs_new + $this->jobs_duplicate;
    }

    /**
     * Get success rate.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->jobs_found === 0) {
            return 0;
        }

        return ($this->jobs_new / $this->jobs_found) * 100;
    }

    /**
     * Format duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_ms) {
            return 'N/A';
        }

        $seconds = $this->duration_ms / 1000;

        if ($seconds < 60) {
            return number_format($seconds, 2) . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%dm %02ds', $minutes, $remainingSeconds);
    }

    /**
     * Create success log.
     */
    public static function createSuccess(array $data): self
    {
        return self::create(array_merge($data, ['status' => 'success']));
    }

    /**
     * Create failure log.
     */
    public static function createFailure(array $data, string $error): self
    {
        return self::create(array_merge($data, [
            'status' => 'failure',
            'error_message' => $error,
        ]));
    }
}
