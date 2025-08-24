<?php

namespace Database\Factories;

use App\Models\Team;
use App\Settings\EventSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();
        $email = str_replace([' ', '.'], '', strtolower($name)) . '@' . fake()->safeEmailDomain();
        $finished = fake()->boolean(30); // 30% chance of being finished
        $starting = $finished || fake()->boolean(60); // If finished, definitely starting. Otherwise 60% chance
        $payed = $starting || fake()->boolean(90); // If starting, likely paid. Otherwise 80% chance

        $teamId = null;
        // 60% chance of being assigned to a team if there are available teams
        if (fake()->boolean(60) && Team::notFull()->exists()) {
            $teamId = Team::notFull()->inRandomOrder()->first()?->id;
        }

        // Get available track IDs from EventSettings
        $settings = app(EventSettings::class);
        $trackIds = collect($settings->tracks)->pluck('id')->toArray();
        $trackId = !empty($trackIds) ? fake()->randomElement($trackIds) : 1;

        return [
            'name' => $name,
            'email' => $email,
            'track_id' => $trackId,
            'team_id' => $teamId,
            'age' => fake()->numberBetween(16, 75),
            'gender' => fake()->randomElement(['flinta', 'all_gender']),
            'payed' => $payed,
            'starting' => $starting,
            'finish_time' => $finished ? fake()->time('H:i:s') : null,
            'notes' => fake()->optional(0.3)->realText(200),
        ];
    }

    public function payed(): static
    {
        return $this->state(fn(array $attributes) => [
            'payed' => true,
        ]);
    }

    public function starting(): static
    {
        return $this->state(fn(array $attributes) => [
            'starting' => true,
            'payed' => true, // Must be paid to start
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn(array $attributes) => [
            'finish_time' => fake()->time('H:i:s'),
            'starting' => true,
            'payed' => true,
        ]);
    }

    public function drawn(): static
    {
        return $this->state(fn(array $attributes) => [
            'draw_status' => 'drawn',
            'drawn_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'payed' => true,
        ]);
    }

    public function waitlisted(): static
    {
        return $this->state(fn(array $attributes) => [
            'draw_status' => 'waitlist',
            'drawn_at' => null,
        ]);
    }
}
