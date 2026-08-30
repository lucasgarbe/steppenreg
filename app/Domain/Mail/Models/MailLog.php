<?php

namespace App\Domain\Mail\Models;

use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class MailLog extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\MailLogFactory::new();
    }

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
        Log::info('[Mail Trail] MailLog::markAsSent called', [
            'mail_log_id' => $this->id,
            'registration_id' => $this->registration_id,
            'template_key' => $this->template_key,
            'recipient_email' => $this->recipient_email,
        ]);

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
        Log::error('[Mail Trail] MailLog::markAsFailed called', [
            'mail_log_id' => $this->id,
            'registration_id' => $this->registration_id,
            'template_key' => $this->template_key,
            'recipient_email' => $this->recipient_email,
            'error_message' => $errorMessage,
        ]);

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
        Log::info('[Mail Trail] MailLog::logEmail creating new entry', [
            'template_key' => $templateKey,
            'recipient_email' => $recipientEmail,
            'registration_id' => $registrationId,
        ]);

        $mailLog = self::create([
            'registration_id' => $registrationId,
            'template_key' => $templateKey,
            'recipient_email' => $recipientEmail,
            'template_variables' => $variables,
            'status' => 'queued',
        ]);

        Log::info('[Mail Trail] MailLog entry created', [
            'mail_log_id' => $mailLog->id,
            'template_key' => $templateKey,
        ]);

        return $mailLog;
    }
}
