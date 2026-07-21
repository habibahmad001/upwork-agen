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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();

            // Log entry
            $table->enum('type', ['info', 'warning', 'error', 'debug'])
                  ->default('info')
                  ->comment('Log type');
            $table->text('message')->comment('Log message');
            $table->json('context')->nullable()->comment('Additional context data');
            $table->string('source', 100)->default('system')->comment('Log source/component');

            $table->timestamp('created_at')->useCurrent()->comment('Log timestamp');

            $table->index('type');
            $table->index('source');
            $table->index('created_at');
            $table->index(['created_at', 'type'])->comment('Composite index for cleanup queries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
