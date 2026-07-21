<?php

namespace App\Jobs;

use App\Models\Job;
use App\Models\CrawlerSession;
use App\Services\LoggingService;
use App\Services\Parser\JobParserService;
use App\Services\DuplicateCheckerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ImportJobsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts.
     */
    public $tries = 3;

    /**
     * Job timeout in seconds.
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $sessionId
    ) {
        $this->onQueue('import');
    }

    /**
     * Execute the job.
     */
    public function handle(
        LoggingService $logger,
        JobParserService $parser,
        DuplicateCheckerService $duplicateChecker
    ): void {
        $session = CrawlerSession::where('session_id', $this->sessionId)->firstOrFail();

        // Read the crawler output JSON
        $jobsData = $this->readCrawlerOutput();

        if (empty($jobsData)) {
            $logger->info('No jobs to import', ['session_id' => $this->sessionId], 'crawler');
            return;
        }

        $newCount = 0;
        $duplicateCount = 0;
        $errorCount = 0;

        foreach ($jobsData as $rawJob) {
            try {
                // Parse the raw job data
                $jobDTO = $parser->parse($rawJob);

                // Check for duplicates
                if ($duplicateChecker->isDuplicate($jobDTO)) {
                    $duplicateCount++;
                    continue;
                }

                // Create the job
                $job = $this->createJob($jobDTO);
                $newCount++;

                // Queue AI evaluation
                dispatch(new EvaluateJobsJob($job->id));

            } catch (Exception $e) {
                $errorCount++;
                $logger->log('warning', "Failed to import job: {$e->getMessage()}", [
                    'session_id' => $this->sessionId,
                    'job_data' => $rawJob,
                ], 'crawler');
            }
        }

        $logger->log('info', "Import completed: {$newCount} new, {$duplicateCount} duplicates, {$errorCount} errors", [
            'session_id' => $this->sessionId,
            'new' => $newCount,
            'duplicates' => $duplicateCount,
            'errors' => $errorCount,
        ], 'crawler');

        Log::info('Import job completed', [
            'session_id' => $this->sessionId,
            'new' => $newCount,
            'duplicates' => $duplicateCount,
            'errors' => $errorCount,
        ]);
    }

    /**
     * Read the crawler output JSON.
     */
    protected function readCrawlerOutput(): array
    {
        $storageJson = config('crawler.storage_json');

        if (!file_exists($storageJson)) {
            throw new Exception("Crawler output not found: {$storageJson}");
        }

        $content = file_get_contents($storageJson);
        $data = json_decode($content, true);

        if (!isset($data['jobs']) || !is_array($data['jobs'])) {
            return [];
        }

        return $data['jobs'];
    }

    /**
     * Create a job from DTO.
     */
    protected function createJob($jobDTO): Job
    {
        $job = Job::create([
            'job_id' => $jobDTO->jobId,
            'fingerprint' => $jobDTO->fingerprint(),
            'title' => $jobDTO->title,
            'description' => $jobDTO->description,
            'budget' => $jobDTO->budget,
            'hourly_min' => $jobDTO->hourlyMin,
            'hourly_max' => $jobDTO->hourlyMax,
            'client_country' => $jobDTO->clientCountry,
            'payment_verified' => $jobDTO->paymentVerified,
            'spent' => $jobDTO->spent,
            'hire_rate' => $jobDTO->hireRate,
            'client_rating' => $jobDTO->clientRating,
            'proposals' => $jobDTO->proposals,
            'experience_level' => $jobDTO->experienceLevel,
            'project_length' => $jobDTO->projectLength,
            'time_posted' => $jobDTO->timePosted,
            'url' => $jobDTO->url,
            'status' => 'new',
            'job_posted_at' => $jobDTO->timePosted ? now() : null,
        ]);

        // Attach skills
        if (!empty($jobDTO->skills)) {
            $job->attachSkills($jobDTO->skills);
        }

        return $job;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Import job failed', [
            'session_id' => $this->sessionId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
