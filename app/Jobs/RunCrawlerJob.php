<?php

namespace App\Jobs;

use App\Models\CrawlerSession;
use App\Services\LoggingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Exception;

class RunCrawlerJob implements ShouldQueue
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
    public $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ?string $sessionId = null
    ) {
        $this->onQueue('crawler');
    }

    /**
     * Execute the job.
     */
    public function handle(LoggingService $logger): void
    {
        $session = $this->getSession($logger);

        try {
            $logger->crawlerStarted($session->session_id);

            $startTime = microtime(true);

            // Run the Node.js crawler
            $result = $this->runCrawler($session->session_id);

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            // Parse the output to get job count
            $jobsFound = $this->parseJobsFound($result->output());

            $logger->crawlerFinished($session->session_id, $jobsFound, $duration);

            // Queue the import job if we have results
            if ($jobsFound > 0) {
                dispatch(new ImportJobsJob($session->session_id));
            }

            $session->complete($duration);

        } catch (Exception $e) {
            $session->fail($e->getMessage());
            $logger->crawlerError($session->session_id, $e);
            Log::error('Crawler job failed', [
                'session_id' => $session->session_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get or create crawler session.
     */
    protected function getSession(LoggingService $logger): CrawlerSession
    {
        if ($this->sessionId) {
            return CrawlerSession::where('session_id', $this->sessionId)->firstOrFail();
        }

        return CrawlerSession::start();
    }

    /**
     * Run the Node.js crawler using Process.
     */
    protected function runCrawler(string $sessionId)
    {
        $nodeBinary = config('crawler.node_binary', 'node');
        $crawlerPath = config('crawler.crawler_path');
        $storageJson = config('crawler.storage_json');
        $timeout = config('crawler.timeout', 120);

        if (!file_exists($crawlerPath)) {
            throw new Exception("Crawler file not found: {$crawlerPath}");
        }

        $process = Process::timeout($timeout)
            ->env([
                'SESSION_ID' => $sessionId,
                'STORAGE_PATH' => dirname($storageJson),
            ])
            ->run("{$nodeBinary} {$crawlerPath}");

        if (!$process->successful()) {
            throw new Exception("Crawler failed: " . $process->errorOutput());
        }

        return $process;
    }

    /**
     * Parse the number of jobs found from crawler output.
     */
    protected function parseJobsFound(string $output): int
    {
        // Look for patterns like "Found X jobs" or parse JSON output
        if (preg_match('/Found (\d+) jobs/i', $output, $matches)) {
            return (int) $matches[1];
        }

        // Try to parse as JSON
        $data = json_decode($output, true);
        if (isset($data['jobs_found'])) {
            return (int) $data['jobs_found'];
        }

        return 0;
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Crawler job failed permanently', [
            'session_id' => $this->sessionId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
