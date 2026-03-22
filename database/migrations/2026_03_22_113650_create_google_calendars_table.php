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
        Schema::create('google_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('google_account_id')->constrained()->cascadeOnDelete();
            $table->string('google_calendar_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('timezone')->nullable();
            $table->string('access_role')->nullable();
            $table->string('background_color')->nullable();
            $table->string('foreground_color')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_selected')->default(false);
            $table->timestamps();

            $table->unique(['google_account_id', 'google_calendar_id']);
            $table->index(['google_account_id', 'is_selected']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_calendars');
    }
};
