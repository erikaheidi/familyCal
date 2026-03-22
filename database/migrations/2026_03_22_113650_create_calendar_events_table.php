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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('google_calendar_id')->constrained()->cascadeOnDelete();
            $table->string('google_event_id');
            $table->string('status')->nullable();
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_all_day')->default(false);
            $table->string('html_link')->nullable();
            $table->json('recurrence')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('google_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['google_calendar_id', 'google_event_id']);
            $table->index(['google_calendar_id', 'start_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
