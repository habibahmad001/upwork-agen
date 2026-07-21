<?php

namespace App\Services\WhatsApp;

use App\Models\Job;
use App\Models\JobAiScore;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    /**
     * Send a message via WhatsApp Cloud API.
     */
    public function send(string $message): array
    {
        if (!config('whatsapp.enabled')) {
            throw new Exception('WhatsApp notifications are disabled');
        }

        $phoneNumber = config('whatsapp.phone_number');
        $phoneId = config('whatsapp.phone_id');
        $accessToken = config('whatsapp.access_token');
        $apiVersion = config('whatsapp.api_version', 'v18.0');

        if (!$phoneId || !$accessToken) {
            throw new Exception('WhatsApp credentials not configured');
        }

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneId}/messages";

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $phoneNumber,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]);

            if (!$response->successful()) {
                throw new Exception("WhatsApp API error: " . $response->body());
            }

            $data = $response->json();

            return [
                'success' => true,
                'message_id' => $data['messages'][0]['id'] ?? null,
                'response' => $data,
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp send failed', [
                'error' => $e->getMessage(),
                'phone_number' => $this->maskPhone($phoneNumber),
            ]);
            throw $e;
        }
    }

    /**
     * Format a message from job data.
     */
    public function formatMessage(Job $job, JobAiScore $aiScore): string
    {
        $lines = [];

        // Header with score
        $emoji = $this->getScoreEmoji($aiScore->score);
        $lines[] = "{$emoji} *Upwork Job Match - Score: {$aiScore->score}/100*";

        $lines[] = '';

        // Job title
        $lines[] = "*{$job->title}*";

        $lines[] = '';

        // Budget
        if (config('whatsapp.message_template.include_budget', true)) {
            $lines[] = "💰 Budget: {$job->budget_range}";
        }

        // Client info
        $clientInfo = [];
        if ($job->client_country) {
            $clientInfo[] = $job->client_country;
        }
        if ($job->payment_verified) {
            $clientInfo[] = '✓ Verified';
        }
        if ($job->client_rating) {
            $clientInfo[] = "⭐ {$job->client_rating}/5";
        }
        if (!empty($clientInfo)) {
            $lines[] = "👤 Client: " . implode(' • ', $clientInfo);
        }

        $lines[] = '';

        // AI Reasoning
        if (config('whatsapp.message_template.include_reason', true) && $aiScore->reasoning) {
            $lines[] = "*Why this matches:*";
            $lines[] = $aiScore->reasoning;
            $lines[] = '';
        }

        // Technologies/Skills
        if (!empty($aiScore->technologies)) {
            $lines[] = "*Matched Skills:* " . implode(', ', $aiScore->technologies);
            $lines[] = '';
        }

        // Red flags
        if (!empty($aiScore->red_flags)) {
            $lines[] = "⚠️ *Flags:* " . implode(', ', $aiScore->red_flags);
            $lines[] = '';
        }

        // Recommendation
        if ($aiScore->recommendation) {
            $lines[] = "*💡 {$aiScore->recommendation}*";
            $lines[] = '';
        }

        // Link
        if (config('whatsapp.message_template.include_link', true) && $job->url) {
            $lines[] = "🔗 View Job: {$job->url}";
        }

        // Footer
        $lines[] = '';
        $lines[] = '_Posted: ' . ($job->time_posted ?? 'Recently') . '_';

        $message = implode("\n", $lines);

        // Check message length
        $maxLength = config('whatsapp.message_template.max_length', 4096);
        if (strlen($message) > $maxLength) {
            // Truncate if too long
            $message = substr($message, 0, $maxLength - 3) . '...';
        }

        return $message;
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
     * Mask phone number for logging.
     */
    protected function maskPhone(string $phone): string
    {
        if (strlen($phone) < 4) {
            return '***';
        }

        return substr($phone, 0, -4) . '****';
    }

    /**
     * Check if WhatsApp service is available.
     */
    public function isAvailable(): bool
    {
        return config('whatsapp.enabled') &&
               config('whatsapp.phone_id') &&
               config('whatsapp.access_token');
    }

    /**
     * Get queue status.
     */
    public function getQueueStatus(): array
    {
        return [
            'enabled' => $this->isAvailable(),
            'rate_limit' => config('whatsapp.rate_limit', 10),
            'phone_number' => $this->maskPhone(config('whatsapp.phone_number')),
        ];
    }

    /**
     * Send a test message.
     */
    public function sendTest(): array
    {
        $message = "🧪 Test message from Upwork Job Agent\n\n" .
                   "Time: " . now()->toIso8601String() . "\n" .
                   "Status: WhatsApp integration is working!";

        return $this->send($message);
    }
}
