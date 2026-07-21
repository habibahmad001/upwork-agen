<?php

namespace App\Contracts;

use App\Models\Job;
use App\DTOs\AIScoreDTO;
use App\DTOs\NotificationDTO;

/**
 * Notification Service Interface
 *
 * Defines the contract for notification delivery services.
 */
interface NotificationServiceInterface
{
    /**
     * Send notification immediately.
     *
     * @param Job $job The job to notify about
     * @param AIScoreDTO $score The AI score details
     * @return bool True if sent successfully
     * @throws \App\Exceptions\NotificationException
     */
    public function send(Job $job, AIScoreDTO $score): bool;

    /**
     * Queue notification for background sending.
     *
     * @param Job $job The job to notify about
     * @param AIScoreDTO $score The AI score details
     * @return void
     */
    public function queue(Job $job, AIScoreDTO $score): void;

    /**
     * Format notification message.
     *
     * @param Job $job The job to format message for
     * @param AIScoreDTO $score The AI score details
     * @return string Formatted message
     */
    public function formatMessage(Job $job, AIScoreDTO $score): string;

    /**
     * Check if notification service is available.
     *
     * @return bool True if service is available
     */
    public function isAvailable(): bool;

    /**
     * Get current queue status.
     *
     * @return array<string, mixed> Queue status information
     */
    public function getQueueStatus(): array;
}
