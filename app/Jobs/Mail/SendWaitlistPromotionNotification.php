<?php

namespace App\Jobs\Mail;

use App\Models\Registration;
use App\Services\MailTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWaitlistPromotionNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120, 600]; // 30sec, 2min, 10min

    public function __construct(
        public Registration $registration
    ) {
        // Using default queue for simplicity
    }

    public function handle(MailTemplateService $mailService): void
    {
        // Always use the draw_waitlist template for promotions from waitlist
        $mailService->sendEmail('draw_waitlist', $this->registration);
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Waitlist promotion notification email failed', [
            'registration_id' => $this->registration->id,
            'email' => $this->registration->email,
            'error' => $exception->getMessage(),
        ]);
    }
}