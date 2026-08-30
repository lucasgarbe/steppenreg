<?php

namespace App\Domain\Mail\Jobs;

use App\Domain\Mail\Models\MailFailureBatch;
use App\Domain\Mail\Models\MailLog;
use App\Models\Registration;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class MailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public Registration $registration;

    abstract protected function templateKey(): string;

    abstract protected function retryHours(): int;

    abstract protected function failureLogContext(): array;

    public function retryUntil(): DateTime
    {
        return now()->addHours($this->retryHours());
    }

    public function backoff(): int
    {
        $baseBackoffs = [60, 300, 900, 1800, 3600];
        $attemptIndex = min($this->attempts() - 1, count($baseBackoffs) - 1);
        $baseBackoff = $baseBackoffs[$attemptIndex];

        $jitter = rand(-20, 20) / 100;

        return (int) ($baseBackoff * (1 + $jitter));
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('emails'))
                ->releaseAfter(config('mail.rate_limit_release_delay', 60)),
        ];
    }

    protected function findQueuedMailLog(): ?MailLog
    {
        $mailLog = MailLog::where('registration_id', $this->registration->id)
            ->where('template_key', $this->templateKey())
            ->where('status', 'queued')
            ->latest()
            ->first();

        Log::info('[Mail Trail] findQueuedMailLog', [
            'registration_id' => $this->registration->id,
            'template_key' => $this->templateKey(),
            'found' => $mailLog !== null,
            'mail_log_id' => $mailLog?->id,
        ]);

        return $mailLog;
    }

    protected function incrementAttempt(): void
    {
        $mailLog = $this->findQueuedMailLog();

        if ($mailLog) {
            $mailLog->incrementAttempt();
            Log::info('[Mail Trail] incrementAttempt', [
                'mail_log_id' => $mailLog->id,
                'new_attempt_count' => $mailLog->fresh()->attempt_count,
            ]);
        } else {
            Log::warning('[Mail Trail] incrementAttempt: no queued mail log found', [
                'registration_id' => $this->registration->id,
                'template_key' => $this->templateKey(),
            ]);
        }
    }

    protected function handleFailure(\Throwable $exception): void
    {
        $context = $this->failureLogContext();
        $context['registration_id'] = $this->registration->id;
        $context['email'] = $this->registration->email;
        $context['error'] = $exception->getMessage();
        $context['exception_class'] = get_class($exception);
        $context['trace'] = $exception->getTraceAsString();

        Log::error('[Mail Trail] '.$this->failureLogMessage(), $context);

        $mailLog = $this->findQueuedMailLog();

        if ($mailLog) {
            $mailLog->markAsFailed($exception->getMessage());
            MailFailureBatch::recordFailure($mailLog);
            Log::info('[Mail Trail] handleFailure: mail log marked as failed', [
                'mail_log_id' => $mailLog->id,
            ]);
        } else {
            Log::warning('[Mail Trail] handleFailure: no queued mail log found to mark as failed', [
                'registration_id' => $this->registration->id,
            ]);
        }
    }

    protected function failureLogMessage(): string
    {
        return 'Mail job failed';
    }
}
