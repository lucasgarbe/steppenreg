<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class EventSettings extends Settings
{
    public string $event_name;

    public bool $site_active;

    public string $application_state = 'closed';

    public array $tracks;

    public static function group(): string
    {
        return 'event';
    }

    public static function getApplicationStates(): array
    {
        return [
            'closed' => 'Closed',
            'open_flinta' => 'Open for FLINTA*',
            'open_everyone' => 'Open for Everyone',
            'closed_waitlist' => 'Closed - Waitlist Only',
            'live_event' => 'Live Event',
        ];
    }

    public function getApplicationStateLabel(): string
    {
        return static::getApplicationStates()[$this->application_state] ?? 'Unknown';
    }

    public function isRegistrationOpen(): bool
    {
        return in_array($this->application_state, ['open_flinta', 'open_everyone', 'closed_waitlist']);
    }

    public function isOpenForFlintaOnly(): bool
    {
        return $this->application_state === 'open_flinta';
    }

    public function isWaitlistOnly(): bool
    {
        return $this->application_state === 'closed_waitlist';
    }

    public function isLiveEvent(): bool
    {
        return $this->application_state === 'live_event';
    }
}
