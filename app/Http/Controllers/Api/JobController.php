<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobAiScore;
use App\Models\Notification;
use App\Jobs\EvaluateJobsJob;
use App\Jobs\SendNotificationJob;
use App\Services\LoggingService;
use App\Services\Email\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    public function __construct(
        protected LoggingService $logger
    ) {}

    /**
     * Store a new job from crawler (public endpoint)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate incoming job data
            $validator = Validator::make($request->all(), [
                'job_id' => 'required|string|unique:jobs,job_id',
                'title' => 'required|string|max:500',
                'description' => 'nullable|string',
                'url' => 'required|url',
                'budget' => 'nullable|string',
                'hourly_rate' => 'nullable|string',
                'skills' => 'nullable|array',
                'skills.*' => 'string|max:100',
                'client_country' => 'nullable|string|max:100',
                'payment_verified' => 'nullable|boolean',
                'spent' => 'nullable|numeric',
                'hire_rate' => 'nullable|string|max:50',
                'client_rating' => 'nullable|numeric|max:5',
                'proposals' => 'nullable|integer',
                'experience_level' => 'nullable|string|max:50',
                'project_length' => 'nullable|string|max:100',
                'time_posted' => 'nullable|string|max:100',
                'job_type' => 'nullable|string|max:50',
                // AI fields (optional - can be provided by crawler)
                'ai_score' => 'nullable|integer|min:0|max:100',
                'ai_recommendation' => 'nullable|string|max:50',
                'ai_reason' => 'nullable|string',
                'ai_confidence' => 'nullable|numeric',
                'fetched_at' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                $this->logger->warning('Job validation failed', [
                    'errors' => $validator->errors()->toArray(),
                    'job_id' => $request->input('job_id'),
                ], 'crawler');

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Log job received from crawler
            $this->logger->info('Job received from crawler', [
                'job_id' => $validated['job_id'],
                'title' => substr($validated['title'], 0, 100),
                'has_ai_score' => isset($validated['ai_score']),
            ], 'crawler');

            // Parse budget if present (handle strings like "$10", "$500", etc.)
            $budgetValue = null;
            if (!empty($validated['budget'])) {
                if (is_numeric($validated['budget'])) {
                    $budgetValue = (float) $validated['budget'];
                } elseif (preg_match('/\$?([\d,]+(?:\.\d+)?)/', $validated['budget'], $matches)) {
                    $budgetValue = (float) str_replace(',', '', $matches[1]);
                }
            }

            // Parse hourly rate if present
            $hourlyMin = null;
            $hourlyMax = null;
            if (!empty($validated['hourly_rate'])) {
                // Parse patterns like "$50-75/hr" or "$50+/hr"
                if (preg_match('/\$(\d+)\s*[-–]\s*\$(\d+)/', $validated['hourly_rate'], $matches)) {
                    $hourlyMin = (float) $matches[1];
                    $hourlyMax = (float) $matches[2];
                } elseif (preg_match('/\$(\d+)\+/', $validated['hourly_rate'], $matches)) {
                    $hourlyMin = (float) $matches[1];
                } elseif (preg_match('/\$(\d+)/', $validated['hourly_rate'], $matches)) {
                    $hourlyMin = (float) $matches[1];
                }
            }

            // Create or update job
            $job = Job::updateOrCreate(
                ['job_id' => $validated['job_id']],
                [
                    'fingerprint' => md5($validated['job_id'] . $validated['title']),
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? '',
                    'url' => $validated['url'],
                    'budget' => $budgetValue,
                    'hourly_min' => $hourlyMin,
                    'hourly_max' => $hourlyMax,
                    'client_country' => $validated['client_country'] ?? null,
                    'payment_verified' => $validated['payment_verified'] ?? false,
                    'spent' => $validated['spent'] ?? null,
                    'hire_rate' => $validated['hire_rate'] ?? null,
                    'client_rating' => $validated['client_rating'] ?? null,
                    'proposals' => $validated['proposals'] ?? null,
                    'experience_level' => $validated['experience_level'] ?? null,
                    'project_length' => $validated['project_length'] ?? null,
                    'time_posted' => $validated['time_posted'] ?? null,
                    'status' => 'new',
                    'job_posted_at' => isset($validated['fetched_at'])
                        ? now()->parse($validated['fetched_at'])
                        : now(),
                ]
            );

            // Attach skills
            if (!empty($validated['skills']) && is_array($validated['skills'])) {
                $job->attachSkills($validated['skills']);
            }

            // Store AI score if provided by crawler
            $aiScoreProvided = isset($validated['ai_score']) && $validated['ai_score'] !== null;
            $aiScoreId = null;

            if ($aiScoreProvided) {
                $aiScoreRecord = JobAiScore::updateOrCreate(
                    ['job_id' => $job->id],
                    [
                        'score' => $validated['ai_score'],
                        'reasoning' => $validated['ai_reason'] ?? 'AI analysis from crawler',
                        'technologies' => $validated['skills'] ?? [],
                        'red_flags' => [],
                        'estimated_hours' => null,
                        'estimated_price' => $validated['budget'] ?? $validated['hourly_rate'] ?? null,
                        'recommendation' => $validated['ai_recommendation'] ?? 'consider',
                        'model_version' => 'crawler-groq',
                        'threshold_used' => 80,
                    ]
                );
                $aiScoreId = $aiScoreRecord->id;

                // Update job status based on AI score
                if ($validated['ai_score'] >= 80) {
                    $job->update(['status' => 'scored']);

                    // Dispatch notification for high-scoring jobs
                    dispatch(new SendNotificationJob($job->id, $aiScoreId));

                    $this->logger->info('High-scoring job notification queued', [
                        'job_id' => $job->id,
                        'score' => $validated['ai_score'],
                    ], 'notification');
                } elseif ($validated['ai_score'] <= 30) {
                    $job->update(['status' => 'skipped']);
                } else {
                    $job->update(['status' => 'scored']);
                }

                // Log AI score received
                $this->logger->info('AI score stored from crawler', [
                    'job_id' => $job->id,
                    'score' => $validated['ai_score'],
                    'recommendation' => $validated['ai_recommendation'] ?? 'consider',
                ], 'ai');
            } else {
                // Queue AI evaluation if not provided
                dispatch(new EvaluateJobsJob($job->id));

                $this->logger->info('AI evaluation queued', [
                    'job_id' => $job->id,
                ], 'ai');
            }

            $response = [
                'success' => true,
                'message' => 'Job stored successfully',
                'job_id' => $job->id,
                'upwork_job_id' => $job->job_id,
                'ai_provided' => $aiScoreProvided,
                'ai_score_id' => $aiScoreId,
                'status' => $job->status
            ];

            $this->logger->info('Job stored successfully from crawler', [
                'job_id' => $job->id,
                'upwork_job_id' => $job->job_id,
                'status' => $job->status,
                'ai_provided' => $aiScoreProvided,
            ], 'crawler');

            return response()->json($response, 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to store job from crawler', [
                'error' => $e->getMessage(),
                'job_id' => $request->input('job_id'),
            ], 'crawler');

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all jobs (requires auth)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $jobs = Job::with('aiScore', 'skills')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    /**
     * Get a specific job (requires auth)
     *
     * @param Job $job
     * @return JsonResponse
     */
    public function show(Job $job): JsonResponse
    {
        $job->load('aiScore', 'skills', 'notifications');

        return response()->json([
            'success' => true,
            'data' => $job
        ]);
    }
}
