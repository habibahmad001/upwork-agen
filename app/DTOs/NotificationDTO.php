<?php

namespace App\DTOs;

/**
 * Notification Data Transfer Object
 *
 * Immutable DTO for notification data.
 */
readonly class NotificationDTO
{
    /**
     * Create a new NotificationDTO instance.
     *
     * @param string $phoneNumber Target WhatsApp phone number
     * @param string $message Message content
     * @param int $jobId Associated job ID
     * @param int|null $aiScoreId Associated AI score ID
     */
    public function __construct(
        public string $phoneNumber,
        public string $message,
        public int $jobId,
        public ?int $aiScoreId = null
    ) {}

    /**
     * Validate phone number format.
     *
     * @return bool True if valid format
     */
    public function isValidPhone(): bool
    {
        return preg_match('/^\+[1-9]\d{1,14}$/', $this->phoneNumber) === 1;
    }

    /**
     * Mask phone number for privacy in logs.
     *
     * @return string Masked phone number
     */
    public function maskPhone(): string
    {
        $number = substr($this->phoneNumber, 1); // Remove +
        $visibleStart = substr($number, 0, 4);
        $visibleEnd = substr($number, -4);

        return '+' . $visibleStart . '****' . $visibleEnd;
    }

    /**
     * Get message length.
     *
     * @return int Message character count
     */
    public function getMessageLength(): int
    {
        return strlen($this->message);
    }

    /**
     * Check if message exceeds WhatsApp limit.
     *
     * @return bool True if exceeds limit (4096 chars)
     */
    public function exceedsLimit(): bool
    {
        return $this->getMessageLength() > 4096;
    }

    /**
     * Convert to array for database storage.
     *
     * @return array<string, mixed> Array representation
     */
    public function toArray(): array
    {
        return [
            'phone_number' => $this->phoneNumber,
            'message_content' => $this->message,
            'job_id' => $this->jobId,
            'ai_score_id' => $this->aiScoreId,
        ];
    }

    /**
     * Create NotificationDTO from array.
     *
     * @param array<string, mixed> $data Source data
     * @return self NotificationDTO instance
     */
    public static function fromArray(array $data): self
    {
        return new self(
            phoneNumber: $data['phone_number'] ?? '',
            message: $data['message_content'] ?? '',
            jobId: (int) ($data['job_id'] ?? 0),
            aiScoreId: $data['ai_score_id'] ?? null,
        );
    }
}
