<?php

namespace App\Domain\Mail\Jobs;

use App\Domain\Mail\Services\MailTemplateService;
use App\Models\Registration;

class SendDrawNotification extends MailJob
{
    public function __construct(
        public Registration $registration
    ) {}

    protected function templateKey(): string
    {
        return $this->registration->draw_status_value === 'drawn'
            ? 'draw_success'
            : 'draw_rejection';
    }

    protected function retryHours(): int
    {
        return 20;
    }

    protected function failureLogMessage(): string
    {
        return 'Draw notification email failed';
    }

    protected function failureLogContext(): array
    {
        return [
            'draw_status' => $this->registration->draw_status_value,
            'template_key' => $this->templateKey(),
        ];
    }

    public function handle(MailTemplateService $mailService): void
    {
        $this->incrementAttempt();
        $mailService->sendEmail($this->templateKey(), $this->registration);
    }

    public function failed(\Throwable $exception): void
    {
        $this->handleFailure($exception);
    }
}
