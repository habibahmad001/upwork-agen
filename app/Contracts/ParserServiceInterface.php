<?php

namespace App\Contracts;

use App\DTOs\JobDTO;

/**
 * Parser Service Interface
 *
 * Defines the contract for parsing raw data into structured DTOs.
 */
interface ParserServiceInterface
{
    /**
     * Parse raw crawler data into JobDTO.
     *
     * @param array<string, mixed> $rawData Raw data from crawler
     * @return JobDTO Parsed job data transfer object
     * @throws \App\Exceptions\ParserException
     */
    public function parse(array $rawData): JobDTO;

    /**
     * Normalize and clean job data.
     *
     * @param JobDTO $dto The DTO to normalize
     * @return JobDTO Normalized DTO
     */
    public function normalize(JobDTO $dto): JobDTO;

    /**
     * Parse budget string to numeric value.
     *
     * @param string|null $budgetString Budget string (e.g., "$500 Fixed")
     * @return float|null Parsed budget or null
     */
    public function parseBudget(?string $budgetString): ?float;

    /**
     * Parse hourly rate string to min/max values.
     *
     * @param string|null $hourlyString Hourly string (e.g., "$25-$40/hr")
     * @return array{min: float|null, max: float|null} Parsed hourly range
     */
    public function parseHourly(?string $hourlyString): array;

    /**
     * Generate fingerprint for duplicate detection.
     *
     * @param JobDTO $dto The DTO to fingerprint
     * @return string MD5 hash fingerprint
     */
    public function generateFingerprint(JobDTO $dto): string;

    /**
     * Validate JobDTO has required fields.
     *
     * @param JobDTO $dto The DTO to validate
     * @return bool True if valid
     */
    public function validate(JobDTO $dto): bool;
}
