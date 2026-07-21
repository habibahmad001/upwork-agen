<?php

namespace App\Console\Commands;

use App\Services\AI\GroqAIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestGroqCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:groq';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Groq AI integration';

    /**
     * Execute the console command.
     */
    public function handle(GroqAIService $groq): int
    {
        $this->info('Testing Groq AI Integration...');
        $this->newLine();

        // Check if Groq is available
        $this->warn('1. Checking Groq service availability...');
        $status = $groq->getStatus();

        $this->table(
            ['Setting', 'Value'],
            [
                ['API Key Configured', $status['api_key_configured'] ? '✅ Yes' : '❌ No'],
                ['API Key', $status['has_api_key'] ?? 'Not configured'],
                ['Available', $status['available'] ? '✅ Yes' : '❌ No'],
                ['Base URL', $status['base_url']],
                ['Current Model', $status['current_model']],
            ]
        );

        if (!$status['available']) {
            $this->newLine();
            $this->error('❌ Groq service is not available!');
            $this->warn('Please check:');
            $this->line('  1. Your GROQ_API_KEY is set in .env');
            $this->line('  2. The API key is valid');
            $this->line('  3. You have internet connection');
            $this->newLine();
            $this->line('Get a free API key at: https://console.groq.com/keys');
            return Command::FAILURE;
        }

        $this->newLine();

        // Show available models
        $this->warn('2. Fetching available models...');
        try {
            $models = $groq->getModels();

            if (!empty($models)) {
                $modelRows = [];
                foreach ($models as $model) {
                    $modelRows[] = [
                        $model['id'] ?? 'Unknown',
                        $model['owned_by'] ?? 'Groq',
                    ];
                }

                $this->table(
                    ['Model ID', 'Owner'],
                    array_slice($modelRows, 0, 10)
                );

                if (count($modelRows) > 10) {
                    $this->line('... and ' . (count($modelRows) - 10) . ' more models');
                }
            } else {
                $this->warn('No models found');
            }
        } catch (\Exception $e) {
            $this->warn('Could not fetch models: ' . $e->getMessage());
        }

        $this->newLine();

        // Test AI evaluation
        $this->warn('3. Testing AI evaluation with a sample job...');

        try {
            // Try to get a real job from database, or create a mock one
            $testJob = \App\Models\Job::first();

            if (!$testJob) {
                $this->line('No jobs found in database. Creating a test job...');
                $testJob = new \App\Models\Job();
                $testJob->id = 999;
                $testJob->title = 'Senior Laravel Developer Needed';
                $testJob->description = 'We are looking for an experienced Laravel developer to build a REST API for our e-commerce platform. The ideal candidate should have experience with Laravel, MySQL, Redis, and AWS. This is a 3-month project with a budget of $3000-5000.';
                $testJob->budget = 4000;
                $testJob->payment_verified = true;
                $testJob->client_country = 'United States';
                $testJob->client_rating = 4.8;
                $testJob->proposals = 12;
                $testJob->experience_level = 'Expert';
                $testJob->project_length = '3+ months';
                $testJob->time_posted = '2 hours ago';
                $testJob->url = 'https://www.upwork.com/job/test-job';

                // Create mock skills
                $testJob->setRelation('skills', collect([
                    new \App\Models\JobSkill(['skill' => 'Laravel']),
                    new \App\Models\JobSkill(['skill' => 'PHP']),
                    new \App\Models\JobSkill(['skill' => 'MySQL']),
                    new \App\Models\JobSkill(['skill' => 'REST API']),
                    new \App\Models\JobSkill(['skill' => 'AWS']),
                ]));
            }

            $this->line('Job: ' . $testJob->title);
            $this->line('Evaluating with Groq AI...');

            $result = $groq->evaluate($testJob);

            $this->newLine();
            $this->info('✅ AI Evaluation Result:');
            $this->newLine();

            $this->table(
                ['Field', 'Value'],
                [
                    ['Score', $result->score . '/100'],
                    ['Category', $result->getCategory()],
                    ['Reasoning', wordwrap($result->reason, 60)],
                    ['Matched Technologies', implode(', ', $result->technologies ?: ['None'])],
                    ['Red Flags', implode(', ', $result->redFlags ?: ['None'])],
                    ['Recommendation', $result->recommendation ?? 'N/A'],
                    ['Estimated Hours', $result->estimatedHours],
                    ['Estimated Price', $result->estimatedPrice],
                ]
            );

            $this->newLine();
            $this->info('✅ Groq AI integration is working correctly!');
            $this->newLine();
            $this->warn('You can now use Groq AI for job evaluation.');
            $this->line('AI_PROVIDER is set to: groq');
            $this->newLine();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Evaluation failed: ' . $e->getMessage());
            $this->newLine();
            $this->warn('For debugging, check:');
            $this->line('  - GROQ_API_KEY in .env is correct');
            $this->line('  - Run: php artisan config:clear');
            $this->line('  - Check Laravel logs: storage/logs/laravel.log');
            return Command::FAILURE;
        }
    }
}
