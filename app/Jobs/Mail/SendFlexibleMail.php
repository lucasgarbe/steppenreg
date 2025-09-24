<?php

namespace App\Jobs\Mail;

use App\Mail\FlexibleMail;
use App\Models\MailLog;
use App\Models\Registration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendFlexibleMail implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120, 600]; // 30sec, 2min, 10min

    public function __construct(
        public Registration $registration,
        public string $subject,
        public string $message
    ) {
        // Using default queue for simplicity
    }

    public function handle(): void
    {
        // Log the email
        $mailLog = MailLog::logEmail(
            templateKey: 'custom_email',
            recipientEmail: $this->registration->email,
            registrationId: $this->registration->id,
            variables: [
                'subject' => $this->subject,
                'message' => $this->message,
                'name' => $this->registration->name,
                'email' => $this->registration->email,
            ]
        );

        try {
            Mail::to($this->registration->email)
                ->send(new FlexibleMail(
                    $this->subject,
                    $this->message,
                    $this->registration
                ));
            
            $mailLog->markAsSent();
        } catch (\Exception $e) {
            $mailLog->markAsFailed($e->getMessage());
            throw $e; // Re-throw to trigger job retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('Flexible email failed to send', [
            'registration_id' => $this->registration->id,
            'email' => $this->registration->email,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}