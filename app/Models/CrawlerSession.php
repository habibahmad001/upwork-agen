<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CrawlerSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'started_at',
        'ended_at',
        'last_activity',
        'status',
        'recovery_count',
        'last_recovery_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_activity' => 'datetime',
        'recovery_count' => 'integer',
        'last_recovery_at' => 'datetime',
    ];

    /**
     * Logs relationship.
     */
    public function logs()
    {
        return $this->hasMany(CrawlerLog::class, 'session_id', 'session_id');
    }

    /**
     * Scope for running sessions.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope for completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed sessions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for stopped sessions.
     */
    public function scopeStopped($query)
    {
        return $query->where('status', 'stopped');
    }

    /**
     * Scope for recent sessions.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>', now()->subHours($hours));
    }

    /**
     * Scope for stale sessions (no activity for 5 minutes).
     */
    public function scopeStale($query)
    {
        return $query->running()
            ->where('last_activity', '<', now()->subMinutes(5));
    }

    /**
     * Get duration.
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->ended_at) {
            return null;
        }

        $duration = $this->started_at->diff($this->ended_at);

        if ($duration->h > 0) {
            return $duration->h . 'h ' . $duration->i . 'm';
        }

        return $duration->i . 'm ' . $duration->s . 's';
    }

    /**
     * Get uptime (for running sessions).
     */
    public function getUptimeAttribute(): ?string
    {
        if ($this->status !== 'running') {
            return null;
        }

        $duration = $this->started_at->diff(now());

        if ($duration->h > 0) {
            return $duration->h . 'h ' . $duration->i . 'm';
        }

        return $duration->i . 'm ' . $duration->s . 's';
    }

    /**
     * Check if session is stale.
     */
    public function isStale(): bool
    {
        return $this->status === 'running' &&
            $this->last_activity &&
            $this->last_activity->lt(now()->subMinutes(5));
    }

    /**
     * Start a new session.
     */
    public static function start(): self
    {
        return self::create([
            'session_id' => Str::uuid()->toString(),
            'started_at' => now(),
            'last_activity' => now(),
            'status' => 'running',
        ]);
    }

    /**
     * Complete the session.
     */
    public function complete(): bool
    {
        return $this->update([
            'status' => 'completed',
            'ended_at' => now(),
            'last_activity' => now(),
        ]);
    }

    /**
     * Mark session as failed.
     */
    public function fail(string $error = ''): bool
    {
        return $this->update([
            'status' => 'failed',
            'ended_at' => now(),
            'last_activity' => now(),
        ]);
    }

    /**
     * Stop the session.
     */
    public function stop(): bool
    {
        return $this->update([
            'status' => 'stopped',
            'ended_at' => now(),
            'last_activity' => now(),
        ]);
    }

    /**
     * Update last activity.
     */
    public function touchActivity(): bool
    {
        return $this->update(['last_activity' => now()]);
    }

    /**
     * Increment recovery count.
     */
    public function incrementRecovery(): bool
    {
        $this->increment('recovery_count');
        $this->update(['last_recovery_at' => now()]);

        return true;
    }

    /**
     * Check if should recover.
     */
    public function shouldRecover(): bool
    {
        return $this->recovery_count < 3;
    }
}
