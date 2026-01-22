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

    public array $custom_questions = [];

    public array $gender_categories = [];

    // DateTime-based automatic state management
    public mixed $flinta_registration_opens_at = null;

    public mixed $everyone_registration_opens_at = null;

    public mixed $registration_closes_at = null;

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

    // Custom Track Labels
    public string $track_label_singular_en = '';

    public string $track_label_singular_de = '';

    public string $track_label_plural_en = '';

    public string $track_label_plural_de = '';

    public static function group(): string
    {
        return 'event';
    }

    public static function getApplicationStates(): array
    {
        return [
            'closed' => 'Closed',
            'priority_period' => 'Priority Registration Active',
            'general_open' => 'Open for All Categories',
            'live_event' => 'Live Event',
        ];
    }

    public function getApplicationStateLabel(): string
    {
        return static::getApplicationStates()[$this->application_state] ?? 'Unknown';
    }

    public function isRegistrationOpen(): bool
    {
        return in_array($this->application_state, ['priority_period', 'general_open']);
    }

    public function isOpenForPriorityOnly(): bool
    {
        return $this->application_state === 'priority_period';
    }

    public function isLiveEvent(): bool
    {
        return $this->application_state === 'live_event';
    }

    /**
     * Get gender categories marked as priority
     */
    public function getPriorityGenderCategories(): array
    {
        return collect($this->gender_categories)
            ->filter(fn ($cat) => $cat['is_priority'] ?? false)
            ->sortBy('sort_order')
            ->values()
            ->toArray();
    }

    /**
     * Get gender categories marked as general (non-priority)
     */
    public function getGeneralGenderCategories(): array
    {
        return collect($this->gender_categories)
            ->filter(fn ($cat) => ! ($cat['is_priority'] ?? false))
            ->sortBy('sort_order')
            ->values()
            ->toArray();
    }

    /**
     * Get categories available for FRONTEND registration based on current state
     * During priority_period: priority categories (manual mode: all priority, auto mode: opened priority)
     * During general_open: ALL categories
     * During closed/live_event: none
     */
    public function getAvailableGenderCategories(): array
    {
        // If general open, return ALL categories
        if ($this->application_state === 'general_open') {
            return collect($this->gender_categories)
                ->sortBy('sort_order')
                ->values()
                ->toArray();
        }

        // During priority period
        if ($this->application_state === 'priority_period') {
            // Manual mode: return all priority categories
            if (! $this->automatic_state_transitions) {
                return $this->getPriorityGenderCategories();
            }

            // Automatic mode: return only opened priority categories
            $openedCategories = $this->getActiveGenderCategories();

            return collect($openedCategories)
                ->filter(fn ($cat) => $cat['is_priority'] ?? false)
                ->values()
                ->toArray();
        }

        // Closed or live event - no categories available
        return [];
    }

    /**
     * Get category by key
     */
    public function getCategoryByKey(string $key): ?array
    {
        return collect($this->gender_categories)
            ->firstWhere('key', $key);
    }

    /**
     * Get ALL gender categories for ADMIN panels (ignores state and time restrictions)
     * Use this for admin forms, filters, and dropdowns
     */
    public function getAllGenderCategoriesForAdmin(): array
    {
        return collect($this->gender_categories)
            ->sortBy('sort_order')
            ->values()
            ->toArray();
    }

    /**
     * Validate that priority categories open before general categories
     * Returns array of validation errors, empty if valid
     */
    public function validateCategoryOpeningTimes(): array
    {
        $errors = [];
        $priorityCategories = $this->getPriorityGenderCategories();
        $generalCategories = $this->getGeneralGenderCategories();

        if (empty($priorityCategories) || empty($generalCategories)) {
            return $errors; // No validation needed if missing one type
        }

        // Get latest priority opening time
        $latestPriorityTime = collect($priorityCategories)
            ->map(fn ($cat) => $this->carbonize($cat['registration_opens_at'] ?? null))
            ->filter()
            ->max();

        // Get earliest general opening time
        $earliestGeneralTime = collect($generalCategories)
            ->map(fn ($cat) => $this->carbonize($cat['registration_opens_at'] ?? null))
            ->filter()
            ->min();

        if ($latestPriorityTime && $earliestGeneralTime) {
            if ($latestPriorityTime->gte($earliestGeneralTime)) {
                $errors[] = "Priority categories must open before general categories. Latest priority: {$latestPriorityTime->format('Y-m-d H:i')}, Earliest general: {$earliestGeneralTime->format('Y-m-d H:i')}";
            }
        }

        return $errors;
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

        // Registration closes
        $regCloses = $this->carbonize($this->registration_closes_at);
        if ($regCloses && $now->gte($regCloses)) {
            return 'closed';
        }

        // Check priority vs general category opening logic
        $priorityCategories = $this->getPriorityGenderCategories();
        $generalCategories = $this->getGeneralGenderCategories();

        // If no categories configured, stay closed
        if (empty($this->gender_categories)) {
            return 'closed';
        }

        // Check if ANY general (non-priority) category has opened
        $anyGeneralCategoryOpened = collect($generalCategories)->some(function ($cat) use ($now) {
            $opensAt = $this->carbonize($cat['registration_opens_at'] ?? null);

            return $opensAt && $now->gte($opensAt);
        });

        // If any general category opened, transition to general_open
        if ($anyGeneralCategoryOpened) {
            return 'general_open';
        }

        // Check if ALL priority categories have opened (and no general opened yet)
        $allPriorityCategoriesOpened = ! empty($priorityCategories) &&
            collect($priorityCategories)->every(function ($cat) use ($now) {
                $opensAt = $this->carbonize($cat['registration_opens_at'] ?? null);

                return $opensAt && $now->gte($opensAt);
            });

        // If all priority opened but no general categories exist, go to general_open
        if ($allPriorityCategoriesOpened && empty($generalCategories)) {
            return 'general_open';
        }

        // Check if ANY priority category has opened
        $anyPriorityCategoryOpened = collect($priorityCategories)->some(function ($cat) use ($now) {
            $opensAt = $this->carbonize($cat['registration_opens_at'] ?? null);

            return $opensAt && $now->gte($opensAt);
        });

        // If some priority categories opened (but not all general yet), priority_period
        if ($anyPriorityCategoryOpened) {
            return 'priority_period';
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

        // Add transitions for each gender category
        $priorityCategories = $this->getPriorityGenderCategories();
        $generalCategories = $this->getGeneralGenderCategories();

        // Priority category openings
        foreach ($priorityCategories as $category) {
            $opensAt = $this->carbonize($category['registration_opens_at'] ?? null);
            if ($opensAt && $now->lt($opensAt)) {
                $locale = app()->getLocale();
                $label = $category['translations'][$locale]['label'] ?? $category['key'];

                $transitions[] = [
                    'datetime' => $opensAt,
                    'state' => 'priority_period',
                    'label' => "{$label} Priority Registration Opens",
                ];
            }
        }

        // General category openings
        foreach ($generalCategories as $category) {
            $opensAt = $this->carbonize($category['registration_opens_at'] ?? null);
            if ($opensAt && $now->lt($opensAt)) {
                $locale = app()->getLocale();
                $label = $category['translations'][$locale]['label'] ?? $category['key'];

                $transitions[] = [
                    'datetime' => $opensAt,
                    'state' => 'general_open',
                    'label' => "{$label} Opens - General Registration",
                ];
            }
        }

        $regCloses = $this->carbonize($this->registration_closes_at);
        if ($regCloses && $now->lt($regCloses)) {
            $transitions[] = [
                'datetime' => $regCloses,
                'state' => 'closed',
                'label' => 'Registration Closes',
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

    /**
     * Get available locales for the application
     */
    public static function getAvailableLocales(): array
    {
        return ['en', 'de'];
    }

    /**
     * Get question types with their labels
     */
    public static function getQuestionTypes(): array
    {
        return [
            'text' => 'Short Text',
            'textarea' => 'Long Text',
            'number' => 'Number',
            'email' => 'Email',
            'select' => 'Dropdown',
            'radio' => 'Radio Buttons',
            'checkbox' => 'Checkboxes (Multiple)',
            'date' => 'Date',
        ];
    }

    /**
     * Get gender categories that are currently open for registration
     * In manual mode (automatic_state_transitions = false), returns all categories
     * In automatic mode, filters by registration_opens_at time
     */
    public function getActiveGenderCategories(): array
    {
        $now = now();

        return collect($this->gender_categories)
            ->filter(function ($category) use ($now) {
                // If automatic transitions disabled, don't filter by time
                if (! $this->automatic_state_transitions) {
                    return true; // All categories considered "active"
                }

                // In automatic mode, check opening time
                $opensAt = $this->carbonize($category['registration_opens_at'] ?? null);

                return $opensAt && $now->gte($opensAt);
            })
            ->sortBy('sort_order')
            ->values()
            ->toArray();
    }

    /**
     * Get array of gender category keys that are allowed to register right now
     */
    public function getAllowedGenderKeys(): array
    {
        return collect($this->getActiveGenderCategories())
            ->pluck('key')
            ->toArray();
    }

    /**
     * Get all gender category keys (for validation)
     */
    public function getAllGenderKeys(): array
    {
        return collect($this->gender_categories)
            ->pluck('key')
            ->toArray();
    }

    /**
     * Get custom track label or fall back to translation
     */
    public function getTrackLabel(bool $plural = false): string
    {
        $locale = app()->getLocale();
        $key = $plural ? 'track_label_plural_' : 'track_label_singular_';
        $customLabel = $this->{$key.$locale} ?? '';

        if (! empty(trim($customLabel))) {
            return $customLabel;
        }

        // Fallback to translations
        return $plural
            ? __('messages.tracks')
            : __('messages.track');
    }
}
