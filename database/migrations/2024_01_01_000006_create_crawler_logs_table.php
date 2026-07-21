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
        Schema::create('crawler_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->nullable()->comment('Crawler session identifier');

            // Results
            $table->integer('jobs_found')->default(0)->comment('Total jobs found');
            $table->integer('jobs_new')->default(0)->comment('New jobs discovered');
            $table->integer('jobs_duplicate')->default(0)->comment('Duplicate jobs skipped');

            // Status
            $table->enum('status', ['running', 'success', 'failure', 'partial'])
                  ->default('running')
                  ->comment('Crawler status');
            $table->text('error_message')->nullable()->comment('Error details if failed');

            // Performance
            $table->integer('duration_ms')->nullable()->comment('Execution time in milliseconds');
            $table->decimal('memory_mb', 8, 2)->nullable()->comment('Memory usage in MB');

            $table->timestamps();

            $table->index('session_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawler_logs');
    }
};
