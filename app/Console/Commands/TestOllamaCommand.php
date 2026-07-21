<?php

namespace App\Console\Commands;

use App\Services\AI\OllamaAIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestOllamaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ollama';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Ollama AI integration';

    /**
     * Execute the console command.
     */
    public function handle(OllamaAIService $ollama): int
    {
        $this->info('Testing Ollama AI Integration...');

        // Check if Ollama is available
        $this->warn("\n1. Checking Ollama service...");
        $status = $ollama->getStatus();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Available', $status['available'] ? '✅ Yes' : '❌ No'],
                ['Host', $status['host']],
                ['Current Model', $status['current_model']],
                ['Loaded Models', implode(', ', $status['loaded_models'])],
            ]
        );

        if (!$status['available']) {
            $this->error("\n❌ Ollama is not available. Please make sure the service is running.");
            $this->warn("\nTo start Ollama:");
            $this->line('  - Windows: Run the Ollama app or execute: ollama serve');
            $this->line('  - Check status: ollama list');
            return Command::FAILURE;
        }

        // Test API connectivity
        $this->warn("\n2. Testing API connectivity...");
        try {
            $host = config('ollama.host', 'http://localhost:11434');
            $response = Http::timeout(10)->get("{$host}/api/version");
            if ($response->successful()) {
                $version = $response->json('version', 'unknown');
                $this->info("✅ Ollama API v{$version} is accessible");
            }
        } catch (\Exception $e) {
            $this->error("❌ Cannot connect to Ollama API: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Test generation
        $this->warn("\n3. Testing AI generation...");
        try {
            $host = config('ollama.host', 'http://localhost:11434');
            $model = config('ollama.model', 'llama3.2');

            $this->line("Using model: {$model}");
            $this->line("Generating test response...");

            $response = Http::timeout(120)
                ->acceptJson()
                ->post("{$host}/api/generate", [
                    'model' => $model,
                    'prompt' => 'What is Laravel? Answer in one short sentence.',
                    'stream' => false,
                ]);

            if (!$response->successful()) {
                $this->error("❌ API request failed: " . $response->body());
                return Command::FAILURE;
            }

            $result = $response->json();
            $answer = $result['response'] ?? 'No response';

            $this->info("\n✅ AI Response:");
            $this->line("  " . trim($answer));

            $this->newLine();
            $this->info("✅ Ollama integration is working correctly!");
            $this->warn("\nYou can now use AI_PROVIDER=ollama in your .env file.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Generation failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
