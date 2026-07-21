<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_id',
        'ai_score_id',
        'method',
        'destination',
        'phone_number',
        'message_content',
        'whatsapp_message_id',
        'status',
        'error_message',
        'retry_count',
        'last_retry_at',
        'sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'retry_count' => 'integer',
        'last_retry_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Job relationship.
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * AI Score relationship.
     */
    public function aiScore()
    {
        return $this->belongsTo(JobAiScore::class);
    }

    /**
     * Scope for pending notifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processing notifications.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope for sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for retryable notifications.
     */
    public function scopeRetryable($query)
    {
        return $query->failed()
            ->where('retry_count', '<', 3);
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): bool
    {
        return $this->update(['status' => 'processing']);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(string $messageId): bool
    {
        return $this->update([
            'status' => 'sent',
            'whatsapp_message_id' => $messageId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $error): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
        ]);
    }

    /**
     * Check if can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }
}
