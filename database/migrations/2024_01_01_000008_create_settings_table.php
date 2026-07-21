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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Setting key');

            $table->text('value')->nullable()->comment('Setting value');

            // Type for casting
            $table->enum('type', ['string', 'number', 'boolean', 'json', 'encrypted', 'text'])
                  ->default('string')
                  ->comment('Value data type');

            $table->enum('category', ['crawler', 'ai', 'notification', 'filter', 'system'])
                  ->default('system')
                  ->comment('Setting category');

            $table->string('description', 500)->nullable()->comment('Setting description');
            $table->timestamps();

            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
