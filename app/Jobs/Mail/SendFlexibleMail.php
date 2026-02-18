<?php

namespace App\Jobs\Mail;

use App\Mail\FlexibleMail;
use App\Models\MailFailureBatch;
use App\Models\MailLog;
use App\Models\Registration;
use App\Services\MailVariableResolver;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendFlexibleMail implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): DateTime
    {
        // Allow 20 hours for bulk sends (500 emails @ 30/hour ≈ 17h + 3h buffer)
        return now()->addHours(20);
    }

    public function backoff(): int
    {
        $baseBackoffs = [60, 300, 900, 1800, 3600];
        $attemptIndex = min($this->attempts() - 1, count($baseBackoffs) - 1);
        $baseBackoff = $baseBackoffs[$attemptIndex];

        // Add ±20% jitter to prevent thundering herd problem
        $jitter = rand(-20, 20) / 100;

        return (int) ($baseBackoff * (1 + $jitter));
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('emails'))
                ->releaseAfter((int) config('mail.rate_limit_release_delay', 60)),
        ];
    }

    public function __construct(
        public Registration $registration,
        public string $subject,
        public string $message
    ) {
        // Using default queue for simplicity
    }

    public function handle(MailVariableResolver $variableResolver): void
    {
        // Resolve template variables for this registration
        $variables = $variableResolver->resolve($this->registration);

        // Process subject and message with variables
        $processedSubject = $this->processVariables($this->subject, $variables);
        $processedMessage = $this->processVariables($this->message, $variables);

        // Find existing queued mail log or create new one (idempotent)
        $mailLog = MailLog::where('registration_id', $this->registration->id)
            ->where('template_key', 'custom_email')
            ->where('status', 'queued')
            ->latest()
            ->first();

        if (! $mailLog) {
            $mailLog = MailLog::logEmail(
                templateKey: 'custom_email',
                recipientEmail: $this->registration->email,
                registrationId: $this->registration->id,
                variables: [
                    'original_subject' => $this->subject,
                    'original_message' => $this->message,
                    'processed_subject' => $processedSubject,
                    'processed_message' => $processedMessage,
                    'name' => $this->registration->name,
                    'email' => $this->registration->email,
                ]
            );
        }

        // Track attempt
        $mailLog->incrementAttempt();

        try {
            Mail::to($this->registration->email)
                ->send(new FlexibleMail(
                    $processedSubject,
                    $processedMessage,
                    $this->registration
                ));

            $mailLog->markAsSent();
        } catch (\Exception $e) {
            $mailLog->markAsFailed($e->getMessage());
            throw $e; // Re-throw to trigger job retry
        }
    }

    private function processVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Flexible email failed to send', [
            'registration_id' => $this->registration->id,
            'email' => $this->registration->email,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);

        // Mark mail log as finally failed
        $mailLog = MailLog::where('registration_id', $this->registration->id)
            ->where('template_key', 'custom_email')
            ->where('status', 'queued')
            ->latest()
            ->first();

        if ($mailLog) {
            $mailLog->markAsFailed($exception->getMessage());

            // Record failure in batch for later notification
            MailFailureBatch::recordFailure($mailLog);
        }
    }
}
