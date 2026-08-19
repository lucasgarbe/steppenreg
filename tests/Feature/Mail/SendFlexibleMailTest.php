<?php

namespace Tests\Feature\Mail;

use App\Domain\Mail\Jobs\SendFlexibleMail;
use App\Domain\Mail\Mail\FlexibleMail;
use App\Domain\Mail\Models\MailLog;
use App\Models\Registration;
use App\Settings\EventSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendFlexibleMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_processes_variables_sends_email_and_creates_mail_log(): void
    {
        Mail::fake();

        $settings = app(EventSettings::class);
        $settings->custom_questions = [];
        $settings->save();

        $registration = Registration::factory()->create([
            'name' => 'Test Runner',
            'email' => 'test@example.com',
        ]);

        $job = new SendFlexibleMail(
            registration: $registration,
            subject: 'Hello {{name}}',
            message: 'Dear {{name}}, welcome to {{event_name}}!'
        );

        $job->handle(app(\App\Domain\Mail\Services\MailTemplateService::class));

        $mailLog = MailLog::where('registration_id', $registration->id)
            ->where('template_key', 'custom_email')
            ->first();

        $this->assertNotNull($mailLog);
        $this->assertEquals('sent', $mailLog->status);

        Mail::assertSent(FlexibleMail::class);
    }

    public function test_job_marks_log_as_failed_on_exception(): void
    {
        $registration = Registration::factory()->create();

        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $job = new SendFlexibleMail(
            registration: $registration,
            subject: 'Test',
            message: 'Test body'
        );

        try {
            $job->handle(app(\App\Domain\Mail\Services\MailTemplateService::class));
        } catch (\RuntimeException $e) {
            $mailLog = MailLog::where('registration_id', $registration->id)
                ->where('template_key', 'custom_email')
                ->first();

            $this->assertNotNull($mailLog);
            $this->assertEquals('failed', $mailLog->status);

            return;
        }

        $this->fail('Expected exception was not thrown');
    }

    public function test_failed_method_marks_log_and_records_batch_failure(): void
    {
        $registration = Registration::factory()->create();

        $mailLog = MailLog::logEmail(
            templateKey: 'custom_email',
            recipientEmail: $registration->email,
            registrationId: $registration->id,
        );

        $job = new SendFlexibleMail(
            registration: $registration,
            subject: 'Test',
            message: 'Test body'
        );

        $job->failed(new \RuntimeException('Final failure'));

        $mailLog->refresh();
        $this->assertEquals('failed', $mailLog->status);
        $this->assertEquals('Final failure', $mailLog->error_message);

        $this->assertDatabaseHas('mail_failure_batches', [
            'failure_count' => 1,
        ]);
    }
}
