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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();

            // Unique identifiers
            $table->string('job_id')->unique()->nullable()->comment('Upwork job ID for deduplication');
            $table->string('fingerprint', 32)->nullable()->comment('MD5 fallback for duplicate detection');

            // Job details
            $table->string('title');
            $table->text('description');

            // Budget
            $table->decimal('budget', 10, 2)->nullable()->comment('Fixed price budget');
            $table->decimal('hourly_min', 10, 2)->nullable()->comment('Hourly rate minimum');
            $table->decimal('hourly_max', 10, 2)->nullable()->comment('Hourly rate maximum');

            // Client info
            $table->string('client_country', 100)->nullable();
            $table->boolean('payment_verified')->default(false)->comment('Payment verification status');
            $table->decimal('spent', 12, 2)->nullable()->comment('Total spent on Upwork');
            $table->string('hire_rate', 20)->nullable()->comment('Client hire rate percentage');
            $table->decimal('client_rating', 3, 2)->nullable()->comment('Client average rating');

            // Job requirements
            $table->integer('proposals')->nullable()->comment('Number of proposals');
            $table->string('experience_level', 50)->nullable()->comment('Entry/Intermediate/Expert');
            $table->string('project_length', 100)->nullable()->comment('Project duration');
            $table->string('time_posted', 100)->nullable()->comment('Time since posting');

            // URL
            $table->string('url')->nullable()->comment('Upwork job URL');

            // Status
            $table->enum('status', ['new', 'scoring', 'scored', 'notified', 'skipped', 'archived'])
                  ->default('new')
                  ->comment('Job processing status');

            // Timestamps
            $table->timestamp('job_posted_at')->nullable()->comment('When job was posted on Upwork');
            $table->timestamp('notified_at')->nullable()->comment('When notification was sent');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('job_id');
            $table->index('fingerprint');
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
        Schema::dropIfExists('jobs');
    }
};
