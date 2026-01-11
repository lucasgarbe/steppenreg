<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teamNames = [
            'Thunder Riders',
            'Speed Demons',
            'Chain Breakers',
            'Pedal Pushers',
            'Wheel Warriors',
            'Cycle Storm',
            'Road Runners',
            'Wind Riders',
            'Gear Shifters',
            'Mountain Mavericks',
        ];

        return [
            'name' => fake()->unique()->randomElement($teamNames).' '.fake()->numberBetween(1, 99),
            'max_members' => fake()->numberBetween(4, 7), // Updated range around new default of 5
        ];
    }
}
