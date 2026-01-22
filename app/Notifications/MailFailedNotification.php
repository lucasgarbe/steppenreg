<?php

namespace App\Notifications;

use App\Models\MailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MailLog $mailLog,
        public string $errorMessage
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $adminUrl = config('app.url').'/admin/mail-logs';

        return (new MailMessage)
            ->error()
            ->subject('Email Failed: '.$this->mailLog->template_key)
            ->line('An email failed to send after all retry attempts.')
            ->line('**Recipient:** '.$this->mailLog->recipient_email)
            ->line('**Template:** '.$this->mailLog->template_key)
            ->line('**Attempts:** '.$this->mailLog->attempt_count)
            ->line('**Rate Limited:** '.$this->mailLog->rate_limit_count.' times')
            ->line('**Error:** '.$this->errorMessage)
            ->action('View Mail Logs', $adminUrl)
            ->line('Please check the mail logs in the admin panel for more details.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'mail_log_id' => $this->mailLog->id,
            'recipient_email' => $this->mailLog->recipient_email,
            'template_key' => $this->mailLog->template_key,
            'attempt_count' => $this->mailLog->attempt_count,
            'rate_limit_count' => $this->mailLog->rate_limit_count,
            'error_message' => $this->errorMessage,
        ];
    }
}
