<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SkillsProfileSeeder extends Seeder
{
    /**
     * User skills profile for AI matching.
     */
    protected array $skills = [
        // Backend
        'Laravel',
        'PHP',
        'WordPress',
        'WooCommerce',
        'REST API',
        'GraphQL',
        'MySQL',
        'Linux',

        // Frontend
        'React',
        'Vue',
        'JavaScript',

        // AI & Automation
        'OpenAI',
        'Claude',
        'AI Agents',
        'Make.com',
        'n8n',
        'MCP',
        'Automation',

        // DevOps/Infrastructure
        'AWS',
        'Git',
        'Docker',

        // Integrations
        'Stripe',
        'PayPal',
        'Twilio',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create skills profile JSON file
        $skillsPath = storage_path('app/skills_profile.json');
        $directory = dirname($skillsPath);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($skillsPath, json_encode([
            'skills' => $this->skills,
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        $this->command->info('✅ Skills profile created successfully.');
        $this->command->info('   Total skills: ' . count($this->skills));
        $this->command->info('   File: ' . $skillsPath);
    }
}
