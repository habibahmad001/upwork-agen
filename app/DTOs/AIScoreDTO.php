<?php

namespace App\DTOs;

/**
 * AI Score Data Transfer Object
 *
 * Immutable DTO for AI evaluation results.
 */
readonly class AIScoreDTO
{
    /**
     * Create a new AIScoreDTO instance.
     *
     * @param float $score AI score from 0-100
     * @param string $reason Explanation of the score
     * @param array<int, string> $technologies Matched technologies
     * @param array<int, string> $redFlags Potential issues detected
     * @param string|null $estimatedHours Estimated work hours
     * @param string|null $estimatedPrice Estimated fair price
     * @param string|null $recommendation AI recommendation
     */
    public function __construct(
        public float $score,
        public string $reason,
        public array $technologies = [],
        public array $redFlags = [],
        public ?string $estimatedHours = null,
        public ?string $estimatedPrice = null,
        public ?string $recommendation = null
    ) {
        // Validate score range
        if ($this->score < 0 || $this->score > 100) {
            throw new \InvalidArgumentException('Score must be between 0 and 100');
        }
    }

    /**
     * Check if score meets threshold.
     *
     * @param float $threshold Minimum score threshold
     * @return bool True if meets or exceeds threshold
     */
    public function meetsThreshold(float $threshold): bool
    {
        return $this->score >= $threshold;
    }

    /**
     * Check if score is excellent (90+).
     *
     * @return bool True if excellent
     */
    public function isExcellent(): bool
    {
        return $this->score >= 90;
    }

    /**
     * Check if score is good (70+).
     *
     * @return bool True if good
     */
    public function isGood(): bool
    {
        return $this->score >= 70;
    }

    /**
     * Check if score is poor (below 50).
     *
     * @return bool True if poor
     */
    public function isPoor(): bool
    {
        return $this->score < 50;
    }

    /**
     * Get score category.
     *
     * @return string Category label
     */
    public function getCategory(): string
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
     * Check if there are any red flags.
     *
     * @return bool True if red flags present
     */
    public function hasRedFlags(): bool
    {
        return count($this->redFlags) > 0;
    }

    /**
     * Get severity of red flags.
     *
     * @return string Severity level
     */
    public function getRedFlagSeverity(): string
    {
        $count = count($this->redFlags);

        return match (true) {
            $count >= 3 => 'High',
            $count >= 2 => 'Medium',
            $count >= 1 => 'Low',
            default => 'None',
        };
    }

    /**
     * Convert to array for database storage.
     *
     * @return array<string, mixed> Array representation
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'reason' => $this->reason,
            'technologies' => $this->technologies,
            'red_flags' => $this->redFlags,
            'estimated_hours' => $this->estimatedHours,
            'estimated_price' => $this->estimatedPrice,
            'recommendation' => $this->recommendation,
        ];
    }

    /**
     * Create AIScoreDTO from array.
     *
     * @param array<string, mixed> $data Source data
     * @return self AIScoreDTO instance
     */
    public static function fromArray(array $data): self
    {
        return new self(
            score: (float) ($data['score'] ?? 0),
            reason: $data['reason'] ?? '',
            technologies: $data['technologies'] ?? [],
            redFlags: $data['red_flags'] ?? [],
            estimatedHours: $data['estimated_hours'] ?? null,
            estimatedPrice: $data['estimated_price'] ?? null,
            recommendation: $data['recommendation'] ?? null,
        );
    }

    /**
     * Create a mock AIScoreDTO for testing.
     *
     * @param float $score Score to use
     * @return self Mock AIScoreDTO
     */
    public static function mock(float $score = 85.0): self
    {
        return new self(
            score: $score,
            reason: 'Mock score for testing',
            technologies: ['Laravel', 'React'],
            redFlags: [],
            estimatedHours: '20-30 hours',
            estimatedPrice: '$1000-1500',
            recommendation: 'Good match for your skills'
        );
    }
}
