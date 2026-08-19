<?php

namespace App\Domain\Mail\Jobs;

use App\Domain\Mail\Services\MailTemplateService;
use App\Models\Registration;

class SendRegistrationConfirmation extends MailJob
{
    public function __construct(
        public Registration $registration
    ) {}

    protected function templateKey(): string
    {
        return 'registration_confirmation';
    }

    protected function retryHours(): int
    {
        return 6;
    }

    protected function failureLogMessage(): string
    {
        return 'Registration confirmation email failed';
    }

    protected function failureLogContext(): array
    {
        return [];
    }

    public function handle(MailTemplateService $mailService): void
    {
        $this->incrementAttempt();
        $mailService->sendEmail('registration_confirmation', $this->registration);
    }

    public function failed(\Throwable $exception): void
    {
        $this->handleFailure($exception);
    }
}
