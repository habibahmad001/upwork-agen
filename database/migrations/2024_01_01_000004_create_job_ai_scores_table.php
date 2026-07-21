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
        Schema::create('job_ai_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');

            // Score
            $table->decimal('score', 5, 2)->comment('AI score from 0-100');

            // AI response details
            $table->text('reasoning')->nullable()->comment('AI explanation of the score');
            $table->json('technologies')->nullable()->comment('Technologies matched from user profile');
            $table->json('red_flags')->nullable()->comment('Potential issues with the job');
            $table->string('estimated_hours', 50)->nullable()->comment('Estimated work hours');
            $table->string('estimated_price', 50)->nullable()->comment('Estimated fair price');
            $table->text('recommendation')->nullable()->comment('AI recommendation');

            // Metadata
            $table->string('model_version', 50)->default('gpt-4o-mini')->comment('AI model used');
            $table->decimal('threshold_used', 5, 2)->nullable()->comment('Score threshold applied');
            $table->timestamps();

            $table->index('job_id');
            $table->index('score');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_ai_scores');
    }
};
