<?php

namespace App\Console\Commands;

use App\Services\Email\EmailService;
use Illuminate\Console\Command;

class SendTestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email notification';

    /**
     * Execute the console command.
     */
    public function handle(EmailService $email): int
    {
        $this->info('Sending test email...');

        try {
            $result = $email->sendTest();

            $this->info('✅ Test email sent successfully!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Status', $result['success'] ? 'Success' : 'Failed'],
                    ['Recipient', $result['recipient'] ?? 'N/A'],
                    ['Subject', $result['subject'] ?? 'N/A'],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to send test email');
            $this->error('Error: ' . $e->getMessage());

            // Show configuration for debugging
            $this->warn('\n--- Email Configuration ---');
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Mailer', config('mail.mailer')],
                    ['Host', config('mail.host')],
                    ['Port', config('mail.port')],
                    ['Encryption', config('mail.encryption')],
                    ['From', config('mail.from.address')],
                    ['Recipient', config('mail.notification_recipient') ?? 'Not set'],
                ]
            );

            return Command::FAILURE;
        }
    }
}
