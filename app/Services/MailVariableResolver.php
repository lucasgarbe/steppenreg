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
            'participation_count' => $registration->participation_count ?? 0,
            'participation_experience' => $this->getParticipationExperience($registration->participation_count ?? 0),
            'contact_email_link' => $this->getContactEmailLink(),
            'team_members_list' => $this->getTeamMembersList($registration),
            'theme_primary_color' => $eventSettings->theme_primary_color ?? '#F9C458',
            'theme_background_color' => $eventSettings->theme_background_color ?? '#fffdf8c2',
            'theme_text_color' => $eventSettings->theme_text_color ?? '#1a1a1a',
            'theme_accent_color' => $eventSettings->theme_accent_color ?? '#7a58fc',
        ];

        // Add custom answer variables
        foreach ($registration->custom_answers ?? [] as $key => $value) {
            $formattedValue = is_array($value) ? implode(', ', $value) : (string) $value;
            $variables["custom.{$key}"] = $formattedValue;
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
            'participation_count' => 2,
            'participation_experience' => 'Veteran (3rd time)',
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
            $sampleValue = match ($question['type']) {
                'email' => 'sample@example.com',
                'number' => '42',
                'date' => Carbon::now()->format('d.m.Y'),
                'checkbox' => 'Option 1, Option 2',
                'select', 'radio' => $question['options'][0]['label_en'] ?? 'Sample Option',
                default => 'Sample answer for '.($question['translations']['en']['label'] ?? $question['key'])
            };
            $sampleVars["custom.{$question['key']}"] = $sampleValue;
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

    private function getParticipationExperience(int $count): string
    {
        return match (true) {
            $count === 0 => 'First-time participant',
            $count === 1 => 'Returning participant (2nd time)',
            $count >= 2 => 'Veteran ('.($count + 1).'x participant)',
            default => 'Unknown'
        };
    }

    private function getTeamMembersList(Registration $registration): string
    {
        if (! $registration->team_id) {
            return '';
        }

        $teamMembers = $registration->team->registrations;

        return $teamMembers->pluck('name')->join(', ');
    }
}
