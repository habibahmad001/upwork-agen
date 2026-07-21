<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('ai_score_id')->nullable()->constrained('job_ai_scores')->onDelete('set null');

            // Message details
            $table->string('phone_number', 20)->comment('WhatsApp phone number');
            $table->text('message_content')->comment('Full message content');
            $table->string('whatsapp_message_id', 100)->nullable()->comment('WhatsApp Cloud API message ID');

            // Status
            $table->enum('status', ['pending', 'processing', 'sent', 'failed'])
                  ->default('pending')
                  ->comment('Notification status');
            $table->text('error_message')->nullable()->comment('Error details if failed');
            $table->integer('retry_count')->default(0)->comment('Number of retry attempts');
            $table->timestamp('last_retry_at')->nullable()->comment('Last retry timestamp');

            // Timestamps
            $table->timestamp('sent_at')->nullable()->comment('When successfully sent');
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
            $table->index(['status', 'created_at'])->comment('Composite index for cleanup queries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
