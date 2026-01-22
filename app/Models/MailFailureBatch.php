<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailFailureBatch extends Model
{
    protected $fillable = [
        'failure_count',
        'template_breakdown',
        'mail_log_ids',
        'started_at',
        'completed_at',
        'notification_sent',
    ];

    protected $casts = [
        'template_breakdown' => 'array',
        'mail_log_ids' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'notification_sent' => 'boolean',
    ];

    public static function recordFailure(MailLog $mailLog): void
    {
        $batch = self::getCurrentBatch();

        $templateBreakdown = $batch->template_breakdown ?? [];
        $templateKey = $mailLog->template_key;
        $templateBreakdown[$templateKey] = ($templateBreakdown[$templateKey] ?? 0) + 1;

        $mailLogIds = $batch->mail_log_ids ?? [];
        $mailLogIds[] = $mailLog->id;

        $batch->update([
            'failure_count' => $batch->failure_count + 1,
            'template_breakdown' => $templateBreakdown,
            'mail_log_ids' => $mailLogIds,
        ]);
    }

    public static function getCurrentBatch(): self
    {
        return self::firstOrCreate(
            [
                'notification_sent' => false,
                'completed_at' => null,
            ],
            [
                'started_at' => now(),
                'failure_count' => 0,
            ]
        );
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'completed_at' => now(),
            'notification_sent' => true,
        ]);
    }
}
