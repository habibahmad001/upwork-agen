<?php

namespace App\Contracts;

use App\Models\Job;
use App\DTOs\AIScoreDTO;
use Illuminate\Support\Collection;

/**
 * AI Evaluation Service Interface
 *
 * Defines the contract for AI-based job evaluation services.
 */
interface AIEvaluationServiceInterface
{
    /**
     * Evaluate a single job using AI.
     *
     * @param Job $job The job to evaluate
     * @return AIScoreDTO The AI evaluation result
     * @throws \App\Exceptions\AIServiceException
     */
    public function evaluate(Job $job): AIScoreDTO;

    /**
     * Batch evaluate multiple jobs.
     *
     * @param array<int, Job> $jobs Array of jobs to evaluate
     * @return Collection<int, AIScoreDTO> Collection of evaluation results
     * @throws \App\Exceptions\AIServiceException
     */
    public function batchEvaluate(array $jobs): Collection;

    /**
     * Check if the AI service is available.
     *
     * @return bool True if service is available
     */
    public function isAvailable(): bool;

    /**
     * Get the current AI model being used.
     *
     * @return string Model name/identifier
     */
    public function getModel(): string;
}
