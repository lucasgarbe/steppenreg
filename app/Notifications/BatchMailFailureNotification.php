<?php

namespace App\Notifications;

use App\Models\MailFailureBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BatchMailFailureNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MailFailureBatch $batch
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

        $message = (new MailMessage)
            ->error()
            ->subject('Batch Email Failures: '.$this->batch->failure_count.' emails failed')
            ->line('Multiple emails failed to send after exhausting retry attempts.')
            ->line('**Total Failures:** '.$this->batch->failure_count)
            ->line('**Time Period:** '.$this->batch->started_at->diffForHumans().' to '.$this->batch->completed_at->diffForHumans());

        if ($this->batch->template_breakdown) {
            $message->line('**Breakdown by Template:**');
            foreach ($this->batch->template_breakdown as $template => $count) {
                $message->line('- '.$template.': '.$count.' failures');
            }
        }

        return $message
            ->action('View Mail Logs', $adminUrl)
            ->line('Please check the mail logs in the admin panel for detailed information.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'batch_id' => $this->batch->id,
            'failure_count' => $this->batch->failure_count,
            'template_breakdown' => $this->batch->template_breakdown,
            'started_at' => $this->batch->started_at->toISOString(),
            'completed_at' => $this->batch->completed_at?->toISOString(),
        ];
    }
}
