<?php

namespace App\Services\Pusher;

use App\Models\Job;
use App\Models\JobAiScore;
use App\Contracts\NotificationServiceInterface;
use App\DTOs\AIScoreDTO;
use Illuminate\Support\Facades\Log;
use Exception;
use Pusher\Pusher;
use Pusher\PusherException;

class PusherService implements NotificationServiceInterface
{
    /**
     * Pusher instance.
     */
    protected ?Pusher $pusher = null;

    /**
     * Notification channel.
     */
    protected string $channel;

    /**
     * Notification event.
     */
    protected string $event;

    /**
     * Create a new Pusher service instance.
     */
    public function __construct()
    {
        $this->channel = config('services.pusher.channel', 'jobs');
        $this->event = config('services.pusher.event', 'new-job');

        try {
            $cluster = config('services.pusher.cluster', 'mt1');

            $this->pusher = new Pusher(
                config('services.pusher.app_key'),
                config('services.pusher.app_secret'),
                config('services.pusher.app_id'),
                [
                    'cluster' => $cluster,
                    'useTLS' => config('services.pusher.use_tls', true),
                    'timeout' => config('services.pusher.timeout', 30),
                ]
            );
        } catch (PusherException $e) {
            Log::error('Pusher initialization failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification immediately via Pusher.
     */
    public function send(Job $job, AIScoreDTO $score): bool
    {
        if (!$this->isAvailable()) {
            throw new Exception('Pusher service is not available');
        }

        try {
            $data = $this->prepareNotificationData($job, $score);

            $result = $this->pusher->trigger(
                $this->channel,
                $this->event,
                $data
            );

            Log::info('Pusher notification sent', [
                'job_id' => $job->id,
                'channel' => $this->channel,
                'event' => $this->event,
            ]);

            return true;

        } catch (PusherException $e) {
            Log::error('Pusher notification failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (Exception $e) {
            Log::error('Pusher notification error', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Queue notification for background sending.
     * Note: Pusher sends immediately, so this just sends now.
     * For true queuing, use a job dispatch.
     */
    public function queue(Job $job, AIScoreDTO $score): void
    {
        $this->send($job, $score);
    }

    /**
     * Format notification message.
     */
    public function formatMessage(Job $job, AIScoreDTO $score): string
    {
        $emoji = $this->getScoreEmoji($score->score);

        $message = "{$emoji} New Upwork Job Match - Score: {$score->score}/100\n\n";
        $message .= "📌 {$job->title}\n";

        if ($job->budget_range) {
            $message .= "💰 Budget: {$job->budget_range}\n";
        }

        if ($job->client_country) {
            $message .= "🌍 Client: {$job->client_country}\n";
        }

        if ($score->reasoning) {
            $message .= "\n💡 {$score->reasoning}";
        }

        $message .= "\n\n🔗 View: {$job->url}";

        return $message;
    }

    /**
     * Prepare notification data for Pusher.
     */
    protected function prepareNotificationData(Job $job, AIScoreDTO $score): array
    {
        return [
            'id' => (string) $job->id,
            'job_id' => $job->job_id,
            'title' => $job->title,
            'description' => $job->description ?? '',
            'budget' => $job->budget_range,
            'url' => $job->url,
            'client_country' => $job->client_country,
            'payment_verified' => (bool) $job->payment_verified,
            'client_rating' => $job->client_rating,
            'proposals' => $job->proposals,
            'posted_at' => $job->job_posted_at?->toIso8601String(),
            'ai_score' => $score->score,
            'recommendation' => $score->recommendation,
            'reasoning' => $score->reasoning,
            'technologies' => $score->technologies ?? [],
            'red_flags' => $score->red_flags ?? [],
            'emoji' => $this->getScoreEmoji($score->score),
            'message' => $this->formatMessage($job, $score),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get emoji based on score.
     */
    protected function getScoreEmoji(float $score): string
    {
        return match (true) {
            $score >= 90 => '🔥',
            $score >= 80 => '✨',
            $score >= 70 => '👍',
            $score >= 50 => '🤔',
            default => '⚠️',
        };
    }

    /**
     * Check if Pusher service is available.
     */
    public function isAvailable(): bool
    {
        return $this->pusher !== null
            && config('services.pusher.app_key')
            && config('services.pusher.app_secret')
            && config('services.pusher.app_id');
    }

    /**
     * Get current queue status.
     * Note: Pusher sends immediately, so this returns config status.
     */
    public function getQueueStatus(): array
    {
        return [
            'enabled' => $this->isAvailable(),
            'channel' => $this->channel,
            'event' => $this->event,
            'app_id' => config('services.pusher.app_id') ? '***' : null,
            'cluster' => config('services.pusher.cluster'),
        ];
    }

    /**
     * Send a test notification.
     */
    public function sendTest(): array
    {
        if (!$this->isAvailable()) {
            throw new Exception('Pusher service is not available');
        }

        try {
            $testData = [
                'type' => 'test',
                'message' => 'Pusher integration is working!',
                'timestamp' => now()->toIso8601String(),
            ];

            $this->pusher->trigger($this->channel, 'test', $testData);

            return [
                'success' => true,
                'channel' => $this->channel,
                'event' => 'test',
            ];

        } catch (Exception $e) {
            Log::error('Pusher test failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Trigger notification to specific channel (for custom use).
     */
    public function trigger(string $channel, string $event, array $data): bool
    {
        if (!$this->isAvailable()) {
            throw new Exception('Pusher service is not available');
        }

        try {
            $this->pusher->trigger($channel, $event, $data);

            Log::info('Pusher event triggered', [
                'channel' => $channel,
                'event' => $event,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Pusher trigger failed', [
                'channel' => $channel,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
