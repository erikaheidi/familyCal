<?php

namespace Database\Factories;

use App\Models\GoogleCalendar;
use App\Models\GoogleAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoogleCalendar>
 */
class GoogleCalendarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_account_id' => GoogleAccount::factory(),
            'google_calendar_id' => fake()->uuid(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'timezone' => fake()->timezone(),
            'access_role' => fake()->randomElement(['owner', 'writer', 'reader']),
            'background_color' => fake()->optional()->hexColor(),
            'foreground_color' => fake()->optional()->hexColor(),
            'is_primary' => false,
            'is_selected' => true,
        ];
    }
}
