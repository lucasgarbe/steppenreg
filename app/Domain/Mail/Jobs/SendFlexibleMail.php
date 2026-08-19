<?php

namespace App\Domain\Mail\Jobs;

use App\Domain\Mail\Services\MailTemplateService;
use App\Models\Registration;

class SendFlexibleMail extends MailJob
{
    public function __construct(
        public Registration $registration,
        public string $subject,
        public string $message
    ) {}

    protected function templateKey(): string
    {
        return 'custom_email';
    }

    protected function retryHours(): int
    {
        return 20;
    }

    protected function failureLogMessage(): string
    {
        return 'Flexible email failed to send';
    }

    protected function failureLogContext(): array
    {
        return [
            'subject' => $this->subject,
        ];
    }

    public function handle(MailTemplateService $mailService): void
    {
        $this->incrementAttempt();
        $mailService->sendFlexible($this->registration, $this->subject, $this->message);
    }

    public function failed(\Throwable $exception): void
    {
        $this->handleFailure($exception);
    }
}
