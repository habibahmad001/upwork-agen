<?php

namespace App\Jobs;

use App\Models\Job;
use App\Models\JobAiScore;
use App\Models\Notification;
use App\Services\LoggingService;
use App\Services\SettingsService;
use App\Services\Email\EmailService;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\Pusher\PusherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts.
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    public $backoff = [10, 30, 60];

    /**
     * Job timeout in seconds.
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $jobId,
        protected int $aiScoreId
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(
        SettingsService $settings,
        LoggingService $logger,
        EmailService $email,
        WhatsAppService $whatsapp,
        PusherService $pusher
    ): void {
        // Check if notifications are enabled
        if (!$settings->get('notification.enabled', true)) {
            Log::info('Notifications disabled, skipping', ['job_id' => $this->jobId]);
            return;
        }

        // Get notification method
        $method = $settings->get('notification.method', 'email');

        // Check rate limit
        if (!$this->checkRateLimit($method, $logger)) {
            $this->release(60);
            return;
        }

        $job = Job::findOrFail($this->jobId);
        $aiScore = JobAiScore::findOrFail($this->aiScoreId);

        try {
            if ($method === 'email' || $method === 'both') {
                $this->sendEmail($job, $aiScore, $email, $logger);
            }

            if ($method === 'whatsapp' || $method === 'both') {
                $this->sendWhatsApp($job, $aiScore, $whatsapp, $logger);
            }

            // Always send push notification if Pusher is available (separate from email/whatsapp)
            if ($pusher->isAvailable()) {
                $this->sendPushNotification($job, $aiScore, $pusher, $logger);
            }

            // Increment rate limit counter
            $this->incrementRateLimit($method);

        } catch (Exception $e) {
            Log::error('Notification failed', [
                'job_id' => $job->id,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            // Create failed notification record
            Notification::create([
                'job_id' => $this->jobId,
                'ai_score_id' => $this->aiScoreId,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $this->attempts(),
            ]);

            $logger->notificationFailed($job->id, $e->getMessage(), $this->attempts());

            if ($this->attempts() < $this->tries) {
                $this->release(60);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmail(
        Job $job,
        JobAiScore $aiScore,
        EmailService $email,
        LoggingService $logger
    ): void {
        try {
            $result = $email->sendJobNotification($job, $aiScore);

            Notification::create([
                'job_id' => $this->jobId,
                'ai_score_id' => $this->aiScoreId,
                'method' => 'email',
                'destination' => $result['recipient'] ?? 'unknown',
                'message_content' => $email->formatMessage($job, $aiScore),
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $logger->notificationSent($job->id, 'email:' . ($result['recipient'] ?? 'unknown'));

            Log::info('Email notification sent', [
                'job_id' => $job->id,
                'recipient' => $result['recipient'] ?? 'unknown',
            ]);

        } catch (Exception $e) {
            Log::error('Email notification failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send WhatsApp notification.
     */
    protected function sendWhatsApp(
        Job $job,
        JobAiScore $aiScore,
        WhatsAppService $whatsapp,
        LoggingService $logger
    ): void {
        try {
            $message = $whatsapp->formatMessage($job, $aiScore);
            $result = $whatsapp->send($message);

            Notification::create([
                'job_id' => $this->jobId,
                'ai_score_id' => $this->aiScoreId,
                'method' => 'whatsapp',
                'destination' => config('whatsapp.phone_number'),
                'message_content' => $message,
                'status' => 'sent',
                'whatsapp_message_id' => $result['message_id'] ?? null,
                'sent_at' => now(),
            ]);

            $logger->notificationSent($job->id, 'whatsapp:' . ($result['message_id'] ?? 'unknown'));

            Log::info('WhatsApp notification sent', [
                'job_id' => $job->id,
                'message_id' => $result['message_id'] ?? null,
            ]);

        } catch (Exception $e) {
            Log::error('WhatsApp notification failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send Pusher push notification.
     */
    protected function sendPushNotification(
        Job $job,
        JobAiScore $aiScore,
        PusherService $pusher,
        LoggingService $logger
    ): void {
        try {
            // Convert JobAiScore to AIScoreDTO for the service
            $scoreDto = new \App\DTOs\AIScoreDTO(
                score: $aiScore->score,
                recommendation: $aiScore->recommendation,
                reasoning: $aiScore->reasoning,
                technologies: $aiScore->technologies ?? [],
                red_flags: $aiScore->red_flags ?? []
            );

            $pusher->send($job, $scoreDto);

            Notification::create([
                'job_id' => $this->jobId,
                'ai_score_id' => $this->aiScoreId,
                'method' => 'pusher',
                'destination' => config('services.pusher.channel'),
                'message_content' => $pusher->formatMessage($job, $scoreDto),
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $logger->notificationSent($job->id, 'pusher:' . config('services.pusher.channel'));

            Log::info('Pusher notification sent', [
                'job_id' => $job->id,
                'channel' => config('services.pusher.channel'),
                'event' => config('services.pusher.event'),
            ]);

        } catch (Exception $e) {
            Log::error('Pusher notification failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - push notifications are optional
        }
    }

    /**
     * Check if we're within rate limits.
     */
    protected function checkRateLimit(string $method, LoggingService $logger): bool
    {
        $rateLimit = (int) config('notification.rate_limit', 10);
        $window = 60; // 60 seconds
        $key = "notification:rate_limit:{$method}:" . now()->format('YmdHi');

        $current = Cache::get($key, 0);

        if ($current >= $rateLimit) {
            $logger->rateLimitHit($rateLimit);
            return false;
        }

        return true;
    }

    /**
     * Increment the rate limit counter.
     */
    protected function incrementRateLimit(string $method): void
    {
        $window = 60;
        $key = "notification:rate_limit:{$method}:" . now()->format('YmdHi');

        Cache::increment($key, 1, $window);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Notification job failed permanently', [
            'job_id' => $this->jobId,
            'ai_score_id' => $this->aiScoreId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
