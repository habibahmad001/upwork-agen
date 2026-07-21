<?php

namespace App\Jobs;

use App\Models\Job;
use App\Models\JobAiScore;
use App\Contracts\AIEvaluationServiceInterface;
use App\Services\FilterService;
use App\Services\LoggingService;
use App\DTOs\AIScoreDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class EvaluateJobsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts.
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     */
    public $backoff = [30, 60, 120];

    /**
     * Job timeout in seconds.
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $jobId
    ) {
        $this->onQueue('scoring');
    }

    /**
     * Execute the job.
     */
    public function handle(
        AIEvaluationServiceInterface $ai,
        FilterService $filter,
        LoggingService $logger
    ): void {
        $job = Job::with('skills')->findOrFail($this->jobId);
        $job->update(['status' => 'scoring']);

        $startTime = microtime(true);

        try {
            $logger->aiRequest($job->id, strlen($job->description));

            // Get AI evaluation
            $score = $ai->evaluate($job);

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            // Store the AI score
            $aiScore = $this->createAiScore($job, $score);

            $logger->aiResponse($job->id, $score->score, $duration);

            // Check if we should send notification
            if ($filter->shouldNotify($score, $job)) {
                dispatch(new SendNotificationJob($job->id, $aiScore->id));
                $job->update(['status' => 'notified']);
            } else {
                $job->update(['status' => 'scored']);
            }

            Log::debug('Job scored successfully', [
                'job_id' => $job->id,
                'score' => $score->score,
                'duration_ms' => $duration,
            ]);

        } catch (Exception $e) {
            $job->update(['status' => 'skipped']);
            $logger->aiError($job->id, $e);
            Log::error('AI scoring failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create AI score record.
     */
    protected function createAiScore(Job $job, AIScoreDTO $score): JobAiScore
    {
        return $job->aiScores()->create([
            'score' => $score->score,
            'reasoning' => $score->reason,
            'technologies' => $score->technologies,
            'red_flags' => $score->redFlags,
            'estimated_hours' => $score->estimatedHours,
            'estimated_price' => $score->estimatedPrice,
            'recommendation' => $score->recommendation,
            'model_version' => $this->getModelVersion(),
            'threshold_used' => $this->getThreshold(),
        ]);
    }

    /**
     * Get the model version being used.
     */
    protected function getModelVersion(): string
    {
        return config('openai.provider', 'mock') . '-' .
               config('openai.providers.' . config('openai.provider', 'mock') . '.model', 'v1');
    }

    /**
     * Get the current threshold.
     */
    protected function getThreshold(): float
    {
        return (float) config('openai.threshold', 80.0);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        // Mark job as skipped if scoring fails
        $job = Job::find($this->jobId);
        if ($job) {
            $job->update(['status' => 'skipped']);
        }

        Log::error('AI scoring job failed permanently', [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
