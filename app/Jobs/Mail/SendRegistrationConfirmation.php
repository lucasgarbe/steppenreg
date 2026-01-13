<?php

namespace App\Jobs\Mail;

use App\Models\Registration;
use App\Services\MailTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRegistrationConfirmation implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function middleware(): array
    {
        return [new RateLimited('emails')];
    }

    public function __construct(
        public Registration $registration
    ) {
        // Using default queue for simplicity
    }

    public function handle(MailTemplateService $mailService): void
    {
        $mailService->sendEmail('registration_confirmation', $this->registration);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Registration confirmation email failed', [
            'registration_id' => $this->registration->id,
            'email' => $this->registration->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
