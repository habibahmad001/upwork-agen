<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobAiScore extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_id',
        'score',
        'reasoning',
        'technologies',
        'red_flags',
        'estimated_hours',
        'estimated_price',
        'recommendation',
        'model_version',
        'threshold_used',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'decimal:2',
        'technologies' => 'array',
        'red_flags' => 'array',
        'threshold_used' => 'decimal:2',
    ];

    /**
     * Job relationship.
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Notifications that use this score.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Check if score meets threshold.
     */
    public function meetsThreshold(float $threshold = null): bool
    {
        $threshold = $threshold ?? (float) config('ai.threshold', 80);
        return $this->score >= $threshold;
    }

    /**
     * Get score category.
     */
    public function getCategoryAttribute(): string
    {
        return match (true) {
            $this->score >= 90 => 'Excellent',
            $this->score >= 80 => 'Very Good',
            $this->score >= 70 => 'Good',
            $this->score >= 50 => 'Fair',
            default => 'Poor',
        };
    }

    /**
     * Check if has red flags.
     */
    public function hasRedFlags(): bool
    {
        return count($this->red_flags ?? []) > 0;
    }

    /**
     * Get red flag severity.
     */
    public function getRedFlagSeverityAttribute(): string
    {
        $count = count($this->red_flags ?? []);

        return match (true) {
            $count >= 3 => 'High',
            $count >= 2 => 'Medium',
            $count >= 1 => 'Low',
            default => 'None',
        };
    }
}
