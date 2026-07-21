<?php

namespace App\Services\Parser;

use App\Contracts\ParserServiceInterface;
use App\DTOs\JobDTO;

class JobParserService implements ParserServiceInterface
{
    /**
     * Parse raw crawler data into JobDTO.
     */
    public function parse(array $rawData): JobDTO
    {
        return new JobDTO(
            jobId: $rawData['job_id'] ?? null,
            title: $this->cleanString($rawData['title'] ?? ''),
            description: $this->cleanHtml($rawData['description'] ?? ''),
            budget: $this->parseBudget($rawData['budget'] ?? null),
            hourlyMin: $this->parseHourlyMin($rawData),
            hourlyMax: $this->parseHourlyMax($rawData),
            clientCountry: $rawData['client_country'] ?? null,
            paymentVerified: (bool) ($rawData['payment_verified'] ?? false),
            spent: $this->parseDecimal($rawData['spent'] ?? null),
            hireRate: $rawData['hire_rate'] ?? null,
            clientRating: $this->parseDecimal($rawData['client_rating'] ?? null),
            proposals: $this->parseInt($rawData['proposals'] ?? null),
            experienceLevel: $rawData['experience_level'] ?? null,
            projectLength: $rawData['project_length'] ?? null,
            timePosted: $rawData['time_posted'] ?? null,
            url: $rawData['url'] ?? null,
            skills: $this->parseSkills($rawData['skills'] ?? []),
        );
    }

    /**
     * Normalize job data.
     */
    public function normalize(JobDTO $dto): JobDTO
    {
        // Apply any normalization rules
        return $dto;
    }

    /**
     * Parse budget string to decimal.
     */
    public function parseBudget(?string $budget): ?float
    {
        if (empty($budget)) {
            return null;
        }

        // Remove currency symbols and extract numbers
        $cleaned = preg_replace('/[^0-9.,]/', '', $budget);

        // Handle comma as decimal separator
        $cleaned = str_replace(',', '.', $cleaned);

        return (float) $cleaned ?: null;
    }

    /**
     * Parse hourly minimum.
     */
    protected function parseHourlyMin(array $data): ?float
    {
        if (isset($data['hourly_min'])) {
            return $this->parseDecimal($data['hourly_min']);
        }

        // Try to parse from hourly_range
        if (isset($data['hourly_range']) && is_string($data['hourly_range'])) {
            if (preg_match('/(\d+[.,]?\d*)\s*-\s*(\d+[.,]?\d*)/i', $data['hourly_range'], $matches)) {
                return $this->parseDecimal($matches[1]);
            }
        }

        return null;
    }

    /**
     * Parse hourly maximum.
     */
    protected function parseHourlyMax(array $data): ?float
    {
        if (isset($data['hourly_max'])) {
            return $this->parseDecimal($data['hourly_max']);
        }

        // Try to parse from hourly_range
        if (isset($data['hourly_range']) && is_string($data['hourly_range'])) {
            if (preg_match('/(\d+[.,]?\d*)\s*-\s*(\d+[.,]?\d*)/i', $data['hourly_range'], $matches)) {
                return $this->parseDecimal($matches[2]);
            }
        }

        return null;
    }

    /**
     * Parse skills array.
     */
    protected function parseSkills($skills): array
    {
        if (is_array($skills)) {
            return array_map(fn ($skill) => trim($skill), $skills);
        }

        if (is_string($skills)) {
            // Split by common delimiters
            return array_map('trim', preg_split('/[,|;]/', $skills));
        }

        return [];
    }

    /**
     * Generate fingerprint for duplicate detection.
     */
    public function generateFingerprint(JobDTO $dto): string
    {
        $data = $dto->title . '|' . ($dto->clientCountry ?? '') . '|' . ($dto->timePosted ?? '');
        return md5(strtolower($data));
    }

    /**
     * Validate DTO.
     */
    public function validate(JobDTO $dto): bool
    {
        // Check required fields
        if (empty($dto->title)) {
            return false;
        }

        if (empty($dto->description)) {
            return false;
        }

        return true;
    }

    /**
     * Clean string from extra whitespace.
     */
    protected function cleanString(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Clean HTML from description.
     */
    protected function cleanHtml(string $html): string
    {
        // Strip HTML tags but preserve line breaks
        $cleaned = strip_tags($html, '<br><p><div><ul><li><h1><h2><h3><h4><h5><h6>');

        // Convert HTML entities
        $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);

        return trim($cleaned);
    }

    /**
     * Parse decimal value.
     */
    protected function parseDecimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Handle comma as decimal separator
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        $parsed = (float) $value;

        return $parsed > 0 ? $parsed : null;
    }

    /**
     * Parse integer value.
     */
    protected function parseInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value ?: null;
    }
}
