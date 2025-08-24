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
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'template_variables' => 'array',
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
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
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
