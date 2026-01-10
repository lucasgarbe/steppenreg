<?php

namespace App\Settings;

use Carbon\Carbon;
use Spatie\LaravelSettings\Settings;

class EventSettings extends Settings
{
    public string $event_name;

    public string $organization_name = 'Your Organization';

    public string $organization_website = 'https://example.com';

    public string $contact_email = 'contact@example.com';

    public string $organization_logo_path = 'logo.png';

    public string $event_website_url = 'https://example.com/event';

    public string $application_state = 'closed';

    public array $tracks;

    // DateTime-based automatic state management
    public mixed $flinta_registration_opens_at = null;

    public mixed $everyone_registration_opens_at = null;

    public mixed $registration_closes_at = null;

    public mixed $waitlist_only_starts_at = null;

    public mixed $event_starts_at = null;

    public mixed $event_ends_at = null;

    // Control flags for automatic transitions
    public bool $automatic_state_transitions = false;

    public bool $manual_override_active = false;

    public ?string $manual_override_state = null;

    // Theme Colors for Public Pages
    public string $theme_primary_color = '#F9C458';

    public string $theme_background_color = '#fffdf8c2';

    public string $theme_text_color = '#1a1a1a';

    public string $theme_accent_color = '#7a58fc';

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

    /**
     * Calculate what the application state should be based on current datetime
     */
    public function calculateAutomaticState(): string
    {
        $now = now();

        // If manual override is active, use that
        if ($this->manual_override_active && $this->manual_override_state) {
            return $this->manual_override_state;
        }

        // If automatic transitions are disabled, keep current state
        if (! $this->automatic_state_transitions) {
            return $this->application_state;
        }

        // Event has ended, close everything
        $eventEnds = $this->carbonize($this->event_ends_at);
        if ($eventEnds && $now->gte($eventEnds)) {
            return 'closed';
        }

        // Event is currently live
        $eventStarts = $this->carbonize($this->event_starts_at);
        if ($eventStarts && $now->gte($eventStarts) &&
            ($eventEnds === null || $now->lt($eventEnds))) {
            return 'live_event';
        }

        // Waitlist only period
        $waitlistStarts = $this->carbonize($this->waitlist_only_starts_at);
        if ($waitlistStarts && $now->gte($waitlistStarts)) {
            return 'closed_waitlist';
        }

        // Registration closes
        $regCloses = $this->carbonize($this->registration_closes_at);
        if ($regCloses && $now->gte($regCloses)) {
            return 'closed';
        }

        // Open for everyone
        $everyoneOpens = $this->carbonize($this->everyone_registration_opens_at);
        if ($everyoneOpens && $now->gte($everyoneOpens)) {
            return 'open_everyone';
        }

        // Open for FLINTA* only
        $flintaOpens = $this->carbonize($this->flinta_registration_opens_at);
        if ($flintaOpens && $now->gte($flintaOpens)) {
            return 'open_flinta';
        }

        // Default: closed
        return 'closed';
    }

    /**
     * Update application state based on current datetime if automatic transitions are enabled
     */
    public function updateStateFromDateTime(): bool
    {
        $newState = $this->calculateAutomaticState();

        if ($newState !== $this->application_state) {
            $oldState = $this->application_state;
            $this->application_state = $newState;
            $this->save();

            // Log the state change
            logger()->info('Application state automatically changed', [
                'from' => $oldState,
                'to' => $newState,
                'triggered_by' => 'automatic_datetime_check',
                'timestamp' => now()->toISOString(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get the next scheduled state transition
     */
    public function getNextStateTransition(): ?array
    {
        if (! $this->automatic_state_transitions) {
            return null;
        }

        $now = now();
        $transitions = [];

        $flintaOpens = $this->carbonize($this->flinta_registration_opens_at);
        if ($flintaOpens && $now->lt($flintaOpens)) {
            $transitions[] = [
                'datetime' => $flintaOpens,
                'state' => 'open_flinta',
                'label' => 'FLINTA* Registration Opens',
            ];
        }

        $everyoneOpens = $this->carbonize($this->everyone_registration_opens_at);
        if ($everyoneOpens && $now->lt($everyoneOpens)) {
            $transitions[] = [
                'datetime' => $everyoneOpens,
                'state' => 'open_everyone',
                'label' => 'Registration Opens for Everyone',
            ];
        }

        $regCloses = $this->carbonize($this->registration_closes_at);
        if ($regCloses && $now->lt($regCloses)) {
            $transitions[] = [
                'datetime' => $regCloses,
                'state' => 'closed',
                'label' => 'Registration Closes',
            ];
        }

        $waitlistStarts = $this->carbonize($this->waitlist_only_starts_at);
        if ($waitlistStarts && $now->lt($waitlistStarts)) {
            $transitions[] = [
                'datetime' => $waitlistStarts,
                'state' => 'closed_waitlist',
                'label' => 'Waitlist Only Period Begins',
            ];
        }

        $eventStarts = $this->carbonize($this->event_starts_at);
        if ($eventStarts && $now->lt($eventStarts)) {
            $transitions[] = [
                'datetime' => $eventStarts,
                'state' => 'live_event',
                'label' => 'Event Begins',
            ];
        }

        $eventEnds = $this->carbonize($this->event_ends_at);
        if ($eventEnds && $now->lt($eventEnds)) {
            $transitions[] = [
                'datetime' => $eventEnds,
                'state' => 'closed',
                'label' => 'Event Ends',
            ];
        }

        if (empty($transitions)) {
            return null;
        }

        // Sort by datetime and return the next one
        usort($transitions, fn ($a, $b) => $a['datetime']->timestamp <=> $b['datetime']->timestamp);

        return $transitions[0];
    }

    /**
     * Helper method to ensure datetime fields are converted to Carbon instances for comparison
     */
    private function carbonize($value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_string($value)) {
            return Carbon::parse($value);
        }

        return null;
    }
}
