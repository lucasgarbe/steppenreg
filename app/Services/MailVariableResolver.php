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

        return [
            'name' => $registration->name,
            'email' => $registration->email,
            'track_name' => $trackInfo['name'] ?? 'Unknown Track',
            'track_distance' => $trackInfo['distance'] ?? '',
            'event_name' => $eventSettings->event_name ?? 'Event',
            'registration_date' => $registration->created_at->format('d.m.Y'),
            'draw_status' => $this->formatDrawStatus($registration->draw_status),
            'team_name' => $registration->team?->name ?? '',
            'waitlist_url' => $registration->getWaitlistUrl(),
            'withdraw_url' => $registration->getWithdrawUrl(),
            // Email link using mail config
            'contact_email_link' => $this->getContactEmailLink(),
        ];
    }

    public function getSampleVariables(): array
    {
        $eventSettings = app(EventSettings::class);

        return [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'track_name' => 'Example Track',
            'track_distance' => '10 km',
            'event_name' => $eventSettings->event_name ?? 'Sample Event',
            'registration_date' => Carbon::now()->format('d.m.Y'),
            'draw_status' => 'Not drawn yet',
            'team_name' => 'Sample Team',
            'waitlist_url' => 'https://example.com/waitlist/join/sample-token',
            'withdraw_url' => 'https://example.com/withdraw/sample-token',
            // Email link using mail config
            'contact_email_link' => $this->getContactEmailLink(),
        ];
    }

    private function getTrackInfo(int $trackId, EventSettings $settings): array
    {
        if (!isset($settings->tracks) || !is_array($settings->tracks)) {
            return [];
        }

        foreach ($settings->tracks as $track) {
            if ($track['id'] == $trackId) {
                return [
                    'name' => $track['name'],
                    'distance' => isset($track['distance']) ? $track['distance'] . ' km' : '',
                ];
            }
        }

        return [];
    }

    private function formatDrawStatus(?string $status): string
    {
        return match ($status) {
            'drawn' => 'Drawn - Confirmed to participate',
            'waitlist' => 'On waitlist',
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
        $contactEmail = config('mail.from.address', 'contact@' . $this->getEmailDomain());

        return '<a href="mailto:' . $contactEmail . '">E-Mail</a>';
    }
}

