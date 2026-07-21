<?php

namespace App\Services\AI;

use App\DTOs\AIScoreDTO;
use App\Models\Job;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Ollama AI Service for local model inference
 */
class OllamaAIService
{
    /**
     * The Ollama API host
     */
    protected string $host;

    /**
     * The model to use
     */
    protected string $model;

    /**
     * Request timeout in seconds
     */
    protected int $timeout = 120;

    /**
     * Create a new service instance
     */
    public function __construct()
    {
        $this->host = config('ollama.host', 'http://localhost:11434');
        $this->model = config('ollama.model', 'phi3');
        $this->timeout = config('ollama.timeout', 120);
    }

    /**
     * Evaluate a job and return AI score
     */
    public function evaluate(Job $job): AIScoreDTO
    {
        try {
            $prompt = $this->buildPrompt($job);

            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post("{$this->host}/api/generate", [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.3,
                        'num_predict' => 500,
                    ],
                ]);

            if (!$response->successful()) {
                throw new Exception("Ollama API error: " . $response->body());
            }

            $result = $response->json();
            $responseText = $result['response'] ?? '';

            return $this->parseResponse($responseText, $job);

        } catch (Exception $e) {
            Log::error('Ollama evaluation failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            // Return fallback score on error
            return $this->getFallbackScore($job);
        }
    }

    /**
     * Build the evaluation prompt
     */
    protected function buildPrompt(Job $job): string
    {
        $skillsProfile = $this->getSkillsProfile();

        $prompt = "You are a job matching expert. Rate this Upwork job (0-100) based on the developer's skills profile.\n\n";
        $prompt .= "DEVELOPER SKILLS:\n" . $skillsProfile . "\n\n";
        $prompt .= "JOB DETAILS:\n";
        $prompt .= "Title: " . $job->title . "\n";
        $prompt .= "Description: " . substr($job->description, 0, 2000) . "\n";

        if ($job->budget_range) {
            $prompt .= "Budget: " . $job->budget_range . "\n";
        }

        if ($job->client_country) {
            $prompt .= "Client Country: " . $job->client_country . "\n";
        }

        if ($job->proposals) {
            $prompt .= "Proposals: " . $job->proposals . "\n";
        }

        $prompt .= "\nProvide your response in this exact JSON format:\n";
        $prompt .= "{\n";
        $prompt .= "  \"score\": 85,\n";
        $prompt .= "  \"reasoning\": \"Brief explanation\",\n";
        $prompt .= "  \"technologies\": [\"React\", \"Node.js\"],\n";
        $prompt .= "  \"red_flags\": [\"Low budget\", \"New client\"],\n";
        $prompt .= "  \"recommendation\": \"Apply or pass advice\"\n";
        $prompt .= "}\n\n";
        $prompt .= "Ensure score is 0-100. JSON only, no extra text.";

        return $prompt;
    }

    /**
     * Parse the AI response
     */
    protected function parseResponse(string $response, Job $job): AIScoreDTO
    {
        // Try to extract JSON from response
        $json = $this->extractJson($response);

        if ($json === null) {
            Log::warning('Failed to parse AI response', [
                'response' => substr($response, 0, 500),
            ]);
            return $this->getFallbackScore($job);
        }

        return new AIScoreDTO(
            score: $this->clampScore($json['score'] ?? 70),
            reasoning: $json['reasoning'] ?? 'AI analysis completed',
            technologies: $json['technologies'] ?? [],
            red_flags: $json['red_flags'] ?? [],
            recommendation: $json['recommendation'] ?? null,
            estimatedPrice: $this->estimatePrice($job),
            estimatedHours: $this->estimateHours($job),
        );
    }

    /**
     * Extract JSON from response text
     */
    protected function extractJson(string $text): ?array
    {
        // Try to find JSON between curly braces
        if (preg_match('/\{[^{}]*\{[^{}]*\}[^{}]*\}|\{[^{}]+\}/s', $text, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        // Fallback: try to decode the whole response
        $json = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return null;
    }

    /**
     * Get skills profile from storage
     */
    protected function getSkillsProfile(): string
    {
        $profileFile = storage_path('app/skills_profile.json');

        if (!file_exists($profileFile)) {
            return 'Full-stack developer with experience in web development';
        }

        $profile = json_decode(file_get_contents($profileFile), true);

        $skills = [];
        if (!empty($profile['skills'])) {
            $skills[] = "Skills: " . implode(', ', array_slice($profile['skills'], 0, 20));
        }

        return implode("\n", $skills);
    }

    /**
     * Get fallback score when AI fails
     */
    protected function getFallbackScore(Job $job): AIScoreDTO
    {
        // Simple keyword-based fallback
        $score = 70; // Base score
        $technologies = [];
        $redFlags = [];

        // Check for payment verification
        if ($job->payment_verified) {
            $score += 10;
        } else {
            $redFlags[] = 'Payment not verified';
        }

        // Check for budget
        if ($job->budget > 500) {
            $score += 5;
        } elseif ($job->budget < 100) {
            $score -= 10;
            $redFlags[] = 'Low budget';
        }

        return new AIScoreDTO(
            score: $this->clampScore($score),
            reasoning: 'Fallback analysis (AI unavailable)',
            technologies: $technologies,
            red_flags: $redFlags,
            recommendation: 'Review carefully before applying',
            estimatedPrice: $this->estimatePrice($job),
            estimatedHours: $this->estimateHours($job),
        );
    }

    /**
     * Clamp score between 0 and 100
     */
    protected function clampScore(float $score): float
    {
        return max(0, min(100, (float) $score));
    }

    /**
     * Estimate project hours
     */
    protected function estimateHours(Job $job): string
    {
        if ($job->project_length) {
            // Try to parse common patterns
            if (preg_match('/(\d+)\+?\s*(week|month)s?/i', $job->project_length, $matches)) {
                $value = (int) $matches[1];
                $unit = strtolower($matches[2]);

                if ($unit === 'week') {
                    $hours = $value * 40;
                    $maxHours = $hours + 20;
                    return "{$hours}-{$maxHours} hours";
                } else if ($unit === 'month') {
                    $hours = $value * 160;
                    $maxHours = $hours + 40;
                    return "{$hours}-{$maxHours} hours";
                }
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
     * Estimate project price
     */
    protected function estimatePrice(Job $job): string
    {
        if ($job->budget) {
            return '$' . number_format($job->budget, 0);
        }

        if ($job->hourly_min && $job->hourly_max) {
            return '$' . number_format($job->hourly_min, 0) . ' - $' . number_format($job->hourly_max, 0) . '/hr';
        }

        if ($job->hourly_min) {
            return '$' . number_format($job->hourly_min, 0) . '+/hr';
        }

        return 'Not specified';
    }

    /**
     * Check if Ollama service is available
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->host}/api/tags");
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get loaded models
     */
    public function getModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->host}/api/tags");
            if ($response->successful()) {
                $data = $response->json();
                return $data['models'] ?? [];
            }
        } catch (Exception $e) {
            Log::error('Failed to get Ollama models', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get service status
     */
    public function getStatus(): array
    {
        $models = $this->getModels();
        $modelNames = array_column($models, 'name');

        return [
            'available' => $this->isAvailable(),
            'host' => $this->host,
            'current_model' => $this->model,
            'loaded_models' => $modelNames,
        ];
    }
}
