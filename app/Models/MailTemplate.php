<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'subject',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mailLogs(): HasMany
    {
        return $this->hasMany(MailLog::class, 'template_key', 'key');
    }

    public function renderContent(array $variables = []): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($variables as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    public function getVariablesFromContent(): array
    {
        $content = $this->subject.' '.$this->body;
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

        return array_unique($matches[1] ?? []);
    }

    public static function getAvailableVariables(): array
    {
        return [
            'name' => 'Participant name',
            'email' => 'Participant email',
            'track_name' => 'Track name',
            'event_name' => 'Event name',
            'registration_date' => 'Registration date',
            'draw_status' => 'Draw status',
            'team_name' => 'Team name (if applicable)',
            'track_distance' => 'Track distance',
            'participation_count' => 'Number of previous participations (0 for first-time)',
            'participation_experience' => 'Participation experience level (First-time/Returning/Veteran)',
            'waitlist_url' => 'Waitlist registration URL',
            'withdraw_url' => 'Withdrawal URL',
            'contact_email_link' => 'Contact email link (clickable)',
            'theme_primary_color' => 'Theme primary color (configured in settings)',
            'theme_background_color' => 'Theme background color (configured in settings)',
            'theme_text_color' => 'Theme text color (configured in settings)',
            'theme_accent_color' => 'Theme accent color (configured in settings)',
        ];
    }
}
