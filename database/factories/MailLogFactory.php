<?php

namespace Database\Factories;

use App\Domain\Mail\Models\MailLog;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Mail\Models\MailLog>
 */
class MailLogFactory extends Factory
{
    protected $model = MailLog::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'template_key' => 'registration_confirmation',
            'recipient_email' => fake()->safeEmail(),
            'status' => 'queued',
            'template_variables' => [],
            'attempt_count' => 0,
            'rate_limit_count' => 0,
            'metadata' => [],
        ];
    }

    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'queued',
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Test error message',
        ]);
    }
}
