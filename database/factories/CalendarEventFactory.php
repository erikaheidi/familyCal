<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('-1 week', '+1 week');
        $endAt = (clone $startAt)->modify('+1 hour');

        return [
            'google_calendar_id' => GoogleCalendar::factory(),
            'google_event_id' => fake()->uuid(),
            'status' => fake()->randomElement(['confirmed', 'tentative', 'cancelled']),
            'summary' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'location' => fake()->optional()->address(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'is_all_day' => false,
            'html_link' => fake()->optional()->url(),
            'recurrence' => null,
            'payload' => null,
            'google_updated_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
