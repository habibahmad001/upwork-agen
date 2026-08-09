<?php

namespace App\Console\Commands;

use App\Jobs\SendNotificationJob;
use App\Models\Job;
use App\Models\JobAiScore;
use App\Services\Pusher\PusherService;
use Illuminate\Console\Command;

class TestPusherCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:pusher {--test : Send simple test notification} {--full : Run full email+pusher test}';

    /**
     * The console command description.
     */
    protected $description = 'Test Pusher push notification service';

    /**
     * Execute the console command.
     */
    public function handle(PusherService $pusher): int
    {
        $this->info('📡 Pusher Service Test');
        $this->info('=====================');
        $this->newLine();

        // Check if Pusher is configured
        if (!$pusher->isAvailable()) {
            $this->error('❌ Pusher is not configured!');
            $this->newLine();
            $this->info('Please add the following to your .env file:');
            $this->warn('PUSHER_APP_ID=your-app-id');
            $this->warn('PUSHER_APP_KEY=your-app-key');
            $this->warn('PUSHER_APP_SECRET=your-app-secret');
            $this->warn('PUSHER_CLUSTER=your-cluster');
            return self::FAILURE;
        }

        $this->info('✅ Pusher is configured');
        $this->newLine();

        // Show configuration
        $status = $pusher->getQueueStatus();
        $this->table(
            ['Setting', 'Value'],
            [
                ['App ID', $status['app_id'] ?? 'Not set'],
                ['Channel', $status['channel']],
                ['Event', $status['event']],
                ['Cluster', $status['cluster']],
                ['Pusher Status', $status['enabled'] ? '✅ Enabled' : '❌ Disabled'],
                ['Email Status', config('mail.enabled', true) ? '✅ Enabled' : '❌ Disabled'],
            ]
        );

        $this->newLine();

        if ($this->option('test')) {
            return $this->runSimpleTest($pusher);
        }

        if ($this->option('full')) {
            return $this->runFullTest();
        }

        $this->comment('Options:');
        $this->comment('  --test  : Send simple test notification to Pusher');
        $this->comment('  --full  : Simulate full notification flow (Email + Pusher)');

        return self::SUCCESS;
    }

    /**
     * Run simple Pusher test.
     */
    protected function runSimpleTest(PusherService $pusher): int
    {
        $this->info('Sending test notification...');

        try {
            $result = $pusher->sendTest();

            $this->newLine();
            $this->info('✅ Test notification sent successfully!');
            $this->info("Channel: {$result['channel']}");
            $this->info("Event: {$result['event']}");
            $this->newLine();
            $this->comment('🔗 Check your dashboard: http://127.0.0.1:8000/pusher-test');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Test notification failed!');
            $this->error('Error: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Run full email + pusher test.
     */
    protected function runFullTest(): int
    {
        $this->info('Simulating full notification flow (Email + Pusher)...');
        $this->newLine();

        // Find or create test job
        $testJob = Job::first();
        if (!$testJob) {
            $this->warn('No jobs found. Creating test job...');

            $testJob = Job::create([
                'job_id' => 'test_' . time(),
                'title' => '🧪 Test Job - Full Stack Developer Needed',
                'description' => 'This is a test job to verify email and Pusher notifications.',
                'hourly_min' => 50,
                'hourly_max' => 100,
                'url' => 'https://www.upwork.com/jobs/test-' . time(),
                'client_country' => 'US',
                'payment_verified' => true,
                'client_rating' => 5.0,
                'proposals' => 5,
                'job_posted_at' => now(),
                'status' => 'scored',
            ]);
        }

        // Find or create test AI score
        $testScore = JobAiScore::where('job_id', $testJob->id)->first();
        if (!$testScore) {
            $testScore = JobAiScore::create([
                'job_id' => $testJob->id,
                'score' => 85.0,
                'recommendation' => 'Great match - Apply immediately!',
                'reasoning' => 'Test notification to verify the system is working.',
                'technologies' => ['PHP', 'Laravel', 'JavaScript'],
                'red_flags' => [],
            ]);
        }

        $this->info('Test Job:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Title', $testJob->title],
                ['Budget', $testJob->budget_range],
                ['AI Score', $testScore->score . '/100'],
            ]
        );

        $this->newLine();
        $this->info('⏳ Dispatching notification job...');

        // This will send both email AND pusher notification
        SendNotificationJob::dispatch($testJob->id, $testScore->id);

        $this->info('✅ Notification job dispatched!');
        $this->newLine();
        $this->info('What happens next:');
        $this->bullet('📧 Email sent to: ' . config('mail.notification_recipient', config('mail.from.address')));
        $this->bullet('🔔 Pusher notification sent to your dashboard');
        $this->newLine();
        $this->comment('💡 Keep dashboard open: http://127.0.0.1:8000/pusher-test');

        return self::SUCCESS;
    }
}
