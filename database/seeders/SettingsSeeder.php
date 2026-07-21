<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crawler Settings
        $this->createSetting('crawler.enabled', 'true', 'boolean', 'crawler', 'Enable or disable crawler');
        $this->createSetting('crawler.interval', '30', 'number', 'crawler', 'Seconds between scans');
        $this->createSetting('crawler.max_jobs', '50', 'number', 'crawler', 'Maximum jobs to scrape per run');
        $this->createSetting('crawler.timeout', '120', 'number', 'crawler', 'Crawler timeout in seconds');

        // AI Settings
        $this->createSetting('ai.provider', 'mock', 'string', 'ai', 'AI provider (mock, openai, groq)');
        $this->createSetting('ai.model', 'gpt-4o-mini', 'string', 'ai', 'AI model to use');
        $this->createSetting('ai.threshold', '80', 'number', 'ai', 'Minimum score to notify (0-100)');
        $this->createSetting('ai.openai_key', '', 'encrypted', 'ai', 'OpenAI API key');
        $this->createSetting('ai.groq_key', '', 'encrypted', 'ai', 'Groq API key');

        // Notification Settings
        $this->createSetting('notification.enabled', 'true', 'boolean', 'notification', 'Enable notifications');
        $this->createSetting('notification.method', 'email', 'string', 'notification', 'Notification method (email, whatsapp, both)');
        $this->createSetting('notification.email_address', env('ADMIN_NOTIFICATION_EMAIL', 'habibahmed001@gmail.com'), 'string', 'notification', 'Email address for notifications');
        $this->createSetting('notification.phone_number', '+923228594463', 'string', 'notification', 'WhatsApp phone number for notifications');
        $this->createSetting('notification.phone_id', '', 'encrypted', 'notification', 'WhatsApp Phone ID from Meta');
        $this->createSetting('notification.access_token', '', 'encrypted', 'notification', 'WhatsApp Access Token');
        $this->createSetting('notification.rate_limit', '10', 'number', 'notification', 'Max notifications per minute');

        // Filter Settings
        $this->createSetting('filter.budget_min', '0', 'number', 'filter', 'Minimum budget (0 = disabled)');
        $this->createSetting('filter.hourly_min', '0', 'number', 'filter', 'Minimum hourly rate (0 = disabled)');
        $this->createSetting('filter.ignored_skills', json_encode([]), 'json', 'filter', 'Skills to ignore');
        $this->createSetting('filter.ignored_countries', json_encode([]), 'json', 'filter', 'Client countries to ignore');
        $this->createSetting('filter.require_payment_verified', 'false', 'boolean', 'filter', 'Require payment verification');
        $this->createSetting('filter.min_hire_rate', '0', 'number', 'filter', 'Minimum client hire rate % (0 = disabled)');

        // System Settings
        $this->createSetting('system.log_retention_days', '1', 'number', 'system', 'Days to keep logs');
        $this->createSetting('system.job_retention_hours', '2', 'number', 'system', 'Hours to keep jobs');
        $this->createSetting('system.cleanup_interval', '10', 'number', 'system', 'Cleanup job interval in minutes');
        $this->createSetting('system.max_concurrent_runs', '1', 'number', 'system', 'Max concurrent crawler runs');

        $this->command->info('✅ Default settings created successfully.');
    }

    /**
     * Create or update a setting.
     */
    protected function createSetting(string $key, string $value, string $type, string $category, string $description): void
    {
        $settingValue = $value;

        // Encrypt if type is encrypted
        if ($type === 'encrypted' && !empty($value)) {
            $settingValue = Crypt::encrypt($value);
        }

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $settingValue,
                'type' => $type,
                'category' => $category,
                'description' => $description,
            ]
        );
    }
}
