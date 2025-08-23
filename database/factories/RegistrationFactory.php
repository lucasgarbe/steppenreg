<?php

namespace Database\Factories;

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
        $finished = fake()->boolean(30); // 30% chance of being finished
        $starting = $finished || fake()->boolean(60); // If finished, definitely starting. Otherwise 60% chance
        $payed = $starting || fake()->boolean(80); // If starting, likely paid. Otherwise 80% chance
        
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'track_id' => fake()->randomElement([1, 2, 3]), // Random track selection
            'age' => fake()->numberBetween(16, 75),
            'payed' => $payed,
            'starting' => $starting,
            'finish_time' => $finished ? fake()->time('H:i:s') : null,
            'notes' => fake()->optional(0.3)->realText(200),
        ];
    }

    public function payed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payed' => true,
        ]);
    }

    public function starting(): static
    {
        return $this->state(fn (array $attributes) => [
            'starting' => true,
            'payed' => true, // Must be paid to start
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'finish_time' => fake()->time('H:i:s'),
            'starting' => true,
            'payed' => true,
        ]);
    }
}
