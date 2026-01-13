<?php

namespace App\Jobs\Mail;

use App\Mail\FlexibleMail;
use App\Models\MailLog;
use App\Models\Registration;
use App\Services\MailVariableResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendFlexibleMail implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;

    public $backoff = [30, 120, 600]; // 30sec, 2min, 10min

    public function middleware(): array
    {
        return [new RateLimited('emails')];
    }

    public function __construct(
        public Registration $registration,
        public string $subject,
        public string $message
    ) {
        // Using default queue for simplicity
    }

    public function handle(MailVariableResolver $variableResolver): void
    {
        // Resolve template variables for this registration
        $variables = $variableResolver->resolve($this->registration);

        // Process subject and message with variables
        $processedSubject = $this->processVariables($this->subject, $variables);
        $processedMessage = $this->processVariables($this->message, $variables);

        // Log the email with both original and processed content
        $mailLog = MailLog::logEmail(
            templateKey: 'custom_email',
            recipientEmail: $this->registration->email,
            registrationId: $this->registration->id,
            variables: [
                'original_subject' => $this->subject,
                'original_message' => $this->message,
                'processed_subject' => $processedSubject,
                'processed_message' => $processedMessage,
                'name' => $this->registration->name,
                'email' => $this->registration->email,
            ]
        );

        try {
            Mail::to($this->registration->email)
                ->send(new FlexibleMail(
                    $processedSubject,
                    $processedMessage,
                    $this->registration
                ));

            $mailLog->markAsSent();
        } catch (\Exception $e) {
            $mailLog->markAsFailed($e->getMessage());
            throw $e; // Re-throw to trigger job retry
        }
    }

    private function processVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Flexible email failed to send', [
            'registration_id' => $this->registration->id,
            'email' => $this->registration->email,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
        ]);
    }
}
