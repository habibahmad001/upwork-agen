<?php

namespace App\DTOs;

/**
 * Job Data Transfer Object
 *
 * Immutable DTO for job data to ensure type safety and prevent accidental mutations.
 */
readonly class JobDTO
{
    /**
     * Create a new JobDTO instance.
     *
     * @param string|null $jobId Upwork job ID
     * @param string $title Job title
     * @param string $description Job description
     * @param float|null $budget Fixed price budget
     * @param float|null $hourlyMin Hourly rate minimum
     * @param float|null $hourlyMax Hourly rate maximum
     * @param string|null $clientCountry Client country
     * @param bool $paymentVerified Payment verification status
     * @param float|null $spent Total spent on Upwork
     * @param string|null $hireRate Client hire rate
     * @param float|null $clientRating Client average rating
     * @param int|null $proposals Number of proposals
     * @param string|null $experienceLevel Experience level
     * @param string|null $projectLength Project length
     * @param string|null $timePosted Time since posting
     * @param string|null $url Job URL
     * @param array<int, string> $skills Required skills
     */
    public function __construct(
        public ?string $jobId,
        public string $title,
        public string $description,
        public ?float $budget,
        public ?float $hourlyMin,
        public ?float $hourlyMax,
        public ?string $clientCountry,
        public bool $paymentVerified,
        public ?float $spent,
        public ?string $hireRate,
        public ?float $clientRating,
        public ?int $proposals,
        public ?string $experienceLevel,
        public ?string $projectLength,
        public ?string $timePosted,
        public ?string $url,
        public array $skills = []
    ) {}

    /**
     * Generate fingerprint for duplicate detection.
     *
     * @return string MD5 hash of unique combination
     */
    public function fingerprint(): string
    {
        $components = [
            $this->title,
            $this->clientCountry,
            $this->timePosted,
        ];

        return md5(implode('|', array_filter($components)));
    }

    /**
     * Check if job has hourly rate.
     *
     * @return bool True if hourly rate exists
     */
    public function isHourly(): bool
    {
        return $this->hourlyMin !== null || $this->hourlyMax !== null;
    }

    /**
     * Check if job has fixed budget.
     *
     * @return bool True if fixed budget exists
     */
    public function isFixedPrice(): bool
    {
        return $this->budget !== null;
    }

    /**
     * Get budget range as string.
     *
     * @return string Budget range representation
     */
    public function getBudgetRange(): string
    {
        if ($this->isFixedPrice()) {
            return '$' . number_format($this->budget, 0);
        }

        if ($this->isHourly()) {
            $min = $this->hourlyMin ?? '?';
            $max = $this->hourlyMax ?? '?';
            return "\${$min}-\${$max}/hr";
        }

        return 'Not specified';
    }

    /**
     * Convert to array for database storage.
     *
     * @return array<string, mixed> Array representation
     */
    public function toArray(): array
    {
        return [
            'job_id' => $this->jobId,
            'title' => $this->title,
            'description' => $this->description,
            'budget' => $this->budget,
            'hourly_min' => $this->hourlyMin,
            'hourly_max' => $this->hourlyMax,
            'client_country' => $this->clientCountry,
            'payment_verified' => $this->paymentVerified,
            'spent' => $this->spent,
            'hire_rate' => $this->hireRate,
            'client_rating' => $this->clientRating,
            'proposals' => $this->proposals,
            'experience_level' => $this->experienceLevel,
            'project_length' => $this->projectLength,
            'time_posted' => $this->timePosted,
            'url' => $this->url,
        ];
    }

    /**
     * Create JobDTO from array.
     *
     * @param array<string, mixed> $data Source data
     * @return self JobDTO instance
     */
    public static function fromArray(array $data): self
    {
        return new self(
            jobId: $data['job_id'] ?? null,
            title: $data['title'] ?? '',
            description: $data['description'] ?? '',
            budget: isset($data['budget']) ? (float) $data['budget'] : null,
            hourlyMin: isset($data['hourly_min']) ? (float) $data['hourly_min'] : null,
            hourlyMax: isset($data['hourly_max']) ? (float) $data['hourly_max'] : null,
            clientCountry: $data['client_country'] ?? null,
            paymentVerified: (bool) ($data['payment_verified'] ?? false),
            spent: isset($data['spent']) ? (float) $data['spent'] : null,
            hireRate: $data['hire_rate'] ?? null,
            clientRating: isset($data['client_rating']) ? (float) $data['client_rating'] : null,
            proposals: isset($data['proposals']) ? (int) $data['proposals'] : null,
            experienceLevel: $data['experience_level'] ?? null,
            projectLength: $data['project_length'] ?? null,
            timePosted: $data['time_posted'] ?? null,
            url: $data['url'] ?? null,
            skills: $data['skills'] ?? [],
        );
    }
}
