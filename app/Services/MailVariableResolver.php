<?php

namespace App\Services;

use App\Models\Registration;
use App\Settings\EventSettings;
use Carbon\Carbon;

class MailVariableResolver
{
    public function resolve(Registration $registration): array
    {
        $eventSettings = app(EventSettings::class);

        $trackInfo = $this->getTrackInfo($registration->track_id, $eventSettings);

        $variables = [
            'name' => $registration->name,
            'email' => $registration->email,
            'track_name' => $trackInfo['name'] ?? 'Unknown Track',
            'track_distance' => $trackInfo['distance'] ?? '',
            'event_name' => $eventSettings->event_name ?? 'Event',
            'registration_date' => $registration->created_at->format('d.m.Y'),
            'draw_status' => $this->formatDrawStatus($registration->draw_status),
            'team_name' => $registration->team?->name ?? '',
            'contact_email_link' => $this->getContactEmailLink(),
            'team_members_list' => $this->getTeamMembersList($registration),
            'theme_primary_color' => $eventSettings->theme_primary_color ?? '#F9C458',
            'theme_background_color' => $eventSettings->theme_background_color ?? '#fffdf8c2',
            'theme_text_color' => $eventSettings->theme_text_color ?? '#1a1a1a',
            'theme_accent_color' => $eventSettings->theme_accent_color ?? '#7a58fc',
        ];

        // Add custom answer variables
        $customQuestions = $eventSettings->custom_questions ?? [];

        foreach ($registration->custom_answers ?? [] as $key => $value) {
            // Find the question definition
            $question = collect($customQuestions)->firstWhere('key', $key);

            // Raw value (backward compatible)
            $formattedValue = is_array($value) ? implode(', ', $value) : (string) $value;
            $variables["custom.{$key}"] = $formattedValue;

            // Add locale-suffixed variables only for option-based question types
            if ($question && in_array($question['type'] ?? '', ['select', 'radio', 'checkbox'])) {
                foreach (['en', 'de'] as $locale) {
                    $variables["custom.{$key}.{$locale}"] = $this->formatCustomAnswerForLocale(
                        $value,
                        $question,
                        $locale
                    );
                }
            }
        }

        return $variables;
    }

    public function getSampleVariables(): array
    {
        $eventSettings = app(EventSettings::class);

        $sampleVars = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'track_name' => 'Example Track',
            'track_distance' => '10 km',
            'event_name' => $eventSettings->event_name ?? 'Sample Event',
            'registration_date' => Carbon::now()->format('d.m.Y'),
            'draw_status' => 'Not drawn yet',
            'team_name' => 'Sample Team',
            'contact_email_link' => $this->getContactEmailLink(),
            'team_members_list' => 'John Doe, Jane Smith, Bob Johnson',
            'theme_primary_color' => $eventSettings->theme_primary_color ?? '#F9C458',
            'theme_background_color' => $eventSettings->theme_background_color ?? '#fffdf8c2',
            'theme_text_color' => $eventSettings->theme_text_color ?? '#1a1a1a',
            'theme_accent_color' => $eventSettings->theme_accent_color ?? '#7a58fc',
        ];

        // Add sample custom variables
        $customQuestions = $eventSettings->custom_questions ?? [];
        foreach ($customQuestions as $question) {
            $key = $question['key'];
            $type = $question['type'];

            // Raw value sample
            $sampleValue = match ($type) {
                'email' => 'sample@example.com',
                'number' => '42',
                'date' => Carbon::now()->format('d.m.Y'),
                'checkbox' => 'value1, value2',
                'select', 'radio' => $question['options'][0]['value'] ?? 'sample_value',
                default => 'Sample answer for '.($question['translations']['en']['label'] ?? $key)
            };
            $sampleVars["custom.{$key}"] = $sampleValue;

            // Add locale-suffixed samples only for option-based types
            if (in_array($type, ['select', 'radio', 'checkbox'])) {
                foreach (['en', 'de'] as $locale) {
                    $localizedSample = match ($type) {
                        'checkbox' => collect($question['options'] ?? [])
                            ->take(2)
                            ->pluck("label_{$locale}")
                            ->filter()
                            ->join(', '),
                        'select', 'radio' => $question['options'][0]["label_{$locale}"] ?? 'Sample',
                        default => $sampleValue
                    };

                    $sampleVars["custom.{$key}.{$locale}"] = $localizedSample ?: 'Sample';
                }
            }
        }

        return $sampleVars;
    }

    private function getTrackInfo(int $trackId, EventSettings $settings): array
    {
        if (! isset($settings->tracks) || ! is_array($settings->tracks)) {
            return [];
        }

        foreach ($settings->tracks as $track) {
            if ($track['id'] == $trackId) {
                return [
                    'name' => $track['name'],
                    'distance' => isset($track['distance']) ? $track['distance'].' km' : '',
                ];
            }
        }

        return [];
    }

    private function formatDrawStatus(?string $status): string
    {
        return match ($status) {
            'drawn' => 'Drawn - Confirmed to participate',
            'not_drawn' => 'Not drawn',
            default => 'Not drawn yet',
        };
    }

    private function getEmailDomain(): string
    {
        // Try to get domain from app.url config
        $appUrl = config('app.url');
        if ($appUrl) {
            $domain = parse_url($appUrl, PHP_URL_HOST);
            if ($domain && $domain !== 'localhost' && $domain !== '127.0.0.1') {
                return $domain;
            }
        }

        // Try to get from mail.from.address config
        $mailFrom = config('mail.from.address');
        if ($mailFrom && str_contains($mailFrom, '@')) {
            return explode('@', $mailFrom)[1];
        }

        // Fallback to generic domain
        return 'event.com';
    }

    private function getContactEmailLink(): string
    {
        // Use mail config or fallback
        $contactEmail = config('mail.from.address', 'contact@'.$this->getEmailDomain());

        return '<a href="mailto:'.$contactEmail.'">E-Mail</a>';
    }

    private function getTeamMembersList(Registration $registration): string
    {
        if (! $registration->team_id) {
            return '';
        }

        $teamMembers = $registration->team->registrations;

        return $teamMembers->pluck('name')->join(', ');
    }

    /**
     * Format custom answer value for a specific locale
     */
    private function formatCustomAnswerForLocale(mixed $value, ?array $question, string $locale): string
    {
        // If question not found, return raw value
        if (! $question) {
            return is_array($value) ? implode(', ', $value) : (string) $value;
        }

        // Handle arrays (checkboxes)
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($val) => $this->getOptionLabel($val, $question, $locale))
                ->filter()
                ->join(', ');
        }

        // Handle select/radio (single value with options)
        return $this->getOptionLabel($value, $question, $locale);
    }

    /**
     * Get the label for an option value in a specific locale
     */
    private function getOptionLabel(string $value, array $question, string $locale): string
    {
        $options = $question['options'] ?? [];

        foreach ($options as $option) {
            if (($option['value'] ?? '') === $value) {
                // Return locale-specific label, fallback to raw value if not found
                return $option["label_{$locale}"] ?? $value;
            }
        }

        // Option not found, return raw value
        return $value;
    }
}
