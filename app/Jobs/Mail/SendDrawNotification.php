<?php

namespace App\Jobs\Mail;

use App\Models\Registration;
use App\Services\MailTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDrawNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120, 600]; // 30sec, 2min, 10min - faster for time-sensitive notifications

    public function __construct(
        public Registration $registration
    ) {
        // Using default queue for simplicity
    }

    public function handle(MailTemplateService $mailService): void
    {
        $templateKey = match ($this->registration->draw_status) {
            'drawn' => 'draw_success',
            'waitlist' => 'draw_waitlist',
            default => 'draw_rejection',
        };

        $mailService->sendEmail($templateKey, $this->registration);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Draw notification email failed', [
            'registration_id' => $this->registration->id,
            'email' => $this->registration->email,
            'draw_status' => $this->registration->draw_status,
            'error' => $exception->getMessage(),
        ]);
    }
}
