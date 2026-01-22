<?php

namespace App\Jobs;

use App\Models\MailFailureBatch;
use App\Models\User;
use App\Notifications\BatchMailFailureNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendBatchedMailFailureNotifications implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find all unsent batches with failures
        $batches = MailFailureBatch::where('notification_sent', false)
            ->where('failure_count', '>', 0)
            ->get();

        foreach ($batches as $batch) {
            // Mark as completed
            $batch->markAsCompleted();

            // Send notification to all admins
            $admins = User::all();
            Notification::send($admins, new BatchMailFailureNotification($batch));
        }
    }
}
