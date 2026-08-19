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
        return MailLog::where('registration_id', $this->registration->id)
            ->where('template_key', $this->templateKey())
            ->where('status', 'queued')
            ->latest()
            ->first();
    }

    protected function incrementAttempt(): void
    {
        $mailLog = $this->findQueuedMailLog();

        if ($mailLog) {
            $mailLog->incrementAttempt();
        }
    }

    protected function handleFailure(\Throwable $exception): void
    {
        $context = $this->failureLogContext();
        $context['registration_id'] = $this->registration->id;
        $context['email'] = $this->registration->email;
        $context['error'] = $exception->getMessage();

        Log::error($this->failureLogMessage(), $context);

        $mailLog = $this->findQueuedMailLog();

        if ($mailLog) {
            $mailLog->markAsFailed($exception->getMessage());
            MailFailureBatch::recordFailure($mailLog);
        }
    }

    protected function failureLogMessage(): string
    {
        return 'Mail job failed';
    }
}
