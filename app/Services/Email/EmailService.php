<?php

namespace App\Services\Email;

use App\Models\Job;
use App\Models\JobAiScore;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;
use App\Mail\JobNotificationMail;

class EmailService
{
    /**
     * Send a job notification email.
     */
    public function sendJobNotification(Job $job, JobAiScore $aiScore): array
    {
        if (!config('mail.enabled', true)) {
            throw new Exception('Email notifications are disabled');
        }

        $recipient = config('mail.notification_recipient', config('mail.from.address'));

        if (!$recipient) {
            throw new Exception('No email recipient configured');
        }

        try {
            $message = $this->formatMessage($job, $aiScore);

            $mail = Mail::to($recipient)->send(new JobNotificationMail($job, $aiScore, $message));

            return [
                'success' => true,
                'recipient' => $this->maskEmail($recipient),
                'subject' => "🔥 Upwork Job Match - Score: {$aiScore->score}/100",
            ];

        } catch (Exception $e) {
            Log::error('Email send failed', [
                'error' => $e->getMessage(),
                'recipient' => $this->maskEmail($recipient),
            ]);
            throw $e;
        }
    }

    /**
     * Send a test email.
     */
    public function sendTest(): array
    {
        $recipient = config('mail.notification_recipient', config('mail.from.address'));

        if (!$recipient) {
            throw new Exception('No email recipient configured');
        }

        try {
            $message = "This is a test email from Upwork Job Agent.\n\n" .
                       "Time: " . now()->toIso8601String() . "\n" .
                       "Status: Email integration is working!";

            $mail = Mail::raw($message, function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('🧪 Test Email - Upwork Job Agent')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return [
                'success' => true,
                'recipient' => $this->maskEmail($recipient),
                'subject' => '🧪 Test Email - Upwork Job Agent',
            ];

        } catch (Exception $e) {
            Log::error('Test email failed', [
                'error' => $e->getMessage(),
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
        $lines[] = "{$emoji} Upwork Job Match - Score: {$aiScore->score}/100";

        $lines[] = '';
        $lines[] = str_repeat('=', 50);
        $lines[] = '';

        // Job title
        $lines[] = "TITLE: {$job->title}";

        $lines[] = '';

        // Budget
        $lines[] = "BUDGET: {$job->budget_range}";

        // Client info
        $lines[] = "CLIENT INFO:";
        if ($job->client_country) {
            $lines[] = "  - Country: {$job->client_country}";
        }
        if ($job->payment_verified) {
            $lines[] = "  - Payment: ✓ Verified";
        }
        if ($job->client_rating) {
            $lines[] = "  - Rating: ⭐ {$job->client_rating}/5";
        }
        if ($job->client_hires > 0) {
            $lines[] = "  - Hires: {$job->client_hires}";
        }

        $lines[] = '';

        // Job Details
        if ($job->proposals) {
            $lines[] = "PROPOSALS: {$job->proposals}";
        }
        if ($job->experience_level) {
            $lines[] = "EXPERIENCE: {$job->experience_level}";
        }
        if ($job->project_length) {
            $lines[] = "DURATION: {$job->project_length}";
        }
        if ($job->time_posted) {
            $lines[] = "POSTED: {$job->time_posted}";
        }

        $lines[] = '';
        $lines[] = str_repeat('-', 50);
        $lines[] = '';

        // AI Reasoning
        if ($aiScore->reasoning) {
            $lines[] = "WHY THIS MATCHES:";
            $lines[] = $aiScore->reasoning;
            $lines[] = '';
        }

        // Technologies/Skills
        if (!empty($aiScore->technologies)) {
            $lines[] = "MATCHED SKILLS: " . implode(', ', $aiScore->technologies);
            $lines[] = '';
        }

        // Red flags
        if (!empty($aiScore->red_flags)) {
            $lines[] = "⚠️  FLAGS: " . implode(', ', $aiScore->red_flags);
            $lines[] = '';
        }

        // Recommendation
        if ($aiScore->recommendation) {
            $lines[] = "💡 RECOMMENDATION: {$aiScore->recommendation}";
            $lines[] = '';
        }

        // Link
        if ($job->url) {
            $lines[] = "VIEW JOB: {$job->url}";
        }

        // Footer
        $lines[] = '';
        $lines[] = str_repeat('=', 50);
        $lines[] = '';
        $lines[] = "Generated by Upwork Job Agent";
        $lines[] = "Time: " . now()->toIso8601String();

        return implode("\n", $lines);
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
     * Mask email for logging.
     */
    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***';
        }

        $name = $parts[0];
        $domain = $parts[1];

        if (strlen($name) <= 2) {
            $nameMask = str_repeat('*', strlen($name));
        } else {
            $nameMask = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
        }

        return $nameMask . '@' . $domain;
    }

    /**
     * Check if email service is available.
     */
    public function isAvailable(): bool
    {
        return config('mail.enabled', true) &&
               config('mail.from.address') &&
               (config('mail.mailer') === 'smtp' || config('mail.mailer') === 'sendmail');
    }

    /**
     * Get queue status.
     */
    public function getStatus(): array
    {
        return [
            'enabled' => $this->isAvailable(),
            'mailer' => config('mail.mailer'),
            'from' => config('mail.from.address'),
            'recipient' => $this->maskEmail(config('mail.notification_recipient', config('mail.from.address'))),
        ];
    }
}
