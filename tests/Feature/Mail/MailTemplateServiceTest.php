<?php

namespace Tests\Feature\Mail;

use App\Domain\Mail\Mail\FlexibleMail;
use App\Domain\Mail\Mail\TemplateBasedEmail;
use App\Domain\Mail\Models\MailLog;
use App\Domain\Mail\Models\MailTemplate;
use App\Domain\Mail\Services\MailTemplateService;
use App\Models\Registration;
use App\Settings\EventSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private MailTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MailTemplateService::class);
    }

    public function test_send_email_creates_mail_log_sends_and_marks_sent(): void
    {
        Mail::fake();

        $template = MailTemplate::factory()->registrationConfirmation()->create();
        $registration = Registration::factory()->create();

        $mailLog = $this->service->sendEmail('registration_confirmation', $registration);

        $this->assertNotNull($mailLog);
        $this->assertEquals('sent', $mailLog->status);
        $this->assertNotNull($mailLog->sent_at);
        $this->assertEquals($registration->id, $mailLog->registration_id);
        $this->assertEquals('registration_confirmation', $mailLog->template_key);

        Mail::assertSent(TemplateBasedEmail::class, function (TemplateBasedEmail $mail) use ($registration) {
            return $mail->hasTo($registration->email);
        });
    }

    public function test_send_email_returns_null_for_inactive_template(): void
    {
        Mail::fake();

        MailTemplate::factory()->registrationConfirmation()->inactive()->create();
        $registration = Registration::factory()->create();

        $result = $this->service->sendEmail('registration_confirmation', $registration);

        $this->assertNull($result);
        Mail::assertNothingSent();
    }

    public function test_send_email_returns_null_for_missing_template(): void
    {
        Mail::fake();

        $registration = Registration::factory()->create();

        $result = $this->service->sendEmail('nonexistent_template', $registration);

        $this->assertNull($result);
        Mail::assertNothingSent();
    }

    public function test_send_email_creates_log_before_sending(): void
    {
        Mail::fake();

        $template = MailTemplate::factory()->registrationConfirmation()->create();
        $registration = Registration::factory()->create();

        $mailLog = $this->service->sendEmail('registration_confirmation', $registration);

        $this->assertNotNull($mailLog);
        $this->assertEquals($registration->id, $mailLog->registration_id);
        $this->assertEquals('registration_confirmation', $mailLog->template_key);
        $this->assertEquals($registration->email, $mailLog->recipient_email);
    }

    public function test_send_email_uses_existing_queued_mail_log(): void
    {
        Mail::fake();

        $template = MailTemplate::factory()->registrationConfirmation()->create();
        $registration = Registration::factory()->create();

        $existingLog = MailLog::logEmail(
            templateKey: 'registration_confirmation',
            recipientEmail: $registration->email,
            registrationId: $registration->id,
        );

        $mailLog = $this->service->sendEmail('registration_confirmation', $registration);

        $this->assertEquals($existingLog->id, $mailLog->id);
        $this->assertEquals('sent', $mailLog->status);
    }

    public function test_preview_template_returns_rendered_content(): void
    {
        $template = MailTemplate::factory()->registrationConfirmation()->create();
        $registration = Registration::factory()->create();

        $result = $this->service->previewTemplate('registration_confirmation', $registration);

        $this->assertArrayHasKey('subject', $result);
        $this->assertArrayHasKey('body', $result);
        $this->assertStringContainsString($registration->name, $result['subject']);
    }

    public function test_preview_template_returns_error_for_missing_template(): void
    {
        $result = $this->service->previewTemplate('nonexistent');

        $this->assertEquals(['error' => 'Template not found'], $result);
    }

    public function test_send_email_renders_template_end_to_end(): void
    {
        Mail::fake();

        $template = MailTemplate::factory()->create([
            'key' => 'registration_confirmation',
            'subject' => 'Welcome {{name}}',
            'body' => "Dear {{name}},\n\n**Bold** text for {{event_name}}.\n\n{{#team_name}}Team: {{team_name}}{{/team_name}}",
            'is_active' => true,
        ]);

        $registration = Registration::factory()->create(['name' => 'Alice']);

        $mailLog = $this->service->sendEmail('registration_confirmation', $registration);

        $this->assertNotNull($mailLog);
        $this->assertEquals('sent', $mailLog->status);

        Mail::assertSent(TemplateBasedEmail::class, function (TemplateBasedEmail $mail) {
            return $mail->renderedSubject === 'Welcome Alice'
                && str_contains($mail->renderedHtmlBody, '<strong>Bold</strong>')
                && str_contains($mail->renderedHtmlBody, 'Alice');
        });
    }

    public function test_send_email_renders_conditionals_correctly(): void
    {
        Mail::fake();

        $template = MailTemplate::factory()->create([
            'key' => 'draw_success',
            'subject' => 'Drawn!',
            'body' => "{{#team_name}}Team: {{team_name}}{{/team_name}}\n\nNo team.",
            'is_active' => true,
        ]);

        $registration = Registration::factory()->create(['name' => 'Alice', 'team_id' => null]);

        $this->service->sendEmail('draw_success', $registration);

        Mail::assertSent(TemplateBasedEmail::class, function (TemplateBasedEmail $mail) {
            return ! str_contains($mail->renderedHtmlBody, 'Team:');
        });
    }

    public function test_send_flexible_renders_variables_and_sends(): void
    {
        Mail::fake();

        $settings = app(EventSettings::class);
        $settings->custom_questions = [];
        $settings->save();

        $registration = Registration::factory()->create([
            'name' => 'Jane Runner',
            'email' => 'jane@example.com',
        ]);

        $mailLog = $this->service->sendFlexible(
            $registration,
            'Hello {{name}}',
            'Dear {{name}}, welcome to {{event_name}}!'
        );

        $this->assertEquals('sent', $mailLog->status);
        $this->assertEquals('custom_email', $mailLog->template_key);

        Mail::assertSent(FlexibleMail::class);
    }

    public function test_send_flexible_creates_mail_log(): void
    {
        Mail::fake();

        $settings = app(EventSettings::class);
        $settings->custom_questions = [];
        $settings->save();

        $registration = Registration::factory()->create();

        $mailLog = $this->service->sendFlexible($registration, 'Test', 'Body');

        $this->assertDatabaseHas('mail_logs', [
            'id' => $mailLog->id,
            'registration_id' => $registration->id,
            'template_key' => 'custom_email',
            'status' => 'sent',
        ]);
    }

    public function test_send_flexible_processes_markdown(): void
    {
        Mail::fake();

        $settings = app(EventSettings::class);
        $settings->custom_questions = [];
        $settings->save();

        $registration = Registration::factory()->create(['name' => 'Test']);

        $mailLog = $this->service->sendFlexible(
            $registration,
            'Subject',
            '**Bold text** and *italic*'
        );

        $this->assertEquals('sent', $mailLog->status);
        $this->assertStringContainsString('<strong>Bold text</strong>', $mailLog->template_variables['processed_message']);
    }

    public function test_get_template_stats_returns_counts(): void
    {
        $registration = Registration::factory()->create();

        MailLog::factory()->create([
            'template_key' => 'registration_confirmation',
            'registration_id' => $registration->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        MailLog::factory()->create([
            'template_key' => 'registration_confirmation',
            'registration_id' => $registration->id,
            'status' => 'failed',
            'error_message' => 'test error',
        ]);
        MailLog::factory()->create([
            'template_key' => 'registration_confirmation',
            'registration_id' => $registration->id,
            'status' => 'queued',
        ]);

        $stats = $this->service->getTemplateStats('registration_confirmation');

        $this->assertEquals(1, $stats['total_sent']);
        $this->assertEquals(1, $stats['total_failed']);
        $this->assertEquals(1, $stats['total_queued']);
    }
}
