<?php

namespace App\Domain\Mail\Jobs;

use App\Domain\Mail\Services\MailTemplateService;
use App\Models\Registration;
use Illuminate\Support\Facades\Log;

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
        Log::info('[Mail Trail] SendRegistrationConfirmation::handle started', [
            'registration_id' => $this->registration->id,
            'email' => $this->registration->email,
            'attempt' => $this->attempts(),
            'job_id' => $this->job?->getJobId(),
        ]);

        $this->incrementAttempt();

        Log::info('[Mail Trail] Attempt incremented, calling MailTemplateService::sendEmail', [
            'registration_id' => $this->registration->id,
        ]);

        $mailService->sendEmail('registration_confirmation', $this->registration);

        Log::info('[Mail Trail] SendRegistrationConfirmation::handle completed successfully', [
            'registration_id' => $this->registration->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Mail Trail] SendRegistrationConfirmation::failed called', [
            'registration_id' => $this->registration->id,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);

        $this->handleFailure($exception);
    }
}
