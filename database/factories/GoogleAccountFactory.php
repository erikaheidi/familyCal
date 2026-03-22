<?php

namespace Database\Factories;

use App\Models\GoogleAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoogleAccount>
 */
class GoogleAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'google_account_id' => fake()->uuid(),
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'avatar_url' => fake()->optional()->imageUrl(128, 128),
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'scopes' => ['https://www.googleapis.com/auth/calendar'],
            'last_sync_at' => null,
        ];
    }
}
