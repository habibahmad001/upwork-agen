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
        Schema::table('notifications', function (Blueprint $table) {
            // Add method column
            $table->enum('method', ['email', 'whatsapp', 'both'])
                  ->default('email')
                  ->after('ai_score_id')
                  ->comment('Notification method used');

            // Add destination column (more flexible than phone_number)
            $table->string('destination', 255)->nullable()
                  ->after('method')
                  ->comment('Email address or phone number');

            // Make phone_number nullable (for backward compatibility)
            $table->string('phone_number', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['method', 'destination']);
            $table->string('phone_number', 20)->nullable(false)->change();
        });
    }
};
