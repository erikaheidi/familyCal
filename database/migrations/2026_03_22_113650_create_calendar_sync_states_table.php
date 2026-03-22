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
        Schema::create('calendar_sync_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('google_calendar_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('sync_token')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_full_sync_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_sync_states');
    }
};
