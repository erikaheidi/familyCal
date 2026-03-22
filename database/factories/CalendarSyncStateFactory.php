<?php

namespace Database\Factories;

use App\Models\CalendarSyncState;
use App\Models\GoogleCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarSyncState>
 */
class CalendarSyncStateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_calendar_id' => GoogleCalendar::factory(),
            'sync_token' => fake()->sha1(),
            'last_synced_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'last_full_sync_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ];
    }
}
