<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    /**
     * Get a setting value.
     */
    public function get(string $key, $default = null)
    {
        // Check environment first
        $envValue = $this->getFromEnv($key);
        if ($envValue !== null) {
            return $envValue;
        }

        // Check database
        return Setting::get($key, $default);
    }

    /**
     * Get boolean setting.
     */
    public function bool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    /**
     * Get integer setting.
     */
    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * Get float setting.
     */
    public function float(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, $default);
    }

    /**
     * Get array setting.
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key);
        return is_array($value) ? $value : $default;
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, $value, string $type = 'string', string $category = 'system', string $description = ''): Setting
    {
        return Setting::set($key, $value, $type, $category, $description);
    }

    /**
     * Get all settings.
     */
    public function all(): array
    {
        return Setting::allAsArray();
    }

    /**
     * Get settings by category.
     */
    public function category(string $category): array
    {
        return Setting::byCategory($category);
    }

    /**
     * Get crawler settings.
     */
    public function crawler(): array
    {
        return [
            'enabled' => $this->bool('crawler.enabled', true),
            'interval' => $this->int('crawler.interval', 30),
            'timeout' => $this->int('crawler.timeout', 120),
            'max_jobs' => $this->int('crawler.max_jobs', 50),
        ];
    }

    /**
     * Get AI settings.
     */
    public function ai(): array
    {
        return [
            'provider' => $this->get('ai.provider', 'mock'),
            'model' => $this->get('ai.model', 'gpt-4o-mini'),
            'threshold' => $this->float('ai.threshold', 80),
            'openai_key' => $this->get('ai.openai_key'),
            'groq_key' => $this->get('ai.groq_key'),
            'ollama_host' => $this->get('ollama.host', 'http://localhost:11434'),
            'ollama_model' => $this->get('ollama.model', 'qwen:0.5b'),
        ];
    }

    /**
     * Get notification settings.
     */
    public function notification(): array
    {
        return [
            'enabled' => $this->bool('notification.enabled', true),
            'method' => $this->get('notification.method', 'email'),
            'email_address' => $this->get('notification.email_address', config('mail.notification_recipient', config('mail.from.address'))),
            'phone_number' => $this->get('notification.phone_number', '+923228594463'),
            'phone_id' => $this->get('notification.phone_id'),
            'access_token' => $this->get('notification.access_token'),
            'rate_limit' => $this->int('notification.rate_limit', 10),
        ];
    }

    /**
     * Get filter settings.
     */
    public function filter(): array
    {
        return [
            'budget_min' => $this->float('filter.budget_min', 0),
            'hourly_min' => $this->float('filter.hourly_min', 0),
            'ignored_skills' => $this->array('filter.ignored_skills'),
            'ignored_countries' => $this->array('filter.ignored_countries'),
            'require_payment_verified' => $this->bool('filter.require_payment_verified', false),
            'min_hire_rate' => $this->float('filter.min_hire_rate', 0),
        ];
    }

    /**
     * Get system settings.
     */
    public function system(): array
    {
        return [
            'log_retention_days' => $this->int('system.log_retention_days', 1),
            'job_retention_hours' => $this->int('system.job_retention_hours', 2),
            'cleanup_interval' => $this->int('system.cleanup_interval', 10),
            'max_concurrent_runs' => $this->int('system.max_concurrent_runs', 1),
        ];
    }

    /**
     * Check if crawler is enabled.
     */
    public function isCrawlerEnabled(): bool
    {
        return $this->bool('crawler.enabled', true);
    }

    /**
     * Check if notifications are enabled.
     */
    public function areNotificationsEnabled(): bool
    {
        return $this->bool('notification.enabled', true);
    }

    /**
     * Get AI threshold.
     */
    public function getAiThreshold(): float
    {
        return $this->float('ai.threshold', 80);
    }

    /**
     * Get scan interval in seconds.
     */
    public function getScanInterval(): int
    {
        return $this->int('crawler.interval', 30);
    }

    /**
     * Get WhatsApp phone number.
     */
    public function getWhatsAppNumber(): string
    {
        return $this->get('notification.phone_number', '+923228594463');
    }

    /**
     * Get value from environment.
     */
    protected function getFromEnv(string $key): ?string
    {
        $envKey = $this->keyToEnv($key);

        if (!array_key_exists($envKey, $_ENV)) {
            return null;
        }

        $value = $_ENV[$envKey];

        if ($value === '' || $value === 'null') {
            return null;
        }

        return $value;
    }

    /**
     * Convert setting key to env key.
     */
    protected function keyToEnv(string $key): string
    {
        return strtoupper(str_replace('.', '_', $key));
    }
}
