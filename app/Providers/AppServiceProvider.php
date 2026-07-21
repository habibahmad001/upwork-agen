<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SettingsService;
use App\Services\LoggingService;
use App\Services\AI\MockAIService;
use App\Services\AI\OllamaAIService;
use App\Services\AI\GroqAIService;
use App\Contracts\AIEvaluationServiceInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Settings Service
        $this->app->singleton(SettingsService::class);
        $this->app->alias(SettingsService::class, 'settings');

        // Logging Service
        $this->app->singleton(LoggingService::class);
        $this->app->alias(LoggingService::class, 'logs');

        // AI Evaluation Service - bind based on config
        $this->app->bind(AIEvaluationServiceInterface::class, function ($app) {
            $provider = config('ai.provider', 'mock');

            return match ($provider) {
                'mock' => new MockAIService($app->make(SettingsService::class)),
                'ollama' => new OllamaAIService(),
                'groq' => new GroqAIService(),
                // OpenAI implementation will be added later
                default => new MockAIService($app->make(SettingsService::class)),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure strict mode for relationships
        \Illuminate\Database\Eloquent\Model::shouldBeStrict(!app()->isProduction());
    }
}
