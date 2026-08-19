<?php

namespace Tests\Feature\Mail;

use App\Domain\Mail\Models\MailLog;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_sent_updates_status_and_metadata(): void
    {
        $mailLog = MailLog::factory()->queued()->create([
            'attempt_count' => 2,
        ]);

        $mailLog->markAsSent();

        $mailLog->refresh();

        $this->assertEquals('sent', $mailLog->status);
        $this->assertNotNull($mailLog->sent_at);
        $this->assertNull($mailLog->error_message);
        $this->assertEquals(2, $mailLog->metadata['final_attempt_count']);
    }

    public function test_mark_as_failed_updates_status_and_error_message(): void
    {
        $mailLog = MailLog::factory()->queued()->create([
            'attempt_count' => 3,
        ]);

        $mailLog->markAsFailed('SMTP connection refused');

        $mailLog->refresh();

        $this->assertEquals('failed', $mailLog->status);
        $this->assertEquals('SMTP connection refused', $mailLog->error_message);
        $this->assertEquals(3, $mailLog->metadata['final_attempt_count']);
        $this->assertArrayHasKey('failed_at', $mailLog->metadata);
    }

    public function test_mark_as_rate_limited_increments_count(): void
    {
        $mailLog = MailLog::factory()->create([
            'rate_limit_count' => 0,
        ]);

        $mailLog->markAsRateLimited(60);

        $mailLog->refresh();

        $this->assertEquals(1, $mailLog->rate_limit_count);
        $this->assertNotNull($mailLog->last_rate_limited_at);
        $this->assertEquals(60, $mailLog->metadata['last_release_delay']);
    }

    public function test_increment_attempt_increments_count(): void
    {
        $mailLog = MailLog::factory()->create([
            'attempt_count' => 0,
        ]);

        $mailLog->incrementAttempt();
        $mailLog->refresh();

        $this->assertEquals(1, $mailLog->attempt_count);

        $mailLog->incrementAttempt();
        $mailLog->refresh();

        $this->assertEquals(2, $mailLog->attempt_count);
    }

    public function test_is_rate_limited_returns_true_within_five_minutes(): void
    {
        $mailLog = MailLog::factory()->create([
            'last_rate_limited_at' => now()->subMinutes(3),
        ]);

        $this->assertTrue($mailLog->isRateLimited());
    }

    public function test_is_rate_limited_returns_false_after_five_minutes(): void
    {
        $mailLog = MailLog::factory()->create([
            'last_rate_limited_at' => now()->subMinutes(6),
        ]);

        $this->assertFalse($mailLog->isRateLimited());
    }

    public function test_is_rate_limited_returns_false_when_null(): void
    {
        $mailLog = MailLog::factory()->create([
            'last_rate_limited_at' => null,
        ]);

        $this->assertFalse($mailLog->isRateLimited());
    }

    public function test_log_email_creates_queued_record(): void
    {
        $registration = Registration::factory()->create();

        $mailLog = MailLog::logEmail(
            templateKey: 'registration_confirmation',
            recipientEmail: $registration->email,
            registrationId: $registration->id,
            variables: ['name' => 'Test']
        );

        $this->assertDatabaseHas('mail_logs', [
            'id' => $mailLog->id,
            'registration_id' => $registration->id,
            'template_key' => 'registration_confirmation',
            'recipient_email' => $registration->email,
            'status' => 'queued',
        ]);

        $this->assertEquals(['name' => 'Test'], $mailLog->template_variables);
    }
}
