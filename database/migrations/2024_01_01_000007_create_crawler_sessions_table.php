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
        Schema::create('crawler_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->unique()->comment('Unique session identifier');

            // Timing
            $table->timestamp('started_at')->comment('Session start time');
            $table->timestamp('ended_at')->nullable()->comment('Session end time');
            $table->timestamp('last_activity')->nullable()->comment('Last activity timestamp');

            // Status
            $table->enum('status', ['running', 'completed', 'failed', 'stopped'])
                  ->default('running')
                  ->comment('Session status');

            // Recovery
            $table->integer('recovery_count')->default(0)->comment('Number of recovery attempts');
            $table->timestamp('last_recovery_at')->nullable()->comment('Last recovery timestamp');

            $table->timestamps();

            $table->index('session_id');
            $table->index('status');
            $table->index('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawler_sessions');
    }
};
