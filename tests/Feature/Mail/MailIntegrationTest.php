<?php

namespace Tests\Feature\Mail;

use App\Domain\Mail\Jobs\SendDrawNotification;
use App\Domain\Mail\Jobs\SendFlexibleMail;
use App\Domain\Mail\Jobs\SendRegistrationConfirmation;
use App\Domain\Mail\Mail\FlexibleMail;
use App\Domain\Mail\Mail\TemplateBasedEmail;
use App\Domain\Mail\Models\MailFailureBatch;
use App\Domain\Mail\Models\MailLog;
use App\Domain\Mail\Models\MailTemplate;
use App\Domain\Mail\Services\MailTemplateService;
use App\Models\Registration;
use App\Settings\EventSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = app(EventSettings::class);
        $settings->custom_questions = [];
        $settings->save();
    }

    public function test_registration_confirmation_goes_through_mail_template_service(): void
    {
        Mail::fake();

        MailTemplate::factory()->create([
            'key' => 'registration_confirmation',
            'name' => 'Registration Confirmation',
            'subject' => 'Welcome {{name}}',
            'body' => 'Dear {{name}}, your registration is confirmed.',
            'is_active' => true,
        ]);

        $registration = Registration::factory()->create(['name' => 'Alice']);

        SendRegistrationConfirmation::dispatch($registration);

        $mailLog = MailLog::where('registration_id', $registration->id)
            ->where('template_key', 'registration_confirmation')
            ->first();

        $this->assertNotNull($mailLog);
        $this->assertEquals('sent', $mailLog->status);

        Mail::assertSent(TemplateBasedEmail::class);
    }

    public function test_draw_notification_goes_through_mail_template_service(): void
    {
        Mail::fake();

        MailTemplate::factory()->create([
            'key' => 'draw_success',
            'name' => 'Draw Success',
            'subject' => 'You are drawn, {{name}}!',
            'body' => 'Congratulations {{name}}!',
            'is_active' => true,
        ]);

        $registration = Registration::factory()->create(['name' => 'Bob']);
        \App\Domain\Draw\Models\DrawStatus::create([
            'registration_id' => $registration->id,
            'status' => 'drawn',
            'drawn_at' => now(),
        ]);
        $registration->load('drawStatus');

        $service = app(MailTemplateService::class);
        $service->sendEmail('draw_success', $registration);

        $mailLog = MailLog::where('registration_id', $registration->id)
            ->where('template_key', 'draw_success')
            ->first();

        $this->assertNotNull($mailLog);
        $this->assertEquals('sent', $mailLog->status);

        Mail::assertSent(TemplateBasedEmail::class);
    }

    public function test_draw_rejection_notification(): void
    {
        Mail::fake();

        MailTemplate::factory()->create([
            'key' => 'draw_rejection',
            'name' => 'Draw Rejection',
            'subject' => 'Update on your registration, {{name}}',
            'body' => 'Dear {{name}}, unfortunately you were not drawn.',
            'is_active' => true,
        ]);

        $registration = Registration::factory()->create(['name' => 'Carol']);

        SendDrawNotification::dispatch($registration);

        $mailLog = MailLog::where('registration_id', $registration->id)
            ->where('template_key', 'draw_rejection')
            ->first();

        $this->assertNotNull($mailLog);
        $this->assertEquals('sent', $mailLog->status);

        Mail::assertSent(TemplateBasedEmail::class);
    }

    public function test_flexible_mail_goes_through_mail_template_service(): void
    {
        Mail::fake();

        $registration = Registration::factory()->create(['name' => 'Dave']);

        SendFlexibleMail::dispatch($registration, 'Hello {{name}}', 'Custom message for {{name}}');

        $mailLog = MailLog::where('registration_id', $registration->id)
            ->where('template_key', 'custom_email')
            ->first();

        $this->assertNotNull($mailLog);
        $this->assertEquals('sent', $mailLog->status);

        Mail::assertSent(FlexibleMail::class);
    }

    public function test_all_paths_create_mail_log_with_correct_status(): void
    {
        Mail::fake();

        MailTemplate::factory()->create([
            'key' => 'registration_confirmation',
            'subject' => 'Welcome',
            'body' => 'Hello',
            'is_active' => true,
        ]);

        $reg1 = Registration::factory()->create();
        $reg2 = Registration::factory()->create();
        $reg3 = Registration::factory()->create();

        SendRegistrationConfirmation::dispatch($reg1);
        SendFlexibleMail::dispatch($reg2, 'Test', 'Body');

        $log1 = MailLog::where('registration_id', $reg1->id)->first();
        $log2 = MailLog::where('registration_id', $reg2->id)->first();

        $this->assertEquals('sent', $log1->status);
        $this->assertEquals('sent', $log2->status);
    }

    public function test_all_paths_render_variables_correctly(): void
    {
        Mail::fake();

        MailTemplate::factory()->create([
            'key' => 'registration_confirmation',
            'subject' => 'Welcome {{name}}',
            'body' => 'Dear {{name}}, track: {{track_name}}',
            'is_active' => true,
        ]);

        $registration = Registration::factory()->create(['name' => 'Eve']);

        $service = app(MailTemplateService::class);
        $mailLog = $service->sendEmail('registration_confirmation', $registration);

        $this->assertNotNull($mailLog);
        $this->assertArrayHasKey('name', $mailLog->template_variables);
        $this->assertEquals('Eve', $mailLog->template_variables['name']);
    }

    public function test_failure_records_to_mail_failure_batch(): void
    {
        $registration = Registration::factory()->create();

        $mailLog = MailLog::logEmail(
            templateKey: 'registration_confirmation',
            recipientEmail: $registration->email,
            registrationId: $registration->id,
        );

        $job = new SendRegistrationConfirmation($registration);
        $job->failed(new \RuntimeException('All retries exhausted'));

        $mailLog->refresh();
        $this->assertEquals('failed', $mailLog->status);

        $batch = MailFailureBatch::first();
        $this->assertNotNull($batch);
        $this->assertEquals(1, $batch->failure_count);
        $this->assertContains($mailLog->id, $batch->mail_log_ids);
    }

    public function test_rate_limiting_middleware_applies_to_all_jobs(): void
    {
        $registration = Registration::factory()->create();

        $job1 = new SendRegistrationConfirmation($registration);
        $job2 = new SendDrawNotification($registration);
        $job3 = new SendFlexibleMail($registration, 'Test', 'Body');

        $middleware1 = $job1->middleware();
        $middleware2 = $job2->middleware();
        $middleware3 = $job3->middleware();

        $this->assertCount(1, $middleware1);
        $this->assertCount(1, $middleware2);
        $this->assertCount(1, $middleware3);

        $this->assertInstanceOf(\Illuminate\Queue\Middleware\RateLimited::class, $middleware1[0]);
        $this->assertInstanceOf(\Illuminate\Queue\Middleware\RateLimited::class, $middleware2[0]);
        $this->assertInstanceOf(\Illuminate\Queue\Middleware\RateLimited::class, $middleware3[0]);
    }

    public function test_jobs_have_correct_retry_windows(): void
    {
        $registration = Registration::factory()->create();

        $job1 = new SendRegistrationConfirmation($registration);
        $job2 = new SendDrawNotification($registration);
        $job3 = new SendFlexibleMail($registration, 'Test', 'Body');

        $this->assertEqualsWithDelta(6 * 3600, $job1->retryUntil()->getTimestamp() - now()->getTimestamp(), 5);
        $this->assertEqualsWithDelta(20 * 3600, $job2->retryUntil()->getTimestamp() - now()->getTimestamp(), 5);
        $this->assertEqualsWithDelta(20 * 3600, $job3->retryUntil()->getTimestamp() - now()->getTimestamp(), 5);
    }

    public function test_jobs_have_backoff_with_jitter(): void
    {
        $registration = Registration::factory()->create();
        $job = new SendRegistrationConfirmation($registration);

        $backoffs = [];
        for ($i = 0; $i < 10; $i++) {
            $backoffs[] = $job->backoff();
        }

        $this->assertNotEmpty(array_unique($backoffs), 'Backoff should have jitter');

        foreach ($backoffs as $backoff) {
            $this->assertGreaterThanOrEqual(48, $backoff);
            $this->assertLessThanOrEqual(72, $backoff);
        }
    }
}
