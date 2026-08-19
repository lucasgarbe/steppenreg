<?php

namespace Database\Factories;

use App\Domain\Mail\Models\MailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Mail\Models\MailTemplate>
 */
class MailTemplateFactory extends Factory
{
    protected $model = MailTemplate::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->sentence(3),
            'subject' => 'Hello {{name}}, '.fake()->sentence(4),
            'body' => "Dear {{name}},\n\n".fake()->paragraphs(2, true)."\n\nBest regards,\n{{event_name}}",
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function registrationConfirmation(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'registration_confirmation',
            'name' => 'Registration Confirmation',
            'subject' => 'Registration confirmed for {{name}}',
            'body' => "Dear {{name}},\n\nYour registration for {{event_name}} ({{track_name}}) has been confirmed.\n\nRegistration date: {{registration_date}}",
        ]);
    }

    public function drawSuccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'draw_success',
            'name' => 'Draw Success',
            'subject' => 'You have been drawn, {{name}}!',
            'body' => "Dear {{name}},\n\nCongratulations! You have been drawn for {{event_name}}.\n\n{{#team_name}}Your team: {{team_name}}{{/team_name}}",
        ]);
    }
}
