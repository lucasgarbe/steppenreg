<?php

namespace App\Services;

use App\Models\Registration;
use App\Settings\EventSettings;

class StartingNumberService
{
    private const OVERFLOW_START = 800;

    public function assignNumber(Registration $registration): ?int
    {
        if ($registration->draw_status !== 'drawn') {
            return null;
        }

        $ranges = $this->getTrackRanges($registration->track_id);

        return $this->findNextAvailableInRange($ranges['main'], $registration->track_id);
    }

    public function getTrackRanges(int $trackId): array
    {
        $ranges = $this->getBaseRanges($trackId);
        
        // Check if waitlist overflow exists
        $overflowCount = $this->getWaitlistOverflowCount($trackId);
        if ($overflowCount > 0) {
            $ranges['waitlist_overflow'] = [
                'start' => self::OVERFLOW_START + ($trackId * 100),
                'end' => self::OVERFLOW_START + ($trackId * 100) + $overflowCount - 1
            ];
        }
        
        return $ranges;
    }

    private function getBaseRanges(int $trackId): array
    {
        $track = $this->getTrackConfig($trackId);
        $baseStart = $this->calculateTrackBaseStart($trackId);
        
        $mainRange = [
            'start' => $baseStart,
            'end' => $baseStart + $track['max_participants'] - 1
        ];
        
        $waitlistRange = [
            'start' => $mainRange['end'] + 1,
            'end' => $mainRange['end'] + $this->getWaitlistSize($track['max_participants'])
        ];
        
        return [
            'main' => $mainRange,
            'waitlist' => $waitlistRange
        ];
    }

    private function calculateTrackBaseStart(int $trackId): int
    {
        $settings = app(EventSettings::class);
        $tracks = collect($settings->tracks)->sortBy('id');
        
        $currentStart = 1;
        
        foreach ($tracks as $track) {
            if ($track['id'] == $trackId) {
                return $currentStart;
            }
            
            // Move to next 100-increment boundary after this track's full range
            $trackEnd = $currentStart + $track['max_participants'] - 1;
            $waitlistEnd = $trackEnd + $this->getWaitlistSize($track['max_participants']);
            $nextBoundary = (int)(($waitlistEnd / 100) + 1) * 100;
            
            $currentStart = $nextBoundary;
        }
        
        return 1; // Fallback
    }

    private function getWaitlistSize(int $maxParticipants): int
    {
        // 30% of main capacity, minimum 10, maximum 100
        return max(10, min(100, (int) ceil($maxParticipants * 0.3)));
    }

    private function getTrackConfig(int $trackId): array
    {
        $settings = app(EventSettings::class);
        $tracks = collect($settings->tracks);
        
        return $tracks->firstWhere('id', $trackId) ?? ['max_participants' => 100];
    }

    private function findNextAvailableInRange(array $range, int $trackId): ?int
    {
        $usedNumbers = Registration::withTrashed()->where('track_id', $trackId)
            ->whereNotNull('starting_number')
            ->whereBetween('starting_number', [$range['start'], $range['end']])
            ->pluck('starting_number')
            ->toArray();

        for ($i = $range['start']; $i <= $range['end']; $i++) {
            if (!in_array($i, $usedNumbers)) {
                return $i;
            }
        }

        return null; // Range is full
    }



    private function getWaitlistOverflowCount(int $trackId): int
    {
        $baseRanges = $this->getBaseRanges($trackId);
        $waitlistCapacity = $baseRanges['waitlist']['end'] - $baseRanges['waitlist']['start'] + 1;
        
        $waitlistCount = Registration::withTrashed()->where('track_id', $trackId)
            ->where('draw_status', 'drawn')
            ->whereNotNull('starting_number')
            ->whereBetween('starting_number', [$baseRanges['waitlist']['start'], $baseRanges['waitlist']['end']])
            ->count();
            
        return max(0, $waitlistCount - $waitlistCapacity);
    }

    public function getWaitlistStatus(int $trackId): array
    {
        $ranges = $this->getTrackRanges($trackId);
        $used = Registration::where('track_id', $trackId)
            ->where('draw_status', 'drawn')
            ->whereBetween('starting_number', [$ranges['waitlist']['start'], $ranges['waitlist']['end']])
            ->count();
            
        $capacity = $ranges['waitlist']['end'] - $ranges['waitlist']['start'] + 1;
        $percentage = ($used / $capacity) * 100;
        
        $overflowUsed = isset($ranges['waitlist_overflow']) 
            ? Registration::where('track_id', $trackId)
                ->where('draw_status', 'drawn')
                ->whereBetween('starting_number', [$ranges['waitlist_overflow']['start'], $ranges['waitlist_overflow']['end']])
                ->count()
            : 0;

        return [
            'track_id' => $trackId,
            'used' => $used,
            'capacity' => $capacity,
            'percentage' => $percentage,
            'overflow_used' => $overflowUsed,
            'status' => match(true) {
                $overflowUsed > 0 => 'overflow_active',
                $percentage >= 90 => 'critical',
                $percentage >= 75 => 'warning',
                default => 'normal'
            }
        ];
    }

    public function bulkAssignNumbers(array $registrationIds): array
    {
        $results = [];
        
        foreach ($registrationIds as $id) {
            $registration = Registration::find($id);
            if (!$registration) {
                continue;
            }
            
            $number = $this->assignNumber($registration);
            if ($number) {
                $registration->starting_number = $number;
                $registration->save();
                $results['assigned'][] = $id;
            } else {
                $results['failed'][] = $id;
            }
        }
        
        return $results;
    }
}