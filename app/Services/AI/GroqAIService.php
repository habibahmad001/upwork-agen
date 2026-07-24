<?php

namespace App\Services\AI;

use App\DTOs\AIScoreDTO;
use App\Models\Job;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Groq AI Service for fast, free AI inference
 * Uses Groq's API with Llama models for job evaluation
 */
class GroqAIService
{
    /**
     * The Groq API key
     */
    protected string $apiKey;

    /**
     * The model to use
     */
    protected string $model;

    /**
     * The API base URL
     */
    protected string $baseUrl;

    /**
     * Request timeout in seconds
     */
    protected int $timeout = 60;

    /**
     * Create a new service instance
     */
    public function __construct()
    {
        $this->apiKey = config('ai.providers.groq.api_key');
        $this->model = config('ai.providers.groq.model', 'llama3-70b-8192');
        $this->baseUrl = config('ai.providers.groq.base_url', 'https://api.groq.com/openai/v1');
        $this->timeout = config('ai.providers.groq.timeout', 60);
    }

    /**
     * Evaluate a job and return AI score
     */
    public function evaluate(Job $job): AIScoreDTO
    {
        if (empty($this->apiKey)) {
            throw new Exception('Groq API key is not configured. Please set GROQ_API_KEY in .env');
        }

        try {
            $prompt = $this->buildPrompt($job);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert job matching analyst. You evaluate Upwork jobs and provide detailed scoring based on developer skills. Always respond with valid JSON only, no additional text.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 1000,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                $error = $this->parseError($response);
                throw new Exception("Groq API error: {$error}");
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '';

            return $this->parseResponse($content, $job);

        } catch (Exception $e) {
            Log::error('Groq evaluation failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            // Return fallback score on error
            return $this->getFallbackScore($job, $e->getMessage());
        }
    }

    /**
     * Build the evaluation prompt
     */
    protected function buildPrompt(Job $job): string
    {
        $skillsProfile = $this->getSkillsProfile();

        $prompt = "Evaluate this Upwork job for a developer with the following skills:\n\n";
        $prompt .= "=== DEVELOPER SKILLS ===\n" . $skillsProfile . "\n\n";
        $prompt .= "=== JOB DETAILS ===\n";
        $prompt .= "Title: " . $job->title . "\n";
        $prompt .= "Description: " . substr(strip_tags($job->description), 0, 2000) . "\n";

        $skillsList = method_exists($job, 'getSkillsListAttribute') ? $job->skills_list : [];
        if (!empty($skillsList)) {
            $prompt .= "Required Skills: " . implode(', ', $skillsList) . "\n";
        }

        if ($job->budget_range) {
            $prompt .= "Budget: " . $job->budget_range . "\n";
        }

        if ($job->hourly_min || $job->hourly_max) {
            $prompt .= "Hourly Rate: $" . ($job->hourly_min ?? '?') . ' - $' . ($job->hourly_max ?? '?') . "\n";
        }

        if ($job->client_country) {
            $prompt .= "Client Country: " . $job->client_country . "\n";
        }

        if ($job->payment_verified) {
            $prompt .= "Payment Verified: Yes\n";
        } else {
            $prompt .= "Payment Verified: No\n";
        }

        if ($job->client_rating) {
            $prompt .= "Client Rating: " . $job->client_rating . "/5\n";
        }

        if ($job->proposals) {
            $prompt .= "Proposals: " . $job->proposals . "\n";
        }

        if ($job->experience_level) {
            $prompt .= "Experience Level: " . $job->experience_level . "\n";
        }

        if ($job->project_length) {
            $prompt .= "Project Length: " . $job->project_length . "\n";
        }

        $prompt .= "\nProvide your evaluation in this exact JSON format:\n";
        $prompt .= "{\n";
        $prompt .= "  \"score\": 85,\n";
        $prompt .= "  \"reasoning\": \"Brief explanation of why this job matches or doesn't match\",\n";
        $prompt .= "  \"technologies\": [\"React\", \"Node.js\", \"Laravel\"],\n";
        $prompt .= "  \"red_flags\": [\"Low budget\", \"New client\", \"High competition\"],\n";
        $prompt .= "  \"recommendation\": \"Apply quickly - strong match\"\n";
        $prompt .= "}\n\n";
        $prompt .= "Guidelines:\n";
        $prompt .= "- Score: 0-100 based on skill match, client quality, and job attractiveness\n";
        $prompt .= "- Technologies: List matching skills from the developer profile\n";
        $prompt .= "- Red flags: Mention any concerns (low budget, new client, etc.)\n";
        $prompt .= "- Recommendation: Brief advice on whether to apply\n\n";
        $prompt .= "Respond with JSON only, no additional text.";

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
            Log::warning('Failed to parse Groq response', [
                'response' => substr($response, 0, 500),
            ]);
            return $this->getFallbackScore($job, 'Failed to parse AI response');
        }

        return new AIScoreDTO(
            score: $this->clampScore($json['score'] ?? 70),
            reason: $json['reasoning'] ?? 'AI analysis completed',
            technologies: $json['technologies'] ?? [],
            redFlags: $json['red_flags'] ?? [],
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
     * Parse error from Groq API response
     */
    protected function parseError($response): string
    {
        $data = $response->json();

        if (isset($data['error']['message'])) {
            return $data['error']['message'];
        }

        if (isset($data['error'])) {
            return is_string($data['error']) ? $data['error'] : json_encode($data['error']);
        }

        return $response->body() ?: 'Unknown error';
    }

    /**
     * Get skills profile from storage
     */
    protected function getSkillsProfile(): string
    {
        $profileFile = storage_path('app/skills_profile.json');

        if (!file_exists($profileFile)) {
            // Default skills profile
            $defaultSkills = [
                'Backend' => ['Laravel', 'PHP', 'WordPress', 'WooCommerce', 'REST API', 'GraphQL', 'MySQL', 'Linux'],
                'Frontend' => ['React', 'Vue', 'JavaScript', 'HTML', 'CSS'],
                'AI & Automation' => ['OpenAI', 'Claude', 'AI Agents', 'Make.com', 'n8n', 'MCP', 'Automation'],
                'DevOps' => ['AWS', 'Git', 'Docker'],
                'Integrations' => ['Stripe', 'PayPal', 'Twilio'],
            ];

            $lines = [];
            foreach ($defaultSkills as $category => $skills) {
                $lines[] = "{$category}: " . implode(', ', $skills);
            }
            return implode("\n", $lines);
        }

        $profile = json_decode(file_get_contents($profileFile), true);

        $lines = [];
        if (!empty($profile['skills'])) {
            $lines[] = "Skills: " . implode(', ', array_slice($profile['skills'], 0, 30));
        }
        if (!empty($profile['experience'])) {
            $lines[] = "Experience: " . $profile['experience'];
        }

        return implode("\n", $lines);
    }

    /**
     * Get fallback score when AI fails
     */
    protected function getFallbackScore(Job $job, string $errorReason): AIScoreDTO
    {
        // Simple keyword-based fallback
        $score = 60; // Base score
        $technologies = [];
        $redFlags = [];

        // Check for payment verification
        if ($job->payment_verified) {
            $score += 15;
        } else {
            $redFlags[] = 'Payment not verified';
            $score -= 10;
        }

        // Check for budget
        if ($job->budget > 500) {
            $score += 10;
        } elseif ($job->budget && $job->budget < 100) {
            $score -= 15;
            $redFlags[] = 'Low budget';
        }

        // Check hourly rate
        if ($job->hourly_min >= 25) {
            $score += 10;
        }

        // Check client rating
        if ($job->client_rating && $job->client_rating >= 4.5) {
            $score += 10;
        } elseif ($job->client_rating && $job->client_rating < 4.0) {
            $redFlags[] = "Low client rating ({$job->client_rating}/5)";
            $score -= 5;
        }

        // Check proposals count
        if ($job->proposals && $job->proposals > 50) {
            $redFlags[] = "High competition ({$job->proposals} proposals)";
            $score -= 5;
        }

        return new AIScoreDTO(
            score: $this->clampScore($score),
            reason: "Fallback analysis (Groq AI unavailable: {$errorReason})",
            technologies: $technologies,
            redFlags: $redFlags,
            recommendation: 'Review job details carefully before applying',
            estimatedPrice: $this->estimatePrice($job),
            estimatedHours: $this->estimateHours($job),
        );
    }

    /**
     * Clamp score between 0 and 100
     */
    protected function clampScore($score): float
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
     * Check if Groq service is available
     */
    public function isAvailable(): bool
    {
        try {
            if (empty($this->apiKey)) {
                return false;
            }

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->get("{$this->baseUrl}/models");

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Groq availability check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get available models
     */
    public function getModels(): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->get("{$this->baseUrl}/models");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }
        } catch (Exception $e) {
            Log::error('Failed to get Groq models', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get service status
     */
    public function getStatus(): array
    {
        return [
            'available' => $this->isAvailable(),
            'api_key_configured' => !empty($this->apiKey),
            'base_url' => $this->baseUrl,
            'current_model' => $this->model,
            'has_api_key' => !empty($this->apiKey) ? 'Yes (' . substr($this->apiKey, 0, 8) . '...)' : 'No',
        ];
    }

    /**
     * Get current model
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Generate a job proposal
     */
    public function generateProposal(array $jobData): string
    {
        if (empty($this->apiKey)) {
            throw new Exception('Groq API key is not configured');
        }

        try {
            $prompt = $this->buildProposalPrompt($jobData);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are an expert Upwork proposal writer. Write personalized, professional proposals that convert. No emojis, no fluff, just value.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 800,
                ]);

            if (!$response->successful()) {
                throw new Exception('Groq API error: ' . $this->parseError($response));
            }

            $result = $response->json();
            return $result['choices'][0]['message']['content'] ?? 'Failed to generate proposal';

        } catch (Exception $e) {
            Log::error('Proposal generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Build proposal generation prompt
     */
    protected function buildProposalPrompt(array $job): string
    {
        $skills = is_array($job['skills'] ?? null) ? implode(', ', $job['skills']) : ($job['skills'] ?? 'Not specified');

        $prompt = "You are an expert Upwork proposal writer with a high success rate.\n\n";
        $prompt .= "Your goal is NOT to write a generic proposal. Your goal is to convince the client that I fully understand their problem and that hiring me is the safest and lowest-risk decision.\n\n";
        $prompt .= "Read the client's job description carefully and create a personalized proposal following these rules:\n\n";
        $prompt .= "1. Maximum 8-10 short lines.\n";
        $prompt .= "2. Never use generic phrases like:\n";
        $prompt .= "   - \"I am excited to apply.\"\n";
        $prompt .= "   - \"I believe I am the best candidate.\"\n";
        $prompt .= "   - \"I have read your job posting.\"\n";
        $prompt .= "   - \"I can do this job.\"\n";
        $prompt .= "3. Start by showing you understand the client's exact problem in 1-2 sentences.\n";
        $prompt .= "4. Mention the likely cause or technical challenge when possible. This demonstrates expertise without overwhelming the client.\n";
        $prompt .= "5. Briefly explain how you would solve the problem or your approach.\n";
        $prompt .= "6. Ask ONE intelligent technical question that naturally follows from the job description.\n";
        $prompt .= "7. Mention ONLY the skills that are directly relevant to this specific job.\n";
        $prompt .= "8. Mention:\n";
        $prompt .= "   - 15+ years of web development experience.\n";
        $prompt .= "   - Relevant experience only if it matches the project.\n";
        $prompt .= "9. Include my portfolio: https://habib-ahmad.netlify.app/portfolio\n";
        $prompt .= "10. End with a confident but professional CTA.\n\n";
        $prompt .= "Writing style:\n";
        $prompt .= "- Natural and conversational.\n";
        $prompt .= "- Confident without sounding arrogant.\n";
        $prompt .= "- Avoid buzzwords and unnecessary adjectives.\n";
        $prompt .= "- Every sentence should provide value.\n";
        $prompt .= "- Don't repeat information.\n";
        $prompt .= "- Don't make unrealistic promises.\n";
        $prompt .= "- Don't use emojis.\n";
        $prompt .= "- Sound like an experienced senior developer, not a salesperson.\n\n";
        $prompt .= "Whenever appropriate, subtly reduce the client's perceived risk by mentioning things like:\n";
        $prompt .= "- identifying root causes instead of applying temporary fixes,\n";
        $prompt .= "- writing maintainable code,\n";
        $prompt .= "- considering scalability,\n";
        $prompt .= "- minimizing downtime,\n";
        $prompt .= "- avoiding regressions,\n";
        $prompt .= "- keeping communication clear throughout the project.\n\n";
        $prompt .= "Use this signature:\n\n";
        $prompt .= "Best Regards,\n";
        $prompt .= "Habib Ahmad\n\n";
        $prompt .= "---\n\n";
        $prompt .= "Job Details:\n";
        $prompt .= "Title: " . ($job['title'] ?? 'Unknown') . "\n";
        $prompt .= "Description: " . substr(strip_tags($job['description'] ?? ''), 0, 2000) . "\n";
        $prompt .= "Budget: " . ($job['budget'] ?? 'Not specified') . "\n";
        $prompt .= "Skills: " . $skills . "\n\n";
        $prompt .= "Write a personalized proposal for this job:";

        return $prompt;
    }

    /**
     * Batch evaluate jobs (interface implementation)
     */
    public function batchEvaluate(array $jobs): array
    {
        $results = [];
        foreach ($jobs as $job) {
            $results[] = $this->evaluate($job);
        }
        return $results;
    }
}
