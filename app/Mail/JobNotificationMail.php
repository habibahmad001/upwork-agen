<?php

namespace App\Mail;

use App\Models\Job;
use App\Models\JobAiScore;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Job $job,
        public JobAiScore $aiScore,
        public string $plainMessage
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $emoji = $this->getScoreEmoji($this->aiScore->score);
        $subject = "{$emoji} Upwork Job: {$this->job->title}";

        // Truncate subject if too long
        if (strlen($subject) > 70) {
            $subject = substr($subject, 0, 67) . '...';
        }

        return new Envelope(
            subject: $subject,
            from: [
                'address' => config('mail.from.address'),
                'name' => config('mail.from.name', 'Upwork Job Agent'),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.job-notification',
            text: 'emails.job-notification-plain',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
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
     * Get the plain text version.
     */
    public function buildPlainMessage(): string
    {
        return $this->plainMessage;
    }
}
