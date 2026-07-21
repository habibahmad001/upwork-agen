<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Job extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_id',
        'fingerprint',
        'title',
        'description',
        'budget',
        'hourly_min',
        'hourly_max',
        'client_country',
        'payment_verified',
        'spent',
        'hire_rate',
        'client_rating',
        'proposals',
        'experience_level',
        'project_length',
        'time_posted',
        'url',
        'status',
        'job_posted_at',
        'notified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budget' => 'decimal:2',
        'hourly_min' => 'decimal:2',
        'hourly_max' => 'decimal:2',
        'payment_verified' => 'boolean',
        'spent' => 'decimal:2',
        'client_rating' => 'decimal:2',
        'proposals' => 'integer',
        'job_posted_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    /**
     * Skills relationship.
     */
    public function skills(): HasMany
    {
        return $this->hasMany(JobSkill::class);
    }

    /**
     * AI scores relationship.
     */
    public function aiScores(): HasMany
    {
        return $this->hasMany(JobAiScore::class)->latest();
    }

    /**
     * Latest AI score.
     */
    public function aiScore()
    {
        return $this->hasOne(JobAiScore::class)->latest();
    }

    /**
     * Notifications relationship.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Scope for new jobs.
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for scoring jobs.
     */
    public function scopeScoring($query)
    {
        return $query->where('status', 'scoring');
    }

    /**
     * Scope for scored jobs.
     */
    public function scopeScored($query)
    {
        return $query->where('status', 'scored');
    }

    /**
     * Scope for notified jobs.
     */
    public function scopeNotified($query)
    {
        return $query->where('status', 'notified');
    }

    /**
     * Scope for skipped jobs.
     */
    public function scopeSkipped($query)
    {
        return $query->where('status', 'skipped');
    }

    /**
     * Scope for recent jobs.
     */
    public function scopeRecent($query, int $hours = 2)
    {
        return $query->where('created_at', '>', now()->subHours($hours));
    }

    /**
     * Check if job is hourly.
     */
    public function isHourly(): bool
    {
        return $this->hourly_min !== null || $this->hourly_max !== null;
    }

    /**
     * Check if job is fixed price.
     */
    public function isFixedPrice(): bool
    {
        return $this->budget !== null;
    }

    /**
     * Get budget range as string.
     */
    public function getBudgetRangeAttribute(): string
    {
        if ($this->isFixedPrice()) {
            return '$' . number_format($this->budget, 0);
        }

        if ($this->isHourly()) {
            $min = $this->hourly_min ?? '?';
            $max = $this->hourly_max ?? '?';
            return "\${$min}-\${$max}/hr";
        }

        return 'Not specified';
    }

    /**
     * Get skills array.
     */
    public function getSkillsListAttribute(): array
    {
        return $this->skills->pluck('skill')->toArray();
    }

    /**
     * Attach skills to job.
     */
    public function attachSkills(array $skills): void
    {
        foreach ($skills as $skill) {
            $this->skills()->firstOrCreate([
                'skill' => trim($skill),
            ]);
        }
    }

    /**
     * Mark as notified.
     */
    public function markAsNotified(): bool
    {
        return $this->update([
            'status' => 'notified',
            'notified_at' => now(),
        ]);
    }

    /**
     * Mark as skipped.
     */
    public function markAsSkipped(string $reason = ''): bool
    {
        return $this->update([
            'status' => 'skipped',
        ]);
    }
}
