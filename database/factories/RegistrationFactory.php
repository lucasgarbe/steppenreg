<?php

namespace Database\Factories;

use App\Domain\Draw\Models\DrawStatus;
use App\Models\Registration;
use App\Models\Team;
use App\Settings\EventSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Registration>
 */
class RegistrationFactory extends Factory
{
    protected bool $createDrawnStatus = false;

    public function definition(): array
    {
        $name = fake()->name();
        $email = str_replace([' ', '.'], '', strtolower($name)).'@'.fake()->safeEmailDomain();
        $finished = fake()->boolean(30);
        $starting = $finished || fake()->boolean(60);
        $payed = $starting || fake()->boolean(90);

        $teamId = null;
        if (fake()->boolean(60) && Team::notFull()->exists()) {
            $teamId = Team::notFull()->inRandomOrder()->first()?->id;
        }

        $settings = app(EventSettings::class);
        $trackIds = collect($settings->tracks)->pluck('id')->toArray();
        $trackId = ! empty($trackIds) ? fake()->randomElement($trackIds) : 1;

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

    public function configure(): static
    {
        return $this->afterCreating(function (Registration $registration) {
            if ($this->createDrawnStatus) {
                DrawStatus::create([
                    'registration_id' => $registration->id,
                    'status' => 'drawn',
                    'drawn_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);
            }
        });
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
            'payed' => true,
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

    public function drawn(): static
    {
        $this->createDrawnStatus = true;

        return $this->state(fn (array $attributes) => [
            'payed' => true,
        ]);
    }
}
