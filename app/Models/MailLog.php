<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailLog extends Model
{
    protected $fillable = [
        'registration_id',
        'template_key',
        'recipient_email',
        'status',
        'sent_at',
        'error_message',
        'template_variables',
        'attempt_count',
        'last_rate_limited_at',
        'rate_limit_count',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'last_rate_limited_at' => 'datetime',
        'template_variables' => 'array',
        'metadata' => 'array',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MailTemplate::class, 'template_key', 'key');
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
            'metadata' => array_merge($this->metadata ?? [], [
                'final_attempt_count' => $this->attempt_count,
            ]),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'metadata' => array_merge($this->metadata ?? [], [
                'final_attempt_count' => $this->attempt_count,
                'failed_at' => now()->toISOString(),
            ]),
        ]);
    }

    public function markAsRateLimited(int $releaseDelay): void
    {
        $this->increment('rate_limit_count');
        $this->update([
            'last_rate_limited_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'last_release_delay' => $releaseDelay,
                'last_rate_limited_iso' => now()->toISOString(),
            ]),
        ]);
    }

    public function incrementAttempt(): void
    {
        $this->increment('attempt_count');
    }

    public function isRateLimited(): bool
    {
        return $this->last_rate_limited_at !== null
            && $this->last_rate_limited_at->isAfter(now()->subMinutes(5));
    }

    public static function logEmail(
        string $templateKey,
        string $recipientEmail,
        ?int $registrationId = null,
        array $variables = []
    ): self {
        return self::create([
            'registration_id' => $registrationId,
            'template_key' => $templateKey,
            'recipient_email' => $recipientEmail,
            'template_variables' => $variables,
            'status' => 'queued',
        ]);
    }
}
