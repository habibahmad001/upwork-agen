<?php

namespace App\Services\AI;

use App\Contracts\AIEvaluationServiceInterface;
use App\DTOs\AIScoreDTO;
use App\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class MockAIService implements AIEvaluationServiceInterface
{
    /**
     * User skills profile.
     */
    protected array $userSkills = [
        // Backend
        'Laravel', 'PHP', 'WordPress', 'WooCommerce', 'REST API', 'GraphQL', 'MySQL', 'Linux',
        // Frontend
        'React', 'Vue', 'JavaScript',
        // AI & Automation
        'OpenAI', 'Claude', 'AI Agents', 'Make.com', 'n8n', 'MCP', 'Automation',
        // DevOps
        'AWS', 'Git', 'Docker',
        // Integrations
        'Stripe', 'PayPal', 'Twilio',
    ];

    /**
     * Spam keywords.
     */
    protected array $spamKeywords = [
        'data entry', 'typing', 'translation', 'captcha',
        'copy paste', 'excel', 'spreadsheet', 'manual entry',
    ];

    /**
     * Evaluate a job using keyword matching (mock AI).
     */
    public function evaluate(Job $job): AIScoreDTO
    {
        $jobSkills = $job->skills_list;
        $jobTitle = strtolower($job->title);
        $jobDescription = strtolower($job->description);

        // Check for spam
        $spamFlags = $this->checkSpam($jobTitle, $jobDescription);
        if (count($spamFlags) > 0) {
            return $this->createLowScore($job, $spamFlags);
        }

        // Calculate skill match score
        $skillMatches = $this->calculateSkillMatches($jobSkills);
        $score = $this->calculateScore($skillMatches, $job);

        // Generate reasoning
        $reason = $this->generateReason($skillMatches, $job);

        // Identify red flags
        $redFlags = $this->identifyRedFlags($job);

        // Estimate hours and price
        $estimatedHours = $this->estimateHours($job);
        $estimatedPrice = $this->estimatePrice($job);

        return new AIScoreDTO(
            score: $score,
            reason: $reason,
            technologies: array_intersect($this->userSkills, $jobSkills),
            redFlags: $redFlags,
            estimatedHours: $estimatedHours,
            estimatedPrice: $estimatedPrice,
            recommendation: $this->generateRecommendation($score, $skillMatches),
        );
    }

    /**
     * Batch evaluate jobs.
     */
    public function batchEvaluate(array $jobs): Collection
    {
        return collect($jobs)->map(fn ($job) => $this->evaluate($job));
    }

    /**
     * Check if service is available.
     */
    public function isAvailable(): bool
    {
        return true; // Mock is always available
    }

    /**
     * Get current model.
     */
    public function getModel(): string
    {
        return 'mock-keyword-matcher-v1';
    }

    /**
     * Calculate skill matches.
     */
    protected function calculateSkillMatches(array $jobSkills): array
    {
        $matches = [];
        $jobSkillsLower = array_map('strtolower', $jobSkills);

        foreach ($this->userSkills as $userSkill) {
            $userSkillLower = strtolower($userSkill);
            foreach ($jobSkillsLower as $index => $jobSkill) {
                // Exact match
                if ($userSkillLower === $jobSkill) {
                    $matches[] = $userSkill;
                    break;
                }
                // Contains match
                if (str_contains($jobSkill, $userSkillLower) || str_contains($userSkillLower, $jobSkill)) {
                    $matches[] = $userSkill;
                    break;
                }
            }
        }

        return array_unique($matches);
    }

    /**
     * Calculate score based on matches.
     */
    protected function calculateScore(array $matches, Job $job): float
    {
        $baseScore = 30.0; // Base score for any job
        $matchScore = count($matches) * 8; // 8 points per matched skill
        $clientBonus = 0;

        // Client quality bonus
        if ($job->payment_verified) {
            $clientBonus += 10;
        }

        if ($job->client_rating && $job->client_rating >= 4.5) {
            $clientBonus += 10;
        }

        if ($job->spent && $job->spent > 1000) {
            $clientBonus += 5;
        }

        if ($job->hire_rate && str_replace('%', '', $job->hire_rate) > 50) {
            $clientBonus += 5;
        }

        // Cap at 100
        $totalScore = min($baseScore + $matchScore + $clientBonus, 100);

        // Round to 1 decimal
        return round($totalScore, 1);
    }

    /**
     * Generate reasoning.
     */
    protected function generateReason(array $matches, Job $job): string
    {
        $count = count($matches);

        if ($count === 0) {
            return "No matching skills found. Job requirements don't align with your profile.";
        }

        $reason = "Found {$count} matching skill(s): " . implode(', ', $matches) . ". ";

        if ($job->payment_verified) {
            $reason .= "Client has verified payment. ";
        }

        if ($job->client_rating) {
            $reason .= "Client rating: {$job->client_rating}/5. ";
        }

        if ($job->isHourly() && $job->hourly_min >= 20) {
            $reason .= "Hourly rate is competitive. ";
        }

        return trim($reason);
    }

    /**
     * Identify red flags.
     */
    protected function identifyRedFlags(Job $job): array
    {
        $flags = [];

        if (!$job->payment_verified) {
            $flags[] = 'Payment not verified';
        }

        if ($job->client_rating && $job->client_rating < 4.0) {
            $flags[] = "Low client rating ({$job->client_rating}/5)";
        }

        if ($job->hire_rate && (int) str_replace('%', '', $job->hire_rate) < 20) {
            $flags[] = "Low hire rate ({$job->hire_rate})";
        }

        if ($job->proposals && $job->proposals > 50) {
            $flags[] = "High competition ({$job->proposals} proposals)";
        }

        if ($job->experience_level === 'Entry') {
            $flags[] = 'Entry level job';
        }

        return $flags;
    }

    /**
     * Estimate hours.
     */
    protected function estimateHours(Job $job): string
    {
        // Simple heuristic based on job type
        if ($job->project_length) {
            if (str_contains(strtolower($job->project_length), 'month')) {
                return '80-120 hours';
            }
            if (str_contains(strtolower($job->project_length), 'week')) {
                return '20-40 hours';
            }
        }

        if ($job->isFixedPrice() && $job->budget) {
            // Assume $50/hr
            $hours = (int) ($job->budget / 50);
            $maxHours = $hours + 20;
            return "{$hours}-{$maxHours} hours";
        }

        return '20-60 hours';
    }

    /**
     * Estimate price.
     */
    protected function estimatePrice(Job $job): string
    {
        if ($job->budget) {
            return '$' . number_format($job->budget, 0);
        }

        if ($job->hourly_min) {
            $minPrice = $job->hourly_min * 40; // 40 hours
            $maxPrice = ($job->hourly_max ?? $job->hourly_min) * 60;
            return "\${$minPrice}-\$$maxPrice";
        }

        return '$500-1500';
    }

    /**
     * Generate recommendation.
     */
    protected function generateRecommendation(float $score, array $matches): string
    {
        if ($score >= 80) {
            return "Strong match with your skills! High quality client. Apply quickly.";
        }

        if ($score >= 60) {
            return "Good skill match. Consider applying if interested in the project.";
        }

        if ($score >= 40) {
            return "Partial skill match. Review job details carefully before applying.";
        }

        return "Low match - skills don't align well with your profile.";
    }

    /**
     * Check for spam keywords.
     */
    protected function checkSpam(string $title, string $description): array
    {
        $found = [];
        $combined = $title . ' ' . $description;

        foreach ($this->spamKeywords as $keyword) {
            if (str_contains($combined, strtolower($keyword))) {
                $found[] = $keyword;
            }
        }

        return $found;
    }

    /**
     * Create low score for spam jobs.
     */
    protected function createLowScore(Job $job, array $spamFlags): AIScoreDTO
    {
        return new AIScoreDTO(
            score: 10.0,
            reason: "Job contains spam keywords: " . implode(', ', $spamFlags),
            technologies: [],
            redFlags: $spamFlags,
            estimatedHours: 'N/A',
            estimatedPrice: 'N/A',
            recommendation: 'Skip - appears to be spam or low-quality job.',
        );
    }
}
