<?php

namespace App\Jobs\Mail;

use App\Models\MailFailureBatch;
use App\Models\MailLog;
use App\Models\Registration;
use App\Services\MailTemplateService;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDrawNotification implements ShouldQueue
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
        public Registration $registration
    ) {
        // Using default queue for simplicity
    }

    public function handle(MailTemplateService $mailService): void
    {
        $templateKey = $this->registration->draw_status === 'drawn'
            ? 'draw_success'
            : 'draw_rejection';

        // Find the mail log for this job and track attempt
        $mailLog = MailLog::where('registration_id', $this->registration->id)
            ->where('template_key', $templateKey)
            ->where('status', 'queued')
            ->latest()
            ->first();

        if ($mailLog) {
            $mailLog->incrementAttempt();
        }

        // Send the email (service is idempotent)
        $mailService->sendEmail($templateKey, $this->registration);
    }

    public function failed(\Throwable $exception): void
    {
        $templateKey = $this->registration->draw_status === 'drawn'
            ? 'draw_success'
            : 'draw_rejection';

        Log::error('Draw notification email failed', [
            'registration_id' => $this->registration->id,
            'email' => $this->registration->email,
            'draw_status' => $this->registration->draw_status,
            'template_key' => $templateKey,
            'error' => $exception->getMessage(),
        ]);

        // Mark mail log as finally failed
        $mailLog = MailLog::where('registration_id', $this->registration->id)
            ->where('template_key', $templateKey)
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
